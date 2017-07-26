<html>
<head>
<title>Select Aggregate - TeamDesk REST API Sample</title>
<link rel="stylesheet" href="style.css"/>
</head>
<body>
<h1>Select Aggregate Method</h1>
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

	$columns = array("Text//FL", "Id//COUNT");
	
	// Select Records
	$data = $restAPI->Select("Test", array(/*group by first letter*/"Text//FL", /*calculate count per group*/"Id//COUNT"));

	Utils::dumpTable($data);
}
catch(Exception $e)
{
	echo "<pre><code>ERROR: " . htmlentities($e) . "</code></pre>";
}
?>
</body>
</html>