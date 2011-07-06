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
* An abstract class that serves as an ancestor of all API objects. Defines the structure of the __construct() call and provides a function that merges the connections of the current object into the big static queue.
* @author mleithner@sba-research.org
*/
require 'settings.inc.php';

abstract class APIObject
{
	// The largest depth we will descend to. If we go further, getConnections() simply returns NULL.
	//const MAX_DEPTH = $_depth;
	const MAX_DEPTH = 2;

	// Variables for the content of the object (in json_decode()d form) and the current depth
	protected $json, $depth;

	// A PriorityQueue with connections. Old implementing classes used this as an array (can't really forbid it, since this is PHP), getConnections() tries to compensate for that.
	protected $connections; 
	/**
	* Constructs a new APIObject.
	* @param json A String array containing the parsed json that describes this object.
	* @param depth The depth in users. If this object is a user, it shall increase this value by 1. If the value of this parameter exceeds MAX_DEPTH, no further connections shall be created (which also means that getConnections shall return an empty array)
	*/
	abstract public function __construct($json, $depth);

	/**
	* Merges the connections of this object into the big static queue.
	*/
	public function getConnections()
	{
		// If we've gone too deep, simply return NULL.
		if($this->depth>APIObject::MAX_DEPTH)
		//if($this->depth>$_maxdepth)
			return NULL; 
		// This is just a workaround for classes that still use the old way of storing connections (that is, as an array).
		if(is_array($this->connections))
		{
			$retval = new PriorityQueue();
			// Unshift each element of our connection array into the PriorityQueue
			foreach($this->connections as $connection)
				$retval->unshift($connection);
			// Replace our connections with the PriorityQueue.
			$this->connections = $retval;
		}
		// Merge our own connections into the big queue.
		Facebook::getQueue()->merge($this->connections);

		return Facebook::getQueue();
	}
}

?>
