<html>
<head>
<title>Trace - TeamDesk REST API Sample</title>
</head>
<body>
<h1>Request/Response tracing</h1>
<?php 
require_once("../TeamDesk-REST/RestApi.php");
require_once("utils.php");

try
{
	$restAPI = new TeamDesk\RestApi(
			array(
				"database" => Utils::$database, 
				"token" => "nosuchtoken", 
				"trace" => true,
				"ssl-verification" => Utils::$sslVerification)
			);

	// this will trigger an Exception
	$restAPI->User();
}
catch(Exception $e)
{
	echo "<pre><code>" . htmlentities($restAPI->dump()) . "</code></pre>";
}
?>
</body>
</html>