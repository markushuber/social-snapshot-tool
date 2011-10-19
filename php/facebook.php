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

// This file is based on FB Graph API example code released by 
// Facebook, Inc under the Apache License, Version 2.0.
// Original code can be found at https://github.com/facebook/php-sdk/blob/master/src/facebook.php. 
require 'settings.inc.php';

if (!function_exists('curl_init')) {
  throw new Exception('Facebook needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
  throw new Exception('Facebook needs the JSON PHP extension.');
}

/**
 * Thrown when an API call returns an exception.
 *
 * @author Naitik Shah <naitik@facebook.com>
 */
class FacebookApiException extends Exception
{
  /**
   * The result from the API server that represents the exception information.
   */
  private $result;

  /**
   * Make a new API Exception with the given result.
   *
   * @param Array $result the result from the API server
   */
  public function __construct($result) {
    $this->result = $result;

    $code = isset($result['error_code']) ? $result['error_code'] : 0;
    $msg  = isset($result['error'])
              ? $result['error']['message'] : $result['error_msg'];
    parent::__construct($msg, $code);
  }

  /**
   * Return the associated result object returned by the API server.
   *
   * @returns Array the result from the API server
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * Returns the associated type for the error. This will default to
   * 'Exception' when a type is not available.
   *
   * @return String
   */
  public function getType() {
    return
      isset($this->result['error']) && isset($this->result['error']['type'])
      ? $this->result['error']['type']
      : 'Exception';
  }

  /**
   * To make debugging easier.
   *
   * @returns String the string representation of the error
   */
  public function __toString() {
    $str = $this->getType() . ': ';
    if ($this->code != 0) {
      $str .= $this->code . ': ';
    }
    return $str . $this->message;
  }
}

/**
 * Provides access to the Facebook Platform.
 *
 * @author Naitik Shah <naitik@facebook.com>
 */
class Facebook
{
   private static $queue;
   public static function getQueue()
   {
	if(!isset(Facebook::$queue))
		Facebook::$queue = new PriorityQueue();
	return Facebook::$queue;
   }
   private $logfd;
   public function getLogFd()
   {
	if(!isset($this->logfd))
		$this->logfd = fopen("../logs/facebook" . $this->getUnique() . ".log", "w");
	return $this->logfd;
   }
   public function log($string)
   {
  fprintf($this->getLogFd(), $string . "\n"); 
   }
   /**
   * List of error codes that trigger clearing of the session.
   */
  
  private $handlers = array();
  private $handlerindex = 0;
  private static $HANDLER_REFRESH = 25;
  
  private static $SESSION_INVALID_ERRORS = array(
    190, // Invalid OAuth Access Token
  );

  /**
   * Default options for curl.
   */
  public static $CURL_OPTS = array(
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_USERAGENT      => 'facebook-php-2.0',
    CURLOPT_FOLLOWLOCATION	=>	true,
  );

  /**
   * List of query parameters that get automatically dropped when rebuilding
   * the current URL.
   */
  private static $DROP_QUERY_PARAMS = array(
    'session',
  );

  /**
   * Maps aliases to Facebook domains.
   */
  public static $DOMAIN_MAP = array(
    'api'      => 'https://api.facebook.com/',
    'api_read' => 'https://api-read.facebook.com/',
    'graph'    => 'https://graph.facebook.com/',
    'www'      => 'https://www.facebook.com/',
  );

  /**
   * The Application ID.
   */
  private $appId;

  /**
   * The Application API Secret.
   */
  private $apiSecret;

  /**
   * The active user session, if one is available.
   */
  private $session;

  /**
   * Indicates that we already loaded the session as best as we could.
   */
  private $sessionLoaded = false;

  /**
   * Indicates if Cookie support should be enabled.
   */
  private $cookieSupport = false;

  /**
   * Base domain for the Cookie.
   */
  private $baseDomain = '';
	
  /*
   * A unique ID for this instance of the class
   */
  private $unique = 0;

  /**
   * Initialize a Facebook Application.
   *
   * The configuration:
   * - appId: the application API key
   * - secret: the application secret
   * - cookie: (optional) boolean true to enable cookie support
   * - domain: (optional) domain for the cookie
   *
   * @param Array $config the application configuration
   */
  public function __construct($config) {
    $this->setAppId($config['appId']);
    $this->setApiSecret($config['secret']);
    $this->unique = time();
    if (isset($config['cookie'])) {
      $this->setCookieSupport($config['cookie']);
    }
    if (isset($config['domain'])) {
      $this->setBaseDomain($config['domain']);
    }
  }

  public function getUnique()
  {
	return $this->unique;
  }

  public function setUnique($uniquetoken)
  {
	  $this->unique = $uniquetoken;
	  return $this;
  }

  /**
   * Set the Application ID.
   *
   * @param String $appId the API key
   */
  public function setAppId($appId) {
    $this->appId = $appId;
    return $this;
  }

  /**
   * Get the API Key.
   *
   * @return String the API key
   */
  public function getAppId() {
    return $this->appId;
  }

  /**
   * Set the API Secret.
   *
   * @param String $appId the API Secret
   */
  public function setApiSecret($apiSecret) {
    $this->apiSecret = $apiSecret;
    return $this;
  }

  /**
   * Get the API Secret.
   *
   * @return String the API Secret
   */
  public function getApiSecret() {
    return $this->apiSecret;
  }

  /**
   * Set the Cookie Support status.
   *
   * @param Boolean $cookieSupport the Cookie Support status
   */
  public function setCookieSupport($cookieSupport) {
    $this->cookieSupport = $cookieSupport;
    return $this;
  }

  /**
   * Get the Cookie Support status.
   *
   * @return Boolean the Cookie Support status
   */
  public function useCookieSupport() {
    return $this->cookieSupport;
  }

  /**
   * Set the base domain for the Cookie.
   *
   * @param String $domain the base domain
   */
  public function setBaseDomain($domain) {
    $this->baseDomain = $domain;
    return $this;
  }

  /**
   * Get the base domain for the Cookie.
   *
   * @return String the base domain
   */
  public function getBaseDomain() {
    return $this->baseDomain;
  }

  /**
   * Set the Session.
   *
   * @param Array $session the session
   */
  public function setSession($session=null) {
    $session = $this->validateSessionObject($session);
    $this->sessionLoaded = true;
    $this->session = $session;
    $this->setCookieFromSession($session);
    return $this;
  }

  /**
   * Get the session object. This will automatically look for a signed session
   * sent via the Cookie or Query Parameters if needed.
   *
   * @return Array the session
   */
  public function getSession() {
    if (!$this->sessionLoaded) {
      $session = null;

      // try loading session from $_GET
      if (isset($_GET['session'])) {
        $session = json_decode(
          get_magic_quotes_gpc()
            ? stripslashes($_GET['session'])
            : $_GET['session'],
          true
        );
        $session = $this->validateSessionObject($session);
      }

      // try loading session from cookie if necessary
      if (!$session && $this->useCookieSupport()) {
        $cookieName = $this->getSessionCookieName();
        if (isset($_COOKIE[$cookieName])) {
          $session = array();
          parse_str(trim(
            get_magic_quotes_gpc()
              ? stripslashes($_COOKIE[$cookieName])
              : $_COOKIE[$cookieName],
            '"'
          ), $session);
          $session = $this->validateSessionObject($session);
        }
      }

      $this->setSession($session);
    }

    return $this->session;
  }

  /**
   * Get the UID from the session.
   *
   * @return String the UID if available
   */
  public function getUser() {
    $session = $this->getSession();
    return $session ? $session['uid'] : null;
  }

  
	/**
	* Get a link to a graph object
	*/
	public function getGraphUrl($object="me",$params=array())
	{
		if (!isset($params['access_token'])) {
      $session = $this->getSession();
      // either user session signed, or app signed
      if ($session) {
        $params['access_token'] = $session['access_token'];
    }

    // json_encode all params values that are not strings
    foreach ($params as $key => $value) {
      if (!is_string($value)) {
        $params[$key] = json_encode($value);
      }
    }
}

		return $this->getUrl('graph', $object, $params);
	}
   /**
   * Get a Login URL for use with redirects. By default, full page redirect is
   * assumed. If you are using the generated URL with a window.open() call in
   * JavaScript, you can pass in display=popup as part of the $params.
   *
   * The parameters:
   * - next: the url to go to after a successful login
   * - cancel_url: the url to go to after the user cancels
   * - req_perms: comma separated list of requested extended perms
   * - display: can be "page" (default, full page) or "popup"
   *
   * @param Array $params provide custom parameters
   * @return String the URL for the login flow
   */
  public function getLoginUrl($params=array()) {
    $currentUrl = $this->getCurrentUrl();
    return $this->getUrl(
      'www',
      'login.php',
      array_merge(array(
        'api_key'         => $this->getAppId(),
        'cancel_url'      => $currentUrl,
        'display'         => 'page',
        'fbconnect'       => 1,
        'next'            => $currentUrl,
        'return_session'  => 1,
        'session_version' => 3,
        'v'               => '1.0',
      ), $params)
    );
  }

  /**
   * Get a Logout URL suitable for use with redirects.
   *
   * The parameters:
   * - next: the url to go to after a successful logout
   *
   * @param Array $params provide custom parameters
   * @return String the URL for the logout flow
   */
  public function getLogoutUrl($params=array()) {
    $session = $this->getSession();
    return $this->getUrl(
      'www',
      'logout.php',
      array_merge(array(
        'api_key'     => $this->getAppId(),
        'next'        => $this->getCurrentUrl(),
        'session_key' => $session['session_key'],
      ), $params)
    );
  }

  /**
   * Get a login status URL to fetch the status from facebook.
   *
   * The parameters:
   * - ok_session: the URL to go to if a session is found
   * - no_session: the URL to go to if the user is not connected
   * - no_user: the URL to go to if the user is not signed into facebook
   *
   * @param Array $params provide custom parameters
   * @return String the URL for the logout flow
   */
  public function getLoginStatusUrl($params=array()) {
    return $this->getUrl(
      'www',
      'extern/login_status.php',
      array_merge(array(
        'api_key'         => $this->getAppId(),
        'no_session'      => $this->getCurrentUrl(),
        'no_user'         => $this->getCurrentUrl(),
        'ok_session'      => $this->getCurrentUrl(),
        'session_version' => 3,
      ), $params)
    );
  }

  /**
   * Make an API call.
   *
   * @param Array $params the API call parameters
   * @return the decoded response
   */
  public function api(/* polymorphic */) {
    $args = func_get_args();
    if (is_array($args[0])) {
      return $this->_restserver($args[0]);
    } else {
      return call_user_func_array(array($this, '_graph'), $args);
    }
  }
  
public function api_multi() 
{
	$args = func_get_args();
	call_user_func_array(array($this, '_graph_multi'), $args);
}

  /**
   * Invoke the old restserver.php endpoint.
   *
   * @param Array $params method call object
   * @return the decoded response object
   * @throws FacebookApiException
   */
  private function _restserver($params) {
    // generic application level parameters
    $params['api_key'] = $this->getAppId();
    $params['format'] = 'json';

    $result = json_decode($this->_oauthRequest(
      $this->getApiUrl($params['method']),
      $params
    ), true);

    // results are returned, errors are thrown
    if (isset($result['error_code'])) {
      if (in_array($result['error_code'], self::$SESSION_INVALID_ERRORS)) {
        $this->setSession(null);
      }
      throw new FacebookApiException($result);
    }
    return $result;
  }
  
  /**
   * For each param->filename pair, invokes the old REST API using curl_multi.
   * Note that the "params" parameter here is actually a two-dimensional array (think of it as the 'multi' variant of _restserver($params)).
   * Returns an array of ($param, $filename) arrays that failed to be retrieved.
   */
  public function apiQueue($params, $filenames)
  {
    $handlers = array(); // Temporary storage for the cURL handlers so we can iterate over them
    $failed = array();  
    $mc = curl_multi_init();
    
    // Prepare all the requests and attach them to handlers
    //URL is the same for all of them..
    $url = $this->getApiUrl($params[0]['method']);
    foreach($params as $i=>$param)
    {
      $param['api_key'] = $this->getAppId();
      $param['format'] = 'json';
      if (!isset($param['access_token'])) {
        $session = $this->getSession();
        // either user session signed, or app signed
        if ($session) {
          $param['access_token'] = $session['access_token'];
        }
      }

      // json_encode all params values that are not strings
      foreach ($param as $key => $value) {
        if (!is_string($value)) {
          $param[$key] = json_encode($value);
        }
      }
      $ch = $this->constructRequest($this->getApiUrl($param['method']), $param);
      curl_multi_add_handle($mc, $ch);
      $handlers[$i] = $ch; 
    }
    
    // Execute the requests
    do
    {
      $execret = curl_multi_exec($mc, $running);
    } while ($execret == CURLM_CALL_MULTI_PERFORM);
    while($running && $execret == CURLM_OK)
    {
      $ready = curl_multi_select($mc);
      if($ready != -1)
      {
        do
        {
          $execret = curl_multi_exec($mc, $running);
        } while ($execret == CURLM_CALL_MULTI_PERFORM);
      }
    }
    if($execret != CURLM_OK)
    {
      trigger_error("apiQueue Curl multi read error $execret\n", E_USER_WARNING);
    }

    // Handle the return values - if the returned content was empty, attach this request to the $failed array (which will be returned)
    // Otherwise, writes the return value into the appropriate file
    foreach($handlers as $i=>$handler)
    {
      $content = "";
      $curlerror = curl_error($handler);
      if("" == $curlerror)
      {
        $content = curl_multi_getcontent($handler);
      }
      else
        $this->log("apiQueue Curl error on handle $i: $curlerror\n");
      curl_multi_remove_handle($mc, $handler);
      if(empty($content))
      {
        // Push the failed requests back on the call stack
        $failed[] = array($params[$i], $filenames[$i]);
      }
      else
      {
        $outfile = fopen($filenames[$i], "w");
        fwrite($outfile, $content);
        fclose($outfile);
      }
    }
    curl_multi_close($mc); 
    return $failed;
  }
  /**
   * Invoke the Graph API.
   *
   * @param String $path the path (required)
   * @param String $method the http method (default 'GET')
   * @param Array $params the query/post data
   * @return the decoded response object
   * @throws FacebookApiException
   */
  private function _graph($path, $method='GET', $params=array()) {
    if (is_array($method) && empty($params)) {
      $params = $method;
      $method = 'GET';
    }
    $params['method'] = $method; // method override as we always do a POST

    $result = json_decode($this->_oauthRequest(
      $this->getUrl('graph', $path),
      $params
    ), true);

    // results are returned, errors are thrown
    if (isset($result['error'])) {
      throw new FacebookApiException($result);
    }
    
    return $result;
  }

private function _graph_multi($method='GET', $params=array(array()), $callback="echo")
{
	$mc = curl_multi_init();
	// FIRST: Prepare the parameters for all the requests.	
	for($i=0;$i<count($params);$i++)
	{
		$params[$i]['method'] = $method;
		if(!isset($params[$i]['access_token']))
                {
                        $session = $this->getSession();
                        if($session)
                        {
                                $params[$i]['access_token'] = $session['access_token'];
                        }
                }
                foreach(array_keys($params[$i]) as $j)
                {
                        if(!is_string($params[$i][$j]))
                        {
                                $params[$i][$j] = json_encode($params[$i][$j]);
                        }
                }

	}
	
	// SECOND: Grab the initial set of connections from the global queue
	$i=0;
	try{
		$connections = Facebook::getQueue()->shift(PriorityQueue::$POPCNT);
		$this->log(date("G:i:s ") . "Grabbed elements; objects remaining in queue: " . Facebook::getQueue()->count() . " (highest level: " . Facebook::getQueue()->highestLevel() . ")");
	} catch(Exception $e)
	{	
		//echo "_graph_mult() Exception when shifting: " . $e->getMessage() . "<br />";
		ob_flush();
		flush();
		return;
	}
	
	// THIRD: Go through all of these connections, replace it if it refers to an existing file, then construct the cURL multi request
	foreach($connections as $i=>$connection)
	{
                $fname = Connection::createSafeName($this, $connection->getUrl());
                while(file_exists($fname))
		{
			try
                        {
                                $connectionarray = Facebook::getQueue()->shift(1);
				$this->log("Replacing connection " . $i . " pointing to " . $fname);
                                $connections[$i] = $connection = $connectionarray[0];
                        }
                        catch(Exception $e)
                        {
                                unset($connection);
                                fprintf($this->getLogFd(), "_graph_mult() Exception when shifting to grab replacement: " . $e->getMessage() . "\n");
				// If the queue is empty, bail out
				if(Facebook::getQueue()->count() < 1)
					break 2;
                                continue;
                        }
 			fprintf($this->getLogFd(), "Replaced by connection to " . $connection->getUrl() . ", " . Facebook::getQueue()->count() . " elements left in queue\n");
			$fname = Connection::createSafeName($this, $connection->getUrl());
		}
		if(FALSE===strpos($connection->getUrl(), "http"))
			$url = $this->getUrl('graph', $connection->getUrl());
		else
			$url = $connection->getUrl();
		$this->log("Constructing request to " . $url);
		$ch = $this->constructRequest($url, $params[$i]);
		if(!isset($params[$i]['access_token']))
                        $this->log("[ERROR] No access token set on request to " . $url . ", have to discard it.");
                else
		{
                        $channels[] = $ch;
			// What is this i don't even
			curl_multi_add_handle($mc, $ch);
		}

		$i++;		
	}

	// FOURTH: Execute the request(s) and check for errors.
        do
        {
                $execret = curl_multi_exec($mc, $running);
        } while ($execret == CURLM_CALL_MULTI_PERFORM);
	
	while($running && $execret == CURLM_OK)
        {
                $ready = curl_multi_select($mc);
                if($ready != -1)
                {
                        do
                        {
                                $execret = curl_multi_exec($mc, $running);
                        } while ($execret == CURLM_CALL_MULTI_PERFORM);
                }
        }
        if($execret != CURLM_OK)
        {
                trigger_error("makeRequest_multi() Curl multi read error $execret\n", E_USER_WARNING);
        }


	// FIFTH: Go through returned connections, pass the result to the callback and clean up cURL_multi
        $index=0;
        foreach($connections as $i=>$connection)
        {
                $this->log("Handling returned connection for " . $connection->getUrl());
                //echo "makeRequest_multi() Trying to read data from a connection<br />"
                if($channels[$index]==NULL)
                {
                        $curlerror = "Channel is NULL, there probably was no access token set. Continuing regardless.<br />";
                }
                else
                        $curlerror = curl_error($channels[$index]);
                if("" == $curlerror)
                {
			$handlerinfo = curl_getinfo($channels[$index], CURLINFO_EFFECTIVE_URL);
			$this->log("URL on the cURL handler was " . $handlerinfo);
                        $content = curl_multi_getcontent($channels[$index]);
                        call_user_func($callback, $connection, curl_multi_getcontent($channels[$index]), $this);
                        //echo "Back from callback.<br />";
                }
                else
                        print "makeRequest_multi() Curl error on handle $i: $curlerror\n";
                if($channels[$index] != NULL)
                {
                        curl_multi_remove_handle($mc, $channels[$index]);
                        //curl_close($channels[$index]);
                }
                $index++;
        }
        curl_multi_close($mc);

	//echo "---------------------------------------------GRAPH_MULTI END---------------------------------------------<br />";
}
  /**
   * Make a OAuth Request
   *
   * @param String $path the path (required)
   * @param Array $params the query/post data
   * @return the decoded response object
   * @throws FacebookApiException
   */
  private function _oauthRequest($url, $params) {
    if (!isset($params['access_token'])) {
      $session = $this->getSession();
      // either user session signed, or app signed
      if ($session) {
        $params['access_token'] = $session['access_token'];
      } else {
        // TODO (naitik) sync with abanker
        //$params['access_token'] = $this->getAppId() .'|'. $this->getApiSecret();
      }
    }

    // json_encode all params values that are not strings
    foreach ($params as $key => $value) {
      if (!is_string($value)) {
        $params[$key] = json_encode($value);
      }
    }
    //echo "Printing request params...";
    //print_r($params);
    return $this->makeRequest($url, $params);
  }


  /**
   * Makes an HTTP request. This method can be overriden by subclasses if
   * developers want to do fancier things or use something other than curl to
   * make the request.
   *
   * @param String $url the URL to make the request to
   * @param Array $params the parameters to use for the POST body
   * @param CurlHandler $ch optional initialized curl handle
   * @return String the response text
   */
  protected function makeRequest($url, $params, $ch=null) {
    $ch = $this->constructRequest($url, $params, $ch);
    $result = curl_exec($ch);
    @curl_close($ch);
    return $result;
  }


 private function handler_roundrobin()
 {
    // Initial setup
    while(count($this->handlers) < PriorityQueue::$POPCNT * 2)
    {
        $this->handlers[$this->handlerindex++] = curl_init();
	// In the last round...
	if($this->handlerindex >= PriorityQueue::$POPCNT * 2)
	    $this->handlerindex = 0;
    }
    // Close and renew the handler that is exactly POPCNT away from this one
    // Slightly inefficient because we're refreshing a few handlers twice at the beginning...
    if($this->handlerindex < PriorityQueue::$POPCNT)
    {
        @curl_close($this->handlers[$this->handlerindex + PriorityQueue::$POPCNT]);
	$this->handlers[$this->handlerindex + PriorityQueue::$POPCNT] = curl_init();
    }
    else
    {
	@curl_close($this->handlers[$this->handlerindex - PriorityQueue::$POPCNT]);
	$this->handlers[$this->handlerindex - PriorityQueue::$POPCNT] = curl_init();
    }
    $this->log("Handing out handler " . $this->handlerindex);
    $retval = $this->handlers[$this->handlerindex ++];
    if($this->handlerindex >= PriorityQueue::$POPCNT * 2)
    {
        $this->handlerindex = 0;
        $this->log("Rotating connection handlers...");
    } 
    return $retval;
 }
 protected function constructRequest($url, $params, $ch=null)	{
    if (!$ch) {
      $ch = $this->handler_roundrobin();
    }
    else
	$this->log("Using provided handler (instead of round robin)");

    $opts = self::$CURL_OPTS;
    if(FALSE===strpos($url, 'fbcdn'))
	    $opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
    $opts[CURLOPT_URL] = $url;
    curl_setopt_array($ch, $opts);
    return $ch;
    }


  /**
   * The name of the Cookie that contains the session.
   *
   * @return String the cookie name
   */
  private function getSessionCookieName() {
    return 'fbs_' . $this->getAppId();
  }

  /**
   * Set a JS Cookie based on the _passed in_ session. It does not use the
   * currently stored session -- you need to explicitly pass it in.
   *
   * @param Array $session the session to use for setting the cookie
   */
  private function setCookieFromSession($session=null) {
    if (!$this->useCookieSupport()) {
      return;
    }

    $cookieName = $this->getSessionCookieName();
    $value = 'deleted';
    $expires = time() - 3600;
    $domain = $this->getBaseDomain();
    if ($session) {
      $value = '"' . http_build_query($session, null, '&') . '"';
      if (isset($session['base_domain'])) {
        $domain = $session['base_domain'];
      }
      $expires = $session['expires'];
    }

    // if an existing cookie is not set, we dont need to delete it
    if ($value == 'deleted' && empty($_COOKIE[$cookieName])) {
      return;
    }

    if (headers_sent()) {
      // disable error log if a argc is set as we are most likely running in a
      // CLI environment
      // @codeCoverageIgnoreStart
      if (!array_key_exists('argc', $_SERVER)) {
        error_log('Could not set cookie. Headers already sent.');
      }
      // @codeCoverageIgnoreEnd

    // ignore for code coverage as we will never be able to setcookie in a CLI
    // environment
    // @codeCoverageIgnoreStart
    } else {
      setcookie($cookieName, $value, $expires, '/', '.' . $domain);
    }
    // @codeCoverageIgnoreEnd
  }

  /**
   * Validates a session_version=3 style session object.
   *
   * @param Array $session the session object
   * @return Array the session object if it validates, null otherwise
   */
  protected function validateSessionObject($session) {
    // make sure some essential fields exist
    if (is_array($session) &&
        isset($session['uid']) &&
        isset($session['session_key']) &&
        isset($session['secret']) &&
        isset($session['access_token']) &&
        isset($session['sig'])) {
      // validate the signature
      $session_without_sig = $session;
      unset($session_without_sig['sig']);
      $expected_sig = self::generateSignature(
        $session_without_sig,
        $this->getApiSecret()
      );
      if ($session['sig'] != $expected_sig) {
        // disable error log if a argc is set as we are most likely running in
        // a CLI environment
        // @codeCoverageIgnoreStart
        if (!array_key_exists('argc', $_SERVER)) {
          error_log('Got invalid session signature in cookie.');
        }
        // @codeCoverageIgnoreEnd
        $session = null;
      }
      // check expiry time
    } else {
      $session = null;
    }
    return $session;
  }

  /**
   * Build the URL for api given parameters.
   *
   * @param $method String the method name.
   * @return String the URL for the given parameters
   */
  private function getApiUrl($method) {
    static $READ_ONLY_CALLS =
      array('admin.getallocation' => 1,
            'admin.getappproperties' => 1,
            'admin.getbannedusers' => 1,
            'admin.getlivestreamvialink' => 1,
            'admin.getmetrics' => 1,
            'admin.getrestrictioninfo' => 1,
            'application.getpublicinfo' => 1,
            'auth.getapppublickey' => 1,
            'auth.getsession' => 1,
            'auth.getsignedpublicsessiondata' => 1,
            'comments.get' => 1,
            'connect.getunconnectedfriendscount' => 1,
            'dashboard.getactivity' => 1,
            'dashboard.getcount' => 1,
            'dashboard.getglobalnews' => 1,
            'dashboard.getnews' => 1,
            'dashboard.multigetcount' => 1,
            'dashboard.multigetnews' => 1,
            'data.getcookies' => 1,
            'events.get' => 1,
            'events.getmembers' => 1,
            'fbml.getcustomtags' => 1,
            'feed.getappfriendstories' => 1,
            'feed.getregisteredtemplatebundlebyid' => 1,
            'feed.getregisteredtemplatebundles' => 1,
            'fql.multiquery' => 1,
            'fql.query' => 1,
            'friends.arefriends' => 1,
            'friends.get' => 1,
            'friends.getappusers' => 1,
            'friends.getlists' => 1,
            'friends.getmutualfriends' => 1,
            'gifts.get' => 1,
            'groups.get' => 1,
            'groups.getmembers' => 1,
            'intl.gettranslations' => 1,
            'links.get' => 1,
            'notes.get' => 1,
            'notifications.get' => 1,
            'pages.getinfo' => 1,
            'pages.isadmin' => 1,
            'pages.isappadded' => 1,
            'pages.isfan' => 1,
            'permissions.checkavailableapiaccess' => 1,
            'permissions.checkgrantedapiaccess' => 1,
            'photos.get' => 1,
            'photos.getalbums' => 1,
            'photos.gettags' => 1,
            'profile.getinfo' => 1,
            'profile.getinfooptions' => 1,
            'stream.get' => 1,
            'stream.getcomments' => 1,
            'stream.getfilters' => 1,
            'users.getinfo' => 1,
            'users.getloggedinuser' => 1,
            'users.getstandardinfo' => 1,
            'users.hasapppermission' => 1,
            'users.isappuser' => 1,
            'users.isverified' => 1,
            'video.getuploadlimits' => 1);
    $name = 'api';
    if (isset($READ_ONLY_CALLS[strtolower($method)])) {
      $name = 'api_read';
    }
    return self::getUrl($name, 'restserver.php');
  }

  /**
   * Build the URL for given domain alias, path and parameters.
   *
   * @param $name String the name of the domain
   * @param $path String optional path (without a leading slash)
   * @param $params Array optional query parameters
   * @return String the URL for the given parameters
   */
  private function getUrl($name, $path='', $params=array()) {
    $url = self::$DOMAIN_MAP[$name];
    if ($path) {
      if ($path[0] === '/') {
        $path = substr($path, 1);
      }
      $url .= $path;
    }
    if ($params) {
      $url .= '?' . http_build_query($params);
    }
    return $url;
  }

  /**
   * Returns the Current URL, stripping it of known FB parameters that should
   * not persist.
   *
   * @return String the current URL
   */
  private function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'
      ? 'https://'
      : 'http://';
    $currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $parts = parse_url($currentUrl);

    // drop known fb params
    $query = '';
    if (!empty($parts['query'])) {
      $params = array();
      parse_str($parts['query'], $params);
      foreach(self::$DROP_QUERY_PARAMS as $key) {
        unset($params[$key]);
      }
      if (!empty($params)) {
        $query = '?' . http_build_query($params);
      }
    }

    // use port if non default
    $port =
      isset($parts['port']) &&
      (($protocol === 'http://' && $parts['port'] !== 80) ||
       ($protocol === 'https://' && $parts['port'] !== 443))
      ? ':' . $parts['port'] : '';

    // rebuild
    return $protocol . $parts['host'] . $port . $parts['path'] . $query;
  }

  /**
   * Generate a signature for the given params and secret.
   *
   * @param Array $params the parameters to sign
   * @param String $secret the secret to sign with
   * @return String the generated signature
   */
  private static function generateSignature($params, $secret) {
    // work with sorted data
    ksort($params);

    // generate the base string
    $base_string = '';
    foreach($params as $key => $value) {
      $base_string .= $key . '=' . $value;
    }
    $base_string .= $secret;

    return md5($base_string);
  }
}
