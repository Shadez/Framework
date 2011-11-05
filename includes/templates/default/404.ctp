<?php
$code_messages = array(
	400 => array('Bad Request', 'The request cannot be fulfilled due to bad syntax.'),
	401 => array('Authorization Required', 'This server could not verify that you are authorized to access the document requested. Either you supplied the wrong credentials (e.g., bad password), or your browser doesn\'t understand how to supply the credentials required.'),
	403 => array('Forbidden', 'You don\'t have permission to access {url} on this server.'),
	404 => array('Not Found', 'The requested URL {url} was not found on this server.'),
);
if (!isset($code_messages[$code]))
	$error = $code_messages[404];
else
	$error = $code_messages[$code];
?>
<h1><?php echo $error[0]; ?></h1> 
<p><?php echo str_replace('{url}', '/' . $urlAddress, $error[1]); ?></p>