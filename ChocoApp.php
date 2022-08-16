<?php
require_once dirname(__FILE__) . "/ChocoPathDb.php";
require_once dirname(__FILE__) . "/ChocoRenderer.php"; 
// --------------------------------------------

ini_set('memory_limit','600M');

class ChocoApp {

    /**
     * @var $pathDb ChocoPathDb
     */
    public $pathDb;

	private $_jan_1_2005 = 1104566400;
	private $_nov_15_2011 = 1321344000;

	private function _getBackgroundImage($path) {

	// if null or empty $path, make a new transparent png for the background
	if (is_null($path) || strlen($path) == 0) {

		// HD = 1920 x 1080
		// make it double-sized so we can get cheap anti-aliasing by resizing/resampling it to 1920x1080
		$width = 2880;
		$height = 1594;
//		$width = 2000;
//		$height = 1107;
		
		$img = imagecreatetruecolor($width, $height);
		imagesavealpha($img, true);

		$trans_color = imagecolorallocatealpha($img, 0, 0, 0, 127);
		imagefill($img, 0, 0, $trans_color);

		return array($img, $width, $height);
	}
	
	else { // try to use the provided $path

		if (preg_match('#[0-9]+x[0-9]+#',$path)) {
			list($width,$height) = explode('x',$path);
			$img = imagecreatetruecolor($width,$height);
		} elseif (file_exists($path)) {
			$img = imagecreatefrompng($path);
			$width = imagesx($img);
			$height = imagesy($img);
		} else {
			throw new Exception("Path $path not a proper image");
		}
		return array($img,$width,$height);
	}

	}

	/**
	 * save the rendered frame
	 * @param  $frame	frame number
	 * @param  $img		the image we've been writing everything into
	 * @param  $outdir	where to write the image.  frames are saved to out/$outdir for simple file management
	 * @return $outfile 	string - path to the image
	 */
	private function _writeFrameImage($frame,&$img,$outdir) {
		if ( ! is_dir("out/$outdir") ) { mkdir("out/$outdir"); }
		$fn = ChocoRenderer::relativeFn("out/$outdir/%04d.png");
		$outfile = sprintf($fn,$frame);
		echo $outfile . "\n";

		imagealphablending($img, false);
		imagesavealpha($img, true);
		$trans_layer_overlay = imagecolorallocatealpha($img, 220, 220, 220, 127);
		imagefill($img, 0, 0, $trans_layer_overlay);

		$img_final = $this->_resizeFrameImage($img);
		imagepng($img_final, $outfile);

		imagedestroy($img);
		imagedestroy($img_final);
		return $outfile;
	}

	private function _resizeFrameImage($img) {
		$width = imagesx($img);
		$height = imagesy($img);
		$resampled_img = imagecreatetruecolor(ChocoRenderer::FINAL_FRAME_WIDTH, ChocoRenderer::FINAL_FRAME_HEIGHT);

		// prep it like the source $img
		imagealphablending($resampled_img, false);
		imagesavealpha($resampled_img, true);
		$trans_layer_overlay = imagecolorallocatealpha($resampled_img, 220, 220, 220, 127);
		imagefill($resampled_img, 0, 0, $trans_layer_overlay);

		imagecopyresampled($resampled_img, $img, 0, 0, 0, 0, ChocoRenderer::FINAL_FRAME_WIDTH, ChocoRenderer::FINAL_FRAME_HEIGHT, $width, $height);

		return $resampled_img;
	}

	private function _initPathDb() {
		if (empty($this->pathDb)) {
			$this->pathDb = new ChocoPathDb();
			$this->pathDb->load();
		}
	}
	public function doFrame($path,$nFrames,$frame,$filenameOffset=1, $callback = "bezierPath", $filter_id, $anim_style, $what_to_render, $alpha, $debug) {

		$this->_initPathDb();
		list($img,$width,$height) = $this->_getBackgroundImage($path);
		$r = new ChocoRenderer();
		$r->initGeo($width,$height);
		//$r->initTime($this->pathDb->getMinTime(), $this->pathDb->getMaxTime(), $nFrames);
		$r->initTime(ChocoTime::START_TIME, ChocoTime::END_TIME, $nFrames);
		$r->drawFrame($frame, $img, $this->pathDb, $callback, $filter_id, $anim_style, $what_to_render, $alpha, $debug);

		// special case -- HeatMap data is generated/aggregated in ChocoRender, but needs to be rendered here
		if ($anim_style == "HeatMap") {
			switch ($what_to_render) {

				case "lenders":
					$this->drawLenderHeatMap($img, $GLOBALS['lender_heat_map']);
					break;

				case "borrowers":
					$this->drawBorrowerFrothMap($img, $r, $GLOBALS['borrower_froth_map']);
					break;
			}
		}

		// reset after every frame
		$GLOBALS['borrowers_already_drawn'] = Array();
		$GLOBALS['lenders_already_drawn'] = Array();
		$GLOBALS['lender_heat_map'] = Array();
		$GLOBALS['borrower_froth_map'] = Array();

		$outdir = $anim_style . "_" . $what_to_render;
		if ( ! is_null($filter_id)) { $outdir .= "_$filter_id"; }
		return $this->_writeFrameImage($frame+$filenameOffset,$img, $outdir);
	}

