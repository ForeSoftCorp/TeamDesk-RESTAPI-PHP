<?php namespace TeamDesk;

/*
	Object-oriented wrappers to cURL
*/

function_exists("curl_init") or die("CURL extension is not installed");

interface IHttpCache
{
	public function getResponse(/*string*/$url); // HttpResponse
	public function setResponse(/*string*/$url, /*HttpResponse*/$response);
}

class HttpContent
{
	public function __construct($contentType, $data)
	{
		$this->headers = array("Content-Type" => $contentType);
		$this->data = $data;
	}

	public function getHeaders()
	{
		return $this->headers;
	}

	public function getHeader($name)
	{
		return isset($this->headers[$name]) ? $this->headers[$name] : null;
	}

	public function setHeader($name, $value)
	{
		$this->headers[$name] = $value;
	}

	public function clearHeader($name)
	{
		unset($this->headers[$name]);
	}

	// Resource serialization

	public function read($handle)
	{
		if($this->readHeaders($handle))
			$this->readContent($handle);
	}

	public function readHeaders($handle)
	{
		while(($line = fgets($handle)) !== false)
		{
			$line = rtrim($line, "\r");
			if($line == "")
				return true;
			$colon = strpos($line, ":");
			if($colon == false)
				throw new \Exception("Header colon not found");
			$this->setHeader(substr($line, 0, $colon), trim(substr($line, $colon + 1)));
		}
		return false;
	}

	public function readContent($handle)
	{
		$this->data = stream_get_contents($handle);
	}

	public function write($handle)
	{
		$this->writeHeaders($handle);
		fwrite($handle, "\r\n");
		$this->writeContent($handle);
	}

	public function save($path)
	{
		$handle = fopen($path, "wb");
		if($handle === false)
			throw new \Exception("Failed to open file \"$path\"");
		$this->writeContent($handle);
		fclose($handle);
	}

	public function passthru($withHeaders = false)
	{
		$handle = fopen("php://output", "wb");
		if($handle === false)
			throw new \Exception("Can not open output");
		if($withHeaders)
		{
			foreach($this->headers as $hname=>$hvalue)
			{
				if(strcasecmp($hname, "Content-Length") != 0)
					header("$hname: $hvalue");
			}
		}
		$this->writeContent($handle);
		fclose($handle);
	}

	protected function writeHeaders($handle)
	{
		foreach($this->headers as $hname=>$hvalue)
			fwrite($handle, "$hname: $hvalue\r\n");
	}

	public function writeContent($handle)
	{
		fwrite($handle, $this->data);
	}

	public function getData()
	{
		return $this->data;
	}

	public function getAsString()
	{
		$contentType = HttpMessage::parseHeader($this->getHeader("Content-Type"));
		$charSet = isset($contentType["charset"]) ? $contentType["charset"] : "UTF-8";
		return mb_convert_encoding($this->data, mb_internal_encoding(), $charSet);
	}
	
	public function __toString()
	{
		$result = "";
		foreach($this->headers as $hname=>$hvalue)
			$result .= "$hname: $hvalue\r\n";
		$result .= "\r\n";
		$contentType = HttpMessage::parseHeader($this->getHeader("Content-Type"));
		$charSet = isset($contentType["charset"]) ? $contentType["charset"] : null;
		if($charSet) 
			$result .= mb_convert_encoding($this->data, mb_internal_encoding(), $charSet);
		else
			$result .= "{{binary data}}";
		return $result;
	}

	protected function setFileName($fileName)
	{
		if($fileName != null)
			$this->setHeader("Content-Disposition", "attachment;filename*=UTF-8''" . rawurlencode(mb_convert_encoding($fileName, "UTF-8")));
	}

	public static function fromData($data, $contentType = "application/octet-stream", $fileName = null)
	{
		$result = new HttpContent($contentType, $data);
		$result->setFileName($fileName);
		return $result;
	}
	
	public static function fromString($value, $mediaType, $charSet = "UTF-8")
	{
		return self::fromData(mb_convert_encoding($value, $charSet), "$mediaType;charset=$charSet");
	}

	public static function fromFile($path, $contentType = null)
	{
		return new FileContent($path, $contentType);
	}

	protected /*array*/$headers;
	protected /*mixed*/$data;
}

