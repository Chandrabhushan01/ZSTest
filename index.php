<?php

include_once "templates/base.php";
session_start();

require_once realpath(dirname(__FILE__) . '/../src/Google/autoload.php');
//require_once 'Google/Client.php';
//require_once 'Google/Service/Gmail.php'

 $client_id = '447034989084-2p3n3f104lmoljfsv41ra3inv014gave.apps.googleusercontent.com';
 $client_secret = 'D9-xvhsqhKjEbW9J6rVBLOwZ';
 $redirect_uri = 'http://localhost/gmaila/examples/new.php';
 $scope = 'https://www.googleapis.com/auth/gmail.readonly';

$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
//$client->setAccessType('offline');
//$client->setApprovalPrompt('force');
$client->addScope($scope);
//$client->setClassConfig('Google_IO_Curl', 'options',array(CURLOPT_CONNECTTIMEOUT => 60,CURLOPT_TIMEOUT => 60));

$service = new Google_Service_Gmail($client);

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

echo pageHeader("GMAIL ANALYSIS");
if (strpos($client_id, "googleusercontent") == false) {
  echo missingClientSecretsWarning();
  exit;
}
?>
<div class="box">
  <div class="request">
<?php 
//$messages = array();
function multiexplode ($delimiters,$string) {
    $ready = str_replace($delimiters, $delimiters[0], $string);
    $launch = explode($delimiters[0], $ready);
    return  $launch;
}

function listMessages($service, $userId) {
  $pageToken = NULL;
  $messages = array();
  $opt_param = array();
  
	  $date=date("Y-m-d");
	$dt= date("Y-m-d",strtotime("-3 Months"));
	$str="after:".$dt." before:".$date;
  do {
    try {
      if ($pageToken) {
        $opt_param['pageToken'] = $pageToken;
      }
	  //$messagesResponse = $service->users_messages->listUsersMessages('me',array('q'=>$str),$opt_param);
      $messagesResponse = $service->users_messages->listUsersMessages('me',$opt_param);
      if ($messagesResponse->getMessages()) {
        $messages = array_merge($messages, $messagesResponse->getMessages());
        $pageToken = $messagesResponse->getNextPageToken();
      }
    } catch (Exception $e) {
      print 'An error occurred: ' . $e->getMessage();
    }
  } while ($pageToken);
  return $messages;
}

if (isset($authUrl)) {
  echo "<a class='login' href='" . $authUrl . "'>Connect Me!</a>";
} else 
{
	$service = new Google_Service_Gmail($client);
	$messages=listMessages($service,'me');
   $con=count($messages);
   //echo $con.'<br/>';
        $ar=[];
		$cnt=0;
  
  for($q=0;$q<200;$q+=100)
   {
	   $messageList=array();
       for($m=$q,$x=0;$m<($q+100),$x<100;$m++,$x++)
	   {	
           $messageList[$x]=$messages[$m];
	   }
	//$messages= $service->users_messages->listUsersMessages('me', array('labelIds'=>array("IMPORTANT"),'maxResults' => 100,'q'=>$str),$opt_param);
	//$messageList = $messages->getMessages();
	$client->setUseBatch(true);
	$batch = new Google_Http_Batch($client);
		foreach($messageList as $msg_obj)
	   {
	       //echo $msg_obj->id.'<br/>';
			$request = $service->users_messages->get('me', $msg_obj->id,array('format' => 'metadata', 'metadataHeaders' => array('Date','To','From')));        
			$batch->add($request, "mail-".$msg_obj->id);
       }
	   //var_dump($batch);
        echo '<br/>';
		$bMess = $batch->execute();
		//var_dump($bMess);
		
		foreach($bMess as $bmn)
	    {	
			$headers = $bmn->getPayload()->getHeaders();
			$w=count($headers);
			//print $w.'<br/>';
			for($qq=0;$qq<$w;$qq++)
			{
				if($headers[$qq]['name']=="From")
				{
					$from=$headers[$qq]['value']; 
					$temp=multiexplode(array("<",">","\"",","),$from);
					foreach($temp as $te)
					{
						if(!(strpos($te,"@")===false))
						{
							$te=strtolower($te);
							//echo $te.'<br/>';
							$ar[$cnt]=$te;
							$cnt++;
						
						}
					}
				}
				else
				if($headers[$qq]['name']=="To")
				{
					$to=$headers[$qq]['value']; 
					$temp=multiexplode(array("<",">","\"",","),$to);
					foreach($temp as $te)
					{
						if(!(strpos($te,"@")===false))
						{
							$te=strtolower($te);
							//echo $te.'<br/>';
							$ar[$cnt]=$te;
							$cnt++;
						}
					}
				}else
				if($headers[$qq]['name']=="Cc")
				{
					$cc=$headers[$qq]['value']; 
					$temp=multiexplode(array("<",">","\"",","),$cc);
					foreach($temp as $te)
					{
						//echo "BCC===>".$bcc.'<br/>';
						if(!(strpos($te,"@")===false))
						{
							$te=strtolower($te);
							//echo $te.'<br/>';
								$ar[$cnt]=$te;
							$cnt++;
						
						}
					}
				}
				else
				if($headers[$qq]['name']=="Bcc")
				{
					$bcc=$headers[$q]['value'];
					//echo "BCC===>".$bcc.'<br/>';
					$temp=multiexplode(array("<",">","\"",","),$bcc);
					foreach($temp as $te)
					{
						if(!(strpos($te,"@")===false))
						{
							$te=strtolower($te);
							//echo $te.'<br/>';
								$ar[$cnt]=$te;
							$cnt++;
						
						}
					}
				}		
			}
		}
			
	}
	//var_dump($ar);
    $result=array_count_values($ar);
	//print_r(array_count_values($ar));
	//var_dump($result);
	arsort($result);
	$key=key($result);
	
	print "Email: ".$key."<br/>";
	print "Start date: ".date("d-M-Y",strtotime("-3 Months"))."<br/>";
	print "End date: ".date("d-M-Y")."<br/>";
	print "Email:         #Conv.<br/>";
	unset($result[$key]);
	foreach($result as $re=>$re_value)
	{
		echo $re."          ".$re_value.'<br/>';
	}
  echo <<<END
   
    <a class='logout' href='?logout'>Logout</a>
END;
}
?>
