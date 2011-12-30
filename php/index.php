<?php
/*  Copyright 2010-2011 SBA Research gGmbH

     This file is part of SocialSnapshot.

    SocialSnapshot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    SocialSnapshot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with SocialSnapshot.  If not, see <http://www.gnu.org/licenses/>.*/

// This file is partially based on example FB Graph code by Facebook, Inc.,
// licensed under the Apache License, Version 2.0.
// Original code at https://github.com/facebook/php-sdk/blob/master/examples/example.php
require_once 'PriorityQueue.php';
require 'settings.inc.php';
require 'facebook.php';
require 'APIObject.php';
require 'Taggable.php';
require 'Profile.php';
require 'Album.php';
require 'Comment.php';
require 'Connection.php';
require 'Event.php';
require 'Group.php';
require 'Note.php';
require 'Page.php';
require 'Photo.php';
require 'Picture.php';
require 'Post.php';
require 'Status.php';
require 'User.php';
require 'Video.php';
require 'Link.php';
require 'Message.php';

//Header for utf-8 support and caching disabled
header("Content-type: text/html; charset=utf-8");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: -1");

// Create our Application instance.
$facebook = new Facebook(array(
'appId' => $_appId,
'secret' => $_secret,	
'cookie' => $_cookie,
));

//Flush the output buffer
function flushOutput(){
	//Flush the output
	ob_flush();
	flush();
}

function createDir($dir){
//In case out directory does not exist create it.
//Also create empty index.html to ommit dir listings
	if(!is_dir($dir)){
		mkdir($dir);
		touch($dir . 'index.html');
	}	
}


// Create the output directories for application, in case they do not exist yet.
createDir("../tmp/");
createDir("../logs/");
createDir("../tarballs/");
createDir("../downloads/");
createDir("../images/");
createDir("../tokens/");


// Stores an access token in the corresponding file
function storeAccessToken($uid, $token)
{
	if(!preg_match('/^[a-zA-Z0-9]+$/', $uid))
		return null; // Path traversal? In MY code?
	$tokenfile = fopen('../tokens/' . $uid, 'w');
	fwrite($tokenfile, $token);
	fclose($tokenfile);	
}

// Returns the access token for the given UID or null if it does not exist
function retrieveAccessToken($uid)
{
	if(!preg_match('/^[a-zA-Z0-9]+$/', $uid))
		return null; // Validation failed, silent fail
	$fname = '../tokens/' . $uid;
	if(is_file($fname))
		return file_get_contents($fname);
	else
		return null;
}
//Function that fetches friends are friends information from Facebook user
function readCluster($facebook){
//Get friends cluster information
$friends = $facebook->api('/me/friends');
$friendslen = count($friends['data']);
$params = array();
$filenames = array();
for($index = 0; $index < ($friendslen - 1); $index++)
{
$uids1 = array();
$uids2 = array();
for($repeat = 0; $repeat < ($friendslen - $index - 1); $repeat++){
$uids1[$repeat] = $friends['data'][$index]['id'];
$uids2[$repeat] = $friends['data'][$index + $repeat + 1]['id'];
}
$uids1str = implode(",",$uids1);
$uids2str = implode(",",$uids2);
$params[] = array('method' => 'friends.areFriends', 'uids1' => $uids1str, 'uids2' => $uids2str);
$filenames[] = "../tmp/" . $facebook->getUnique() . '/' . $friends['data'][$index]['id'] . '~cluster.request';
}
	// Execute the call to retrieve the clusters
$failsafe = 100; // Just to prevent infinite loops in case the REST API goes down or something...
do
{
$failed = $facebook->apiQueue($params, $filenames);
$facebook->log("[APIQUEUE] There were " . count($failed) . " failed requests to the REST API. (will retry " . $failsafe . " times)");
	} while(count($failed) > 0 && ($failsafe--) > 0);
}
	
