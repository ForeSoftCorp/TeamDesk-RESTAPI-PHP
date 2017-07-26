<?php namespace TeamDesk;

require_once("HttpClient.php");

class RestApi
{
	const VERSION = "1.0";

	public function __construct(array $options)
	{
		$this->options = $options + array(
			"url" => null,					// : string
			"domain" => "www.teamdesk.net", // : string
			"database" => null,				// : int|string
			"user" => null,					// : string
			"password" => null,				// : string
			"token" => null,				// : string
			"ssl-verification" => true,		// : bool|string
			"trace" => false,				// : bool
			"no-workflow" => false,			// : bool
			"variables" => null,			// : array<name=>value>
			"cache" => null					// : IHttpCache
		);

		$cacheProvider = $this->options["cache"];
		if($cacheProvider && !($cacheProvider instanceof IHttpCache))
			throw new \Exception("Expected instance of TeamDesk\HttpCache-derived class");
		if($this->options["token"] == null)
		{
			if($this->options["user"] == null)
				throw new \Exception("Expected user or token parameter");
			if($this->options["password"] == null)
				throw new \Exception("Expected user and password parameters");
		}
		if($this->options["url"] == null)
		{
			if($this->options["database"] == null)
				throw new \Exception("Expected database parameter");
			$this->options["url"] = "https://{$this->options["domain"]}/secure/api/v2/{$this->options["database"]}/";
		}
		if($this->options["variables"] != null && !is_array($this->options["variables"]))
			throw new \Exception("Expected name=>value array of variables");
			
		$curl_opt = array(
			CURLOPT_ENCODING => "", 
			CURLOPT_FOLLOWLOCATION => 0,
			CURLOPT_SSLVERSION => 1 /*CURL_SSLVERSION_TLSv1*/);
		
		if($this->options["ssl-verification"] === false)
		{
			$curl_opt[CURLOPT_SSL_VERIFYHOST] = 0;
			$curl_opt[CURLOPT_SSL_VERIFYPEER] = 0;
		}
		else if(is_string($this->options["ssl-verification"]))
		{
			$curl_opt[CURLOPT_CAINFO] = $this->options["ssl-verification"];
		}

		$this->client = new HttpClient(
			array(
				"Authorization" =>
					$this->options["token"] ? 
						"Bearer " . $this->options["token"] : 
						"Basic " . base64_encode($this->options["user"] . ":" . $this->options["password"]),
				"User-Agent" => "TeamDesk.RestApi/" . self::VERSION . " (PHP " . phpversion() . ")",
				"Expect" => ""
			),
			$curl_opt
		);
		$this->client->cacheProvider = $cacheProvider;
	}

	/**
	* Retrieves current user's information
	*
	* @return	stdClass			User information
	*/
	public function User() /* : stdClass */
	{
		$o = $this->getJSON("user.json");
		return $o;
	}

	/**
	* Retrieves application's and tables' metadata 
	*
	* @param	string		$table  Optional, the name of the table to retrieve metadata for.
	* @return	stdClass	Application or table's metadata
	*/
	public function Describe(/*string*/$table = null) /* : stdClass */
	{
		$url = "";
		if($table != null)
			$url .= self::urlPathEncode($table) . "/";
		$url .= "describe.json";
		$o = $this->getJSON($url, 
			$table != null ? $this->createVars() : null);
		return $o;
	}

	/**
	*	Retrieves the data from the table.
	*
	*	@param string	$table Table alias or name in a singular form.
	*	@param array	$columns Array of column names or aliases to retrieve.
	*	@param string	$filter Optional string containing filter expression text.
	*	@param array	$sort Optional array of column names to sort by.
	*
	*	@return array An array of records, each record is represented as associative name=>value array.
	*/
	public function Select(/*string*/$table, array $columns, $filter = null, /*string|array<string>*/$sort = null) /* : array<name=>value>*/ 
	{
		return $this->SelectTop($table, null, null, $columns, $filter, $sort);
	}