	public function drawBorrowerFrothMap(&$img, &$r, $matrix) {
	// Froth map renders only the top-most data point on a specific x,y coordinate.
	// Rendering every single borrower in the other anim_styles takes waaaay too much time; this is a suitable
	// optimization that works fine visually.  To avoid animation jumpiness, the data needs to be ordered in
	// a way that the first result changes infrequently.  We're fudging this by hacking the ChocoPathDb sql statement
	// to order-by the loan's start_ts (born timestamp)

		// $matrix is an array with:
		// 	key = "x y" (where x and y are coordinates) 
		//	value = "$frame $born $dies $borrower_r $borrower_g $borrower_b"

		foreach ($matrix as $coords=>$values) {
			$xy = explode(" ", $coords);
			$x = $xy[0];
			$y = $xy[1];

			$value = explode(" ", $values);
			$frame = $value[0];
			$born = $value[1];
			$dies = $value[2];
			$borrower_r = $value[3];
			$borrower_g = $value[4];
			$borrower_b = $value[5];

			$alpha = 25; // consistent with other borrower renders
			$color = imagecolorallocatealpha($img, $borrower_r, $borrower_g, $borrower_b, $alpha);
			$r->drawBorrower($img, $x, $y, $frame, $born, $dies, $color);
		}

	}

	public function drawLenderHeatMap(&$img, $matrix) {

		// $matrix is an array with key = "x y" (where x and y are coordinates) and value = weight at that coordinate

		$min_alpha = 1;
		$max_alpha = 100;

		$size = 5;
		asort($matrix);
		$keys = array_keys($matrix);
		$min_value = $matrix[$keys[0]];
		$max_value = $matrix[$keys[sizeof($matrix) - 1]];
		$value_range = $max_value - $min_value + 1;
		imagesetthickness($img, 3);

		echo "$min_value through $max_value \n";

		foreach ($matrix as $coords=>$count) {
			$alpha = $max_alpha - intval($count / $value_range * ($max_alpha - $min_alpha));
			$color = imagecolorallocatealpha($img, 80, 80, 255, $alpha);
			$xy = explode(" ", $coords);
			$x = $xy[0];
			$y = $xy[1];
			imagerectangle($img,$x - $size, $y - $size, $x + $size, $y + $size, $color);
			echo "drew $alpha at $x , $y \n";
		}

	}

	public function drawLatLongTest() {
	// test lat/long functions by plotting several coastal/border cities

//	list($img,$width,$height) = $this->_getBackgroundImage( ChocoRenderer::relativeFn('img/equirectangular.png') );
//	list($img,$width,$height) = $this->_getBackgroundImage( ChocoRenderer::relativeFn('img/5000cropped_bg.png') );
//	list($img,$width,$height) = $this->_getBackgroundImage( ChocoRenderer::relativeFn('img/0001.png') );
	list($img,$width,$height) = $this->_getBackgroundImage("" );
	$red = imagecolorallocatealpha($img, 255, 0, 0, 75);
	$blue = imagecolorallocatealpha($img, 50, 50, 255, 10);

	 $r = new ChocoRenderer();
	$r->initGeo($width,$height);
	$test_cities = 	array( array( 37.7749295, -122.4194155 ) , // San Francisco
			array( 24.5610008239746, -81.7789001464844 ), // Key West FL
			array( 9.913668, -84.039017 ), // San Jose CR
			array( 18.4663338, -66.1057217 ), // San Juan, PR
			array( 27.506407, -99.5075421 ), // Laredo, TX
			array( 19.6931991577148, -155.089996337891 ), // Hilo, HI
			array( 32.7591018676758, 129.865997314453 ), // Nagasaki
			array( -34.92577, 138.599732), // Adelaide
			array( -14.27933, -170.700897 ) // Pago Pago, Samoa
	);

	foreach ($test_cities as $city) {
		list($test_x, $test_y) = $r->geo->latlongToXY($city[0],$city[1]);
		$r->drawPoint($img, $test_x, $test_y, $red, 10);
		$r->drawPoint($img, $test_x, $test_y, $blue, 1);
	}

	imagepng($img,ChocoRenderer::relativeFn('latlongtest.png'));
	imagedestroy($img);
	}