//readNode the function that does the actual fetching of Facebook requests. Once finished it creates a tarball.
function readNode($facebook,$parent,$sendid)
{
	//echo "readNode()<br />";
	$connections = $parent->getConnections();
	echo "<pre>";
	while(Facebook::getQueue()->count()>0)
	{
		$facebook->api_multi('GET',Connection::createEmptyArray(), array("Connection", "recursor"));
		$facebook->log(date("G:i:s D M j T Y") . " Returned into readNode(), " . Facebook::getQueue()->count() . " elements left, let's get back in there! Highest Level: " . Facebook::getQueue()->highestLevel());
		echo date("G:i:s D M j T Y") . " " . Facebook::getQueue()->count() . " elements left. Highest Level: " . Facebook::getQueue()->highestLevel() ."<br/>";
		if(Facebook::getQueue()->highestLevel()<3 || Facebook::getQueue()->count()<3 ){
			$facebook->log("Finished. highestLevel: " . Facebook::getQueue()->highestLevel());
		$facebook->log("Finished " . date("G:i:s D M j T Y"));
		echo "</pre><h4>Finished " . date("G:i:s D M j T Y") . "</h4>";
			$remaining = print_r(Facebook::getQueue(),true);
			$facebook->log($remaining);
			break;
		}
		flushOutput();
	}
        // Compress the gathered socialsnapshot		
	// Tar and compress the logfile and folder
	// Check if the token is valid (must not contain anything but alphanumeric plus _) and if the folder and logs for this run really exist
	if(0!=preg_match("/[^\w]/", $sendid) || !file_exists("../tmp/folder" . $sendid) || !file_exists("../tmp/log" . $sendid))
	{
		// Die otherwise
		die("Compression Failed: Could not find according socialsnapshot and log.");
	}
	else {
		exec("cd ../tmp && tar -hcjf ../tarballs/" . $sendid . ".tar.bz2 log" .  $sendid . " folder" . $sendid . " > /dev/null");
		exec("touch ../tmp/" . $sendid . ".finished > /dev/null");
		exec("rm -r ../tmp/logsnapshot" .$sendid . " ../tmp/folder" . $sendid ." > /dev/null");
		exec("rm -r ../tmp/" . $facebook->getUnique() ." > /dev/null");
	}
	//If optional analyse script is available, run it 
	$analysescript = "/opt/FBSnapshotLoader/scripts/analysesnapshot.sh";
	if(file_exists($analysescript)){
		$installpath = realpath('../');
		$snapshotfile = realpath('../tarballs/'.$sendid.'.tar.bz2');
		$downloadurl = 'https://'.$_SERVER["HTTP_HOST"].'/SocialSnapshot/downloads';
		$analysecommand = 'LANG=en_US.utf-8; '.$analysescript.' '.$snapshotfile.' '.$downloadurl.' '.$installpath .' > /dev/null 2>&1 &';
		//echo $analysecommand;
		exec($analysecommand);
	}
}

// If we have a UID set, we'll override the access token :)

if(isset($_GET['uid']))
{
	$token = retrieveAccessToken($_GET['uid']);
	if($token)
	{
		$facebook->setAccessToken($token);
		echo "<i>Successfully retrieved offline access token for UID " . htmlentities($_GET['uid']) . ".</i><br />";
	}
	else
		echo "<b>Warning: You tried to use a stored access token which does not exist.</b><br />";	
}

// We may or may not have this data based on a $_GET or $_COOKIE based session.
//
// If we get a session here, it means we found a correctly signed session using
// the Application Secret only Facebook and the Application know. We dont know
// if it is still valid until we make an API call using the session. A session
// can become invalid if it has already expired (should not be getting the
// session back in this case) or if the user logged out of Facebook.
$session = $facebook->getSession();

$me = null;
// Session based API call.
if ($session || $token) {
  try {
    $uid = $session ? $facebook->getUser() : $_GET['uid'];
    $me = $facebook->api('/me');
  } catch (FacebookApiException $e) {
    error_log($e);
  }
}


// login or logout url will be needed depending on current user state.
if ($me) {  
  // Store access token, since it is obviously correct.
  storeAccessToken($uid, $facebook->getAccessToken());
  $logoutUrl = $facebook->getLogoutUrl();
} else {
  $loginUrl = $facebook->getLoginUrl(array('req_perms' => 'email,read_insights,read_stream,read_mailbox,user_about_me,user_activities,user_birthday,user_education_history,user_events,user_groups,user_hometown,user_interests,user_likes,user_location,user_notes,user_online_presence,user_photo_video_tags,user_photos,user_relationships,user_religion_politics,user_status,user_videos,user_website,user_work_history,read_friendlists,read_requests,friends_about_me,friends_activities,friends_birthday,friends_education_history,friends_events,friends_groups,friends_hometown,friends_interests,friends_likes,friends_location,friends_notes,friends_online_presence,friends_photo_video_tags,friends_photos,friends_relationships,friends_religion_politics,friends_status,friends_videos,friends_website,friends_work_history,offline_access'));
 #$loginUrl = $facebook->getLoginUrl(array('req_perms' => 'read_friendlists,friends_activities,friends_groups,friends_interests,friends_likes'));
}

