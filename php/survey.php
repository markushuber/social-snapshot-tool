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
}

?>
<!doctype html>
<html>
<head>
<title>SocialSnapshot Survey</title>
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
<?php if ($me && isset($_GET['submit'])): ?>
<?php

if ($_GET['optin'] != "true"){
die("</br>Do not fool around with the parameters.");
}

if (isset($_GET['optout'])){
	if ($_GET['optout'] != "sec1"){
	die("</br>Do not fool around with the parameters.");
	}
}

echo "<h3>Dearest " . $me['name'] . "!</br>Thank you for participating in our survey.</h3>";

echo "<h3>You should soon receive an email to ". $me['email'] . " with a download link.</h3>";
echo "<h4>In case you have further questions, contact the survey author: Markus Huber mhuber@sba-research.org</h4>";
echo "<h4>Spread the surveylink: http://is.gd/snapshotsurvey</h4>";

$sendid="";

// It's probably a safe assumption to use the & here (instead of checking if we need ?), the Graph API needs the access token in the URL anyway, so there are parameters.
if(!isset($_GET['sendid']))
{
	$sendid = "snapshot" . $facebook->getUnique();
}
else{
	$sendid = $_GET['sendid'];
}
/*
echo "<h4>Facebook account: " . $me['email'] . "</h4>";
echo "</br>" . $sendid;
echo "</br>" . $_GET['optin'];
echo "</br>" . $_GET['optout'];
echo "</br>" . $facebook->getUser() . "</br>";*/
//echo $entry;

$entry = $facebook->getUser() . ";" . $_GET['optin'] . ";" . $_GET['optout'] . ";" . $sendid . "\n";

//Cookie for only one snapshot a week
if ($_COOKIE["uid"] != $facebook->getUser()){ 
$surveylogfile = "/var/www/SocialSnapshot/survey.log";
$fh = fopen($surveylogfile, 'a') or die("can't open surveyfile: contact mhuber@sba-research.org");
fwrite($fh, $entry);
fclose($fh);
setcookie("uid", $facebook->getUser(), time()+(3600*24*7));
exec("echo \"Hello " . $me['name'] . "!\nThank you for participating in our survey. Please allow up to 24 hours until your data is automatically fetched and you will receive a download link.\nBest, http://is.gd/snapshotsurvey\" | mail -s \"SocialSnapshot Survey\" " . $me['email']);
}
flushOutput();

?>

<?php else: ?>
<h2>SocialSnapshot Survey</h2>
<h3>After adding the app, you will receive an Email with a link to download all your Facebook data.</h3>

<?php
	if (isset($_GET['submit']) && isset($_GET['optin'])){
	//Dirty JavaScript redirect
	echo "<script type=\"text/javascript\">";
	echo "window.location = \"$loginUrl\";";
	echo "</script>";}
	
   if (isset($_GET['submit']) && !isset($_GET['optin'])){
   echo "<strong>You have to agree to the SocialSnapshot privacy terms.</br></strong>";
	}
?>
<form action="survey.php" method="get">
<input type="checkbox" name="optin" value="true"><strong>I hereby agree with the survey's Privacy terms&#42; (required)</strong>
</br></input>

<input type="checkbox" name="optout" value="sec1" checked>I want to participate in a future security experiment (optional)</input></br>
<input type="submit" name="submit" value="Add SocialSnapshot Facebook Application" style="cursor:pointer; font-size: 18px;" />
</form>
<p>&#42;Privacy terms</br>
a) Facebook account data will be automatically read and analysed. No Facebook content will be modified/posted.</br>
b) We do not have any access to your credentials but merely read your data through Facebook's Graph API.</br>
c) No personal information whatsoever is going to be published or shared with third parties.</br>
d) You agree that we securely store your information and use a anonymized version of your data for research. 
</p>
<h3>Contact</h3>
Markus Huber</br>
mhuber AT sba-research.org
<p>This Facebook app is part of the <a href="http://socialsnapshot.nysos.net" target="_blank">SocialSnapshot tool</a>.<br/>
The source code is available at: 
<a href="https://github.com/mleithner/SocialSnapshot" target="_blank">https://github.com/mleithner/SocialSnapshot</a><br/>
&copy; SBA Research gGmbH, 2011
</p>
<?php endif ?>
</body>
</html>
