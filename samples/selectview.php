<html>
<head>
<title>Select View - TeamDesk REST API Sample</title>
<link rel="stylesheet" href="style.css"/>
</head>
<body>
<h1>Select View Method</h1>
<?php 
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
	
	// Select Records
	$data = $restAPI->SelectView("Test", "List All");

	Utils::dumpTable($data);
}
catch(Exception $e)
{
	echo "<pre><code>ERROR: " . htmlentities($e) . "</code></pre>";
}
?>
</body>
</html>