class FileContent extends HttpContent
{
	public function __construct($path, $contentType = null)
	{
		if(!$contentType)
		{
			if(!function_exists("mime_content_type"))
				throw new \Exception("Can not detect content type: mime_content_type does not exist");
			$contentType = mime_content_type($path);
		}
		parent::__construct($contentType, null);
		$this->path = $path;
		$this->setFileName(basename($path));
	}

	public function readContent($handle)
	{
		throw new \Exception("Not implemented");
	}

	public function writeContent($handle)
	{
		$input = fopen($this->path, "r");
		if($input === false)
			throw new \Exception("Failed to open \"$this->path\"");
		try
		{
			if(stream_copy_to_stream($input, $handle) == false)
				throw new \Exception("Failed to copy \"$this->path\"");
			fclose($input);
		}
		catch(\Exception $e)
		{
			fclose($input);
			throw $e;
		}
	}

	public function __toString()
	{
		return parent::__toString() . "{{File: $path}}";
	}

	protected /*string*/ $path;
}

class MultiPartContent extends HttpContent
{
	public function __construct($type, $boundary = null)
	{
		if(!$boundary)
			$boundary = uniqid("part-", true);
		parent::__construct("multipart/$type;boundary=$boundary", null);
		$this->boundary = $boundary;
	}

	public function addPart(/*HttpContent*/$part)
	{
		$this->parts[] = $part;
	}

	public function readContent($handle)
	{
		throw new \Exception("Not implemented");
	}

	public function writeContent($handle)
	{
		foreach($this->parts as $part)
		{
			fwrite($handle, "--$this->boundary\r\n");
			$part->write($handle);
			fwrite($handle, "\r\n");
		}
		fwrite($handle, "--$this->boundary--");
	}

	public function __toString()
	{
		$result = parent::__toString(); 
		foreach($this->parts as $part)
		{
			$result .= "--$this->boundary\r\n";
			$result .= (string)$part;
			$result .= "\r\n";
		}
		$result .= "--$this->boundary--";
	}
	
	protected /*string*/$boundary;
	protected /*array*/ $parts;
}

class HttpMessage
{
	public function __construct($content = null)
	{
		$this->headers = array();
		$this->content = $content;
	}

	public function getHeaders()
	{
		return $this->headers;
	}

	public function getHeader($name)
	{
		return isset($this->headers[$name]) ? $this->headers[$name] : null;
	}

	public function setHeader($name, $value)
	{
		$this->headers[$name] = $value;
	}

	public function clearHeader($name)
	{
		unset($this->headers[$name]);
	}

	public function hasContent()
	{
		return $this->content != null;
	}

	public function readHeaders($handle)
	{
		while(($line = fgets($handle)) !== false)
		{
			$line = rtrim($line, "\r");
			if($line == "")
				return true;
			$colon = strpos($line, ":");
			if($colon == false)
				throw new \Exception("Header colon not found");
			$this->setHeader(substr($line, 0, $colon), ltrim(substr($line, $colon + 1)));
		}
		return false;
	}

	public function write($handle)
	{
		foreach($this->headers as $hname=>$hvalue)
			fwrite($handle, "$hname: $hvalue\r\n");
		if($this->content)
			$this->content->write($handle);
	}

	public function __toString()
	{
		$result ="";
		foreach($this->headers as $hname=>$hvalue)
			$result .= "$hname: $hvalue\r\n";
		$result .= $this->content;
		return $result;
	}

	public static function parseHeader($header, $sep = ";")
	{
		$result = array("type" => null);
		if($header)
		{
			$header = explode($sep, $header);
			for($i = 0; $i < count($header); $i++)
			{
				$headerPart = trim($header[$i]);
				if($i == 0 && $sep == ";")
					$result["type"] = $headerPart;
				else
				{
					$eq = strpos($headerPart, "=");
					if($eq === false)
						$result[$headerPart] = true;
					else
						$result[substr($headerPart, 0, $eq)] = substr($headerPart, $eq + 1);
				}
			}
		}
		return $result;
	}
	
	public /*array*/ $headers;
	public /*HttpContent*/$content;
}

class HttpRequest extends HttpMessage
{
	public function __construct($method, $url, /*HttpContent*/$content = null)
	{
		parent::__construct($content);
		$this->url = $url;
		$this->method = $method;
	}

