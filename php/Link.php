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
* Users, pages and groups can post links on Facebook; this is our internal representation of them.
* Connections:
* - The profile that created this link
* - The comments made on it
*/
class Link extends APIObject 
{
	function __construct($json, $depth)
	{
		$this->connections = new PriorityQueue();
		$this->depth=$depth;
		if(isset($json['id']))
			$this->connections->unshift(new Connection(number_format($json['id'],0,'',''), $depth, "Link", false), 3);
		/* Not for now
		if(isset($json['from']['id']))
			$this->connections->unshift(new Connection($json['from']['id'], $depth, "Profile", false));
		$this->connections->unshift(new Connection($json['id'] . '/comments', $depth, "Comment", true));*/
	}
}
