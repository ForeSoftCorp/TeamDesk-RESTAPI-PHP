<html>
<head>
<title>Describe - TeamDesk REST API Sample</title>
</head>
<body>
<h1>Describe Method</h1>
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
			"ssl-verification" => Utils::$sslVerification));


	// Describe Database
	$dbDescription = $restAPI->Describe();

	echo "Database name: " . htmlentities($dbDescription->name) . "<br>";

	// Get first short table descriptor
	$firstTable = $dbDescription->tables[0];

	echo "<br>First table is: " . htmlentities($firstTable->recordsName) . "<br>";

	// Get full table description for first table, by alias or by name in singular form ($firstTable->recordName)
	$tableDescription = $restAPI->Describe($firstTable->alias);

	echo "<br>First column is: " . htmlentities($tableDescription->columns[0]->name);
}
catch(Exception $e)
{
	echo "<pre><code>ERROR: " . htmlentities($e) . "</code></pre>";
}
?>
</body>
</html>