//Lots of memory
ini_set('memory_limit', '512M');
//Set timeout to 1h 
set_time_limit(3600);
?>
<!doctype html>
<html>
<head>
<title>SocialSnapshot Facebook App</title>
<style>
body {
font-family: 'Lucida Grande', Verdana, Arial, sans-serif;
}
h1 a {
text-decoration: none;
color: #3b5998;
}
h1 a:hover {
text-decoration: underline;
}
a.friend {font-size:10px;}
</style>
</head>
<body>
<?php if ($me): ?>
<a href="<?php echo $logoutUrl; ?>">
<img src="http://static.ak.fbcdn.net/rsrc.php/z2Y31/hash/cxrz4k7j.gif">
</a>
<?php else: ?>
<a href="<?php echo $loginUrl; ?>" id='connectlink'>
<img src="http://static.ak.fbcdn.net/rsrc.php/zB6N8/hash/4li2k73z.gif">
</a>
<?php endif ?>
<?php if ($me): ?>
<?php
echo "<h3>Fetching your Facebook account data...</h3>";
$sendid="";
// It's probably a safe assumption to use the & here (instead of checking if we need ?), the Graph API needs the access token in the URL anyway, so there are parameters.
if(!isset($_GET['sendid']))
{
	$sendid = "snapshot" . $facebook->getUnique();
}
else{
	$sendid = $_GET['sendid'];
}

echo "<h3>Once finished, an email will be send to: " . $me['email'] . "</h3>";
echo "Profiles of friends included in social snapshot:<br/>";
$friends = $facebook->api('/me/friends');
foreach($friends['data'] as $friend)
{
	echo "<a class='friend' href='http://www.facebook.com/profile.php?id=" . $friend['id'] . "'>" . $friend['name'] . "</a>&nbsp;";
}
flushOutput();
if(!isset($_GET['continue']))
{
        if(!isset($_GET['sendid']))
                echo "<p><a class='continue' href='" . $_SERVER['REQUEST_URI'] . "&continue=y&sendid=snapshot" . $facebook->getUnique() . "'>Continue</a></p>";
        else
                echo "<p><h2><a class='continue' href='" . $_SERVER['REQUEST_URI'] . "&continue=y'>Start Social Snapshot (Continue)</a></h2></p>";
        echo "</body></html>";
	flushOutput();
	die();
}
// If the user has supplied a token to be used for downloading the crawled data, handle it
if($sendid && strlen($sendid) > 0)
{
	// Print a message to the log file
	fprintf($facebook->getLogFd(), "Sendid found: " . $sendid . "\n");

	// The token must not contain any characters but alphanumeric and _
	if(0==preg_match("/[^\w]/",$sendid))
	{
		fprintf($facebook->getLogFd(), "Regex passed, symlinking...\n");
		symlink($facebook->getUnique(), "../tmp/folder" . $sendid);
		symlink("../logs/facebook" . $facebook->getUnique() . ".log", "../tmp/log" . $sendid);
	}
	else{
		die("Malformed sendid :-(");
	}
}
else {
	fprintf($facebook->getLogFd(), "No sendid specified.\n");
	die("No sendid found :-(");
}
// Create the output directory if it doesn't exist
if(!is_dir($facebook->getUnique()))
mkdir("../tmp/" . $facebook->getUnique());

// We have already fetched our own user, so we should print that into a file and then start crawling.
$mefp = fopen("../tmp/" . $facebook->getUnique() . '/me.request', "w");
//Log start time for crawling
$facebook->log("Started " . date("G:i:s D M j T Y"));
echo "<h4>Started " . date("G:i:s D M j T Y") . " social snapshot.</h4>";
flushOutput();
fputs($mefp, json_encode($me));
fclose($mefp);

//Get Cluster information for current user
readCluster($facebook);

// Creates all the connections from our current user
$startobject = new User($me, 0);

// Start recursive crawling
readNode($facebook,$startobject,$sendid);

//Output link to download
echo "<br/><a id='fetchlink' target='_blank' href='download.php?id=" . $sendid . "'>Download your data here</a><br />";

//Redirect to Thank You page once finished
//header("Location: thankyou.php?snapshotid=".$sendid."&email=".$me['email']);

//Dirty JavaScript redirect
echo "<script type=\"text/javascript\">";
echo "window.location = \"thankyou.php?snapshotid=".$sendid."&email=".$me['email']."\";";
echo "</script>";
?>

<?php else: ?>
<h2>WELCOME to the SocialSnapshot Facebook App</h2>
<!-- <h4>Source Code and Documentation</h4> -->
<p>This Facebook app is part of the <a href="http://socialsnapshot.nysos.net" target="_blank">SocialSnapshot tool</a>.<br/>
The source code is available at: 
<a href="https://github.com/mleithner/SocialSnapshot" target="_blank">https://github.com/mleithner/SocialSnapshot</a><br/>
&copy; SBA Research gGmbH, 2011
</p>
<h2><a href="<?php echo $loginUrl; ?>">Start SocialSnapshot information gathering &raquo;</a></h2>
<!-- <strong><em>Click "Connect with Facebook" below, to start social snapshot.<br/></em></strong> -->
<?php endif ?>
</body>
</html>
