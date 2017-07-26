<?php
class Utils
{
	public static $database = 21995;
	public static $user = "test@test.com";
	public static $password = "pwd";
	/* 
	 * By default, SSL verification is turned on, but old PHP versions 
	 * have root certificate chain out of date and likely 
	 * fail with "local certificate error".
	 *
	 * You can override SSL verification behavior by either:
	 * 1. Turning it off using FALSE (not recommended)
	 * 2. Providing path to the file that contains up-to-date certificate chain.
	 *    The file can be obtained, say from CURL website
	 *    https://curl.haxx.se/docs/caextract.html
	 */ 
	// public static $sslVerification = true;
	public static $sslVerification = "./cacert-2017-06-07.pem";
	
	public static function dumpTable(array $ds)
	{
		if(count($ds) == 0)
		{
			echo "<p><i>No data</i></p>";
			return;
		}

		$firstDataRow = 0;
		if(isset($ds[0]["@row.type"]))
			$firstDataRow++;

		$cols = array();

		echo "<table border=1>";
		echo "<thead>";
		echo "<tr>";
		foreach($ds[$firstDataRow] as $n=>$c)
		{
			if(strncmp($n, "@row.", 5) == 0)
				continue;
			echo "<th>" . htmlentities($n) . "</th>";
			$cols[$n] = true;
		}
		echo "</tr>";
		echo "</thead>";
		if($firstDataRow != 0)
		{
			echo "<tfoot>";
			echo "<tr>";
			foreach($cols as $n=>$v)
			{
				$v = "";
				if(isset($ds[0][$n]))
					$v = $ds[0][$n];
				self::dumpCell($v);
			}
			echo "</tr>";
			echo "</tfoot>";
		}
		echo "<tbody>";
		for($i = $firstDataRow; $i < count($ds); $i++)
		{
			$r = $ds[$i];
			echo "<tr>";
			foreach($r as $n=>$v)
			{
				if(strncmp($n, "@row.", 5) == 0)
					continue;
				self::dumpCell($v);
			}
			echo "</tr>";
		}
		echo "</tbody>";
		echo "</table>";
	}

	public static function dumpCell($v)
	{
		echo "<td>";
		if($v === null)
			$v = "";
		else if($v === true)
			$v = "Yes";
		else if($v === false)
			$v = "No";
		else if(is_numeric($v))
			$v = number_format($v);
		else
		{
			$d = date_create_from_format(DateTime::ATOM, $v);
			if($d !== false) {
				if(substr($v, -15) === "T00:00:00+00:00")
					$v = $d->format("m/d/Y");
				else if(substr($v, 0, 11) === "0001-01-01T")
					$v = $d->format("H:i:s");
				else
					$v = $d->format("m/d/Y H:i:s e");
			}
			else
			{
				$v = htmlentities(strval($v));
			}
		}
		echo $v;
		echo "</td>";	
	}
}
?>