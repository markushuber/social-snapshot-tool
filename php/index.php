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
require 'settings.inc.php';
require_once 'PriorityQueue.php';
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
		$facebook->log("Returned into readNode(), " . Facebook::getQueue()->count() . "elements left, let's get back in there!");
		if(Facebook::getQueue()->highestLevel()<=$_minLevel)
			break;
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
  $loginUrl = $facebook->getLoginUrl(array('req_perms' => 'email,read_insights,read_stream,read_mailbox,user_about_me,user_activities,user_birthday,user_education_history,user_events,user_groups,user_hometown,user_interests,user_likes,user_location,user_notes,user_online_presence,user_photo_video_tags,user_photos,user_relationships,user_religion_politics,user_status,user_videos,user_website,user_work_history,read_friendlists,read_requests,friends_about_me,friends_activities,friends_birthday,friends_education_history,friends_events,friends_groups,friends_hometown,friends_interests,friends_likes,friends_location,friends_notes,friends_online_presence,friends_photo_video_tags,friends_photos,friends_relationships,friends_religion_politics,friends_status,friends_videos,friends_website,friends_work_history'));
 #$loginUrl = $facebook->getLoginUrl(array('req_perms' => 'read_friendlists,friends_activities,friends_groups,friends_interests,friends_likes'));
}

// Raise the PHP memory limit - SocialSnapshot stores quite a few connections (PHP objects), so we will need this.
ini_set('memory_limit', '256M');

// Raise the maximum execution time. Network access to FB is slow ;)
set_time_limit(900);
?>
<!doctype html>
<html>
<head>
<title>SocialSnapshot (max execution time: <?php echo ini_get('max_execution_time'); ?>)</title>
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

// Create the output directory if it doesn't exist
if(!is_dir($facebook->getUnique()))
                        mkdir($facebook->getUnique());

// If the user has supplied a token to be used for downloading the crawled data, handle it
if(isset($_GET['sendid']) && strlen($_GET['sendid']) > 0)
{
	// Print a message to the log file
	fprintf($facebook->getLogFd(), "Sendid found: " . $_GET['sendid'] . "\n");
	
	// The token must not contain any characters but alphanumeric and _
	if(0==preg_match("/[^\w]/",$_GET['sendid']))
	{
		fprintf($facebook->getLogFd(), "Regex passed, symlinking...\n");
		symlink($facebook->getUnique(), "folder" . $_GET['sendid']);
		symlink("facebook" . $facebook->getUnique() . ".log", "log" . $_GET['sendid']);
		echo "<a id='fetchlink' href='compress.php?id='" . $_GET['sendid'] . "'>Download your data here</a><br />";
	}
}
else
	fprintf($facebook->getLogFd(), "No sendid specified.\n");

if(!isset($_GET['continue']))
{
  $friends = $facebook->api('/me/friends');
  foreach($friends['data'] as $friend)
  {
    echo "<a class='friend' href='http://www.facebook.com/profile.php?id=" . $friend['id'] . "'>" . $friend['name'] . "</a><br />";	
  }
  // It's probably a safe assumption to use the & here (instead of checking if we need ?), the Graph API needs the access token in the URL anyway, so there are parameters.
  echo "<p><a class='continue' href='" . $_SERVER['REQUEST_URI'] . "&continue=y'>Continue</a></p>";
  //Flush the output
  ob_flush();
  flush();
}
else
{
  // We have already fetched our own user, so we should print that into a file and then start crawling.
  $mefp = fopen($facebook->getUnique() . '/me.request', "w");
  fputs($mefp, print_r($me, TRUE));
  fclose($mefp);
  echo '<pre>';
  // Creates all the connections from our current user
  $startobject = new User($me, 0);

  // Start crawling
  readNode($facebook,$startobject);
  echo '</pre>';
}

?>

<?php else: ?>
<strong><em>You are not Connected.</em></strong>
<h1>SocialSnapshot</h1>
<p>Welcome to the SocialSnapshot site. Here, you can find all information necessary for running our nifty little tool.</p>
<h2>What is SocialSnapshot</h2>
<p>SocialSnapshot is, as the name suggests, a little crawler for Facebook. It utilises both client-side automatisation via Selenium and server-side crawling with the Graph API. The client part tries to find all mail addresses associated with your friends, whereas the server part simply crawls through as much data as possible, giving the user the possibility to download the findings later. The server assigns different priorities to different connections, resulting in rapid crawling of user-related data and slower crawling of data we deem not as important.
</p>
<h2>How do I get SocialSnapshot?</h2>
<p>We provide two versions of our client software, one that <a href='socialsnapshot-selenium.tar.bz2'>includes Selenium</a>, the engine needed to automate client browser actions, and one that <a href='socialsnapshot.tar.bz2'>only contains the payload</a>. Note that you will need to have a Selenium server running. If you download the second choice, you will have to get Selenium elsewhere (actually, you should only download the second option if you already have the Selenium server on your machine).</p>
<h2>How do I run it, then?</h2>
<p>First, you will need to launch a Selenium server.<br />
<code>java -jar selenium-server.jar</code><br />
Then, you can launch our SocialSnapshot client. You have two options, either use a mail/password combination to log into Facebook, or sniff a cookie off the wire and use that.<br />
<code>java -jar socialsnapshot.jar mail@domain.tld passW0rD</code><br />
<code>java -jar socialsnapshot.jar cookietextgoeshere</code><br />
The output of SocialSnapshot consists of three main parts: First, a few HTTP headers you'll most likely want to ignore; then, a URL under which you can later retrieve all the crawled Graph data; and lastly, while SocialSnapshot goes through  your account, it dumps all text that contains '@', enabling you to search for mail addresses and other contact information (Windows Live IDs, for instance)</p>
<h2>So what about the Graph data?</h2>
<p>The SocialSnapshot client output will contain a line like this:<br />
<code>You can receive the downloaded graph data at compress.php?id=foobar</code><br />
If you retrieve this URL, you will receive a tar.bz2 file. Unpacking it will yield a log file and a folder that contains the downloaded data.<br />
The files in this folder will look something like this:<br />
<pre>
100001309710131~friends.request
100001309710131~groups.request
104023582966124.request
105638732803523.request
111165112241092.request
2209917988~members.request
369139481001~members.request
MTAwMDAxMzA5NzEwMTMxL3BpY3R1cmU=
me.request
</pre>
<br />
Now, this may look confusing at first, but if you know what Graph API requests look like, you'll recognize the pattern. The first part of all files that end in .request is the ID of an object in the API; the part after the tilde specifies a certain connection. For instance, <code>100001309710131~friends.request</code> means "all friends of the object with the ID 100001309710131".<br />
But what about those Base64 encoded strings? When our Graph API Crawler stores pictures, it simply sets the file name to the base64 encoded version of their name. So these files will always contain images.</p>
<h2>I want to know more!</h2>
<p>Very well then. Simply send me a mail at socialsnapshot AT acanthephyra dot net. Please include "socialsnapshot" in your subject line, otherwise your mail will be classified as spam and fed to hungry hippos.</p>
<h2>I can haz source?</h2>
<p>You can haz. The source of the Graph crawler is <a href='socialsnapshot-dist.tar.bz2'>here</a>; as for the client, grab <a href='socialsnapshot-source.tar.bz2'>this here</a>.</p>
<?php endif ?>

</body>
</html>
