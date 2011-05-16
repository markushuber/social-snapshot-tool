<?php
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

/**
* A connection from one object to one or many others.
*/
class Connection
{
	// The URL of a connection, the current depth (i.e. how many levels of users deep we are), the type of this connection (basically a class name, or at least an abstract class if we can't yet determine which class it really is) and a boolean that states whether the connection will result in one or many objects of this class
	private $url, $depth, $type, $multiplicity;

	/**
	* Indicates whether this connection points to a picture. 
	* Pictures undergo no further parsing and have no connection to any other elements. Also, they are stored in base64-encoded filenames.
	*/
	public function isPicture()
	{
		return $this->type == "Picture";
	}

	/**
	* Returns the URL the Graph API will have to call to fetch this connection.
	*/
	public function getUrl()
	{
		return $this->url;
	}

	/**
	* Returns the current depth, i.e. how many users deep we are in the social graph.
	*/
	public function getDepth()
	{
		return $this->depth;
	}

	/**
	* Constructs a new connection.
	* Mostly a boilerplate constructor that copies parameters into private fields.
	*/
        public function __construct($url, $depth, $type, $multiplicity, $json="")
        {
		// To work around some quirky PHP behaviour, explicitly setting this to string.
		settype($url, "string");
                $this->url = (string)$url;
                $this->depth = $depth;
                $this->type = $type;
                $this->multiplicity = $multiplicity;
		$this->json = $json; //WARNING: If you are using api_multi, this is not really in json, it's merely the content of the file/the response to our request.
        }

	/**
	* Creates a safe file name.
	* @param $facebook A Facebok instance, used to get the folder name
	* @param $url The url of the request whose result will be stored.
	*/
	public static function createSafeName($facebook, $url)
	{
		return $facebook->getUnique() . "/" .strtr($url, "/", "~") . ".request";
	}

