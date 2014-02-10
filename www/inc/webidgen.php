<?php

// create a WebID certificate + profile data
if (isset($_POST['SPKAC']) && isset($_POST['username'])) {
    // Prepare the request
    $name = (isset($_POST['name']))?$_POST['name']:'Anonymous';
    
    if (isset($_POST['path'])) {
        $_POST['path'] = (substr($_POST['path'], 0, 1) == '/')?substr($_POST['path'], 1):$_POST['path'];
        $path = $_POST['path'];
    } else {
        $path = 'profile/card#me';
    }
    // Exit if we don't have a #
    if (strpos($path, '#') === false) // missing # 
        die("You must at least provide a # fragment. For example: #me or #public.");

    // remove the # fragment so we get the profile document path
    $path_frag = explode('#', $path);
    $profile = $path_frag[0];
    $hash = $path_frag[1];
    $_root = $_ENV['CLOUD_DATA'].'/'.$_POST['username'].'.'.ROOT_DOMAIN;
    
    // rebuild path for the profile document
    $webid_file = $_root.'/'.$profile;

    // create but do not overwrite existing profile document
    if (file_exists($webid_file) === true) {
        die('Error: <strong>'. $path.'</strong> already exists!');
    } else {           
        // check if the root dir exists and create it (recursively) if it doesn't
        if (!mkdir(dirname($webid_file), 0755, true))
            die('Cannot create directory at '.dirname($webid_file).', please check permissions.');
    }

    $BASE = 'http://'.$_POST['username'].'.'.$_SERVER['SERVER_NAME']; // force https
    $email = isset($_POST['email'])?$_POST['email']:null;
    $spkac = str_replace(str_split("\n\r"), '', $_POST['SPKAC']);
    $webid = $BASE.'/'.$path;
    
    // --- Cert ---
    $cert_cmd = 'python '.$_ENV['CLOUD_HOME'].'/py/pki.py '.
                    " -s '$spkac'" .
                    " -n '$name'" .
                    " -w '$webid'";
    
    // Send the certificate back to the user
    header('Content-Type: application/x-x509-user-cert');
    
    $cert = trim(shell_exec($cert_cmd));
    $ret_cmd = "echo '$cert' | openssl x509 -in /dev/stdin -outform der";
    echo trim(shell_exec($ret_cmd));
    
    $mod_cmd = "echo '$cert' | openssl x509 -in /dev/stdin -modulus -noout";
    // remove the Modulus= part
    $output = explode('=', trim(shell_exec($mod_cmd)));
    $modulus = $output[1];
    
    // --- Workspaces ---
    // create master workspace
    $mw = 'ws/';
    $mw_file = $_root.'/'.$mw;
    $mw_uri = $BASE.'/'.$mw;
    if (!mkdir($_root.'/'.$mw, 0755, true))
            die('Cannot create workspace "'.$_root.'/'.$mw.'", please check permissions');
    // create dedicated workspaces
    $ws = array('apps', 'public', 'shared', 'private');
    foreach ($ws as $w) {
        $w = $_root.'/'.$mw.$w;
        if (!mkdir($w, 0755, true))
            die('Cannot create workspace "'.$w.'", please check permissions');
    }
    $ap_uri = $mw_uri.'apps/';
    $ap_file = $mw_file.'apps/';
    $sh_uri = $mw_uri.'shared/';
    $sh_file = $mw_file.'shared/';
    $pu_uri = $mw_uri.'public/';
    $sh_file = $mw_file.'public/';
    $pr_uri = $mw_uri.'private/';
    $pr_file =$mw_file.'private/';
    // end workspaces

    // --- Profile --- 
    
    // Write the new profile to disk
    $document = new Graph('', $webid_file, '', $BASE.'/'.$profile);
    if (!$document) {
        echo "Cannot create a new graph!";
        exit;
    }
    
    // add a PrimaryTopic
    $document->append_objects($BASE.'/'.$profile,
            'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
             array(array('type'=>'uri', 'value'=>'http://xmlns.com/foaf/0.1/PersonalProfileDocument')));
    $document->append_objects($BASE.'/'.$profile,
            'http://xmlns.com/foaf/0.1/primaryTopic',
             array(array('type'=>'uri', 'value'=>$webid)));
     
    // add a foaf:Person
    $document->append_objects($webid,
            'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
            array(array('type'=>'uri', 'value'=>'http://xmlns.com/foaf/0.1/Person')));
    // add name
    $document->append_objects($webid,
            'http://xmlns.com/foaf/0.1/name',
            array(array('type'=>'literal', 'value'=>$name)));
    // add mbox if we have one
    if (strlen($email) > 0) {
        $document->append_objects($webid,
                'http://xmlns.com/foaf/0.1/mbox',
                array(array('type'=>'uri', 'value'=>'mailto:'.$email)));
    }

    // ---- Add workspaces ----
    // add master workspace
    $document->append_objects($webid,
            'http://www.w3.org/ns/pim/space#masterWorkspace',
            array(array('type'=>'uri', 'value'=>$mw_uri)));
    // add apps workspace
    $document->append_objects($webid,
            'http://www.w3.org/ns/pim/space#workspace',
            array(array('type'=>'uri', 'value'=>$ap_uri)));
    // add public
    $document->append_objects($webid,
            'http://www.w3.org/ns/pim/space#workspace',
            array(array('type'=>'uri', 'value'=>$pu_uri)));
    // add shared
    $document->append_objects($webid,
            'http://www.w3.org/ns/pim/space#workspace',
            array(array('type'=>'uri', 'value'=>$sh_uri)));
    // add private
    $document->append_objects($webid,
            'http://www.w3.org/ns/pim/space#workspace',
            array(array('type'=>'uri', 'value'=>$pr_uri)));
    
    // ---- Certificate ----
    // add modulus and exponent as bnode
    $document->append_objects($webid,
            'http://www.w3.org/ns/auth/cert#key',
            array(array('type'=>'bnode', 'value'=>'_:bnode1')));
    $document->append_objects('_:bnode1',
            'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
            array(array('type'=>'uri', 'value'=>'http://www.w3.org/ns/auth/cert#RSAPublicKey'))); 
    
    if (isset($modulus))
    $document->append_objects('_:bnode1',
            'http://www.w3.org/ns/auth/cert#modulus',
            array(array('type'=>'literal', 'value'=>$modulus, 'datatype'=>'http://www.w3.org/2001/XMLSchema#hexBinary')));
    
    $document->append_objects('_:bnode1',
            'http://www.w3.org/ns/auth/cert#exponent',
            array(array('type'=>'literal', 'value'=>'65537', 'datatype'=>'http://www.w3.org/2001/XMLSchema#int')));
    
    $document->save();
    
    // ------ DONE WITH PROFILE -------
    
    // ------ ACLs ------
    // TODO: check if this is something we should do on the server side
    /*
    // master workspace
    $mw_acl = new Graph('', $mw_file, '', $mw_uri);
    $ap_acl = new Graph('', $ap_file, '', $ap_uri);
    $sh_acl = new Graph('', $sh_file, '', $sh_uri);
    $pu_acl = new Graph('', $pu_file, '', $pu_uri);
    $pr_acl = new Graph('', $pr_file, '', $pr_uri);
    
    if (!$mw_acl || !$ap_acl || $sh_acl || !pu_acl || !pr_acl) {
        echo "Cannot create ACL graphs!";
        exit;
    }
    
    <>
    <http://www.w3.org/ns/auth/acl#accessTo> <> ;
    <http://www.w3.org/ns/auth/acl#agent> <https://my-profile.eu/people/deiu/card#me> ;
    <http://www.w3.org/ns/auth/acl#mode> <http://www.w3.org/ns/auth/acl#Read>, <http://www.w3.org/ns/auth/acl#Write> .

<#private/>
    <http://www.w3.org/ns/auth/acl#accessTo> <private/> ;
    <http://www.w3.org/ns/auth/acl#agent> <https://my-profile.eu/people/deiu/card#me> ;
    <http://www.w3.org/ns/auth/acl#mode> <http://www.w3.org/ns/auth/acl#Read>, <http://www.w3.org/ns/auth/acl#Write> .
    */
}
