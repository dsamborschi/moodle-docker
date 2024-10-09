<?php

/// MOODLE ADMINISTRATION SETUP STEPS
// 1- Install the plugin
// 2- Enable web service advance feature (Admin > Advanced features)
// 3- Enable XMLRPC protocol (Admin > Plugins > Web services > Manage protocols)
// 4- Create a token for a specific user and for the service 'My service' (Admin > Plugins > Web services > Manage tokens)
// 5- Run this script directly from your browser: you should see 'Hello, FIRSTNAME'

/// SETUP - NEED TO BE CHANGED
$token = 'dff3bc6a9368d3b0db1ef59f6760ef7c';
$domainname = 'http://iicqa.dev.refineddata.com';

/// FUNCTION NAME
$functionname = 'local_wstemplate_hello_world';

/// PARAMETERS
$welcomemsg = 'Hello, ';

///// XML-RPC CALL
header('Content-Type: text/plain');
$serverurl = $domainname . '/webservice/xmlrpc/server.php'. '?wstoken=' . $token;
require_once('./curl.php');
$curl = new curl;
$post = xmlrpc_encode_request($functionname, array($welcomemsg));
$resp = xmlrpc_decode($curl->post($serverurl, $post));
print_r($resp);
