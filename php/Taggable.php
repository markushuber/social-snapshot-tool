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
* Taggable is a metaclass for objects in which profiles can be tagged.
* For instance, people can be tagged in a photo, meaning that they are depicted in it.
*/
abstract class Taggable extends APIObject
{

/**
* Returns an instance of the correct class for the given content
*/
public static function getInstance($json, $depth)
	{
		if(isset($json['length']) || isset($json['embed_html'])) // Video
		{
			return new Video($json, $depth);
		}
		else if(isset($json['height']) || isset($json['width']))
		{
			return new Photo($json, $depth);
		}
		else
		{
			return new Post($json, $depth);
		}
	}
}

?>
