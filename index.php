<?php
include_once "templates/base.php";
session_start();

require_once realpath(dirname(__FILE__) . '/src/Google/autoload.php');

$client_id = '555610710963-8b01jgrfg6gbvfapd1poe992t58a8fq2.apps.googleusercontent.com';
$client_secret = 'WmIYft7KT4yy_povjPZaXFTS';
$redirect_uri = 'http://www.zscbg.96.lt/zstest/';
$scope = 'https://www.googleapis.com/auth/gmail.readonly';

$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->addScope($scope);

$service = new Google_Service_Gmail($client);

function multiexplode ($delimiters,$string) {
    $ready = str_replace($delimiters, $delimiters[0], $string);
    $launch = explode($delimiters[0], $ready);
    return  $launch;
}
if (isset($_REQUEST['logout'])) {
    unset($_SESSION['access_token']);
    header('Location: https://www.google.com/accounts/Logout?continue=https://appengine.google.com/_ah/logout?continue=http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
}

if (isset($_GET['code'])) {
  $client->authenticate($_GET['code']);
  $_SESSION['access_token'] = $client->getAccessToken();
  $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
}

if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
  $client->setAccessToken($_SESSION['access_token']);
} else {
  $authUrl = $client->createAuthUrl();
}

if ($client->getAccessToken() && isset($_GET['url'])) {
  $url = new Google_Service_Urlshortener_Url();
  $url->longUrl = $_GET['url'];
  $short = $service->url->insert($url);
  $_SESSION['access_token'] = $client->getAccessToken();
}

echo pageHeader("GMail Analysis");
if (strpos($client_id, "googleusercontent") == false) {
  echo missingClientSecretsWarning();
  exit;
}
?>
<div class="box">
  <div class="request">
<?php 
if (isset($authUrl)) {
  echo "<a class='login' href='" . $authUrl . "'>Connect Me!</a>";
} else {
$service = new Google_Service_Gmail($client);

$pageToken = NULL;
  $messages = [];
  $opt_param = [];
  do {
      if ($pageToken) {
        $opt_param['pageToken'] = $pageToken;
      }
            $optParams['labelIds'] = 'INBOX , SENT'; 
          
$messagesResponse = $service->users_messages->listUsersMessages('me', $opt_param);
      if ($messagesResponse->getMessages()) {
        $messages = array_merge($messages, $messagesResponse->getMessages());
}

	$ms=end($messages);

		$mId=$ms->getId();

                $optParamsGet = [];
                $optParamsGet['format'] = 'full'; 
                $message = $service->users_messages->get('me',$mId,$optParamsGet);
                $headers = $message->getPayload()->getHeaders();

	               
	for($q=0;$q<25;$q++)
	{
		if(isset($headers[$q]))
		{
			if($headers[$q]['name']=="Date")
			$date=$headers[$q]['value']; 
		}

	} 
	
	$strtotime = strtotime($date);
	$date=date("Y-m-d", $strtotime);
	$dt= date("Y-m-d",strtotime("-3 Months"));
	
	if($date > $dt)
	        $pageToken = $messagesResponse->getNextPageToken();
     	else
		$pageToken = NULL;
    
  } while ($pageToken);
	$cnt=0;
	$ar=[];
	
	for($qq=0;$qq<15;$qq++)
	{
		$mId=$messages[$qq]->getId();
		
                $optParamsGet = [];
                $optParamsGet['format'] = 'full'; 
                $message = $service->users_messages->get('me',$mId,$optParamsGet);
                $headers = $message->getPayload()->getHeaders();

	               
		for($q=0;$q<25;$q++)
		{
			if(isset($headers[$q]))
			{
				if($headers[$q]['name']=="To")
				{
					$to=$headers[$q]['value']; 
					$temp=multiexplode(array("<",">","\""),$to);
					foreach($temp as $te)
					{
						if(!(strpos($te,"@")===false))
						{
							$te=strtolower($te);
						//	if($te!="
							$ar[$cnt]=$te;
							$cnt++;
						}
					}
				}
				else
				if($headers[$q]['name']=="From")
				{
					$from=$headers[$q]['value']; 
					$temp=multiexplode(array("<",">","\""),$from);
					foreach($temp as $te)
					{
						if(!(strpos($te,"@")===false))
						{
							$te=strtolower($te);
							$ar[$cnt]=$te;
							$cnt++;
						
						}
					}
				}
				else
				if($headers[$q]['name']=="Cc")
				{
					$cc=$headers[$q]['value']; 
					$temp=multiexplode(array("<",">","\""),$cc);
					foreach($temp as $te)
					{
						if(!(strpos($te,"@")===false))
						{
							$te=strtolower($te);
								$ar[$cnt]=$te;
							$cnt++;
						
						}
					}
				}
				else
				if($headers[$q]['name']=="Bcc")
				{
					$bcc=$headers[$q]['value']; 
					$temp=multiexplode(array("<",">","\""),$bcc);
					foreach($temp as $te)
					{
						if(!(strpos($te,"@")===false))
						{
							$te=strtolower($te);
								$ar[$cnt]=$te;
							$cnt++;
						
						}
					}
				}
			}

		} 
	}
	$pn=[];
	$ct=0;
	sort($ar);
	$pn[$ct]=$ar[0];
	$pv=[];
	$pc[0]=0;	
	for($q=0;$q<$cnt;$q++)
	{
		if(strcmp($ar[$q],$pn[$ct])!=0)
		{
			$ct++;
			$pn[$ct]=$ar[$q];
			$pc[$ct]=0;
		}
		if(strcmp($ar[$q],$pn[$ct])==0)
			$pc[$ct]++;
	}
	for($q=0;$q<=$ct;$q++)
		for($qq=$q+1;$qq<=$ct;$qq++)
		{
			if($pc[$q]<$pc[$qq])
			{
				$cb=$pc[$q];
				$pc[$q]=$pc[$qq];
				$pc[$qq]=$cb;
				$cb=$pn[$q];
				$pn[$q]=$pn[$qq];
				$pn[$qq]=$cb;
			}	
		}
	print "Email: ".$pn[0]."<br/>";
	print "Start date: ".date("d-M-Y",strtotime("-3 Months"))."<br/>";
	print "End date: ".date("d-M-Y")."<br/>";
	print "Email:     #Conv.<br/>";
	

	for($q=1;$q<=$ct;$q++)
	{
		print $pn[$q]." ".$pc[$q]."<br/>";	
	}

	  $client->setUseBatch(true);
	  $batch = new Google_Http_Batch($client);
	
	  foreach ($messages as $message) {
	  	$batch->add($service->users_messages->get('me', $message->id,['format'=>'metadata']),$message->id);
//		print 'Message with ID: ' . $message->getId() . '<br/>';
	  }
	
	//  $bMess=$batch->execute();
      //    var_dump($bMess);
	
/*	foreach($bMess as $bmn)
	  {

		$messageId = $bmn->id;
		$headers = $bmn->payload->headers;
		for($q=0;$q<25;$q++)
		{
			if(isset($headers[$q]))
			{
				if($headers[$q]['name']=="From")
				htmlentities($headers[$q]['value']); 
		
				if($headers[$q]['name']=="To")
				htmlentities($headers[$q]['value']);

				if($headers[$q]['name']=="Bcc")
				print $headers[$q]['value'];		
			
				if($headers[$q]['name']=="Cc")
				print $headers[$q]['value'];

				if($headers[$q]['name']=="Date")
				print $headers[$q]['value'].'<br/>';			
			}
		}
	   }

*/
  echo <<<END
    
    <a class='logout' href='?logout'>Logout</a>
END;
}
?>
 
  </div>
</div>
