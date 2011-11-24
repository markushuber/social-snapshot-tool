#!/usr/bin/python

import os,subprocess,sys

with open('/var/www/SocialSnapshot/survey.log', 'r') as f:
	entries = f.readlines()
	for entry in entries:
		values = entry.split(';')
		uid = values[0]
		optin = values[1]
		optout = values[2]
		snapshotid = values[3].replace('\n','')
		commandline = "wget -q -t 1 --timeout=3600 --no-cache -O /var/www/SocialSnapshot/tmp/" + snapshotid + ".result.html \"http://crunch0r.ifs.tuwien.ac.at/SocialSnapshot/php/index.php?uid=" + uid + "&continue=true&sendid=" + snapshotid + "\""
		if not os.path.exists("/var/www/SocialSnapshot/tmp/" + snapshotid + ".finished"):
			print "Processing snapshot for userid: " + uid
			subprocess.call(commandline,shell=True)
			sys.exit(0)
