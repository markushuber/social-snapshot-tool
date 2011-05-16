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
* Represents a message between profiles on Facebook.
* Connections:
* - The sender of the message
* - The recipient(s)
* - Profiles that left comments on the message
*/
class Message extends APIObject 
{
	function __construct($json, $depth)
	{
		$this->connections = new PriorityQueue();
		$this->depth=$depth;
		if(isset($json['from']['id']))
			$this->insert($json['from']['id']);
		if(isset($json['to']['data']))
		{
			foreach($json['to']['data'] as $toprofile)
				$this->insert($toprofile['id']);
		}
		if(isset($json['comments']) && isset($json['comments']['data']))
		{
			foreach($json['comments']['data'] as $comment)
			{
				if(isset($comment['from']['id']))
					$this->insert($comment['from']['id']);
			}
		}
	}
	
	// Ugly measure: Tries to prevent duplicate connections to a 
	// profile being inserted into the queue
	private function insert($id)
	{
		$newConnection = new Connection($id, $this->depth, "Profile", false);
		$queue = $this->connections->getQueue();
		if(isset($queue[6]))
			foreach($queue[6] as $connection)
				if($connection->getUrl() == $newConnection->getUrl())
					return;
		$this->connections->unshift($newConnection, 6);
	}
}