	/**
	*	Retrieves the data from the table.
	*
	*	@param string	$table	Table alias or name in a singular form.
	*	@param int		$skip	Number of records to skip prior to retrieval.
	*	@param int		$top	Number of records to retrieve.
	*	@param array	$columns Array of column names or aliases to retrieve.
	*	@param string	$filter	Optional string containing filter expression text.
	*	@param array	$sort	Optional array of column names to sort by.
	*
	*	@return array An array of records, each record is represented as associative name=>value array.
	*/
	public function SelectTop(/*string*/$table, /*int*/$skip, /*int*/$top, array $columns, $filter = null, /*string|array<string>*/$sort = null) /* : array<name=>value>*/ 
	{
		$o = $this->getJSON(
			self::urlPathEncode($table) . "/select.json",
			array(
				"top" => $top ? $top : null,
				"skip" => $skip ? $skip : null,
				"column" => $columns,
				"filter" => $filter,
				"sort" => (array)$sort
			) + $this->createVars(), 
			true);
		return $o;
	}

	/**
	*	Retrieves the data from the table.
	*
	*	@param string	$table Table alias or name in a singular form.
	*	@param string	$view  View alias or name.
	*	@param string	$filter Optional string containing filter expression text to apply in addition to view's own filter.
	*
	*	@return array An array of records, each record is represented as associative name=>value array.
	*/
	public function SelectView(/*string*/$table, /*string*/$view, $filter = null) /* : array<name=>value>*/ 
	{
		return $this->SelectViewTop($table, $view, null, null, $filter);
	}

	/**
	*	Retrieves the data from the table.
	*
	*	@param string	$table Table alias or name in a singular form.
	*	@param string	$view  View alias or name.
	*	@param int		$skip	Number of records to skip prior to retrieval.
	*	@param int		$top	Number of records to retrieve.
	*	@param string	$filter Optional string containing filter expression text to apply in addition to view's own filter.
	*
	*	@return array An array of records, each record is represented as associative name=>value array.
	*/
	public function SelectViewTop(/*string*/$table, /*string*/$view, /*int*/$skip, /*int*/$top, $filter = null, /*string|array<string>*/$sort = null) /* : array<name=>value>*/ 
	{
		$o = $this->getJSON(
			self::urlPathEncode($table) . "/" . self::urlPathEncode($view) . "/select.json",
			array(
				"top" => $top ? $top : null,
				"skip" => $skip ? $skip : null,
				"filter" => $filter
			) + $this->createVars(), 
			true);
		return $o;
	}

	/**
	* Retrieves records given their internal IDs 
	*
	* @param	string		$table		The name of the table in a singular form
	* @param	array		$columns	An array of column names to retrieve
	* @param	array		$ids		An array of internal record IDs
	* @return	array		An array of associative arrays where array's key is a column name
	*/
	public function Retrieve(/*string*/$table, array/*<string>*/$columns, /*int|array<int>*/$ids) /* : array<name=>value> */ 
	{
		$o = $this->getJSON(self::urlPathEncode($table) . "/retrieve.json",
			array(
				"column" => $columns, 
				"id" => (array)$ids
			) + $this->createVars(),
			true);
		return $o;
	}

	/**
	* Retrieves records given values of the key column
	*
	* @param	string		$table		The name of the table in a singular form
	* @param	array		$columns	An array of column names to retrieve
	* @param	array		$keys		An array of key values
	* @return	array		An array of associative arrays where array's key is a column name
	*/
	public function RetrieveByKey(/*string*/$table, array/*<string>*/ $columns, /*any|array<any>*/$keys) /* : array<name=>value> */ 
	{
		$o = $this->getJSON(self::urlPathEncode($table) . "/retrieve.json",
			array(
				"column" => $columns, 
				"key" => (array)$keys
			) + $this->createVars(),
			true);
		return $o;
	}

	public function Create(/*string*/$table, array/*<name=>value>*/ $data, /*bool*/$no_workflow = null) // : array<stdClass>
	{
		return $this->doUpsert("create", $table, $data, null, $no_workflow);
	}

	public function Update(/*string*/$table, array/*<name=>value>*/ $data, /*string*/$match = null, /*bool*/$no_workflow = null) // : array<stdClass>
	{
		return $this->doUpsert("update", $table, $data, $match, $no_workflow);
	}

