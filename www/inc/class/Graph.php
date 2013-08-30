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
 
require_once('contrib/jsonld.php');

function absolutize($base, $url) {
    if (!$url)
        return $base;
    $url_p = parse_url($url);
    if (array_key_exists('scheme', $url_p))
        return $url;
    $base_p = parse_url("$base ");
    if (!array_key_exists('path', $base_p))
        $base_p = parse_url("$base/ ");
    $path = ($url[0] == '/') ? $url : dirname($base_p['path']) . "/$url";
    $path = preg_replace('~/\./~', '/', $path);
    $parts = array();
    foreach (explode('/', preg_replace('~/+~', '/', $path)) as $part)
        if ($part === '..') {
            array_pop($parts);
        } elseif ($part != '') {
            $parts[] = $part;
        }
    return (array_key_exists('scheme', $base_p) ? $base_p['scheme'] . '://' . $base_p['host'] : '') . '/' . implode('/', $parts);
}

class Graph {
    private $_world, $_base_uri, $_options, $_store, $_model, $_stream;
    private $_f_relativeURIs, $_f_writeBaseURI;
    private $_name, $_exists, $_storage, $_base;
    function __construct($storage, $name, $options='', $base='null:/') {
        global $_options;
        $ext = strrpos($name, '.');
        $ext = $ext ? substr($name, 1+$ext) : '';
        $this->_exists = false;

        // auto-detect empty storage from name
        if (empty($storage) && !empty($name)) {
            $storage = 'memory';
            if (file_exists($name)) {
                $this->_exists = true;
                if ($ext == 'sqlite')
                    $storage = 'sqlite';
            } elseif (file_exists("$name.sqlite")) {
                $this->_exists = true;
                $name = "$name.sqlite";
                $ext = 'sqlite';
                $storage = 'sqlite';
            } elseif ($ext == 'sqlite' || $_options->sqlite) {
                $storage = 'sqlite';
            }
        }
        if ($storage == 'sqlite') {
            if ($ext != 'sqlite') {
                $ext = 'sqlite';
                $name = "$name.sqlite";
            }
            if (file_exists($name))
                $this->_exists = true;
            if (empty($options) && !$this->exists())
                $options = "new='yes'";
        }
        $this->_name = $name;
        $this->_storage = $storage;
        $this->_options = $options;
        $this->_base = $base;

        // instance state
        $this->_world = librdf_new_world();
        if (function_exists('librdf_php_world_set_logger'))
            librdf_php_world_set_logger($this->_world);
        $this->_base_uri = librdf_new_uri($this->_world, $base);
        $this->_stream = null;

        // const objs
        $this->_f_relativeURIs = librdf_new_uri($this->_world, 'http://feature.librdf.org/raptor-relativeURIs');
        $this->_f_writeBaseURI = librdf_new_uri($this->_world, 'http://feature.librdf.org/raptor-writeBaseURI');
        $this->_n_0 = librdf_new_node_from_literal($this->_world, 0, null, 0);

        $this->reload();
        //$this->sendHeaders();
    }
    function reload() {
        if ($this->_model)
            librdf_free_model($this->_model);
        if ($this->_store)
            librdf_free_storage($this->_store);
        $this->_store = librdf_new_storage(
            $this->_world, $this->_storage,
            $this->_storage == 'memory' ? '' : $this->_name,
            $this->_options
        );
        $this->_model = librdf_new_model($this->_world, $this->_store, null);
        $this->_exists = ($this->_name && file_exists($this->_name)) ? true : false;
        if ($this->_storage == 'memory') {
            if ($this->exists())
                $this->append_file('turtle', "file://{$this->_name}", $this->_base);
        }
    }
    function sendHeaders() {
        header('Base: '.$this->_base);
        header('Filename: '.$this->_name);
        header('Size: '.$this->size());
        header('Storage: '.$this->_storage);
        header('Options: '.$this->_options);
    }
    function base() { return $this->_base; }
    function exists() { return $this->_exists; }
    function etag() {
        if ($this->exists() && file_exists($this->_name)) {
            return filemtime($this->_name).'-'.strtolower(md5(file_get_contents($this->_name, false, null, -1, 1024000)));
        }
    }
    function save($query=null) {
        $etag0 = $this->etag();
        if ($this->_storage == 'memory' && !empty($this->_name)) {
            file_put_contents($this->_name, $this->__toString());
        }
        $r = librdf_model_sync($this->_model);
        clearstatcache();
        $etag1 = $this->etag();

        $ctx = stream_context_create(array('http'=>array(
            'method' => 'POST',
            'content' => 'pub ' . $this->_base . ' ' . http_build_query(array(
                'etag0' => $etag0,
                'etag1' => $etag1,
                'query' => $query,
            )),
            'timeout' => 1,
            'ignore_errors' => true,
        )));
        @file_get_contents('http://localhost:8081/', false, $ctx);

        return $r;
    }
    function truncate() {
        librdf_free_model($this->_model);
        librdf_free_storage($this->_store);
        if ($this->_storage != 'memory')
            $this->delete();
        $this->_store = librdf_new_storage(
            $this->_world, $this->_storage,
            $this->_storage == 'memory' ? '' : $this->_name,
            $this->_storage == 'sqlite' ? "new='yes'" : ''
        );
        $this->_model = librdf_new_model($this->_world, $this->_store, null);
        $this->_exists = $this->_model ? true : false;
    }
    function delete() {
        if ($this->exists()) {
            unlink($this->_name);
            $this->_exists = false;
        }
    }
    function __destruct() {
        /*
        if (isset($this->_uriNodes))
            foreach ($this->_uriNodes as $elt) librdf_free_node($elt);
        if (isset($this->_blankNodes))
            foreach ($this->_blankNodes as $elt) librdf_free_node($elt);
        if (isset($this->_literalNodes))
            foreach ($this->_literalNodes as $type=>$nodes)
                foreach ($nodes as $elt) librdf_free_node($elt);
        */
        if ($this->_stream)
            librdf_free_stream($this->_stream);
        // instance state
        librdf_free_model($this->_model);
        librdf_free_storage($this->_store);
        librdf_free_uri($this->_base_uri);
        // common
        librdf_free_uri($this->_f_relativeURIs);
        librdf_free_uri($this->_f_writeBaseURI);
        librdf_free_node($this->_n_0);
        if ($this->_world)
            librdf_free_world($this->_world);
    }
    function __toString() {
        return $this->to_string('turtle');
    }
    function to_string($name) {
        if (!$this->_model) return;
        if ($name == 'json-ld') return $this->to_jsonld_string();
        $s = librdf_new_serializer($this->_world, $name, null, null);
        if ($name == 'json')
            librdf_serializer_set_feature($s, $this->_f_relativeURIs, $this->_n_0);
        librdf_serializer_set_feature($s, $this->_f_writeBaseURI, $this->_n_0);
        $r = librdf_serializer_serialize_model_to_string($s, $this->_base_uri, $this->_model);
        librdf_free_serializer($s);
        assert(strlen($r) || $this->size()<1);
        return $r;
    }
    function size() {
        if ($this->_model)
            return librdf_model_size($this->_model);
    }
    function to_stream() {
        if ($this->_stream)
            librdf_free_stream($this->_stream);
        $this->_stream = librdf_model_as_stream($this->_model);
        return $this->_stream;
    }
    function add_stream($stream) {
        return librdf_model_add_statements($this->_model, $stream) == 0;
    }
    function append($content_type, $content, $base=null) {
        if ($content_type == 'json-ld') return $this->append_jsonld($content);
        elseif ($content_type == 'json' && raptor_version_decimal_get()<20004)
            return $this->append_array(json_decode($content,1));
        $base_uri = librdf_new_uri($this->_world, is_null($base)?$this->_base:$base);
        $p = librdf_new_parser($this->_world, $content_type, null, null);
        $r = librdf_parser_parse_string_into_model($p, $content, $base_uri, $this->_model);
        librdf_free_parser($p);
        librdf_free_uri($base_uri);
        return $r == 0;
    }
    function append_file($content_type, $file, $base=null) {
        $p = librdf_new_parser($this->_world, $content_type, null, null);
        $file_uri = librdf_new_uri($this->_world, $file);
        $base_uri = librdf_new_uri($this->_world, is_null($base)?$this->_base:$base);
        $r = librdf_parser_parse_into_model($p, $file_uri, $base_uri, $this->_model);
        librdf_free_parser($p);
        librdf_free_uri($base_uri);
        librdf_free_uri($file_uri);
        return $r == 0;
    }
    function load($uri) {
        $uri = librdf_new_uri($this->_world, $uri);
        $r = librdf_model_load($this->_model, $uri, 'guess', null, null);
        librdf_free_uri($uri);
        return $r;
    }
    function _node($node) {
        $r = array();
        if (librdf_node_is_resource($node)) {
            $r['type'] = 'uri';
            $r['value'] = librdf_uri_to_string(librdf_node_get_uri($node));
        } elseif (librdf_node_is_literal($node)) {
            $r['type'] = 'literal';
            $r['value'] = librdf_node_get_literal_value($node);
            $dt = librdf_node_get_literal_value_datatype_uri($node);
            if ($dt)
                $r['datatype'] = librdf_uri_to_string($dt);
        } elseif (librdf_node_is_blank($node)) {
            $r['type'] = 'bnode';
            $r['value'] = librdf_node_get_blank_identifier($node);
        }
        return $r;
    }
    function _statement($statement) {
        return array(
            $this->_node(librdf_statement_get_subject($statement)),
            $this->_node(librdf_statement_get_predicate($statement)),
            $this->_node(librdf_statement_get_object($statement))
        );
    }
    function _uriNode($uri) {
        if (!isset($this->_uriNodes[$uri]))
            $r = $this->_uriNodes[$uri] = librdf_new_node_from_uri_string($this->_world, $uri);
        else
            $r = $this->_uriNodes[$uri];
        return $r;
    }
    function _blankNode($id) {
        if (!isset($this->_blankNodes[$id]))
            $r = $this->_blankNodes[$id] = librdf_new_node_from_blank_identifier($this->_world, $id);
        else
            $r = $this->_blankNodes[$id];
        return $r;
    }
    function _literalNode($value, $type=null) {
        if (!isset($this->_literalNodes[$type]))
            $this->_literalNodes[$type] = array();
        if (!isset($this->_literalNodes[$type][$value]))
            $r = $this->_literalNodes[$type][$value] = librdf_new_node_from_literal($this->_world, $value, NULL, 0);
        else
            $r = $this->_literalNodes[$type][$value];
        return $r;
    }
    function any($s=null, $p=null, $o=null) {
        $r = array();
        if (!is_null($s)) $s = librdf_new_node_from_uri_string($this->_world, absolutize($this->_base, $s));
        if (!is_null($p)) $p = librdf_new_node_from_uri_string($this->_world, absolutize($this->_base, $p));
        $pattern = librdf_new_statement_from_nodes($this->_world, $s, $p, $o);
        $stream = librdf_model_find_statements($this->_model, $pattern);
        while (!librdf_stream_end($stream)) {
            $q = $this->_statement(librdf_stream_get_object($stream));
            if (!isset($r[$q[0]['value']]))
                $r[$q[0]['value']] = array();
            if (!isset($r[$q[0]['value']][$q[1]['value']]))
                $r[$q[0]['value']][$q[1]['value']] = array();
            $r[$q[0]['value']][$q[1]['value']][] = $q[2];
            librdf_stream_next($stream);
        }
        librdf_free_stream($stream);
        //librdf_free_statement($pattern);
        //$s && librdf_free_node($s);
        //$p && librdf_free_node($p);
        return $r;
    }
    function remove_any($s=null, $p=null, $o=null) {
        $r = 0;
        if (!is_null($s)) $s = $this->_uriNode($s);
        if (!is_null($p)) $p = $this->_uriNode($p);
        $pattern = librdf_new_statement_from_nodes($this->_world, $s, $p, $o);
        $stream = librdf_model_find_statements($this->_model, $pattern);
        while (!librdf_stream_end($stream)) {
            $elt = librdf_stream_get_object($stream);
            $r += librdf_model_remove_statement($this->_model, $elt) ? 0 : 1;
            librdf_stream_next($stream);
        }
        librdf_free_stream($stream);
        //librdf_free_statement($pattern);
        //$s && librdf_free_node($s);
        //$p && librdf_free_node($p);
        return $r;
    }
    function remove_triple($triple) {
        if (!isset($triple['type']) || $triple['type'] != 'triple')
            return 0;
        $r = 0;
        $s = $triple['s'];
        $p = $triple['p'];
        $o = $triple['o'];
        if (!is_null($s)) $s = $this->_uriNode($s);
        if (!is_null($p)) $p = $this->_uriNode($p);
        if (!is_null($o)) {
            if ($triple['o_type'] == 'uri')
                $o = $this->_uriNode($o);
            elseif ($triple['o_type'] == 'literal')
                $o = $this->_literalNode($o);
        }
        $pattern = librdf_new_statement_from_nodes($this->_world, $s, $p, $o);
        $stream = librdf_model_find_statements($this->_model, $pattern);
        while (!librdf_stream_end($stream)) {
            $elt = librdf_stream_get_object($stream);
            $r += librdf_model_remove_statement($this->_model, $elt) ? 0 : 1;
            librdf_stream_next($stream);
        }
        librdf_free_stream($stream);
        //librdf_free_statement($pattern);
        //$s && librdf_free_node($s);
        //$p && librdf_free_node($p);
        return $r;
    }
    function query_to_string($query, $format, $base_uri=null) {
        timings($query);
        if (is_null($base_uri)) $base_uri = $this->_base_uri;
        elseif (is_string($base_uri)) $base_uri = librdf_new_uri($this->_world, $base_uri);
        $q = librdf_new_query($this->_world, 'sparql', null, $query, $base_uri);
        $r = librdf_model_query_execute($this->_model, $q);
        if (in_array($format, array('csv', 'tsv')))
            $format = 'http://www.w3.org/ns/formats/SPARQL_Results_'.strtoupper($format);
        else
            $format = 'http://www.w3.org/2001/sw/DataAccess/json-sparql/';
        $format_uri = librdf_new_uri($this->_world, $format);
        if ($r)
            $r = librdf_query_results_to_string($r, $format_uri, $base_uri);
        librdf_free_query($q);
        librdf_free_uri($format_uri);
        timings();
        return $r;
    }
    function SELECT($query, $base_uri=null) {
        return json_decode($this->query_to_string($query, 'json', $base_uri), 1);
    }
    function SELECT_p_o($uri, $base_uri=null) {
        $q = "SELECT * WHERE { <$uri> ?p ?o }";
        $r = array();
        $d = $this->SELECT($q, $base_uri);
        if (isset($d['results']) && isset($d['results']['bindings']))
        foreach($d['results']['bindings'] as $elt) {
            $p = $elt['p']['value'];
            if (!isset($r[$p])) {
                $r[$p] = array();
            }
            $r[$p][] = $elt['o'];
        }
        return $r;
    }
    function CONSTRUCT($query, $base_uri=null) {
        if (is_null($base_uri)) $base_uri = $this->_base_uri;
        timings($query);
        $q = librdf_new_query($this->_world, 'sparql', null, $query, $base_uri);
        $r = librdf_model_query_execute($this->_model, $q);
        $r_stream = librdf_query_results_as_stream($r);
        $r_store = librdf_new_storage($this->_world, 'memory', '', null);
        $r_model = librdf_new_model($this->_world, $r_store, null);
        librdf_model_add_statements($r_model, $r_stream);
        librdf_free_stream($r_stream);
        $serializer = librdf_new_serializer($this->_world, 'json', null, null);
        $r = librdf_serializer_serialize_model_to_string($serializer, null, $r_model);
        librdf_free_serializer($serializer);
        $r = json_decode($r, 1);
        if (is_null($r)) $r = array();
        librdf_free_model($r_model);
        librdf_free_storage($r_store);
        librdf_free_query($q);
        timings();
        return $r;
    }
    function append_objects($s0, $p0, $lst) {
        if (!is_null($s0))
            $s = (strlen($s0) > 1 && $s0{0} == '_' && $s0{1} == ':')
               ? $this->_blankNode($s0)
               : $this->_uriNode(absolutize($this->_base, $s0));
        if (!is_null($p0)) $p = $this->_uriNode(absolutize($this->_base, $p0));
        $r = 0;
        foreach ($lst as $elt) {
            if (isset($elt['type']) && isset($elt['value'])) {
                $type = $elt['type'];
                $value = $elt['value'];
                $datatype = isset($elt['datatype']) ? $elt['datatype'] : '';
                if ($type == 'literal' && $datatype) {
                    //$json = json_encode(array($s0=>array($p0=>array(array('type'=>'literal','value'=>$value)))));
                    $datatype_uri = librdf_new_uri($this->_world, $datatype);
                    $o = librdf_new_node_from_typed_literal($this->_world, $value, NULL, $datatype_uri);
                    //librdf_free_uri($dt);
                } elseif ($type == 'literal') {
                    $o = $this->_literalNode($value, null);
                } elseif ($elt['type'] == 'uri') {
                    $o = $this->_uriNode(absolutize($this->_base, $value));
                } elseif ($elt['type'] == 'bnode') {
                    $o = $this->_blankNode($value);
                }
                $r += librdf_model_add($this->_model, $s, $p, $o) ? 0 : 1;
                //$o && librdf_free_node($o);
            }
        }
        //$p && librdf_free_node($p);
        //$s && librdf_free_node($s);
        return $r;
    }
    function append_array($data) {
        $r = 0;
        librdf_model_transaction_start($this->_model);
        foreach ($data as $s=>$s_data) {
            foreach ($s_data as $p=>$p_data) {
                $r += $this->append_objects($s, $p, $p_data);
            }
        }
        librdf_model_transaction_commit($this->_model);
        return $r;
    }
    function patch_array($data) {
        $r = 0;
        librdf_model_transaction_start($this->_model);
        foreach ($data as $s=>$s_data) {
            $s = absolutize($this->_base, $s);
            foreach ($s_data as $p=>$p_data) {
                $r += $this->remove_any($s, $p);
                $r += $this->append_objects($s, $p, $p_data);
            }
        }
        librdf_model_transaction_commit($this->_model);
        return $r;
    }
    function patch_json($json) {
        if (raptor_version_decimal_get()<20004)
            return $this->patch_array(json_decode($json,1));
        $r = 0;
        librdf_model_transaction_start($this->_model);
        $data = json_decode($json, 1);
        foreach ($data as $s=>$s_data) {
            $s = absolutize($this->_base, $s);
            foreach ($s_data as $p=>$p_data) {
                $r += $this->remove_any($s, $p);
            }
        }
        $r += $this->append('json', $json);
        librdf_model_transaction_commit($this->_model);
        return $r;
    }
    function append_jsonld($content) {
        $data = jsonld_normalize(json_decode($content));
        librdf_model_transaction_start($this->_model);
        foreach($data as $s_data) {
            $s = $s_data->{'@subject'}->{'@iri'};
            unset($s_data->{'@subject'});
            foreach($s_data as $p=>$p_data) {
                if (gettype($p_data) != 'array')
                    $p_data = array($p_data);
                $o_lst = array();
                foreach($p_data as $o) {
                    if (gettype($o) != 'object')
                        $o = (object)array('@literal'=>$o);
                    if (isset($o->{'@iri'})) {
                        $o = $o->{'@iri'};
                        if (strlen($o) > 1 && $o{0} == '_' && $o{1} == ':')
                            array_push($o_lst, array('type'=>'bnode', 'value'=>$o));
                        else
                            array_push($o_lst, array('type'=>'uri', 'value'=>$o));
                    } elseif (isset($o->{'@literal'})) {
                        if (isset($o->{'@datatype'}))
                            array_push($o_lst, array('type'=>'literal', 'value'=>$o->{'@literal'}, 'datatype'=>$o->{'@datatype'}));
                        else
                            array_push($o_lst, array('type'=>'literal', 'value'=>$o->{'@literal'}));
                    }
                }
                $this->append_objects($s, $p, $o_lst);
            }
        }
        librdf_model_transaction_commit($this->_model);
        return true;
    }
    function to_jsonld_string() {
        $r = array();
        $stream = librdf_model_as_stream($this->_model);
        while (!librdf_stream_end($stream)) {
            $elt = $this->_statement(librdf_stream_get_object($stream));
            $d = new \stdClass();
            $d->{'@subject'} = $elt[0]['value'];
            if ($elt[2]['type'] == 'literal')
                if (isset($elt[2]['datatype']))
                    $d->{$elt[1]['value']} = (object)array('@literal'=>$elt[2]['value'],'@datatype'=>$elt[2]['datatype']);
                else
                    $d->{$elt[1]['value']} = $elt[2]['value'];
            else
                $d->{$elt[1]['value']} = (object)array('@iri'=>$elt[2]['value']);
            $r[] = $d;
            librdf_stream_next($stream);
        }
        librdf_free_stream($stream);
        return str_replace('\\/', '/', json_encode(jsonld_normalize($r)));
    }
} // class Graph

/*
$_NS = array(
    'rdfs' => '<http://www.w3.org/2000/01/rdf-schema#>',
    'dc' => '<http://purl.org/dc/terms/>',
    'foaf' => '<http://xmlns.com/foaf/0.1/>',
    'en' => '<http://en.wikipedia.org/wiki/>',
    'rdf' => '<http://www.w3.org/1999/02/22-rdf-syntax-ns#>',
    'xsd' => '<http://www.w3.org/2001/XMLSchema#>',
);
*/
