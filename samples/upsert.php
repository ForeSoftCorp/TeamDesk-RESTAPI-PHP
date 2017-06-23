<html>
<head>
<title>Create/Update/Delete - TeamDesk REST API Sample</title>
</head>
<body>
<h1>Create/Update/Delete Methods</h1>
<?php 
require_once("../TeamDesk-REST/RestApi.php");
require_once("utils.php");

function checkResult(array $status, $operation)
{
	for($i = 0; $i < count($status); $i++)
	{
		if($status[$i]->status >= 300)
			throw new Exception("Failed to $operation record #$i: {$status[$i]->errors[0]->message}");
	}
	echo "<p>$operation succeded</p>";
}

try
{
	$restAPI = new TeamDesk\RestApi(
			array(
				"database" => Utils::$database, 
				"user" => Utils::$user, 
				"password" => Utils::$password, 
				"ssl-verification" => Utils::$sslVerification)
			);


	//
	// Create two records
	//
	$createResult = $restAPI->Create("Test", 
		array(
			array(
				"Text" => "Record Created #1",
				"Number" => 1,
				"Checkbox" => true,
				"Date" => time(),
				"Time" => time(),
				"Timestamp" => time(),
				"User" => "test@test.com",
				// add file from content
				"File" => TeamDesk\HttpContent::fromFile("./logo.png", "image/png")
			),
			array(
				"Text" => "Record Created #2",
				"Number" => 2,
				"Checkbox" => false,
				"Date" => "2017-06-01",
				"Time" => "12:34:56",
				"Timestamp" => "2017-06-01T12:34:56+02:00",
				// add file from raw data
				"File" => TeamDesk\HttpContent::fromData("Quick brown fox jumps...", "text/plain", "sample.txt")
			)
		));

	checkResult($createResult, "create");
	
	$recordIds = array_map(function($r) { return $r->id; }, $createResult);

	//
	// Update first record
	//
	$updateResult = $restAPI->Update("Test", 
		array(
			array(
				"@row.id" => $recordIds[0],
				"Text" => "Record Updated #1",
				"Number" => 2,
				"Checkbox" => false
			)
		)
	);

	checkResult($updateResult, "update");
	
	//
	// Retrieve data
	//
	Utils::dumpTable($restAPI->Retrieve("Test", array("Text", "Number", "Checkbox", "Date", "Time", "Timestamp", "User", "File"), $recordIds));

	//
	// Delete both records
	//
	$deleteResult = $restAPI->Delete("Test", $recordIds);
	checkResult($deleteResult, "delete");
}
catch(Exception $e)
{
	echo "<pre><code>ERROR: " . htmlentities($e) . "</code></pre>";
	echo "<pre><code>{$restAPI->Dump()}</code></pre>";
}
?>
</body>
</html>