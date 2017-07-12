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
				"trace" => true,
				"ssl-verification" => Utils::$sslVerification)
			);

	$result = $restAPI->SelectTop("Test", 1, 0, array("File"), "Begins([File], \"logo.png;\")");

	if(count($result) == 1)
	{
		$row = $result[0];
		if($row["File"])
		{
			$result = $restAPI->Attachment("Test", "File", $row["@row.id"], $row["File"]);
			ob_clean();
			header("Content-Type: " . $result->getHeader("Content-Type"));
			$result->passthru();
			exit();
		}
		else
		{
			ob_flush();
			echo "<p>No attachment</p>";
		}
	}
	else
	{
		ob_flush();
		echo "<p>No record</p>";
	}
}
catch(Exception $e)
{
	ob_flush();
	echo "<pre><code>ERROR: " . htmlentities($e) . "</code></pre>";
}
?>