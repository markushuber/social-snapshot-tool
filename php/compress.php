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

/**
* Compresses the results of a previous run of the FB crawler and sends them to the user.
*/

// Check if the user has supplied a token
if(isset($_GET['id']))
{
	// Check if the token is valid (must not contain anything but alphanumeric plus _) and if the folder and logs for this run exist
	if(0!=preg_match("/[^\w]/", $_GET['id']) || !file_exists("tmp/folder" . $_GET['id']) || !file_exists("tmp/log" . $_GET['id']))
	{
		// Die otherwise
		die("Could not find socialsnapshot id :-(.");
	}

	// Tar and compress the logfile and folder
	exec("cd tmp && tar -hcjf ../tarballs/social" . $_GET['id'] . ".tar.bz2 log" .  $_GET['id'] . " folder" . $_GET['id'] . " > /dev/null");
	
	// Open the output file for reading
	$fp = fopen("tarballs/social" . $_GET['id'] . ".tar.bz2", "r");
	
	// Send the appriopriate MIME type, content length and filename
	header("Content-Type: application/x-bzip2");
	header("Content-Length: " . filesize("tarballs/social" . $_GET['id'] . ".tar.bz2"));
	header('Content-Disposition: attachment; filename="social'.$_GET["id"].'.tar.bz2"');
	
	// Pass all data in the file through to the client
	fpassthru($fp);

	// Close the tar.bz2 file
	fclose($fp);
}
