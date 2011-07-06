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
* A page can be the official account of a company, city or connection of people on Facebook, a music genre, band, movie etc.
* Connections: 
* - The page's wall
* - The logo of the page
* - Other taggable elements this page has tagged (see "Taggable" for an explanation of which objects can be tagged)
* - Links contained in this page
* - Photos this page has posted
* - Photo albums of this page
* - The status messages this page has posted
* - The videos of this page
* - Notes this page has published
* - Other posts made on this page
* - Events this page will attend
*/
class Page extends Profile
{
	function __construct($json, $depth)
	{
		$this->connections = new PriorityQueue();
		$this->depth=$depth;
		if(isset($json['id']))
		                        $this->connections->unshift(new Connection(number_format($json['id'],0,'',''), $depth, "Page", false), 8);
		/* No need for this information right now
		$this->connections->unshift(new Connection($json['id'] . '/picture', $depth, "Picture", false), 8);
		if($depth<2)
		{
			$this->connections->unshift(new Connection($json['id'] . '/tagged', $depth, "Taggable", true));
                	$this->connections->unshift(new Connection($json['id'] . '/links', $depth, "Link", true));
	        	$this->connections->unshift(new Connection($json['id'] . '/photos', $depth, "Photo", true));
                	$this->connections->unshift(new Connection($json['id'] . '/groups', $depth, "Group", true));
               		$this->connections->unshift(new Connection($json['id'] . '/albums', $depth, "Album", true));
                	$this->connections->unshift(new Connection($json['id'] . '/statuses', $depth, "Status", true));
                	$this->connections->unshift(new Connection($json['id'] . '/videos', $depth, "Video", true));
                	$this->connections->unshift(new Connection($json['id'] . '/notes', $depth, "Note", true));
			$this->connections->unshift(new Connection($json['id'] . '/feed', $depth, "Post", true));	
			$this->connections->unshift(new Connection($json['id'] . '/posts', $depth, "Post", true));
			$this->connections->unshift(new Connection($json['id'] . '/events', $depth, "Event", true));
		}*/
	}
}