	public function Upsert(/*string*/$table, array/*<name=>value>*/ $data, /*string*/$match = null, /*bool*/$no_workflow = null) // : array<stdClass>
	{
		return $this->doUpsert("upsert", $table, $data, $match, $no_workflow);
	}

	public function Delete(/*string*/$table, /*int|array<int>*/$ids, /*bool*/$no_workflow = null) // : array<stdClass>
	{
		if($no_workflow == null)
			$no_workflow = $this->options["no-workflow"];
		$no_workflow = $no_workflow ? 0 : null;

		$o = $this->getJSON(self::urlPathEncode($table) . "/delete.json",
			array("id" => (array)$ids, "workflow" => $no_workflow) +
			$this->createVars());
		return $o;
	}

	public function DeleteByKey(/*string*/$table, /*any|array<any>*/$keys, /*string*/$match = null, /*bool*/$no_workflow = null) // : array<stdClass>
	{
		if($no_workflow == null)
			$no_workflow = $this->options["no-workflow"];
		$no_workflow = $no_workflow ? 0 : null;

		$o = $this->getJSON(self::urlPathEncode($table) . "/delete.json",
			array("key" => (array)$keys, "workflow" => $no_workflow) +
			$this->createVars());
		return $o;
	}

	/*
	* Retrieves the list of updated records
	*
	* @param	string	$table	Table alias or name in a singular form.
	* @param	string	$from	Optional start date
	* @param	string  $to		Optional end date
	*
	* @return	array	Array of stdClass
	*/
	public function Updated(/*string*/$table, $from = null, $to = null) // : array<stdClass>
	{
		$o = $this->getJSON(self::urlPathEncode($table) . "/updated.json",
			 array("from" => $from, "to" => $to));
		return $o;
	}

	/*
	* Retrieves the list of deleted records
	*
	* @param	string	$table	Table alias or name in a singular form.
	* @param	string	$from	Optional start date
	* @param	string  $to		Optional end date
	*
	* @return	array	Array of stdClass
	*/
	public function Deleted(/*string*/$table, $from = null, $to = null) // : array<stdClass>
	{
		$o = $this->getJSON(self::urlPathEncode($table) . "/deleted.json",
			 array("from" => $from, "to" => $to));
		return $o;
	}

	public function Attachments(/*string*/$table, /*string*/$column, /*int*/$id, /*int*/$revisions = null) // : array<stdClass>
	{
		$o = $this->getJSON(self::urlPathEncode($table) . "/" . self::urlPathEncode($column) . "/attachments.json",
			 array("id" => $id, "revisions" => $revisions) +
			 $this->createVars());
		return $o;
	}

	public function AttachmentsByKey(/*string*/$table, /*string*/$column, /*any*/$key, /*int*/$revisions = null) // : array<stdClass>
	{
		$o = $this->getJSON(self::urlPathEncode($table) . "/" . self::urlPathEncode($column) . "/attachments.json",
			 array("key" => $key, "revisions" => $revisions) +
			 $this->createVars());
		return $o;
	}

	public function Attachment(/*string*/$table, /*string*/$column, /*int*/$id, /*int|column's value|guid*/$revision = 0) // : TeamDesk\HttpContent
	{
		$guid = null;
		if($revision == null)
			return null;
		if(is_string($revision))
		{
			if(preg_match("/.+;\\d+;([0-9A-Fa-f]{8}(?:-[0-9A-Fa-f]{4}){3}-[0-9A-Fa-f]{12})/", $revision, $guid))
				$guid = $guid[1];
			else
				$guid = $revision;
			$revision = null;
		}
		else if($revision == 0)
		{
			$revision = null;
		}
		$content = $this->getContent("GET", self::urlPathEncode($table) . "/" . self::urlPathEncode($column) . "/attachment",
			 array("id" => $id, "guid" => $guid, "revision" => $revision) +
			 $this->createVars());
		return $content;
	}