	public function __toString()
	{
		return "$this->method $this->url HTTP/1.1\r\n" . parent::__toString();
	}

	public /*string*/$url;
	public /*string*/$method;
}

class HttpResponse extends HttpMessage
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public $request = null;
	public $version = "1.1";
	public $statusCode = 204;
	public $statusText = "No Content";
	public $fromCache = false;
	
	public function read($handle, $readContent = true)
	{
		$contentHeaders = array();
		$firstLine = rtrim(fgets($handle), "\r");
		if($firstLine == false)
			throw new \Exception("Failed to read response");

		$matches = null;
		if(!preg_match("/HTTP\/(\\d\.\\d)\\s+(\\d{3})\\s+([^\\r\\n]*)/", $firstLine, $matches))
			throw new \Exception("Failed to read response");
		$this->version = $matches[1];
		$this->statusCode = intval($matches[2]);
		$this->statusText = $matches[3];

		while(($line = fgets($handle)) !== false)
		{
			$line = rtrim($line, "\r\n");
			if($line == "")
				break;
			$colon = strpos($line, ":");
			if($colon == false)
				throw new \Exception("Header colon not found");
			$hname = substr($line, 0, $colon);
			$hvalue = ltrim(substr($line, $colon + 1));
			if(strncasecmp($hname, "Content-", 8) == 0)
				$contentHeaders[$hname] = $hvalue;
			else
				$this->setHeader($hname, $hvalue);
		}
		if($line == "" && $readContent)
		{
			$this->content = new HttpContent("application/octet-stream", null);
			$this->content->readContent($handle);
			foreach($contentHeaders as $hname=>$hvalue)
				$this->content->setHeader($hname, $hvalue);
		}
		else
		{
			foreach($contentHeaders as $hname=>$hvalue)
				$this->setHeader($hname, $hvalue);
		}
	}

	public static function readString(/*string*/$data)
	{
		$handle = fopen("php://temp","r+b");
		if($handle === false)
			throw new \Exception("Failed to open memory stream");
		fwrite($handle, $data);
		rewind($handle);
		$result = self::fromResource($handle);
		fclose($handle);
		return $result;
	}

	public static function fromResource($handle)
	{
		$result = new HttpResponse();
		$result->read($handle);
		return $result;
	}

	public function isCacheable()
	{
		$cacheControl = HttpMessage::parseHeader($this->getHeader("Cache-Control"), ",");
		return isset($cacheControl["private"]) && isset($cacheControl["max-age"]) && ($this->getHeader("ETag") || $this->getHeader("Last-Modified"));
	}

	public function write($handle)
	{
		fwrite($handle, "HTTP/$this->version $this->statusCode $this->statusText\r\n");
		parent::write($handle);
	}

	public function writeString()
	{
		$handle = fopen("php://temp","r+b");
		if($handle === false)
			throw new \Exception("Failed to open memory stream");
		$this->write($handle);
		rewind($handle);
		$result = stream_get_contents($handle);
		fclose($handle);
		return $result;
	}

	public function __toString()
	{
		return "HTTP/$this->version $this->statusCode $this->statusText" . ($this->fromCache ? " (cached)" : "") . "\r\n" . parent::__toString();
	}
}

class HttpClient
{
	public function __construct(array $headers = array(), array $curl_opt = array())
	{
		$this->headers = $headers;
		$this->curl_opt = $curl_opt;
		$this->cacheProvider = null;
	}

	public /*HttpRequest*/function createRequest($method, $url, /*HttpContent*/$content = null)
	{
		$request = new HttpRequest($method, $url, $content);
		foreach($this->headers as $hname=>$hvalue)
			$request->setHeader($hname, $hvalue);
		return $request;
	}

	public /*HttpResponse*/function get($url)
	{
		return $this->send($this->createRequest("GET", $url));
	}

	public /*HttpResponse*/function post($url, /*HttpContent*/$content)
	{
		return $this->send($this->createRequest("POST", $url, $content));
	}

