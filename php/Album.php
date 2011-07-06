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
* Represents a photo album in Facebook.
* Connections:
* - The profile this album belongs to
* - The photos in this album
* - Comments on this album
*/
class Album extends APIObject 
{
	function __construct($json, $depth)
	{
		$this->depth=$depth;
		$this->connections = new PriorityQueue();

		// Unshift our connections into the queue
		if(isset($json['id']))
                        $this->connections->unshift(new Connection(number_format($json['id'],0,'',''), $depth, "Album", false), 7);	
		/*
		if(isset($json['from']['id']))
			$this->connections->unshift(new Connection($json['from']['id'], $depth, "Profile", false),5);
		$this->connections->unshift(new Connection($json['id'] . '/photos', $depth, "Photo", true), 7-$depth);
		$this->connections->unshift(new Connection($json['id'] . '/comments', $depth, "Comment", true));
		 */
	}
}
