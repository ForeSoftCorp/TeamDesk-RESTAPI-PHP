<html>
<head>
<title>Retrieve - TeamDesk REST API Sample</title>
</head>
<body>
<h1>Retrieve Method</h1>
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

	$columns = array("Id", "Text", "Date");
	// Retrieve records by their internal ids
	$data = $restAPI->Retrieve("Test", $columns, array(1, 2, 3));

	echo "<h2>Records by id</h2>";
	Utils::dumpTable($data);

	if(count($data))
	{
		// now retrieve same records by key
		$data = $restAPI->RetrieveByKey("Test", $columns, array_map(function($row) { return $row["Id"]; }, $data));

		echo "<h2>Records by key (tables should match)</h2>";
		Utils::dumpTable($data);
	}
}
catch(Exception $e)
{
	echo "<pre><code>ERROR: " . htmlentities($e) . "</code></pre>";
}
?>
</body>
</html>