<?php namespace TeamDesk;

/* 
	*** NOT TESTED ***
*/

class_exists("\Memcached") or die("Memcached extension is not installed");

class MemCache implements IHttpCache
{
	public function __construct(array $servers, array $options = null)
	{
		$this->memcached = new \Memcached();
		$this->addServers($servers);
		if($options != null)
			$this->setOptions($options);
	}

	public function getResponse(/*string*/$url) // : HttpResponse
	{
		$response = $this->memcached->get($this->getKey($url));
		return $response ? $response : null;
	}
	
	public function setResponse(/*string*/$url, /*HttpResponse*/$response)
	{
		$this->memcached->set($this->getKey($url), $response);
	}

	protected function getKey($url) // : string
	{
		return "TeamDesk::MemCache::$url";
	}

	protected /*Memcached*/$memcached;
}