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
* A photo posted on Facebook.
* Connections:
* - The profile that published this photo
* - The actual photo
* - The source picture, i.e. the original version
* - Comments on the photo
*/
class Photo extends APIObject 
{
	function __construct($json, $depth)
	{
		$this->connections = new PriorityQueue();
		$this->depth=$depth;	
		if(isset($json['id']))
		                        $this->connections->unshift(new Connection(number_format($json['id'],0,'',''), $depth, "Photo", false), 7);
		/*No need for this so far?
		if(isset($json['from']['id']))
			$this->connections->unshift(new Connection($json['from']['id'], $depth, "Profile", false));
		if(isset($json['picture']))
			$this->connections->unshift(new Connection($json['picture'], $depth, "Picture", false), 8-$depth);
		 if(isset($json['source']))
		        $this->connections->unshift(new Connection($json['source'], $depth, "Picture", false), 8-$depth);
		 $this->connections->unshift(new Connection($json['id'] . '/comments', $depth, "Comment", true));*/
	}

}
