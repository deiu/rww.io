<?php

/**
 * Web Access Control class
 * http://www.w3.org/wiki/WebAccessControl
 */
class WAC {
    private $_req_user;
    private $_meta_name;
    private $_meta_file;
    private $_meta_file_base;
    private $_base_path;
    private $_graph;
    private $_options;
    private $_debug = array();
    private $_reason;
    private $_path;

    /**
     * Constructor for WAC
     * @param string $req_user  the authenticated user 
     * @param string $base_meta the .meta file we're reading from
     * @param string $base_uri  the base URI of the HTTP request
     * @param array $options    local configuration options
     *
     * @return boolean (always true for now)
     */
    function __construct($req_user, $aclbase, $base_path, $resource_uri, $options) {
        if (substr($aclbase, -1) == '/')
            $aclbase = substr($aclbase, 0, -1);

        $this->_path = $_SERVER['SCRIPT_URL'];

        $this->_base_path = $base_path;
        
        // methods: Read/Write/Control
        $this->_resource_uri = $resource_uri;

        $this->_req_user = $req_user;

        
        // building the meta file name
        // building the absolute path for the corresponding meta file
        if (substr(basename($resource_uri), 0, 5) == '.meta') {      
            $this->_meta_name = basename($resource_uri);
            $this->_meta_file = $aclbase;
            $meta_uri = REQUEST_BASE.'/'.$this->_meta_name;
        } else if ($_SERVER['SERVER_NAME'] == basename($aclbase)) {// we're at the root level
            $this->_meta_name = '.meta';
            $this->_meta_file = $aclbase.'/'.$this->_meta_name;
            $meta_uri = REQUEST_BASE.'/'.$this->_meta_name;
        } else {
            $this->_meta_name = '.meta.'.basename($aclbase);
            $this->_meta_file = dirname($aclbase).'/'.$this->_meta_name;
            $meta_uri = dirname($this->_resource_uri).'/'.$this->_meta_name;
        }
        
        $this->_meta_file_base = dirname($this->_meta_file);

        /*
        $this->_debug[] = "<--------WAC--------->";
        $this->_debug[] = "meta_file_name=".$this->_meta_name;
        $this->_debug[] = "meta_file_path=".$this->_meta_file;
        $this->_debug[] = "meta_uri=".$meta_uri;
        $this->_debug[] = "aclbase=".$aclbase;
        $this->_debug[] = "Request base=".REQUEST_BASE;
        $this->_debug[] = "resource_uri=".$this->_resource_uri;
        $this->_debug[] = "WebID=".$this->_req_user;
        */
        return true;
    }

    function getReason() {
        return $this->_reason;
    }
    
    function getDebug() {
        return $this->_debug;
    }

    /**
     * Check if the user has access to a specific URI
     * @param string $method Read/Write/Append/Control
     * @param array $options local configuration options
     * @param string $uri the URI of the resource
     *
     * @return boolean (true if user has access)
     */
    function can($method, $uri=null) {

        $this->_debug[] = "Method=".$method;

        // set the resource URI
        $uri = is_null($uri) ? $this->_resource_uri : $uri;

        // check if we are the domain owner
        if (is_file($this->_base_path.'/.meta')) {
            $g = new Graph('', $this->_base_path.'/.meta', '',REQUEST_BASE.'/.meta');
        
            if ($g->size() > 0) {
                // for the domain owner
                $this->_debug[] = "Graph size=".$g->size();

                $q = "PREFIX acl: <http://www.w3.org/ns/auth/acl#>
                      SELECT ?z WHERE { 
                        ?z acl:agent <".$this->_req_user."> .
                        }";
                $r = $g->SELECT($q);
                if (isset($r['results']['bindings']) && count($r['results']['bindings']) > 0) {
                    $this->_reason .= "User ".$this->_req_user." was authenticated as owner!";

                    return true;
                }
            }
        }
          
        // Recursively find a .meta

        $this->_debug[] = "Not the owner, going recursively! BASE=".REQUEST_BASE;
     
        $res = $uri;
        $sys = $this->_meta_file_base;
        $path = $this->_path;
        $break = false;
        // walk path
        while(true) {
            if ($break == true)
                return true;

            $r = REQUEST_BASE.$path;

            if ($path != '/') {
                $meta_file = (substr(basename($r), 0, 5) != '.meta')?'.'.basename($r):'';
                $meta_path = $sys.'/.meta'.$meta_file;
                $meta_uri = dirname($r).'/.meta.'.$meta_file;

                $sys = (dirname($path) == '/')?$sys:dirname($sys);
                $path = dirname($path);
            } else {
                $meta_path = $sys.'/.meta';
                $meta_uri = $r.'.meta';

                if ($path == '/')
                    $break = true;
            }

            // debug
            $this->_debug[] = "Meta path=".$meta_path." | Meta URI=".$meta_uri;
            $this->_debug[] = "Base URI=".$r." | Ref URI=".$uri;
            
            //$verb = ($r == $uri) ? 'accessTo' : 'defaultForNew';
            if ($r == $uri) {
                $verb = 'accessTo';
            } else {
                $verb = 'defaultForNew';
                if (substr($r, -1) != '/')
                    $r = $r.'/';
            }

            $this->_debug[] = "Verb=".$verb." | newR=".$r;
            
            if (is_file($meta_path)) { 
                $g = new Graph('', $meta_path, '',$meta_uri);
                if ($g->size() > 0) {                    
                    // specific authorization
                    $q = "PREFIX acl: <http://www.w3.org/ns/auth/acl#>
                          SELECT * WHERE { 
                            ?z acl:agent <".$this->_req_user.">; 
                            acl:mode acl:$method; 
                            acl:$verb <$r> . 
                            }";
                            
                    $this->_debug[] = $q;
                    $res = $g->SELECT($q);
                    if (isset($res['results']['bindings']) && count($res['results']['bindings']) > 0) {
                        $this->_reason .= 'User '.$this->_req_user.' is allowed ('.$method.') access to '.$r."\n";
                        return true;
                    }                   
                    
                    // public authorization
                    $q = "PREFIX acl: <http://www.w3.org/ns/auth/acl#>
                          SELECT * WHERE { 
                            ?z acl:agentClass <http://xmlns.com/foaf/0.1/Agent>; 
                            acl:mode acl:$method; 
                            acl:$verb <".$r."> . 
                            }";
                    $this->_debug[] = $q;
                    $res = $g->SELECT($q);
                    if (isset($res['results']['bindings']) && count($res['results']['bindings']) > 0) {
                        $this->_reason .= 'Everyone is allowed ('.$method.') '.$verb.' to '.$r."\n";
                        return true;
                    } 
                    
                    $this->_reason = 'No one is allowed ('.$method.') '.$verb.' to '.$uri."\n";
             
                    return false;
                }
            }
        }
    }
}
