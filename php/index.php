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

header("Content-type: text/html; charset=utf-8");

// Create the output directories for the application if they do not exist yet.
if(!is_dir("tmp"))
	mkdir("tmp/");
if(!is_dir("logs"))
	mkdir("logs/");
if(!is_dir("tarballs"))
	mkdir("tarballs/");

// Create our Application instance.
$facebook = new Facebook(array(
'appId' => $_appId,
'secret' => $_secret,	
'cookie' => $_cookie,
));

function readNode($facebook,$parent)
{
	//echo "readNode()<br />";
	$connections = $parent->getConnections();
	
	while(Facebook::getQueue()->count()>0)
	{
		$facebook->api_multi('GET',Connection::createEmptyArray(), array("Connection", "recursor"));
		$facebook->log(date("G:i:s D M j T Y") . " Returned into readNode(), " . Facebook::getQueue()->count() . " elements left, let's get back in there! Highest Level: " . Facebook::getQueue()->highestLevel());
		echo date("G:i:s D M j T Y") . " " . Facebook::getQueue()->count() . " elements left. Highest Level: " . Facebook::getQueue()->highestLevel() . "<br />";
		if(Facebook::getQueue()->highestLevel()<3 || Facebook::getQueue()->count()<3 ){
			$facebook->log("Finished. highestLevel: " . Facebook::getQueue()->highestLevel());
		$facebook->log("Finished " . date("G:i:s D M j T Y"));
		echo "Finished " . date("G:i:s D M j T Y");
			$remaining = print_r(Facebook::getQueue(),true);
			$facebook->log($remaining);
			break;
		}
		//Flush the output
		ob_flush();
		flush();
	}
        // Compress the gathered socialsnapshot		
	// Tar and compress the logfile and folder
	// Check if the token is valid (must not contain anything but alphanumeric plus _) and if the folder and logs for this run really exist
	if(0!=preg_match("/[^\w]/", $_GET['sendid']) || !file_exists("tmp/folder" . $_GET['sendid']) || !file_exists("tmp/log" . $_GET['sendid']))
	{
		// Die otherwise
		die("Compression Failed: Could not find according socialsnapshot and log.");
	}
	else {
		exec("cd tmp && tar -hcjf ../tarballs/" . $_GET['sendid'] . ".tar.bz2 log" .  $_GET['sendid'] . " folder" . $_GET['sendid'] . " > /dev/null");
		exec("touch tmp/" . $_GET['sendid'] . ".finished > /dev/null");
	}
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
if ($session) {
  try {
    $uid = $facebook->getUser();
    $me = $facebook->api('/me');
  } catch (FacebookApiException $e) {
    error_log($e);
  }
}


// login or logout url will be needed depending on current user state.
if ($me) {
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
<title>SocialSnapshot Facebook Application (max execution time: <?php echo ini_get('max_execution_time'); ?>)</title>
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



if(!isset($_GET['continue']))
{
  // It's probably a safe assumption to use the & here (instead of checking if we need ?), the Graph API needs the access token in the URL anyway, so there are parameters.
	if(!isset($_GET['sendid']))
	{
		echo "<p><a class='continue' href='" . $_SERVER['REQUEST_URI'] . "&continue=y&sendid=snapshot" . $facebook->getUnique() . "'>Continue</a></p>";
	}
	else{
	//echo "<p><a class='continue' href='" . $_SERVER['REQUEST_URI'] . "&continue=y&sendid=" . $_GET['sendid'] . "'>Continue</a></p>";
	echo "<p><a class='continue' href='" . $_SERVER['REQUEST_URI'] . "&continue=y'>Continue</a></p>";
	}

  $friends = $facebook->api('/me/friends');
  foreach($friends['data'] as $friend)
  {
    echo "<a class='friend' href='http://www.facebook.com/profile.php?id=" . $friend['id'] . "'>" . $friend['name'] . "</a><br />";	
  }

  //Flush the output
  ob_flush();
  flush();
}
else
{ 
  	echo "<a id='fetchlink' target='_blank' href='compress.php?id=" . $_GET['sendid'] . "'>Download your data here</a><br />";
	// If the user has supplied a token to be used for downloading the crawled data, handle it
	if(isset($_GET['sendid']) && strlen($_GET['sendid']) > 0)
	{
		// Print a message to the log file
		fprintf($facebook->getLogFd(), "Sendid found: " . $_GET['sendid'] . "\n");

		// The token must not contain any characters but alphanumeric and _
		if(0==preg_match("/[^\w]/",$_GET['sendid']))
		{
			fprintf($facebook->getLogFd(), "Regex passed, symlinking...\n");
			symlink($facebook->getUnique(), "tmp/folder" . $_GET['sendid']);
			symlink("../logs/facebook" . $facebook->getUnique() . ".log", "tmp/log" . $_GET['sendid']);
		}
	}
	else
		fprintf($facebook->getLogFd(), "No sendid specified.\n");

	// Create the output directory if it doesn't exist
	if(!is_dir($facebook->getUnique()))
		mkdir("tmp/" . $facebook->getUnique());
	
	// We have already fetched our own user, so we should print that into a file and then start crawling.
	$mefp = fopen("tmp/" . $facebook->getUnique() . '/me.request', "w");
	//Log start time for crawling
	$facebook->log("Started " . date("G:i:s D M j T Y"));
	echo "Started " . date("G:i:s D M j T Y") . " social snapshot.<br />";
  	//Flush the output
  	ob_flush();
  	flush();
	fputs($mefp, json_encode($me));
	fclose($mefp);
	
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
		$filenames[] = "tmp/" . $facebook->getUnique() . '/' . $friends['data'][$index]['id'] . '~cluster.request';
	}
	// Execute the call to retrieve the clusters
	$failsafe = 100; // Just to prevent infinite loops in case the REST API goes down or something...
	do
	{
		$failed = $facebook->apiQueue($params, $filenames);
		$facebook->log("[APIQUEUE] There were " . count($failed) . " failed requests to the REST API. (will retry " . $failsafe . " times)");
	} while(count($failed) > 0 && ($failsafe--) > 0);
	echo '<pre>';

	// Creates all the connections from our current user
	$startobject = new User($me, 0);
	// Start recursive crawling
	readNode($facebook,$startobject);

	echo '</pre>';
}

?>

<?php else: ?>
<strong><em>You are not Connected.</em></strong>
<h1>Social Snapshot Facebook Application</h1>
</p>
<h2>Source Code and Documentation</h2>
<p>This Facebook application is part of the <a href="http://socialsnapshot.nysos.net" target="_blank">SocialSnapshot tool</a>, which is available at:<br>
<a href="https://github.com/mleithner/SocialSnapshot" target="_blank">SocialSnapshot github repository</a>
</p>
<?php endif ?>

</body>
</html>
