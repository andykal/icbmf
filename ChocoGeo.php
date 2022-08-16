<?php

class ChocoGeo {
	// set this manually, depending on the projection being used for the base map.
	// currently, two projetions are supported: "Mercator" and "Equirectangular"

	const PROJECTION = "Mercator";
	//const PROJECTION = "Equirectangular";

	private $_height;
	private $_width;
	/**
	 * init dimensions of output frames
	 * @param  $width
	 * @param  $height
	 * @return void
	 */
	public function __construct($width, $height) {
		$this->_height = $height;
		$this->_width = $width;
	}

	public function latlongToXY($lat, $lng) {
		if (ChocoGeo::PROJECTION == "Mercator") {
			return $this->latlongToXYMercator($lat, $lng);
		}
		else if (ChocoGeo::PROJECTION == "Equirectangular") {
			return $this->latlongToXYEquirectangular($lat, $lng);
		}
		else {
			die("Fatal: undefined/unsupported PROJECTION in ChocoGeo\n");
		}
	}

	public function latlongToXYMercator($lat, $lng) {
		// Mercator projection
		$longitude_shift = 0;
		//$y_shift = 75; // aesthetic re-centering for icbmf 2.0
		$y_shift = 108; // aesthetic re-centering for icbmf 2.0
		$pi = 3.14159265;

		// longitude: just scale and shift
		$x = ($this->_width * (180 + $lng) / 360) % $this->_width + $longitude_shift;

		$lat = $lat * $pi / 180;
		// convert from degrees to radians
		$y = log(tan(($lat/2) + ($pi/4)));

		$y = ($this->_height / 2) - ($this->_width * $y / (2 * $pi));
		// fit it to our map

		$y = $y + $y_shift;
		return array($x, $y);
	}

	public function latlongToXYEquirectangular($lat, $lng) {
		// Equirectangular projection
		$x = (($this->_width/360.0) * (180 + $lng));
		$y = (($this->_height/180.0) * (90 - $lat));

		$y_shift = 0;
		/*
		$longitude_shift = 0;
		$y_shift = 75; // aesthetic re-centering for icbmf 2.0
		*/
		//$y_shift = -25;

		return array($x, $y + $y_shift);
	}

	public function getWidth() {
		return $this->_width;
	}

	public function getHeight() {
		return $this->_height;
	}

}
