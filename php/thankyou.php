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
?>
<!doctype html>
<html>
<head>
<title>Thank you: SocialSnapshot App finished information gathering</title>
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
<?php
//Header for utf-8 support and caching disabled
header("Content-type: text/html; charset=utf-8");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: -1");
// It's probably a safe assumption to use the & here (instead of checking if we need ?), the Graph API needs the access token in the URL anyway, so there are parameters.
if(!isset($_GET['snapshotid']) && !isset($_GET['email'])){
	die("Please run a SocialSnapshot first.");
}
echo "<h2>Thank you!</h2>";
echo "<h3>SocialSnapshot App has fetched all required data from Facebook.</h3>";
if(isset($_GET['snapshotid'])){
	echo "<h4>Your snapshotid is: " .$_GET['snapshotid'] ."</h4>";
}
if(isset($_GET['email'])){
	echo "<h4>You should soon receive an email to: " . $_GET['email'] . ", with a link to download your social snapshot.</h4>";
}
?>
</body>
</html>