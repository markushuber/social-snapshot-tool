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
* Represents a Facebook event (e.g. parties, conferences etc).
* Connections:
* - The person that created the event
* - The wall of that event
* - Lists of the users that didn't reply to the invitation, replied with "maybe", "yes" or "no" and all that were invited
* - The event's logo
*/
class Event extends Profile
{
	function __construct($json, $depth)
	{
		$this->depth=$depth;
		$this->connections = new PriorityQueue();
		/*No need for this information yet
		if(isset($json['id']) && is_numeric($json['id']))
                        $this->connections->unshift(new Connection(number_format($json['id'],0,'',''), $depth, "Event", false), 5);
		if(isset($json['owner']['id']))
			$this->connections->unshift(new Connection($json['owner']['id'], $depth, "User", false));
		$this->connections->unshift(new Connection($json['id'] . '/feed', $depth, "Post", true));
		$this->connections->unshift(new Connection($json['id'] . '/noreply', $depth, "User", true));
		$this->connections->unshift(new Connection($json['id'] . '/maybe', $depth, "User", true));
		$this->connections->unshift(new Connection($json['id'] . '/invited', $depth, "User", true));
		$this->connections->unshift(new Connection($json['id'] . '/attending', $depth, "User", true));
		$this->connections->unshift(new Connection($json['id'] . '/declined', $depth, "User", true));
		$this->connections->unshift(new Connection($json['id'] . '/picture', $depth, "Picture", false),5);*/
	}
}