	public function AttachmentByKey(/*string*/$table, /*string*/$column, $key, /*int|column's value|guid*/$revision = 0) // : TeamDesk\HttpContent
	{
		$guid = null;
		if($revision == null)
			return null;
		if(is_string($revision))
		{
			if(preg_match("/.+;\\d+;([0-9A-Fa-f]{8}(?:-[0-9A-Fa-f]{4}){3}-[0-9A-Fa-f]{12})/", $revision, $guid))
				$guid = $guid[1];
			else
				$guid = $revision;
			$revision = null;
		}
		else if($revision == 0)
		{
			$revision = null;
		}
		$content = $this->getContent("GET", self::urlPathEncode($table) . "/" . self::urlPathEncode($column) . "/attachment",
			 array("key" => $key, "guid" => $guid, "revision" => $revision) +
			 $this->createVars());
		return $content;
	}

	/**
	* Renders document
	*
	* @param	string		$table		The name of the table in a singular form
	* @param	array		$document	Document's name
	* @param	array		$id			An array internal record IDs
	* @return	TeamDesk\HttpContent	An instance of TeamDesk\HttpContent class
	*/
	public function Document(/*string*/$table, /*string*/$document, /*int|array<int>*/$ids) // : TeamDesk\HttpContent
	{
		$content = $this->getContent("GET", self::urlPathEncode($table) . "/" . self::urlPathEncode($document) . "/document",
			array("id" => $ids) +
			$this->createVars());
		return $content;
	}

	/**
	* Dumps last request and response if tracing is enabled
	*
	* @return	string	The dump of the last request and response
	*/
	public function dump() /* : string */
	{
		$result = "";
		if($this->options["trace"])
		{
			$result .= (string)$this->lastRequest;
			if($this->lastResponse)
			{
				$result .= "\r\n\r\n--------------------------------\r\n\r\n";
				$result .=  (string)$this->lastResponse;
			}
		}
		return $result;
	}

	public function getLastRequest() // : TeamDesk\HttpRequest
	{
		return $this->options["trace"] ? $this->lastRequest : null;
	}

	public function getLastResponse() // : TeamDesk\HttpResponse
	{
		return $this->options["trace"] ? $this->lastResponse : null;
	}

	private function doUpsert($method, $table, array $data, $match, $no_workflow)
	{
		if($no_workflow == null)
			$no_workflow = $this->options["no-workflow"];
		$no_workflow = $no_workflow ? 0 : null;

		$fileParts = array();
		for($i = 0; $i < count($data); $i++)
		{
			$row = $data[$i];
			if(is_array($row))
				$data[$i] = $row = (object)$row;
			else if(is_object($row))
				$data[$i] = $row = clone $object;
			else
				throw \Exception("Expected object or array at index $i");
			foreach($row as $n => $v)
			{
				if($v instanceof \DateTime)
				{
					$row->$n = $v->format(DATE_ATOM);
				}
				else if($v instanceof HttpContent)
				{
					$contentId = uniqid();
					$v->setHeader("Content-ID", $contentId);
					$fileParts[] = $v;
					$row->$n = "cid:$contentId";
				}
				else if(is_string($v))
				{
					$row->$n = mb_convert_encoding($v, "UTF-8");
				}
				else if(is_object($v) || is_array($v))
					throw \Exception("Expected primitive content at index $i, key => \"$n\"");
			}
		}
		$content = $dataPart = HttpContent::fromData(json_encode($data), "application/json;charset=UTF-8");
		if(count($fileParts))
		{
			$content = new MultiPartContent("related");
			$content->addPart($dataPart);
			foreach($fileParts as $filePart)
				$content->addPart($filePart);
		}

		$content = $this->getContent("POST", self::urlPathEncode($table) . "/$method.json", 
			array("match" => $match, "workflow" => $no_workflow) +
			$this->createVars(),
			$content);
		return self::parseJson($content);
	}

	private function createVars() /* : array<name=>value> */
	{
		$result = array();
		if(is_array($this->options["variables"]))
		{
			foreach($this->options["variables"] as $vname=>$vvalue)
				$result["var[$vname]"] = $vvalue;
		}
		return $result;
	}