    public function drawFullPath($callBack = 'wigglePath') {
        $this->_initPathDb();
        $paths = $this->pathDb->getPaths(null,null,null,1,0);
        foreach($paths as $path) { break; }

        list($img,$width,$height) = $this->_getBackgroundImage( ChocoRenderer::relativeFn('img/clean_mercator_2000.png') );
        $r = new ChocoRenderer();
        $r->initGeo($width,$height);
        $r->initTime($path[ChocoPathDb::START_TS], $path[ChocoPathDb::END_TS], $nFrames = 1000);

	for($i = 1; $i <= $nFrames; $i++) {
            $r->drawPath($path,$i,$img,$callBack);
        }
        imagepng($img,$callBack . '.png');
        imagedestroy($img);
    }

    public function drawActors($actors = "both", $start_ts = null, $end_ts = null) {
		// draw all borrowers, lenders, or both

		list($img,$width,$height) = $this->_getBackgroundImage( ChocoRenderer::relativeFn('img/pc2.png') );
		$borrower_color_full = imagecolorallocatealpha($img, 255, 0, 0,15);
		$borrower_color_half = imagecolorallocatealpha($img, 255, 0, 0,64);
		$lender_color_full = imagecolorallocatealpha($img, 0, 0, 255,15);
		$lender_color_half = imagecolorallocatealpha($img, 0, 0, 255,64);

        $r = new ChocoRenderer();
        $r->initGeo($width,$height);

		$this->_initPathDb();

		$total_rows = 0;
		$offset = 60000;
		$start = $this->pathDb->getMinTime();
		$end = $this->pathDb->getMaxTime();
		$n_rows = $this->pathDb->getPathCount($start, $end, null);
		print_r("start end = $start $end");

		for ($i = 0; $i <= $n_rows; $i = $i + $offset) {

        	$paths = $this->pathDb->getPaths(null,null,null,$offset, $i + $offset);

			foreach($paths as $path) {
				$borrower_lat = $path[ChocoPathDB::BORROWER_LAT];
				$borrower_lon = $path[ChocoPathDB::BORROWER_LON];
				$lender_lat = $path[ChocoPathDB::LENDER_LAT];
				$lender_lon = $path[ChocoPathDB::LENDER_LON];

				list($borrower_x, $borrower_y) = $r->geo->latlongToXY($borrower_lat,$borrower_lon);
				list($lender_x, $lender_y) = $r->geo->latlongToXY($lender_lat,$lender_lon);

				if ($actors == "both") {
					$r->drawPoint($img, $lender_x, $lender_y, $lender_color_half, 1);
					$r->drawPoint($img, $borrower_x, $borrower_y, $borrower_color_half, 1);
				}
				else if ($actors == "borrowers") {
					$r->drawPoint($img, $borrower_x, $borrower_y, $borrower_color_full, 1);
				}
				else if ($actors == "lenders") {
					$r->drawPoint($img, $lender_x, $lender_y, $lender_color_full, 1);
				}

				$total_rows++;
			}
		}


        // $r->initTime($path[ChocoPathDb::START_TS], $path[ChocoPathDb::END_TS], $nFrames = 1000);
		print_r("nrows = $n_rows , total rows = $total_rows\n");
        imagepng($img,ChocoRenderer::relativeFn('actors.png'));
        imagedestroy($img);
    }


    public function drawHeightMap() {
		// draw heightmap (for bryce, etc.) based on data file of lat/longs + counts

		list($img,$width,$height) = $this->_getBackgroundImage( ChocoRenderer::relativeFn('img/clean_mercator.png') );
		$max = 144871;

        $r = new ChocoRenderer();
        $r->initGeo($width,$height);

		for ($i = 0; $i < 10000; $i++) {
			$ll_height = $file[1];
			$data_height = 1 + ($ll_height / $max) * 254;
			$height_color = imagecolorallocate($img, $data_height, $data_height, $data_height);
			list($x, $y) = $r->geo->latlongToXY($lat, $lng);

			$r->drawPoint($img, $x, $y, $height_color, 1);
		}

		print_r("done.\n");
        imagepng($img,ChocoRenderer::relativeFn('heightmap.png'));
        imagedestroy($img);
	}
}



