<?php
header("Content-Type: text/plain");
$url = "https://www.yellowpages.com.au/search/listings?clue=Mechanics&locationClue=7260&lat=&lon=&mappable=true&selectedViewMode=list";
if (hasParam("query") && hasParam("location"))
{
$query = urlDecode($_REQUEST["query"]);
$loc = urlDecode($_REQUEST["location"]);
$url = "https://www.yellowpages.com.au/search/listings?clue=".urlEncode($query)."&locationClue=".urlEncode($loc)."";
}



if(!file_exists("proxy.list") || hasParam("updateProxies"))
{
  updateProxies();
}

$stop = false;
$page = 1;
$result = "";
while(!$stop)
{
$outDat = "";
$outDat = curl($url."&pageNumber=".$page."&referredBy=UNKNOWN&eventType=pagination");
$key2 = "pageNumber=".($page+1);
$key = 'data-full-name="';

if (contains($key2,$outDat) && contains($key,$outDat))
{
  $result .= getList($outDat);
  addJob($job);
}
elseif(!contains($key2,$outDat) && contains($key,$outDat))
{
 $job = getList($outDat);
 $result .= $job;
 addJob($job);
 
 $stop = true;
}
else
{
  $stop = true;
}
$page++;
}


echo $result;
clearCookies();

function getList($outDat)
{

$outParts = explode('class="listing listing-search listing-data"',$outDat);
$do = false;
$result = "";
foreach($outParts as $part)
{
$key = 'data-full-name="';
 if ($do && contains($key,$part))
    {
       $data = extractData($part);
       
       $result .= 'Anything, '.html_entity_decode($data["name"]).', '.$data["location"].", ".$data["phone"]."\n";
    }
 $do = true;
}
return $result;
}




function addJob($job)
{
$fp = fopen('jobs.txt', 'a');
fwrite($fp, $job);
fclose($fp);
}

function curl($target)
{
$parse = parse_url($target);
$host = $parse['host'];
$agent = randomAgent();
$agent = "Mozilla/5.0 (iPad; CPU OS 8_3 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) CriOS/43.0.2357.61 Mobile/12F69 Safari/600.1.4";

$proxy = randomProxy();
$proxy = "212.102.53.83:3128";
$cookieFile = "cookies.list";
$server_output = "";
$ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $target);
  curl_setopt($ch, CURLOPT_POST, false);
  curl_setopt($ch, CURLOPT_REFERER, $host);
 curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_USERAGENT, $agent);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
  curl_setopt($ch, CURLOPT_COOKIEFILE,  $cookieFile);
  curl_setopt($ch, CURLOPT_COOKIESESSION, false);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  curl_setopt($ch, CURLOPT_PROXY, $proxy);
//  curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$outputData = curl_exec ($ch);
$error = curl_error($ch);
  curl_close ($ch);
  return $outputData.$error;
}


// REQUIRED FUNCTIONS

function extractData($html)
{
$name = 'data-full-name="';
$state = 'data-state="';
$suburb = 'data-suburb="';
$street = 'data-full-address="';
$post = 'data-postcode="';
$phone = 'href="tel:';

$res = array();
$res["name"] = getPart($html,$name);
$res["location"] = getPart($html,$suburb).' '.getPart($html,$state). ' '.getPart($html,$post);
$res["phone"] = getPart($html,$phone);
return $res;
}

function getPart($html,$key)
{
$in = $html;
if (contains($key,$in))
{
$index = strpos($in,$key)+strlen($key);
$end1 = strpos($in,'"',$index);
$end2 = strpos($in,'<',$index);
$end = ($end1 < $end2)? $end1:$end2;
$length = $end - $index;
$name = substr($in,$index,$length);
return $name;
}
return "";
}

function contains($search, $input)
{
  return (strpos($input,$search) !== false);
}

function clearCookies()
{
 $cookieFile = "cookies.list";
 file_put_contents($cookieFile, " ");
}

function randomProxy()
{
  $proxies = file('proxy.list');
  return trim($proxies[array_rand($proxies,1)]);
}

function updateProxies()
{
  $newProxies = file_get_contents("https://api.proxyscrape.com/v2/?request=getproxies&protocol=http&timeout=2000&country=all&ssl=yes&anonymity=all&simplified=true");
 if (strlen($newProxies) > 0)
   {
  file_put_contents("proxy.list",$newProxies);
  }
}

function randomAgent()
{
   $agents = file('agent.list');
   return trim($agents[array_rand($agents,1)]);
}
function hasParam($param) 
{
   return array_key_exists($param, $_REQUEST);
}

?>