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
* A Group on Facebook.
* Connections:
* - The owner of the group (must be a user)
* - The group's wall
* - All users that are members of this group
* - The group's logo
*/
class Group extends Profile
{
	function __construct($json, $depth)
	{	$this->connections = new PriorityQueue();
		$this->depth=$depth;
		/*Do not fetch for now
		if(isset($json['id']) && is_numeric($json['id']))
                        $this->connections->unshift(new Connection(number_format($json['id'],0,'',''), $depth, "Group", false), 5);
		if(isset($json['owner']['id']))
			$this->connections->unshift(new Connection($json['owner']['id'], $depth, "User", false));
		if($depth<2)
			$this->connections->unshift(new Connection($json['id'] . '/feed', $depth, "Post", true));
		$this->connections->unshift(new Connection($json['id'] . '/members', $depth, "Profile", true));
		$this->connections->unshift(new Connection($json['id'] . '/picture', $depth, "Picture", false), 5);*/
	}
}
