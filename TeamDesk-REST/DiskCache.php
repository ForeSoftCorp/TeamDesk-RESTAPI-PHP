<?php namespace TeamDesk;

class DiskCache implements IHttpCache
{
	// %TEMP%/teamdesk-httpcache is the default and will be created if not exists
	public function __construct($tempDir = null)
	{
		if($tempDir == null)
		{
			$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "teamdesk-httpcache";
			if(!file_exists($tempDir))
				if(!mkdir($tempDir, 0600))
					throw new \Exception("Can not create temp dir \"$tempDir\"");
		}
		$this->tempDir = rtrim($tempDir, DIRECTORY_SEPARATOR);
	}
	
	public function /*string*/getResponse(/*string*/$url)
	{
		$result = null;
		$cacheFile = $this->getFileName($url);
		if(file_exists($cacheFile))
		{
			$handle = fopen($cacheFile, "rb");
			if($handle === false)
				throw new \Exception("Failed to read cache file \"$cacheFile\"");
			try
			{
				$result = HttpResponse::fromResource($handle);
				fclose($handle);
				return $result;
			}
			catch(\Exception $e)
			{
				fclose($handle);
				throw $e;
			}
		}
		return $result;
	}

	public function setResponse(/*string*/$url, /*HttpResponse*/$response)
	{
		$cacheFile = $this->getFileName($url);
		$handle = fopen($cacheFile, "w+b");
		if($handle === false)
			throw new \Exception("Failed to write cache file \"$cacheFile\"");
		try
		{
			$response->write($handle);
			fclose($handle);
		}
		catch(\Exception $e)
		{
			fclose($handle);
			throw $e;
		}
		return true;
	}

	protected function getFileName($url)
	{
		$hash = sha1($url);
		return $this->tempDir . DIRECTORY_SEPARATOR . $hash . ".cache";
	}

	protected $tempDir;
}
?>