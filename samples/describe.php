<html>
<head>
<title>Describe - TeamDesk REST API Sample</title>
<link rel="stylesheet" href="style.css"/>
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
	
	// Get user info
	$userDescription = $restAPI->User();

	echo "<ul>";

	echo "<li>Hello " . htmlentities($userDescription->firstName . " " . $userDescription->lastName);

	// Describe Database
	$dbDescription = $restAPI->Describe();

	echo "<li>Database name: " . htmlentities($dbDescription->name);

	// Get first short table descriptor
	$firstTable = $dbDescription->tables[0];

	echo "<li>First table is: " . htmlentities($firstTable->recordsName);

	// Get full table description for first table, by alias or by name in singular form ($firstTable->recordName)
	$tableDescription = $restAPI->Describe($firstTable->alias);

	echo "<li>First column is: " . htmlentities($tableDescription->columns[0]->name);
	echo "</ul>";
}
catch(Exception $e)
{
	echo "<pre><code>ERROR: " . htmlentities($e) . "</code></pre>";
}
?>
</body>
</html>