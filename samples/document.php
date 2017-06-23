<?php
// make sure we won't write nothing directly to output
ob_start();

require_once("../TeamDesk-REST/RestApi.php");
require_once("utils.php");

try
{
	$restAPI = new TeamDesk\RestApi(
			array(
				"database" => Utils::$database, 
				"user" => Utils::$user, 
				"password" => Utils::$password, 
				"ssl-verification" => Utils::$sslVerification)
			);

	$document = $restAPI->Document("Test", "SampleDoc", array(/*@row.id:*/2));
	
	// save to file
	// $document->save("./doc.pdf");
	
	// enable output and dump document to response
	ob_clean();
	$document->passthru(/*withHeaders:*/true);
	exit();
}
catch(Exception $e)
{
	ob_flush();
	echo "<pre><code>ERROR: " . htmlentities($e) . "</code></pre>";
}
?>