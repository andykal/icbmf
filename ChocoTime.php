<?php

class ChocoTime {
	
	const MIN_LOAN_LIFESPAN = 7776000;
        //const START_TIME = 1321344000; // nov 15 2011
        const START_TIME = 1112342400; // 4/1/2005
	const END_TIME = 1441090800; // sept 1 2015
	const FRAMES = 5600;  // 4/1/05 through 9/1/15 in 5600 frames = 16.3 hours per frame = 20.4 days/second @ 30 fps

	private $_start_ts; // start of time sequence
	private $_end_ts; // end of time sequence
	private $_n_frames; // number of frames to be rendered
	private $_inverval_per_frame; // width of output (pixels)

	/**
	 * init global vars for this sequence
	 * @param  $start_ts
	 * @param  $end_ts
	 * @param  $n_frames
	 * @return void
	 */
	public function __construct($start_ts, $end_ts, $n_frames) {
		$this->_start_ts = $start_ts;
		$this->_end_ts = $end_ts;
		$this->_n_frames = $n_frames;
		$this->_interval_per_frame = ($end_ts - $start_ts) / $n_frames;
	}

	public function getStartTime() {return $this->_start_ts; }

	public function getEndTime() { return $this->_end_ts; }

	public function getIntervalPerFrame() { return $this->_interval_per_frame; }

	public function getFrameCount() { return $this->_n_frames; }

	public function getFrameBoundaries($frame) {
		$frame_time_start = $this->_start_ts + (($frame - 1) * $this->_interval_per_frame);
		$frame_time_end = $this->_start_ts + ($frame * $this->_interval_per_frame);
		return array($frame_time_start,$frame_time_end);
	}

	/* calculate the % complete that a particle is on a path */
	public static function getPercentComplete($start, $end, $frame) {
//echo "$start , $end, $frame \n";
		//$now = ($frame * $this->getIntervalPerFrame);
		$interval_per_frame = (ChocoTime::END_TIME - ChocoTime::START_TIME)/ChocoTime::FRAMES;
		//$now = ($frame * $interval_per_frame);
		//$now = $start + ($frame - 1) * $interval_per_frame;
		$now = ChocoTime::START_TIME + ($frame - 1) * $interval_per_frame;
		$percent = ($now - $start)/($end - $start);
//$d1 = $now - $start;
//$d2 = $end - $start;
//echo "ipf: $interval_per_frame, now: $now , frame: $frame, start: $start, end: $end. $d1 / $d2  = $percent % \n";
		return min(1.0, $percent);
	}

}
