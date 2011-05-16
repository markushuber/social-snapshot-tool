<?php
/*  Copyright 2010-2011 SBA Research gGmbH

     This file is part of FBCrawl.

    FBCrawl is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    FBCrawl is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with FBCrawl.  If not, see <http://www.gnu.org/licenses/>.*/

/**
* A profile is an element on Facebook that can post certain things.
* Profiles can be events, groups, users or pages.
*/
abstract class Profile extends APIObject
{

/**
* Returns an instance of the correct class for specific content.
*/
public static function getInstance($json, $depth)
	{
		if(isset($json['owner'])) // Must be Group or Event
		{
			if(isset($json['location']) || isset($json['start_time']) || isset($json['end_time']))
			{
				//It's an Event
				return new Event($json, $depth);
			}
			else
			{
				return new Group($json, $depth);
			}
		}
		else
		{
			if(isset($json['first_name']) || isset($json['last_name']) || isset($json['gender']))
			{
				return new User($json, $depth);
			}
			else
			{
				return new Page($json, $depth);
			}
		}
	}
}

?>
