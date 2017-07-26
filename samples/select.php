<html>
<head>
<title>Select - TeamDesk REST API Sample</title>
<link rel="stylesheet" href="style.css"/>
</head>
<body>
<h1>Select Method</h1>
<?php 
require_once("../TeamDesk-REST/RestApi.php");
require_once("utils.php");

define("PAGE_SIZE", 10);
$pageNum = isset($_GET["page"]) ? intval($_GET["page"]) : 1;
$thisPage = basename(__FILE__);

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
	$filter = "not IsNull([Date])";
	$sort = "Date//DESC";
	
	// Select Records
	$data = $restAPI->SelectTop("Test", /*skip*/($pageNum - 1) * PAGE_SIZE, /*top*/PAGE_SIZE, $columns, $filter, $sort);

	echo "<h2>Page #$pageNum</h2>";
	Utils::dumpTable($data);
	if(count($data) == PAGE_SIZE)
	{
		echo "<p>";
		if($pageNum > 1)
			echo "<a href=\"$thisPage" . ($pageNum > 2 ? "?page=" . ($pageNum - 1) : "") . "\">Previous Page</a> | ";
		echo "<a href=\"$thisPage?page=" . ($pageNum + 1) . "\">Next Page</a>";
		echo "</p>";
	}
}
catch(Exception $e)
{
	echo "<pre><code>ERROR: " . htmlentities($e) . "</code></pre>";
}
?>
</body>
</html>