<?php
/**
 * Web Access Control class
 * http://www.w3.org/wiki/WebAccessControl
 */
class WAC {
    private $_user;
    private $_path;
    private $_uri;

    private $_root_path;
    private $_root_uri;

    private $_acl_name;
    private $_acl_file;
    private $_acl_uri;

    private $_graph;
    private $_debug = array();
    private $_reason;

    /**
     * Constructor for WAC
     * @param string $user  the authenticated user 
     * @param string $path  the path to the resource on the disk
     * @param string $uri   the URI of the resource
     * @param bool   $showlinkmeta  whether to show the Link header
     *
     * @return boolean (true)
     */
    function __construct($user, $path, $uri, $showlinkmeta=false) {
        $this->_user = $user;
        // set user to delegator if we have one
        $delegator = $this->hasDelegator();
        if (isset($delegator) && (strlen($delegator) > 0)) {
            if ($this->verifyDelegator($delegator, $user)) {
                $this->_debug[] = "Has delegator=".$delegator;
                $this->_user = $delegator;
            }
        }
        $this->_path = $path;
        $this->_uri = $uri;

        $this->_root_path = $_ENV['CLOUD_DATA'].'/'.$_SERVER['SERVER_NAME'].'/';
        $this->_root_uri = REQUEST_BASE.'/';
        
        // building the acl file path and uri
        // building the absolute path for the corresponding acl file        
        if ($_SERVER['SERVER_NAME'] == basename($uri)) { // we're at the root level
            $this->_acl_file = '.acl';
            $this->_acl_path = $path.$this->_acl_file;
            $this->_acl_uri = $uri.$this->_acl_file;
        } else if (substr(basename($path), 0, 4) == '.acl') {      
            $this->_acl_file = basename($path);
            $this->_acl_path = $path;
            $this->_acl_uri = $uri;
        } else {
            $this->_acl_file = '.acl.'.basename($path);
            $this->_acl_path = dirname($path).'/'.$this->_acl_file;
            $this->_acl_uri = dirname($uri).'/'.$this->_acl_file;
        }
        
        // set the default rel=acl link
        if ($showlinkmeta)
            header("Link: <".$this->_acl_uri.">; rel=acl", false);

        /*
        $this->_debug[] = "<--------WAC--------->";
        $this->_debug[] = "WebID=".$this->_user;
        $this->_debug[] = "resource_path=".$this->_path;
        $this->_debug[] = "resource_uri=".$this->_uri;
        $this->_debug[] = "acl_file=".$this->_acl_file;
        $this->_debug[] = "acl_path=".$this->_acl_path;
        $this->_debug[] = "acl_uri=".$this->_acl_uri;
        $this->_debug[] = "root_path=".$this->_root_path;
        $this->_debug[] = "root_uri=".$this->_root_uri;
        
        openlog('ldphp', LOG_PID | LOG_ODELAY,LOG_LOCAL4);
        foreach($this->_debug as $line)
            syslog(LOG_INFO, $line);
        closelog();
        */

        return true;
    }

    function getReason() {
        return $this->_reason;
    }
    
    function getDebug() {
        return $this->_debug;
    }

    function hasDelegator() {
        if (isset($_SERVER['HTTP_ON_BEHALF_OF'])) {
            return $_SERVER['HTTP_ON_BEHALF_OF'];
        }
        return false;
    }

