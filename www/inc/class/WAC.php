<?php

/**
 * Web Access Control class
 * http://www.w3.org/wiki/WebAccessControl
 */
class WAC {
    private $_req_user;
    private $_meta_name;
    private $_base_meta;
    private $_graph;
    private $_options;

    private $_reason;

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

        // methods: Read/Write/Control
        $this->_resource_uri = $resource_uri;

        $this->_req_user = $req_user;
        // building the meta file name
        // building the absolute path for the corresponding meta file
        if ($base_path == $aclbase) {// we're at the root level 
            $this->_meta_name = '.meta';
            $this->_base_meta = $aclbase.'/'.$this->_meta_name;
        } else {
            $this->_meta_name = '.meta.'.basename($aclbase);
            $this->_base_meta = dirname($aclbase).'/'.$this->_meta_name;
        }
        
        $this->_options = $options;
        
        /*
        echo "\n<!-- meta=".$this->_base_meta.
            " \n aclbase=".$aclbase.
            " \n base_path=".$base_path.
            " \n req_base=".REQUEST_BASE.
            " \n uri=".$this->_resource_uri." -->\n";
        */        
        $this->_graph = new Graph('', $this->_base_meta, '', REQUEST_BASE.'/'.$this->_meta_name);
        if ($options->linkmeta || $this->_graph->exists())
            header('Link: <'.dirname($this->_resource_uri).'/'.$this->_meta_name.'>; rel=meta');

        return true;
    }

    function getReason() {
        return $this->_reason;
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
        // there is no .meta file present
        if ($this->_options->open && !$this->_graph->size()) {
            $this->_reason .= 'No .meta file found. in '.REQUEST_BASE."\n";
            return true;
        }

        $uri = is_null($uri) ? $this->_resource_uri : $uri;
        
//        $verb = ($p == $uri) ? 'accessTo' : 'defaultForNew';
        $verb = 'accessTo';
        // specific authorization
        $q = "PREFIX acl: <http://www.w3.org/ns/auth/acl#>
              SELECT * WHERE { 
                ?z acl:agent <".$this->_req_user.">; 
                acl:mode acl:$method; 
                acl:accessTo <$uri> . 
                }";
        $r = $this->_graph->SELECT($q);
        if (isset($r['results']['bindings']) && count($r['results']['bindings']) > 0) {
            $this->_reason .= 'User '.$this->_req_user.' is allowed '.$method.' access to '.$uri."\n";
            return true;
        }
        // public authorization
        $q = "PREFIX acl: <http://www.w3.org/ns/auth/acl#>
              SELECT * WHERE { 
                ?z acl:agentClass <http://xmlns.com/foaf/0.1/Agent>; 
                acl:mode acl:$method; 
                acl:$verb <$uri> . 
                }";
        $r = $this->_graph->SELECT($q);
        if (isset($r['results']['bindings']) && count($r['results']['bindings']) > 0) {
            $this->_reason .= 'Everyone is allowed '.$method.' access to '.$uri."\n";
            return true;
        }

        $this->_reason .= 'User '.$this->_req_user.' is NOT allowed '.$method.' access to '.$uri."\n";
        return false;
    }

}