	private static function parseJson(/*HttpContent*/$content, $use_arrays = false)
	{
		$contentType = HttpMessage::parseHeader($content->getHeader("Content-Type"));
		$data = $content->getData();
		if(isset($contentType["charset"]) && strcasecmp($contentType["charset"], "UTF-8") != 0)
			$data = mb_convert_encoding($data,  "UTF-8", $contentType["charset"]);
		$data = json_decode($data, $use_arrays);
		self::convertutf8($data);
		return $data;
	}

	private static function convertutf8(&$value)
	{
		if(is_object($value))
		{
			foreach($value as $k=>$v)
				 self::convertutf8($value->$k);
		}
		else if(is_array($value))
		{
			foreach($value as $k=>$v)
				 self::convertutf8($value[$k]);
		}
		else if(is_string($value))
		{
			$value = mb_convert_encoding($value, mb_internal_encoding(), "UTF-8");
		}
	}

	private function checkApiResult(/*HttpResponse*/$response)
	{
		if($response->statusCode >= 300)
		{
			if($response->content)
			{
				$contentType = HttpMessage::parseHeader($response->content->getHeader("Content-Type"));
				if($contentType && strcasecmp($contentType["type"], "application/json") == 0)
				{
					$error = self::parseJson($response->content);
					throw new RestApiException(
						$error->message,
						$error->error,
						isset($error->code) ? $error->code : 0,
						isset($error->source) ? $error->source : null);
				}
			}
			throw new RestApiException($response->statusText, $response->statusCode);
		}
	}

	private function getContent(/*string*/$method, /*string*/$url, array/*<name=>value>*/$params = null, /*TeamDesk\HttpContent*/$content = null) /* : TeamDesk\HttpContent */
	{
		$request = $this->client->createRequest($method, $this->options["url"] . $url . self::queryString($params), $content);
		if($this->options["trace"])
		{
			$this->lastRequest = $request;
			$this->lastResponse = null;
		}
		$response = $this->client->send($request);
		if($this->options["trace"])
			$this->lastResponse = $response;
		$this->checkApiResult($response);
		return $response->content;
	}

	private function getJSON(/*string*/$url, array/*<name=>value>*/$params = null, $use_arrays = false) /* : any */
	{
		$content = $this->getContent("GET", $url, $params);
		return self::parseJson($content, $use_arrays);
	}

	private static function queryString(/*array*/$data) /* : string */
	{
		if($data == null)
			return "";
		$result = array();
		foreach($data as $k => $v)
		{
			if(is_array($v))
			{
				for($i = 0; $i < count($v); $i++)
					$result[] = $k . "=" . self::urlEncode($v[$i]);
			}
			else if($v != null)
			{
				$result[] = $k . "=" . self::urlEncode($v);
			}
		}
		$result = count($result) ? "?" . implode($result, "&") : "";
		return $result;
	}

	private static function urlPathEncode(/*string*/$value) /* : string */
	{
		return self::urlEncode(strtr($value, array("%" => "%25",  "/" => "%2F",  "\\" => "%5C",  "?" => "%3F")));
	}

	private static function urlEncode(/*string*/$value) /* : string */
	{
		$value = rawurlencode(mb_convert_encoding($value, "UTF-8"));
		return $value;
	}

	private $options;// : array<name=>value>
	private $lastRequest; // : TeamDesk\HttpRequest
	private $lastResponse; // : TeamDesk\HttpResponse
}

class RestApiException extends \Exception
{
	public function __construct(/*string*/$message, /*int*/$error = 0, /*int*/$code = 0, /*string*/$source = null)
	{
		parent::__construct($message, $code);
		$this->error = $error;
		$this->source = $source;
	}

	/**
	* Retrieves API status code
	*
	* @return	int		API Status Code
	*/
	public function getError() /* : int */
	{
		return $error;
	}

	/**
	* Retrieves error's source
	*
	* @return	string	Error source
	*/
	public function getSource() /* : string */
	{
		return $source;
	}

	public function __toString() /* : string */
	{
		return ($this->source ? "\"$this->source\":  " : " ") . "{$this->getMessage()} ({$this->error}.{$this->getCode()})";
	}
	
	private $error;
	private $source;
}
?>