    function verifyDelegator($delegator, $delegatee) {
        $g = new Graph('uri', $delegator, '', $delegator);
        $q = $g->SELECT(sprintf("PREFIX : <http://xmlns.com/foaf/0.1/>
                        PREFIX acl: <http://www.w3.org/ns/auth/acl#>
                        SELECT ?d FROM <%s> WHERE { 
                            ?s a :Person .
                            FILTER (?s = <%s>) .
                            OPTIONAL { ?s acl:delegatee ?d ; FILTER (?d = <%s>) . } .
                        }", $delegator, $delegator, $delegatee));

        if (isset($q['results']) && isset($q['results']['bindings'])) {
            $r = $q['results']['bindings'];
       
            if (isset($r) && is_array($r) && sizeof($r) > 0)
                return true;
        }
        return false;
    }

    /**
     * Check if the user has access to a specific URI
     * @param string $method Read/Write/Append/Control
     * @param array $options local configuration options
     * @param string $uri the URI of the resource
     *
     * @return boolean (true if user has access)
     */
    function can($method) {
        $this->_debug[] = "Method=".$method;

        // check if we are the domain owner
        if (is_file($this->_root_path.'.acl')) {
            $g = new Graph('', $this->_root_path.'.acl', '',$this->_root_uri.'.acl');
        
            if ($g->size() > 0) {
                // for the domain owner
                $this->_debug[] = "Graph size=".$g->size();

                $q = "PREFIX acl: <http://www.w3.org/ns/auth/acl#>
                      SELECT ?z WHERE { 
                        ?z acl:agent <".$this->_user."> .
                        }";
                $r = $g->SELECT($q);
                if (isset($r['results']['bindings']) && count($r['results']['bindings']) > 0) {
                    $this->_reason .= "User ".$this->_user." was authenticated as owner!";

                    return true;
                }
            }
        }
          
        // Recursively find a .acl
        $path = $this->_path;
        $uri = $this->_uri;
        $parent_path = (dirname($path) == $this->_root_path)?$path:dirname($path);
        $parent_uri = (dirname($uri) == $this->_root_uri)?$uri:dirname($uri);
        $break = false;

        // debug
        $this->_debug[] = " ";
        $this->_debug[] = "------------";
        $this->_debug[] = "Not the owner, going recursively! BASE=".$this->_path;
        $this->_debug[] = "User is: ".$this->_user;

        // walk path (force stop if we hit root level)
        while($path != dirname($this->_root_path)) {
            if ($break == true)
                return true;

            $r = $path;
            $this->_debug[] = "------------";
            $this->_debug[] = "Current level: ".$r;

            $resource = $uri;

            if ($r != $this->_root_path) {
                $path = (dirname($path) == $this->_root_path)?$path:dirname($path).'/';
                $this->_debug[] = "PATH=".$path." / ROOT_PATH=".$this->_root_path;
                $acl_file = (substr(basename($r), 0, 4) != '.acl')?'.acl.'.basename($r):basename($r);
                $acl_path = $path.$acl_file;
                $acl_uri = (dirname($uri) == $this->_root_uri)?$acl_file:dirname($uri).'/'.$acl_file;
                $this->_debug[] = "Dir=".$r." | acl_path=".$acl_path." | acl_uri=".$acl_uri;
                $uri = (dirname($uri) == $this->_root_uri)?$uri:dirname($uri).'/';
                $this->_debug[] = 'Parent_path='.$path.' | parent_uri='.$uri;
                /*
                $acl_file = (substr(basename($r), 0, 4) != '.acl')?'/.acl.'.basename($r):'/'.basename($r);
                $acl_path = $parent_path.$acl_file;
                $acl_uri = (dirname($uri) == $this->_root_uri)?$acl_file:dirname($uri).$acl_file;
                $this->_debug[] = "Dir=".$r." | acl_path=".$acl_path." | acl_uri=".$acl_uri;
                $path = (dirname($path) == $this->_root_path)?$path:dirname($path).'/';
                $uri = (dirname($uri) == $this->_root_uri)?$uri:dirname($uri).'/';
                $this->_debug[] = 'Parent_path='.$path.' | parent_uri='.$uri;
                */
            } else {
                $acl_path = $r.'.acl';
                $acl_uri = $uri.'.acl';
                $this->_debug[] = "ROOT Dir=".$r." | acl_path=".$acl_path." | acl_uri=".$acl_uri;
                if ($path == $this->_root_path)
                    $break = true;
            }

            if ($r == $this->_path) {
                $verb = 'accessTo';
            } else {
                $verb = 'defaultForNew';
                if (substr($resource, -1) != '/')
                    $resource = $resource.'/';
            }

            $this->_debug[] = "Verb=".$verb." | Resource=".$resource;
            
            if (is_file($acl_path)) { 
                $g = new Graph('', $acl_path, '',$acl_uri);
                if ($g->size() > 0) {
                    // specific authorization
                    $q = "PREFIX acl: <http://www.w3.org/ns/auth/acl#>".
                         "SELECT * WHERE { ".
                            "?z acl:agent <".$this->_user."> ; ".
                            "acl:mode acl:".$method." ; ". 
                            "acl:".$verb." <".$resource."> . ". 
                            "}";

                    $this->_debug[] = $q;
                    $res = $g->SELECT($q);
                    if (isset($res['results']['bindings']) && count($res['results']['bindings']) > 0) {
                        $this->_reason .= 'User '.$this->_user.' is allowed ('.$method.') access to '.$r."\n";
                        return true;
                    }                   
                    
                    // public authorization
                    $q = "PREFIX acl: <http://www.w3.org/ns/auth/acl#>".
                         "SELECT * WHERE { ".
                            "?z acl:agentClass <http://xmlns.com/foaf/0.1/Agent>; ".
                            "acl:mode acl:".$method."; ".
                            "acl:".$verb." <".$resource."> . ".
                            "}";
                    $this->_debug[] = $q;
                    $res = $g->SELECT($q);
                    if (isset($res['results']['bindings']) && count($res['results']['bindings']) > 0) {
                        $this->_reason .= 'Everyone is allowed ('.$method.') '.$verb.' to '.$r."\n";
                        return true;
                    } 
                    
                    $this->_reason = 'No one is allowed ('.$verb.') '.$method.' for resource '.$this->_uri."\n";

                    return false;
                }
            }
        }
    }
}
