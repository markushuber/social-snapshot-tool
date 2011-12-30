# Setup

## socialsnapshot third-party Facebook application (php)
* Requirements: php5, php5-curl, Apache2/nginx
* Deploy php directory on webserver
* Change ownership of files to www-data
* Copy settings.inc.php.example to settings.inc.php
* Edit settings.inc.php to reflect your set-up

## socialsnapshot Facebook Client (java)
* Requirement: JRE 1.6
* Copy java/socialsnapshot.config.example to java/socialsnapshot.config
* Edit settings in java/socialsnapshot.config to reflect your set-up
* Within java directory, run "ant" to rebuild client jar

# Usage
## Start Selenium Server
* You need a Mozilla Firefox 3.6 with selenium IDE extension 
* java -jar java/server-patched.jar (selenium server)

## Start client with HTTP cookie authentication ...
* java -jar socialsnapshot.jar fbid@somehost.com 'c_user=11111 lu=xxx ...'

## ...or start client with password authentication
* java -jar socialsnapshot.jar fbid@somehost.com "secretpw"

#Results
## Contact details and socialsnapshot client log
* Local files in /results snapshot11111.csv snapshot11111.log
## socialsnapshot data from third-party application
* User will be automatically notified
