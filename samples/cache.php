<html>
<head>
<title>Caching - TeamDesk REST API Sample</title>
<meta charset="windows-1251">
</head>
<body>
<?php 
mb_internal_encoding("windows-1251");

/* This is modified Select sample with caching added */

require_once("../TeamDesk-REST/RestApi.php");
require_once("../TeamDesk-REST/DiskCache.php");
// require_once("../TeamDesk-REST/WinCache.php");
// require_once("../TeamDesk-REST/MemCache.php");
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
				"ssl-verification" => Utils::$sslVerification,
				
				// set up disk cache
				"cache" => new TeamDesk\DiskCache(/*temp-path*/),
				
				// - or set up win cache
				// "cache" => new TeamDesk\WinCache(/*ttl in seconds*/),
				//
				// - or set up memcached
				// "cache" => new TeamDesk\MemCache(array(array('memcached.domain.com', 11211))),
				//

				// Caching is completely transparent.
				// To determine the data is from cache we need access to last response
				// which is available only if trace is true
				"trace" => true));

	$columns = array("Id", "Text", "Date");
	$filter = "not IsNull([Date])";
	$sort = "Date//DESC";
	
	// Select Records
	$data = $restAPI->SelectTop("Test", /*top*/PAGE_SIZE, /*skip*/($pageNum - 1) * PAGE_SIZE, $columns, $filter, $sort);

	echo "<h2>Page #$pageNum: " . ($restAPI->getLastResponse()->fromCache ? "cached" : "not cached") . "</h2>";
	Utils::dumpTable($data);
	echo "<p>";
	if($pageNum > 1)
	{
		echo "<a href=\"$thisPage" . ($pageNum > 2 ? "?page=" . ($pageNum - 1) : "") . "\">Prev</a>";
		if(count($data) == PAGE_SIZE) echo " | ";
	}
	if(count($data) == PAGE_SIZE)
		echo "<a href=\"$thisPage?page=" . ($pageNum + 1) . "\">Next</a>";
	echo "</p>";
}
catch(Exception $e)
{
	echo "<pre><code>ERROR: " . htmlentities($e) . "</code></pre>";
}
?>
</body>
</html>