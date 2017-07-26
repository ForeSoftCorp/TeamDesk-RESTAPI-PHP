# TeamDesk-RESTAPI-PHP

This library is a PHP wrapper around [TeamDesk REST API](https://teamdesk.crmdesk.com/image.aspx?id=13254) calls. 

Requires PHP 5.3+ with cURL extension.

Implements data decompression and optional [caching](#caching) to improve performance.

If you are planning to migrate from SOAP API, consider reading [migration notes](#migration-from-soap-api).

## Quick example

~~~ PHP
require_once("TeamDesk-REST\RestApi.php");

// Construct REST API Client
$restAPI = new TeamDesk\RestApi([
	"database" => 21995, 
	"user" => "test@test.com", 
	"password" => "pwd"]);

// Retrieve Text and Date columns from the table TEST
$result = $restAPI->Select("Test", [ "Text", "Date" ], "not IsBlank([Date])");

// Dump results
var_dump($result);
~~~

## Samples

* [Retrieve metadata](samples/describe.php)
* [Select records with pagination](samples/select.php)
* [Select records with pagination and caching](samples/cache.php)
* [Calculate aggregate values](samples/selectagg.php)
* [Select view content](samples/selectview.php)
* [Retrieve records by id or key](samples/retrieve.php)
* [Get attachment file](samples/attachment.php)
* [Create, Update, Upsert, Delete](samples/upsert.php)
* [Generate document](samples/document.php)
* [Debug/Trace](samples/trace.php)


## Caching

Caching allows TeamDesk to quickly check whether the data requested was not modified since the last call and skip data retrieval for locally cached copy from last call. 

Cache is particullary effective when querying infrequently modified tables. 

To enable caching pass an instance of the class that implements `TeamDesk\IHttpCache` interface to the `TeamDesk\RestApi` constructor.

~~~PHP
require_once("TeamDesk-REST/DiskCache.php");

$restAPI = new TeamDesk\RestApi([
	"cache" => new TeamDesk\DiskCache("/path/to/folder"),
	// ...other options
]);
~~~

For your convenicence we've implemented three types of cache:

* [DiskCache](TeamDesk-REST/DiskCache.php) for use with the file system
* [MemCache](TeamDesk-REST/MemCache.php) - in-memory cache using Memcached extension
* [WinCache](TeamDesk-REST/WinCache.php) - in-memory cache using Wincache extension

Though you can easily implement your own caching scheme, for example, using local database.

~~~PHP
// DEMO CODE!!! NEEDS ERROR HANDLING!!!
// Assumes cachetable(URL, CONTENT) with URL as a primary key
class MySqlCache implements TeamDesk\IHttpCache
{
	public function getResponse(/*string*/$url)
	{
		$mysqli = $this->connect();
		$result = $mysqli->query("SELECT content FROM cachetable WHERE url = '$url');
		$response = $result->fetch_object()->content;
		$mysqli->close();
		return TeamDesk\HttpResponse::readString($response);
	}

	public function setResponse(/*string*/$url, /*HttpResponse*/$response)
	{
		$mysqli = $this->connect();
		$stmt = $mysqli->prepare("REPLACE INTO cachetable SET url = ?, content = ?");
		$stmt->bind_param("ss", $url, $response->writeString());
		$stmt->execute();
		$stmt->close();
		$mysqli->close();
	}

	function connect()
	{
		return new mysqli("localhost", "my_user", "my_password", "cache_db");
	}
}
~~~

## Migration from SOAP API

REST API was designed to cope with couple of SOAP API shortcomings and this library is **no way** a drop in replacement for SOAP API Client.

While migrating, please keep in mind that:

* SOAP API does not run triggers, REST API does, until explicitly disabled. 
  See [no-workflow](help.md#construct) option.
* Create, Update and Upsert methods in SOAP API works in all-or-nothing mode. 
  If one record fails, no modifications are made at all. In contast, REST API works **with each record individually**. Some records may succeed, some may not. Check method's response to find out.
* For the above methods tabular structure is no longer required. For each record passed in you explicitly specify the value to set. You can update some fields in one record and some other fields in another in a single batch. If you want to blank the value of the field, explicitly pass NULL as a value.
  ~~~PHP
  $restAPI->Create("Table", [[ "Text" => "record 1" ], [ "Text" => NULL, "Date" => mktime() ]]);
  ~~~ 
* There is no drop-in replacement for Query(). [Select](help.md#select) and [SelectTop](help.md#selecttop) methods accept the same data in separate arguments:
  ~~~PHP
  $soapAPI->Query("SELECT TOP 10 [Column1], [Column2] FROM [Table] WHERE 1<>0 ORDER BY [Column1], [Column2] DESC");
  // becomes
  $restAPI->SelectTop(/*FROM:*/"Table", /*skip:*/0, /*TOP:*/10, /*columns:*/["Column1", "Column2"], /*WHERE:*/"1<>0", /*ORDER BY:*/["Column1", "Column2"]);
  ~~~ 
* There is no separate SetAttachment method, pass file's data directly to Create/Update/Upsert methods.
  ~~~PHP
  $restAPI->Create("Table", [[ "Text" => "file upload", "File" => TeamDesk\HttpContent::fromFile("./logo.png") ]]);
  ~~~ 
