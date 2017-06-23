<?php namespace TeamDesk;

function_exists("wincache_ucache_set") or die("WinCache extension is not installed");

class WinCache implements IHttpCache
{
	// keep results in shared memory for 1 hour max
	public function __construct($ttl = 3600)
	{
		$this->ttl = $ttl;
	}

	public function /*HttpResponse*/getResponse(/*string*/$url)
	{
		$success = false;
		$response = wincache_ucache_get($this->getKey($url), $success);
		return $success ? $response : null;
	}
	
	public function setResponse(/*string*/$url, /*HttpResponse*/$response)
	{
		wincache_ucache_set($this->getKey($url), $response, $this->ttl);
	}

	protected function getKey($url)
	{
		return "TeamDesk::WinCache::$url";
	}

	protected /*int*/$ttl;
}