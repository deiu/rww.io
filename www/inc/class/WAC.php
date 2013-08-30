<?php
/*
 *  Copyright (C) 2013 RWW.IO
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal 
 *  in the Software without restriction, including without limitation the rights 
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
 *  copies of the Software, and to permit persons to whom the Software is furnished 
 *  to do so, subject to the following conditions:

 *  The above copyright notice and this permission notice shall be included in all 
 *  copies or substantial portions of the Software.

 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, 
 *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A 
 *  PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT 
 *  HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION 
 *  OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE 
 *  SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
 
/**
 * Web Access Control class
 * http://www.w3.org/wiki/WebAccessControl
 */
class WAC {
    private $_req_user;
    private $_acl_name;
    private $_acl_file;
    private $_acl_file_base;
    private $_base_path;
    private $_graph;
    private $_options;
    private $_debug = array();
    private $_reason;
    private $_path;

    /**
     * Constructor for WAC
     * @param string $req_user  the authenticated user 
     * @param string $base_acl the .acl file we're reading from
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
        
        // building the acl file name
        // building the absolute path for the corresponding acl file
        if (substr(basename($resource_uri), 0, 4) == '.acl') {      
            $this->_acl_name = basename($resource_uri);
            $this->_acl_file = $aclbase;
            $acl_uri = REQUEST_BASE.'/'.$this->_acl_name;
        } else if ($_SERVER['SERVER_NAME'] == basename($aclbase)) {// we're at the root level
            $this->_acl_name = '.acl';
            $this->_acl_file = $aclbase.'/'.$this->_acl_name;
            $acl_uri = REQUEST_BASE.'/'.$this->_acl_name;
        } else {
            $this->_acl_name = '.acl.'.basename($aclbase);
            $this->_acl_file = dirname($aclbase).'/'.$this->_acl_name;
            $acl_uri = dirname($this->_resource_uri).'/'.$this->_acl_name;
        }
        
        $this->_acl_file_base = dirname($this->_acl_file);

        // set the default rel=acl link
        if ($options->linkmeta)
            header('Link: <'.$acl_uri.'>; rel=acl', false);

        /*
        $this->_debug[] = "<--------WAC--------->";
        $this->_debug[] = "acl_file_name=".$this->_acl_name;
        $this->_debug[] = "acl_file_path=".$this->_acl_file;
        $this->_debug[] = "acl_uri=".$acl_uri;
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
        if (is_file($this->_base_path.'/.acl')) {
            $g = new Graph('', $this->_base_path.'/.acl', '',REQUEST_BASE.'/.acl');
        
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
          
        // Recursively find a .acl
        $this->_debug[] = "Not the owner, going recursively! BASE=".REQUEST_BASE;
        $this->_debug[] = "User is: ".$this->_req_user;
        $res = $uri;
        $sys = $this->_acl_file_base;
        $path = $this->_path;
        $break = false;
        // walk path
        while(true) {
            if ($break == true)
                return true;

            $r = REQUEST_BASE.$path;

            if ($path != '/') {
                $acl_file = (substr(basename($r), 0, 4) != '.acl')?'.'.basename($r):'';
                $acl_path = $sys.'/.acl'.$acl_file;
                $acl_uri = dirname($r).'/.acl'.$acl_file;
                $this->_debug[] = "PATH > ACL path=".$acl_path." | ACL URI=".$acl_uri;
                $sys = (dirname($path) == '/')?$sys:dirname($sys);
                $path = dirname($path);
            } else {
                $acl_path = $sys.'/.acl';
                $acl_uri = $r.'.acl';
                $this->_debug[] = "ROOT > ACL path=".$acl_path." | ACL URI=".$acl_uri;
                if ($path == '/')
                    $break = true;
            }

            // debug
            //$this->_debug[] = "ACL path=".$acl_path." | ACL URI=".$acl_uri;
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
            
            if (is_file($acl_path)) { 
                $g = new Graph('', $acl_path, '',$acl_uri);
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
