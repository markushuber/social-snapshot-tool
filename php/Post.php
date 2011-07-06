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
* A post, e.g. a wall-to-wall message.
* Connections: 
* - The sending profile
* - The receiving profile
* - An attached picture
* - The source of the picture
* - Comments on the post
*/
class Post extends APIObject 
{
	function __construct($json, $depth)
	{
		$this->connections = new PriorityQueue();
		$this->depth=$depth;
		if(isset($json['id']))
		                        $this->connections->unshift(new Connection(number_format($json['id'],0,'',''), $depth, "Post", false));
		/*Not for now
		if(isset($json['from']['id']))
			$this->connections->unshift(new Connection($json['from']['id'], $depth, "Profile", false));
		if(isset($json['to']['id']))
			$this->connections->unshift(new Connection($json['to']['id'], $depth, "Profile", false));
		 if(isset($json['picture']))
                        $this->connections->unshift(new Connection($json['picture'], $depth, "Picture", false));
                 if(isset($json['source']))
                        $this->connections->unshift(new Connection($json['source'], $depth, "Picture", false));
		 $this->connections->unshift(new Connection($json['id'] . '/comments', $depth, "Comment", true));*/
	}
}
