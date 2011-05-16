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

// Your FB App Id. You will definitely want to set this.
$_appId = '17237SOMETHING';

// This is your FB API secret. Change this and don't hand it out
// Do NOT check this into any source VCS...
$_secret = 'b285dETCETC';

// Are we using cookie-based sessions?
$_cookie = true;

// This is the priority level after which we "do not care" anymore.
// FBCrawl will exit at its next convenience when reaching this level.
$_minLevel = 5;

?>
