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
* As the name suggests, a queue that returns elements based on their priority.
*/
class PriorityQueue
{
	// The highest and lowest priority used in this queue
	private $maxlevel, $minlevel;

	// The internal representation of the queue, a two-dimensional array
	private $queue;

	// An internal array that stores the value of $element->getUrl() for all objects in $queue[n|n=0...n]
	private $urls = array();

	// The number of elements in this queue
	private $count;
	
	// The number of elements that should usually be returned by a shift() call
	public static $POPCNT = 3;

	/**
	* Returns the highest priority present in this queue.
	*/
	protected function getMaxLevel()
	{
		return $this->maxlevel;
	}

	/**
	* Return the lowest priority present in this queue.
	*/
	protected function getMinLevel()
	{
		return $this->minlevel;
	}
	
	/**
	* Exposes the internal array used to store data in this queue.
	*/
	public function getQueue()
	{
		return $this->queue;
	}


	/**
	* Inserts a new object in the queue.
	* @param $obj The object that shall be inserted
	* @param $priority The priority; a higher value means higher priority. If this parameter is omitted, the standard value 0 is used.
	*/
	public function unshift($obj, $priority=0)
	{
		$this->queue[$priority][] = $obj;

		// Adjust highest or lowest level if necessary
		if($this->maxlevel<$priority)
			$this->maxlevel = $priority;
		if($this->minlevel>$priority)
			$this->minlevel = $priority;
		// Increase the counter of elements in the queue
		$this->count++;
	}

	/**
	* Boilerplate constructor.
	* Simply sets the internal counters (highest level, lowest level, number of elements) to 0 and creates an empty array for the queue.
	*/
	function __construct()
	{
		$this->maxlevel = 0;
		$this->minlevel = 0;
		$this->count = 0;
		$this->queue = array();
	}

	/**
	* Merges the current internal queue with that of the PriorityQueue given as a parameter.
	* @param $mergable A PriorityQueue that shall be merged with this one.
	*/
	public function merge($mergable)
	{
		if($this->count == 0)
		{
			// Seems like our queue is empty, we'll simply replace it with the new one
			$this->queue = $mergable->getQueue();
			for($priority=$mergable->getMinLevel(); $priority <= $mergable->getMaxLevel(); $priority++)
			{
				for($element = 0; $element < count($this->queue[$priority]); $element++)
				{
					$this->urls[] = $this->queue[$priority][$element]->getUrl();	
				}
			}
			$this->count = $mergable->count();
			$this->maxlevel = $mergable->getMaxLevel();
			$this->minlevel = $mergable->getMinLevel();
			return;
		}

		// Grab the internal array of the other queue
		$mergequeue = $mergable->getQueue();

		// Iterate over all priorities used in both queues
		for($priority = min($this->minlevel, $mergable->getMinLevel()); $priority <= max($this->maxlevel, $mergable->getMaxLevel()); $priority++)
		{
			// If the other queue contains entry for this priority, we'll have to merge them in
			if(isset($mergequeue[$priority]))
				// Iterate over all elements with the current priority in the other queue
				for($element = 0; $element < count($mergequeue[$priority]); $element++)
				{
					// Better forget the stuff that's commented out here. It was there to remove duplicates. It's the worst CPU hog you can imagine.
					//TODO: Either remove the stuff that's commented, or find a better algorithm
					//$ignore = 0;
					/*for($i = 0; $i < count($this->queue[$priority]); $i++)
						if($this->queue[$priority][$i]->getUrl() == $mergequeue[$priority][$element]->getUrl())
						{
							//echo "merge() Removing duplicate";
							$ignore = 1;
							break;
						}
					if(!$ignore)
						C
					 */
					 if(!in_array($mergequeue[$priority][$element]->getUrl(), $this->urls))
					 {
					 	// Add the current element to our queue (actually, the $priority part of our internal queue) and increase the object counter.
						$this->queue[$priority][] = $mergequeue[$priority][$element];
						$this->count++;
					}
				}
		}

		// Adjust lowest and highest level used.
		$this->minlevel = min($this->minlevel, $mergable->getMinLevel());
		$this->maxlevel = max($this->maxlevel, $mergable->getMaxLevel());
	}

	/**
	* Return the next few elements from the queue (highest priority first) and removes them.
	* @param $num The number of elements that shall be returned. Should usually be set to PriorityQueue::$POPCNT, but defaults to 1 (which makes sense if somebody who doesn't know the internals of this class uses it).
	*/
	public function shift($num=1)
	{
		// Do we even have any elements left in the queue?
		if($this->count==0)
			throw new Exception("shift() No elements left in queue, but trying to fetch elements.");
		$retarray = array();
		$level = $this->maxlevel;

		// Loop until we either have enough elements in the return array or we're below the lowest level.
		while($num>0 && $level >= $this->minlevel)
		{
			$num--;
			// This loop simply slides to the next priority that has elements
			while(!isset($this->queue[$level]) || 0==count($this->queue[$level]))
				{
				if($this->minlevel == $level--) // Are we at the last level? (slight magic - we're BOTH checking if we're at minlevel and decreasing $level afterwards)
					return $retarray; // Pointless to continue
				}
			// Shift an element from the curret level of the internal queue (also removes the element)
			$retarray[] = array_shift($this->queue[$level]);

			// Decrease the counter, since we now have less elements
			$this->count--;
		}
		return $retarray;
	}

	/**
	* Returns the number of objects in the queue.
	*/
	public function count()
	{
		return $this->count;
	}

	public function highestLevel()
	{
		$level = $this->maxlevel;
		while(!isset($this->queue[$level]) || 0==count($this->queue[$level]))
			if($this->minlevel == $level--)
				return $this->minlevel;
		return $level;	
	}
}

?>