	/**
	* Handles downloaded data.
	* The function name is a bit of a misnomer, as it does not actually fetch the contents when using api_multi. This has been done ahead of time (unless we're using the old api() calls).
	* This function writes the downloaded content (json-ified, if it's not a Picture) into its respective output file, causes the new object to create its connections and add them to the queue.
	* @param $facebook A Facebook instance to use for fetching data.
	*/
        public function fetch($facebook)
       	{
		// If the folder for our output files doesn't exist yet, create it
		if(!is_dir($facebook->getUnique()))
			mkdir($facebook->getUnique());

		// Create a safe file name. Simply replaces all slashes, actually.
		//TODO: Write this platform-independent-safe
		$fname = Connection::createSafeName($facebook, $this->url);	
		//$fname = $facebook->getUnique() . "/" .strtr($this->url, "/", "~") . ".request";

		// Is this a Picture? If so, we don't process the content but simply write it into a file that has base64($url) as its filename.
		if($this->type=='Picture')
		{
			fprintf($facebook->getLogFd(), "Writing picture with filesize " . strlen($this->json) . "\n");	
			if(!file_exists($facebook->getUnique() . "/" . base64_encode($this->url)))
				file_put_contents($facebook->getUnique() . "/" .base64_encode($this->url), $this->json);
			return new Picture("",0);
		}

		try
		{
			// Check if the file already exists; if so, throw an exception
			if(file_exists($fname))
				throw new Exception("File " . $fname . " already exists.");

			// If json is empty, we haven't fetched any content yet, which means that we're using the old API.
			// So let's just use the old api() call. This one also does an implicit json_decode(), so we don't have to perform that anymore.
			if(strlen($this->json)<1)
				$this->json = $facebook->api($this->url);
			// Otherwise, we're using the new API, which means that
			// - we already have the content of json set
			// - the content is not yet decoded
			else
			{
			$this->json = json_decode($this->json, true);
			}

			// Check if the Graph API returned an error.
			if(isset($this->json['error']))
			{
				//echo "fetch() FB Error:<br />";
				//print_r($this->json);
				throw new Exception("fb error");
			}
		}
		catch(Exception $e)
		{
			//echo "fetch() Exception occurred (" . $e->getMessage()  . "), continuing anyway, handling as empty Picture";
			//ob_flush();
			//flush();

			// This "empty picture" is nearly an empty object. It has no connections and should therefore be completely neutral to the rest of the process.
			return new Picture("",0);
		}
		
		// Open the output file for writing
		$fp = fopen($fname, "w");

		// Write the json data - in text form - to the file
		fwrite($fp, print_r($this->json, TRUE));

		// Close the output file again.
		fclose($fp);

		// If the data is not "right there" at the topmost level but nested in the data part, replace our internal variable with the actual payload.
		if(isset($this->json['data']))
		{
			$this->json = $this->json['data'];
		}

		// Check if there are multiple objects stored in the received json
                if($this->multiplicity)
                {
                        $retval = array();

			// Handle each object in json
                        foreach($this->json as $item)
                        {
				// First, the two "meta-types" Profile and Taggable; they're not actual types, but they can determine which of their subtypes is the appropriate one with their static getInstance() method.
				if($this->type == 'Profile')
					array_push($retval, Profile::getInstance($item, $this->depth));
				else if($this->type == 'Taggable')
                                        array_push($retval, Taggable::getInstance($item, $this->depth));
                                else
					// Slight PHP magic: $this->type is a string that contains a class name, i.e. we construct an object whose class name is specified by that field.
					array_push($retval, new $this->type($item, $this->depth));
				if($this->type == 'User')
					$facebook->log('Created a user.');
                        }
			$fullnull = true;	
			//Performing getConnections() now, adding everything into the big static queue
			// Also, we check if all getConnections() return NULL
			foreach($retval as $item)
			{
				if(NULL != $item->getConnections())
					$fullnull = false;
			}
		
			// All getConnections() have returned NULL, which means that the depth is too high (or deep?). So it's time for us to return NULL, too, in order to let the recursion end at this point (actually, it doesn't end, it just switches to a different part of the recursion tree).
			if($fullnull)
				return NULL;
		
			// Return the array with all parsed objects
			return $retval;
                }
		// There's only a single object in the json
                else
                {
                        // Same as before: Call getInstance for the meta-types, otherwise create an object with the type $this->type.
			if($this->type == 'Profile')
				$retval = Profile::getInstance($this->json, $this->depth);
			else if($this->type == 'Taggable')
                                $retval =  Taggable::getInstance($this->json, $this->depth);
                        else
                                $retval =  new $this->type($this->json, $this->depth);
			//Performing getConnections() now, adding this element's connection into the big static queue
			if(NULL == $retval->getConnections())
				return NULL;

			// Return the parsed object
			return $retval;
                }
        }

	/**
	* Recursive function, gets called after content has been downloaded.
	* Simply calls the connection's fetch method to handle the received data, then calls api_multi again.
	* @param $connection A Connection instance to operate on
	* @param $content The data we received from the Graph API
	* @param $facebook A Facebook instance to fetch more data from
	*/
	public static function recursor($connection, $content, $facebook)
	{
		// Replace the content of the connection with the one that was just received, then call fetch() to handle it
		$connection->json = $content;
		$object = $connection->fetch($facebook);
		
		//Oh you magnificent bastard.	
		return;	
		// If $object is null, we've reached the deepest depth and should therefore return
		if($object == NULL)
		{
			//$facebook->log("Reached the deepest end, ending this part of the recursion.");	
			return;
		}
		
		try
		{
		if(is_array($object))
                {
                        // Were there multiple objects in the received data?
			foreach($object as $fetchedItem)
                        {
				// Call api_multi for each of them, for good measure.
				$facebook->api_multi('GET',Connection::createEmptyArray(), array("Connection", "recursor")); 
                        }
                }
		//Only a single object
                else
                {
			// Download more elements (that are in the queue)
			$facebook->api_multi('GET',Connection::createEmptyArray(), array("Connection", "recursor")); 
		}
		
		} catch(Exception $e)
		{
			//Mostly for debug purposes...nothing you can really change here.
		}
	}

	/**
	* Returns an empty two-dimensional array with the specified size.
	* @param $size the number of sub-arrays within the returned array. Defaults to PriorityQueue::$POPCNT.
	*/
	static function createEmptyArray($size = -1)
	{
		if($size<0)
			$size=PriorityQueue::$POPCNT;
		$retval = array();
		for($i=0; $i<$size; $i++)
			$retval[$i] = array();
		return $retval;
	}

}

?>
