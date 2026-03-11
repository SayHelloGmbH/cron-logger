<?php

namespace CronLogger;

class Timer
{

	private int $start;

	public function __construct()
	{
		$this->start = time();
	}

	function getStart(): int
	{
		return $this->start;
	}

	function getDuration(): int
	{
		return time() - $this->start;
	}
}