	public /*HttpResponse*/function send(/*HttpRequest*/$request)
	{
		$curl_opt = array(
			CURLOPT_URL => $request->url,
			CURLOPT_CUSTOMREQUEST => $request->method,
			CURLOPT_HEADER => 1,
			CURLINFO_HEADER_OUT => 1,
			CURLOPT_RETURNTRANSFER => 1,
		) + $this->curl_opt;

		$requestData = null;
		$cachedResponse = null;
		if($request->hasContent())
		{
			$requestData = fopen("php://temp","w+b");
			if($requestData === false)
				throw new \Exception("Failed to open memory stream");
			$request->content->writeContent($requestData);
			$requestLength = ftell($requestData);
			if(!rewind($requestData))
				throw new \Exception("Failed to rewind memory stream");

			$curl_opt[CURLOPT_UPLOAD] =  1; 
			$curl_opt[CURLOPT_INFILESIZE] = $requestLength;
			$curl_opt[CURLOPT_INFILE] = $requestData; 
		}
		else if($request->method == "GET" && $this->cacheProvider)
		{
			$cachedResponse = $this->cacheProvider->getResponse($request->url);
			if($cachedResponse != null)
			{
				$cachedResponse->fromCache = true;
				$cachedHeaders = $cachedResponse->getHeaders();
				$cacheControl = HttpMessage::parseHeader($cachedHeaders["Cache-Control"], ",");
				// see if content is expired
				if(isset($cacheControl["max-age"]))
				{
					$parsed = date_create_from_format("D, d M Y H:i:s O+", $cachedHeaders["Date"]);
					if($parsed === false)
						throw new \Exception("Unexpected Date header format");
					// Content is still fresh?
					if($parsed->getTimestamp() + intval($cacheControl["max-age"]) > time())
						return $cachedResponse;
				}
				if(isset($cacheControl["must-revalidate"]))
				{
					if(isset($cachedHeaders["ETag"]))
						$request->headers["If-None-Match"] = $cachedHeaders["ETag"];
					if(isset($cachedHeaders["Last-Modified"]))
						$request->headers["If-Modified-Since"] = $cachedHeaders["Last-Modified"];
				}
				else
				{
					$cachedResponse = null;
				}
			}
		}

		$headers = array();
		foreach($request->getHeaders() as $hname=>$hvalue)
			$headers[] = "$hname: $hvalue";
		if($request->hasContent())
			foreach($request->content->getHeaders() as $hname=>$hvalue)
				$headers[] = "$hname: $hvalue";

		$curl_opt[CURLOPT_HTTPHEADER] = $headers;

		$responseData = null;
		try
		{
			$curl = curl_init();
			if($curl === false)
				throw new \Exception("Failed to init cURL");
			curl_setopt_array($curl, $curl_opt);
			$responseText = curl_exec($curl);
			$curl_error = null;
			if($responseText === false)
				$curl_error = curl_error($curl);
			curl_close($curl);
			if($requestData)
			{
				fclose($requestData);
				$requestData = null;
			}
			if($curl_error)
				throw new \Exception("CURL: " . $curl_error);
			$responseData = fopen("php://temp","r+b");
			if($responseData === false)
				throw new \Exception("Failed to open memory stream");

			fwrite($responseData, $responseText);
			rewind($responseData);
			$response = HttpResponse::fromResource($responseData);
			fclose($responseData);
			$responseData = null;

			if($request->method == "GET" && $this->cacheProvider)
			{
				if($response->statusCode < 300 && $response->isCacheable())
				{
					$this->cacheProvider->setResponse($request->url, $response);
				}
				else if($response->statusCode === 304)
				{
					if(!$cachedResponse)
						throw new \Exception("Unexpected 304 response - no cached content");
					$response = clone $cachedResponse;
					$response->setHeader("Date", $response->getHeader("Date"));
				}
			}
			return $response;
		}
		catch(\Exception $e)
		{
			if($responseData)
				fclose($responseData);
			if($requestData)
				fclose($requestData);
			throw $e;
		}
	}

	private static function queryString(/*array*/$data)
	{
		if($data == null)
			return "";
		$result = array();
		foreach($data as $k => $v)
		{
			if(is_array($v))
			{
				for($i = 0; $i < count($v); $i++)
					$result[] = $k . "=" . rawurlencode(RestApi::to_utf8($v[$i]));
			}
			else if($v != null)
			{
				$result[] = $k . "=" . rawurlencode(RestApi::to_utf8($v));
			}
		}
		$result = count($result) ? "?" . implode($result, "&") : "";
		return $result;
	}

	private $curl_opt;
	public $headers;
	public $cacheProvider;
}
?>