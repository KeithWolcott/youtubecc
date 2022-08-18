<?php
$success = true;
$print = false;
$str = "";
function getstarttime($start)
{
	if ($start >= 3600)
	{
		// first, get the hours
		$hours = floor($start/3600);
		if ($hours < 10)
			$hours = "0$hours";
		// next, get the minutes
		$secondsremaining = $start - (3600 * floor($start/3600));
		$start2 = floor($secondsremaining/60);
		if ($start2 < 10)
			$start2 = "0$start2";
		elseif ($start2 >= 60)
		{
			$extra1 = floor($start2/60);
			$extra2 = $start2 % 60;
			if ($extra1 < 10)
				$extra1 = "0$extra2";
			$start2 = "$extra1:$extra2";
		}
		$start3 = $secondsremaining - (floor($secondsremaining/60) * 60);
		if ($start3 < 10)
			$start3 = "0$start3";
		elseif ($start3 >= 60)
		{
			$extra1 = floor($start3/60);
			$extra2 = $start3 % 60;
			if ($extra1 < 10)
				$extra1 = "0$extra2";
			$start3 = "$extra1:$extra2";
		}
		return "$hours:$start2:$start3";
	}
	elseif ($start >= 60)
	{
		$start2 = floor($start/60);
		if ($start2 < 10)
			$start2 = "0$start2";
		elseif ($start2 >= 60)
		{
			$extra1 = floor($start2/60);
			$extra2 = $start2 % 60;
			if ($extra1 < 10)
				$extra1 = "0$extra2";
			$start2 = "$extra1:$extra2";
		}
		$start3 = $start - (floor($start/60) * 60);
		if ($start3 < 10)
			$start3 = "0$start3";
		elseif ($start3 >= 60)
		{
			$extra1 = floor($start3/60);
			$extra2 = $start3 % 60;
			if ($extra1 < 10)
				$extra1 = "0$extra2";
			$start3 = "$extra1:$extra2";
		}
		return "$start2:$start3";
	}
	else
	{
		$start3 = $start;
		if ($start3 < 10)
			$start3 = "0$start3";
		return "00:$start3";
	}
}
function gettime($time)
{
	$ex = explode(":",$time);
	if (count($ex) < 3)
	{
		$ex2 = explode(".",$ex[1]);
		if (count($ex2) < 2)
			array_push($ex2,"000");
		else
		{
			if (strlen($ex2[1]) < 3)
			{
				while (strlen($ex2[1]) < 3)
					$ex2[1] .= "0";
			}
			elseif (strlen($ex2[1] > 3))
				$ex2[1] = substr($ex2[1],0,3);
		}
		if ($ex[0] == "00")
			return "00:00:" . str_replace(".",",",implode(".",$ex2));
		else
			return "00:" . $ex[0] . ":" . str_replace(".",",",implode(".",$ex2));
	}
	else
	{
		$ex2 = explode(".",$ex[2]);
		if (count($ex2) < 2)
			array_push($ex2,"000");
		else
		{
			if (strlen($ex2[1]) < 3)
			{
				while (strlen($ex2[1]) < 3)
					$ex2[1] .= "0";
			}
			elseif (strlen($ex2[1] > 3))
				$ex2[1] = substr($ex2[1],0,3);
		}
		return $ex[0] . ":" . $ex[1] . ":" . str_replace(".",",",implode(".",$ex2));
	}
}
if ($_POST && isset($_POST["url"]) && isset($_POST["name"]))
{
	$onlytext = false;
	if (isset($_POST["onlytext"]))
		$onlytext = true;
	$f = file_get_contents($_POST["url"]);
	$i01 = strpos($f,"<title>");
	$i02 = strpos($f," - YouTube</title>",$i01);
	$titl = substr($f,$i01+7,$i02-7-$i01);
	$titl = str_replace("&#39;","'",$titl);
	$notallowed = array("\\","/",":","*","?","<",">","|","&quot;");
	foreach ($notallowed as $ite)
	{
		if (strpos($titl,$ite)!==false)
			$titl = str_replace($ite,"",$titl);
	}
	$exx = explode('{"captionTracks":[{"baseUrl":"', $f);
	if (count($exx)==1) {
		$success = false;
	}
	else
	{
		$i0 = strpos($exx[1],"\",\"");
		$url = substr($exx[1],0,$i0);
		$url = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
			return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UTF-16BE');
		}, $url);
		$url = str_replace("\\","",$url);
		$f2 = file_get_contents($url);
		$ex = explode("<text",$f2);
		for($i=1;$i<count($ex);$i++)
		{
			if (!$onlytext)
				$str .= "$i\r\n";
			$i1 = strpos($ex[$i],'start="');
			$i2 = strpos($ex[$i],'" dur="');
			$i3 = strpos($ex[$i],'">');
			$i4 = strpos($ex[$i],"</text>");
			$start = getstarttime(substr($ex[$i],$i1+7,$i2-7-$i1));
			$duration = substr($ex[$i],$i2+7,$i3-7-$i2);
			$txt = htmlspecialchars_decode(htmlspecialchars_decode(substr($ex[$i],$i3+2,$i4-2-$i3)), ENT_QUOTES);
			if ($i < (count($ex)-1))
			{
				$x = $i + 1;
				$i1 = strpos($ex[$x],'start="');
				$i2 = strpos($ex[$x],'" dur="');
				$start2 = getstarttime(substr($ex[$x],$i1+7,$i2-7-$i1));
			}
			else
			{
				$start2 = getstarttime(substr($ex[$i],$i1+7,$i2-7-$i1)+$duration);
			}
			if ($onlytext)
				$str .= "$txt\r\n";
			else
				$str .= gettime($start) . " --> " . gettime($start2) . "\r\n$txt\r\n\r\n";
		}
	}
	if ($success)
	{
		if (isset($_POST["dontdownload"]) && $_POST["dontdownload"]=="yes")
			$print = true;
		else
		{
			file_put_contents("{$_POST["name"]}.srt", $str);
			header("Location: download.php?f={$_POST["name"]}.srt");
		}
	}
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Youtube Automatic Closed Captioning Downloader</title>
<style>
body
{
font-family:sans-serif;
}
textarea
{
	border:0;
	width:99%;
	height:500px;
	font-size:1em;
	font-family:sans-serif;
}
</style>
</head>
<body>
<h2>Youtube Automatic Closed Captioning Downloader</h2>
<?php
if ($_POST && !$success)
	echo "<p>No captions available.</p><hr>";
?>
<form method="post">
<p>URL: <input type="url" name="url" required onclick="this.select();" /></p>
<p>Name of srt file: <input type="text" name="name" value="captions"/>.srt</p>
<p><input type="checkbox" name="onlytext" value="yes" id="onlytext" <?php echo (($_POST && isset($_POST["onlytext"]) && $_POST["onlytext"] == "yes") ? "checked " : ""); ?>/><label for="onlytext">Only Text</label></p>
<p><input type="checkbox" name="dontdownload" value="yes" id="dontdownload" <?php echo (($_POST && isset($_POST["dontdownload"]) && $_POST["dontdownload"] == "yes") ? "checked " : ""); ?>/><label for="dontdownload">Echo Text instead of Download</label></p>
<input type="submit" value="Generate and Download" /></form>
<?php
if ($_POST && $print && strlen($str) > 0)
{
	echo "<br><textarea readonly onclick=\"this.select();\">" . htmlspecialchars($str) . "</textarea>";
}
?>
</body>
</html>