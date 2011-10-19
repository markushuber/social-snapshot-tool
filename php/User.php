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
* A Facebook user.
* Connections:
* - The user's hometown
* - The user's current location
* - Past and current employers, their location and the position with them
* - Education history (pages of the institutions and their leaving years)
* - Home and news feed
* - Profile picture
* - Objects in which the user has been tagged
* - Links posted by the user
* - Photos published by the user
* - Groups this user is member of
* - Photo albums
* - Status messages
* - Videos and notes published by the user
* - Posts, e.g. wall messages
* - Events this user has RSVP'd to
* - Friends
* - Favourite activities, interests, music, books, movies, TV shows and other pages he/she "like"d.
* - The inbox, outbox and updates of the current user
*/
class User extends Profile
{
	function __construct($json, $depth)
	{
		$this->connections = new PriorityQueue();
		$depth++;
		$this->depth=$depth;
		if(isset($json['id']))
			$this->connections->unshift(new Connection(number_format($json['id'],0,'',''), $depth, "User", false), 10);
		if(isset($json['hometown']['id']) && is_numeric($json['hometown']['id']))
			$this->connections->unshift(new Connection(number_format($json['hometown']['id'],0,'',''), $depth, "Page", false));
		if(isset($json['location']['id']) && is_numeric($json['location']['id']))
                        $this->connections->unshift(new Connection(number_format($json['location']['id'],0,'',''), $depth, "Page", false));
		if(isset($json['work']))
		{
			foreach($json['work'] as $key => $work)
			{
				if(isset($work['employer']['id']))
					$this->connections->unshift(new Connection(number_format($work['employer']['id'],0,'',''), $depth, "Page", false));
				if(isset($work['location']['id']))
                                        $this->connections->unshift(new Connection(number_format($work['location']['id'],0,'',''), $depth, "Page", false));
				if(isset($work['position']['id']))
                                        $this->connections->unshift(new Connection(number_format($work['position']['id'],0,'',''), $depth, "Page", false));
			}
		}	
		if(isset($json['education']))
		{
			foreach($json['education'] as $key => $education)
			{
				if(isset($education['school']['id']))
					$this->connections->unshift(new Connection(number_format($education['school']['id'],0,'',''), $depth, "Page", false));
				if(isset($education['year']['id']))
                                        $this->connections->unshift(new Connection(number_format($education['year']['id'],0,'',''), $depth, "Page", false));
			}
		}
                $this->connections->unshift(new Connection($json['id'] . '/home', $depth, "Post", true), 6);
		if($depth<2) $this->connections->unshift(new Connection($json['id'] . '/feed', $depth, "Post", true), 6);
		//$this->connections->unshift(new Connection($json['id'] . '/picture', $depth, "Picture", false), 9);
                $this->connections->unshift(new Connection($json['id'] . '/tagged', $depth, "Taggable", true), 9);
                $this->connections->unshift(new Connection($json['id'] . '/links', $depth, "Link", true), 5);
		$this->connections->unshift(new Connection($json['id'] . '/photos', $depth, "Photo", true), 10);
                $this->connections->unshift(new Connection($json['id'] . '/groups', $depth, "Group", true), 9);
                //$this->connections->unshift(new Connection($json['id'] . '/albums', $depth - 1, "Album", true), 8);
                $this->connections->unshift(new Connection($json['id'] . '/statuses', $depth, "Status", true), 5);
                $this->connections->unshift(new Connection($json['id'] . '/videos', $depth, "Video", true), 5);
                $this->connections->unshift(new Connection($json['id'] . '/notes', $depth, "Note", true), 5);
                $this->connections->unshift(new Connection($json['id'] . '/posts', $depth, "Post", true), 8);
                $this->connections->unshift(new Connection($json['id'] . '/events', $depth, "Event", true), 9);
                if($depth<2) $this->connections->unshift(new Connection($json['id'] . '/friends', $depth, "User", true), 10);
                $this->connections->unshift(new Connection($json['id'] . '/activities', $depth, "Page", true), 8);
                $this->connections->unshift(new Connection($json['id'] . '/interests', $depth, "Page", true), 8);
                $this->connections->unshift(new Connection($json['id'] . '/music', $depth, "Page", true), 5);
                $this->connections->unshift(new Connection($json['id'] . '/books', $depth, "Page", true), 5);
                $this->connections->unshift(new Connection($json['id'] . '/movies', $depth, "Page", true), 5);
                $this->connections->unshift(new Connection($json['id'] . '/television', $depth, "Page", true), 5);
                $this->connections->unshift(new Connection($json['id'] . '/likes', $depth, "Page", true), 5);
                if($depth<2) $this->connections->unshift(new Connection($json['id'] . '/inbox', $depth, "Message", true), 10);
                if($depth<2) $this->connections->unshift(new Connection($json['id'] . '/outbox', $depth, "Message", true), 10);
                //$this->connections->unshift(new Connection($json['id'] . '/updates', $depth, "Message", true));
	}
}
