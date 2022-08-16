<?php

require_once(dirname(__FILE__)."/ChocoPathDb.php");
require_once(dirname(__FILE__)."/ChocoTime.php");
require_once(dirname(__FILE__)."/ChocoGeo.php");



// initialize the array that keeps track of which borrower & lender points were already drawn in a frame
$GLOBALS['borrowers_already_drawn'] = Array();
$GLOBALS['lenders_already_drawn'] = Array();

// init the array that tracks points & weights for the HeatMap view
$GLOBALS['lender_heat_map'] = Array();
$GLOBALS['borrower_froth_map'] = Array();

class ChocoRenderer {

const FINAL_FRAME_WIDTH  = 2000; 
const FINAL_FRAME_HEIGHT =  1107; 
	
	public static function relativeFn($fn) {
		$fn = dirname(__FILE__) . '/' . $fn;
		if (!file_exists(dirname($fn))) {
			mkdir(dirname($fn),0777,true);
		}
		return $fn;
	}
	
    /**
     * @var $_colors_by_sector Array
     */
	private $_colors_by_sector = array( 	array(255,255,255), 	// 0 - not used
						array(0, 221, 0), 	// 1 green AGRICULTURE  63683
						array(255,255,255), 	// 2 - not used
						array(207, 156, 255), 	// 3  lavender  TRANSPORTATION  10919
						array(255,153,0),	// 4 orange  SERVICES  26414
						array(194, 0, 255),	// 5 purple  CLOTHING  23556
						array(255,255,255),	// 6 white  HEALTH  23556
						array(255,0,0),		// 7 red  RETAIL  77609
						array(173, 255, 47),	// 8 yellow green  MANUFACTURING  4818
						array(0, 255, 255),	// 9  cyan ARTS 7724
						array(153, 204, 255), 	// 10 light bluegrey HOUSING  8311
						array(255,255,255),	// 11 not used
						array(255,255,0),	// 12 yellow FOOD  87776
						array(220,220,220),	// 13  grey  WHOLESALE  875
						array(102, 153, 255), 	// 14 med blue  CONSTRUCTION  7209
						array(255, 0, 151),	// 15 pink  EDUCATION  1664
						array(0, 188, 186),	// 16 teal  PERSONAL USE  2570
						array(255,255,255),	// 17 grey ENTERTAINMENT  516 // LAST ORIGINAL ELEMENT
						array(255,255,255), 	// 18 - not used  // 18-30 are ZIP placeholders only
						array(255,255,255), 	// 19 - not used
						array(255,255,255), 	// 20 - not used
						array(102, 153, 255), 	// 21 - not used
						array(255,255,255), 	// 22 - not used
						array(173, 255, 47), 	// 23 - not used
						array(255,255,255), 	// 24 - not used
						array(255,255,255), 	// 25 - not used
						array(255,255,255), 	// 26 - not used
						array(255,255,0), 	// 27 - not used
						array(255,255,255), 	// 28 - not used
						array(255,255,255), 	// 29 - not used
						array(255,255,255)); 	// 30- not used
    /**
     * @var $_colors_by_business Array
     */
	private $_colors_by_business = array( 	array(0,0,255),
						array(181,255,0),
						array(0,159,255),
						array(128,255,0),
						array(0,255,244),
						array(234,255,0),
						array(255,0,43),
						array(255,11,0),
						array(255,223,0),
						array(0,255,191),
						array(0,255,32),
						array(21,255,0),
						array(255,0,149),
						array(74,255,0),
						array(0,106,255),
						array(0,255,138),
						array(255,0,96),
						array(0,255,85),
						array(255,117,0),
						array(0,213,255),
						array(255,170,0),
						array(255,0,202),
						array(0,53,255),
						array(255,64,0),
						array(255,0,255));

	private $_palette_white = array( 	array( 255, 255, 255)); // this is used to render all-white objects

	private $_palette_gold = array( 	array( 255, 250, 205),
						array( 255, 255, 0),
						array( 255, 239, 0),
						array( 255, 211, 0),
						array( 239, 204, 0),
						array( 254, 223, 0),
						array( 255, 255, 102),
						array( 252, 232, 131));

	private $_palette_green = array( 	array( 118, 255, 122),
						array( 80, 200, 120),
						array( 39, 255, 20),
						array( 11, 218, 81),
						array( 76, 187, 23));

	private $_palette_purple = array( 	array( 154, 78, 174),
						array( 183, 104, 162),
						array( 197, 75, 140),
						array( 223, 0, 255),
						array( 147, 112, 219),
						array( 160, 32, 240),
						array( 147, 112, 219),
						array( 218, 112, 214));

	private $_palette_teal = array( 	array( 0, 255, 255),
						array( 64, 224, 208),
						array( 28, 169, 201),
						array( 33, 182, 168),
						array( 112,219,219));
	private $_palette_chartreuse = array(	array( 127, 255, 0),
						array( 223, 255, 0),
						array( 227, 249, 136),
						array( 227, 255, 0),
						array( 191, 255, 0));

	private $_palette_orange = array(	array( 255, 165, 0),
						array( 255, 117, 56),
						array( 255, 153, 102),
						array( 245, 128, 37));

	private $_palette_pink = array(		array( 255, 105, 180),
						array( 255, 20, 147),
						array( 255, 145, 175),
						array( 222, 111, 161),
						array( 247, 127, 190));

	private $_palette_azure = array(	array( 135, 206, 250),
						array( 0, 191, 255),
						array( 69, 177, 232),
						array( 30, 144, 255));

	private $_palette_magenta = array(      array( 255, 0, 144),
						array( 255, 0, 255),
						array( 204, 51, 204),
						array( 202, 31, 123));

	private $_palette_grays = array(	array( 255, 255, 255),
						array( 240, 240, 240),
						array( 225, 225, 225),
						array( 210, 210, 210),
						array( 195, 195, 195));

	private $_palette_rainbow = array( 	array(255, 255, 84), 	// 0 - yellow
						array(200, 230, 76), 	// 1 lime green 
						array(140, 212, 70), 	// 2 bright green
						array(77, 199, 66), 	// 3 green
						array(69, 210, 176), 	// 4 teal
						array(70, 172, 211), 	// 5 light blue
						array(67, 140, 203), 	// 6 blue
						array(66, 98, 199), 	// 7 deep blue
						array(82, 64, 195), 	// 8 indigo
						array(140, 63, 192), 	// 9 purple
						array(209, 69, 193), 	// 10 pink
						array(230, 76, 141), 	// 11 fuschia
						array(255, 84, 84), 	// 12 red
						array(255, 128, 84), 	// 13 orange-red
						array(255, 160, 84), 	// 14 orange
						array(255, 181, 84)); 	// 15 orange 2

        private $_palette_rainbow2 = array(     array(0, 255, 0),       // 1 green AGRICULTURE  63683
                                                array(207, 156, 255),   // 3  lavender  TRANSPORTATION  10919
                                                array(255,153,0),       // 4 orange  SERVICES  26414
                                                array(194, 0, 255),     // 5 purple  CLOTHING  23556
                                                array(255,50,50),         // 7 red  RETAIL  77609
                                                array(173, 255, 47),    // 8 yellow green  MANUFACTURING  4818
                                                array(0, 255, 255),     // 9  cyan ARTS 7724
                                                array(153, 204, 255),   // 10 light bluegrey HOUSING  8311
                                                array(255,255,0),       // 12 yellow FOOD  87776
                                                array(102, 153, 255),   // 14 med blue  CONSTRUCTION  7209
                                                array(255, 0, 151));     // 15 pink  EDUCATION  1664

    /**
     * @var $time ChocoTime
     */
    public $time;
    /**
     * @var $geo ChocoGeo
     */
    public $geo;

	private $_timeline_img;

	private $_legend_img;

	public function initTime($start_ts, $end_ts, $n_frames) {
		$this->time = new ChocoTime($start_ts,$end_ts,$n_frames);
	}
	public function initGeo($width, $height) {
		$this->geo = new ChocoGeo($width, $height);
	}

    public function drawPoint(&$img,$x,$y,$color,$radius=0) {
        imagefilledrectangle($img,$x-$radius,$y-$radius,$x+$radius,$y+$radius,$color);
    }

    public function drawCircle(&$img,$x,$y,$color,$radius=0,$radius2=0) {
	$black=imagecolorallocatealpha($img, 20,20,20, 80);
	$white=imagecolorallocatealpha($img, 255,255,255, 70);
        //imagefilledarc($img, $x, $y, $radius, $radius, 0, 360, $color, IMG_ARC_PIE);

	// are we drawing a cirlce with a big border?
	if ($radius2 > 0) {
	        imagefilledellipse($img, $x, $y, $radius2, $radius2, $color );
	        //imagefilledellipse($img, $x, $y, $radius, $radius, $color );
	}
	else {
	        imagefilledellipse($img, $x, $y, $radius, $radius, $color );
	        imageellipse($img, $x, $y, $radius, $radius, $white );
	}
    }

    private function drawTrail(&$img,$x1,$y1,$x2,$y2,$color,$radius=0) {

	if ($radius > 0) { $thick = $radius; } else { $thick = 3; }

	if ($thick == 1) {
		return imageline($img, $x1, $y1, $x2, $y2, $color);
	}
//		$thick = 5;
	$t = $thick / 2 - 0.5;

	if ($x1 == $x2 || $y1 == $y2) {
//		imagefilledrectangle($img, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
		return;
	}

	imagesetthickness($img, $thick);
	imageline( $img , $x1 , $y1 , $x2 , $y2 , $color );
        // imagefilledrectangle($img,$x-$radius,$y-$radius,$x+$radius,$y+$radius,$color);
	}

	private function getPreviousFrame($frame) {
		$nFrame = $this->time->getFrameCount();
		$midPoint = ceil(1.0 + $nFrame / 2.0);
		if ($frame >= $midPoint) {
			return max($frame-2,$midPoint);
		} else {
			return max($frame-2,1);
		}

	}

	/* keeps track of borrower points that have already been drawn in the frame
	 *
	 * @param $kiva_id	business ID to check
	 * @returns boolean
	*/
	private function _borrowerAlreadyDrawn($kiva_id) {
		if ( in_array($kiva_id, $GLOBALS['borrowers_already_drawn']) ) {
			return true;
		}
		else {
			$GLOBALS['borrowers_already_drawn'][] = $kiva_id;
			return false;
		}
	}


	/* keeps track of lend points that have already been drawn in the frame
	 *
	 * @param $rfaid	repayment fund account ID to check
	 * @returns boolean
	*/
	private function _lenderAlreadyDrawn($rfaid) {
		if ( in_array($rfaid, $GLOBALS['lenders_already_drawn']) ) {
			return true;
		}
		else {
			$GLOBALS['lenders_already_drawn'][] = $rfaid;
			return false;
		}
	}


	/**
	 * draw individual particle paths 
	 * @param  $path
	 * @param  $frame
	 * @param  $gd
	 * @return void
	 */
	public function drawPath($path, $frame, &$img, $callBack = 'linePath', $nTotalPaths = 100, $anim_style, $what_to_render, $alpha) {

		// from $path, get $orig & $dest
		$lat_dest = $path[ChocoPathDb::BORROWER_LAT];
		$lng_dest = $path[ChocoPathDb::BORROWER_LON];
		$lat_orig = $path[ChocoPathDb::LENDER_LAT];
		$lng_orig = $path[ChocoPathDb::LENDER_LON];
		$born = $path[ChocoPathDb::START_TS];
		$dies = $path[ChocoPathDb::END_TS];
		$kiva_id = $path[ChocoPathDB::KIVA_ID];
		$sector_id = $path[ChocoPathDB::SECTOR_ID];
		$team_id = $path[ChocoPathDB::TEAM_ID];
		$share_price = $path[ChocoPathDB::SHARE_PRICE];
		$loan_price = $path[ChocoPathDB::LOAN_PRICE];
		$gender = $path[ChocoPathDB::GENDER];
		$rfaid = $path[ChocoPathDB::RFAID]; // repayment fund account id of lender (can be used as a unique lender ref)

		$unique_id = 0; // for global -- don't affect loan arc with unique id
		if ( $GLOBALS['loan_select'] == 'local' ) {
			$unique_id = $rfaid + $kiva_id;
		}

		// skip data aberrations -- loans whose duration is obviously too short
		if (($dies - $born) < ChocoTime::MIN_LOAN_LIFESPAN) { return; }

		// convert lat/lng to x,y
		list($x_dest, $y_dest) = $this->geo->latlongToXY($lat_dest, $lng_dest);
		list($x_orig, $y_orig) = $this->geo->latlongToXY($lat_orig, $lng_orig);

		//calculate current point of path

		// echo(sprintf("FRAME(PREV): %s(%s)",$frame,$this->getPreviousFrame($frame)));
		list($x, $y) = $this->$callBack($x_orig, $y_orig, $x_dest, $y_dest, $frame, $born, $dies, $unique_id);
		list($x2, $y2) = $this->$callBack($x_orig, $y_orig, $x_dest, $y_dest, $frame - 1, $born, $dies, $unique_id);

		if ($anim_style <> 'Trails') {
			// make sure trail isn't too long
			// @todo: does this matter any more?  
			 list($x2, $y2) = $this->trimTrail($img, $x, $y, $x2, $y2, 5);
		}

		switch ($anim_style) {

			case "TeamTrace":
				// make borrowers sector-colored
				list($borrower_r, $borrower_g, $borrower_b) = $this->getColor("by_sector", $sector_id);
				$path_color = imagecolorallocatealpha($img, $loan_r, $loan_g, $loan_b, $alpha);
				$borrower_color = imagecolorallocatealpha($img, $borrower_r, $borrower_g, $borrower_b, $alpha);
				$white = imagecolorallocatealpha($img, 255, 255, 255, $alpha);
				$lender_color = imagecolorallocatealpha($img, 255, 255, 255, $alpha);

				switch ($what_to_render) {

					case "lenders":
						if ( ! $this->_lenderAlreadyDrawn($rfaid) ) {
							// we only want to draw each lender once for proper alpha aggregation
							$this->drawLender($img,$x_orig,$y_orig,$frame,$born,$dies, $lender_color);
						}
						break;

					case "borrowers":
						if ( ! $this->_borrowerAlreadyDrawn($kiva_id) ) {
							// we only want to draw each borrower once for proper alpha aggregation
							$this->drawBorrower($img,$x_dest,$y_dest,$frame,$born,$dies, $white);
							//echo "drew $kiva_id \n";
						}
						break;

					case "loans":
						$this->drawTrail($img,$x,$y,$x2,$y2,$path_color,0);
						break;
				}


				break;
			
			case "ColorBySector":


				if ( $GLOBALS['sector_colors'] == true ) {
					// get color based on loan's sector
					list($loan_r, $loan_g, $loan_b) = $this->getColor("by_sector", $sector_id);
					list($borrower_r, $borrower_g, $borrower_b) = $this->getColor("by_sector", $sector_id);

/*
					$color = $this->getPaletteColor($GLOBALS['palette'], $kiva_id);	
					$borrower_r = $color[0];
					$borrower_g = $color[1];
					$borrower_b = $color[2];
					$loan_r = $color[0];
					$loan_g = $color[1];
					$loan_b = $color[2];
*/
				} else {
// @todo fix for palettes
					// color = black because this layer will be post-processed in after effects
					$loan_r = $loan_g = $loan_b = $borrower_r = $borrower_g = $borrower_b = 0;
					//$loan_r = $loan_g = $loan_b = $borrower_r = $borrower_g = $borrower_b = 0;
				}

/*
				$color = $this->getPaletteColor($GLOBALS['palette'], $kiva_id);	
				$borrower_r = $color[0];
				$borrower_g = $color[1];
				$borrower_b = $color[2];
				$loan_r = $color[0];
				$loan_g = $color[1];
				$loan_b = $color[2];
*/

				$path_color = imagecolorallocatealpha($img, $loan_r, $loan_g, $loan_b, $alpha);
				$white_color = imagecolorallocatealpha($img, 255, 255, 255, $alpha);
				$borrower_color = imagecolorallocatealpha($img, $borrower_r, $borrower_g, $borrower_b, $alpha);
				$lender_color = imagecolorallocatealpha($img, 0, 0, 255, $alpha);

				switch ($what_to_render) {

					case "lenders":
						if ( ! $this->_lenderAlreadyDrawn($rfaid) ) {
							// we only want to draw each lender once for proper alpha aggregation
							$this->drawLender($img,$x_orig,$y_orig,$frame,$born,$dies, $lender_color);
						}
						break;

					case "borrowers":
						if ( ! $this->_borrowerAlreadyDrawn($kiva_id) ) {
							// we only want to draw each borrower once for proper alpha aggregation
							$this->drawBorrower($img,$x_dest,$y_dest,$frame,$born,$dies, $borrower_color, $loan_price);
							//echo "drew $kiva_id \n";
						}
						break;

					case "loans":
						$this->drawTrail($img,$x,$y,$x2,$y2,$path_color,3); 
						break;
				}

				break;

			case "Trails":

				if ( $GLOBALS['sector_colors'] == true ) {
					// get color based on loan's sector
					list($loan_r, $loan_g, $loan_b) = $this->getColor("by_sector", $sector_id);
					list($borrower_r, $borrower_g, $borrower_b) = $this->getColor("by_sector", $sector_id);
				} else  { // we must have a palette
					$color = $this->getPaletteColor($GLOBALS['palette'], $kiva_id);	
					$borrower_r = $color[0];
					$borrower_g = $color[1];
					$borrower_b = $color[2];
					$loan_r = $color[0];
					$loan_g = $color[1];
					$loan_b = $color[2];
				}
					
				switch ($what_to_render) {

					case "loans":
						$path_color = imagecolorallocatealpha($img, $loan_r, $loan_g, $loan_b, $alpha);

						// min alpha color for end of trail should at least be visible -- 96
						$min_alpha = 96;
						$outline_min_alpha = $GLOBALS['trail_outline_min_alpha'];

						$alpha_spread = $min_alpha - $alpha;
						//$outline_alpha_spread = 120 - $outline_min_alpha;
						$outline_alpha_spread = 100 - $outline_min_alpha;

						$segments = $GLOBALS['trail_length'];
						$thickness = $GLOBALS['trail_thickness'];

						$tx[0] = $x;
						$ty[0] = $y;
						$t_alpha_outline[0] =  $outline_min_alpha;
						$t_alpha_trail[0] = $alpha;
						
						// calculate & draw trail segments.  the first segment is set to the current alpha,
						// and the second etc. ones are set to increasing alpha values, up to $min_alpha
						// note we are drawing the trail twice -- once as a black alpha outline, and once in 
						// the specified color.  this helps to make the individual loans distinct from eachother
						// when things are going crazy.

						for ($i = 1; $i <= $segments; $i++) {
							list($tx[$i], $ty[$i]) = $this->$callBack($x_orig, $y_orig, $x_dest, $y_dest, ($frame - $i), $born, $dies, $unique_id);
							$t_alpha_outline[$i] = min(120, $outline_min_alpha + $i * $outline_alpha_spread / $segments); 
							$t_alpha_trail[$i] = min($min_alpha, round($i * $alpha_spread / ($segments - 1)));

							// draw outline 
							$segment_thickness = $thickness;
							//$outline_color = imagecolorallocatealpha($img, 0, 0, 0, $t_alpha_outline[$i - 1]);  // black outlines
							$outline_color = imagecolorallocatealpha($img, 255, 255, 255, $t_alpha_outline[$i - 1]); // white outlines
							if ($i > 2 || $segments <= 3) { $segment_thickness = max(2, $thickness - $i + 1); } 

							$this->drawTrail($img,$tx[$i-1],$ty[$i-1],$tx[$i],$ty[$i],$outline_color,$segment_thickness); 

							// draw trail
							//$trail_color = imagecolorallocatealpha($img, $loan_r, $loan_g, $loan_b, $t_alpha_trail[$i - 1]);

							$trail_thickness = $segment_thickness - 1; // one pixel narrower, so outline is a halo

							$white = imagecolorallocatealpha($img, 255, 255, 255, $alpha);
							$orange = imagecolorallocatealpha($img, 255, 153, 0, $alpha);
							$black = imagecolorallocatealpha($img, 0, 0, 0, 1);

							$trail_color = imagecolorallocatealpha($img, $loan_r, $loan_g, $loan_b, $t_alpha_trail[$i - 1]);


							$this->drawTrail($img,$tx[$i-1],$ty[$i-1],$tx[$i],$ty[$i],$trail_color,$trail_thickness); 

						}

					break;

					case "borrowers":
						if ( ! $this->_borrowerAlreadyDrawn($kiva_id) ) {

							$borrower_color = imagecolorallocatealpha($img, $borrower_r, $borrower_g, $borrower_b, $alpha);

							$this->drawBorrower($img,$x_dest,$y_dest,$frame,$born,$dies, $borrower_color, $loan_price);
						}
						break;

					case "lenders":
						if ( ! $this->_lenderAlreadyDrawn($rfaid) ) {
							// we only want to draw each lender once for proper alpha aggregation
							$this->drawLender($img,$x_orig,$y_orig,$frame,$born,$dies, $lender_color);
						}
						break;

					}

				break;


			case "HeatMap":

				switch ($what_to_render) {

					case "lenders":
						$this->addToLenderHeatmap($x_orig, $y_orig);
						break;

					case "borrowers":
						list($borrower_r, $borrower_g, $borrower_b) = $this->getColor("by_sector", $sector_id);
						$this->addToBorrowerFrothmap($x_dest, $y_dest, $frame, $born, $dies, $borrower_r, $borrower_g, $borrower_b);
						break;
				}

				break;


			case "LoanTrace":
				// get color based on modulus biz id; if it's a biz we're highlighting, assign color; otherwise color = white
				// examples of useful/cinematic borrowers
				//$biz_tracked = array(33312);
				//$biz_tracked = array(473908);
				//$biz_tracked[] = 213100;
				//$biz_tracked[] = 47663;
				//$biz_tracked[] = 125140;
				// 33312 starts at 1202428800
				// 117772 starts at 1249110006
				// 82140 starts at 1230937809
				// other trackable ids: 117772, 82140

				$biz_tracked = array();

				$highlight = false;

				if (in_array($kiva_id, $biz_tracked)) {
					// list($loan_r, $loan_g, $loan_b) = $this->getColor("by_business", ($kiva_id % 25));
					$loan_r = 255; // 0
					$loan_g = 255; //220
					$loan_b = 255; //0

					$borrower_r = $loan_r;
					$borrower_g = $loan_g;
					$borrower_b = $loan_b;

					// should be able to delete everything above 
					$alpha = 20;
					$highlight = true;
				}
				else {
					list($frame_time_start,$frame_time_end) = $this->time->getFrameBoundaries($frame);
					$now = ($frame_time_start + $frame_time_end) / 2;
					$loan_midpoint = ($born + $dies) / 2;
					$loan_r = $loan_g = $loan_b =  255;

/*
					// figure out if loan is currently going to borrower, or returning to lender -- color appropriately
					if ($now < $loan_midpoint) {
						// it's on the way to the borrower -- make it white
						$loan_r = $loan_g = $loan_b =  255;
					}
					else {
						// it's on its way back to the lender -- make it red
						$loan_r = 255;
						$loan_g = $loan_b = 0;
					}
*/

					$borrower_r = $borrower_g = $borrower_b = 255;
				}

				$path_color = imagecolorallocatealpha($img, $loan_r, $loan_g, $loan_b, $alpha);
				$borrower_color = imagecolorallocatealpha($img, 255,255,255, $alpha);
				$lender_color = imagecolorallocatealpha($img, 255, 255, 255, $alpha);

				switch ($what_to_render) {

					case "lenders":
						if ( ! $this->_lenderAlreadyDrawn($rfaid) ) {
							// we only want to draw each lender once for proper alpha aggregation
							$this->drawLender($img,$x_orig,$y_orig,$frame,$born,$dies, $lender_color);
						}
						break;

					case "borrowers":

						if ( ! $this->_borrowerAlreadyDrawn($kiva_id) ) {

							// we only want to draw each borrower once for proper alpha aggregation
							$this->drawBorrower($img,$x_dest,$y_dest,$frame,$born,$dies, $borrower_color);
							//echo "drew $kiva_id \n";
						}
						break;

					case "loans":
						if ($highlight && !($x == $x_dest && $y == $y_dest)) { // don't draw if we're at the destination
							// draw alpha'd black halo around loan to offset it from potentially-busy background

							$halo1=imagecolorallocatealpha($img, 0,0,0, 95);
							$halo2=imagecolorallocatealpha($img, 0,0,0, 110);
							$this->drawCircle($img, $x, $y, $halo2, 16, 22);
							$this->drawCircle($img, $x, $y, $halo1, 16, 20);

							$black=imagecolorallocatealpha($img, 0,0,0, 0);
							$this->drawCircle($img, $x, $y, $black, 15.5);
							$this->drawCircle($img, $x, $y, $path_color, 15);

						}
						else {
							$this->drawPoint($img, $x, $y, $path_color, 1);
						}
						break;
				}
//			}

				break;


			case "ShareSize":
				// this mode ignores the $what_to_render param -- it just renders loans
				list($step, $steps) = $this->calcStepAndSteps($born, $dies, $frame);


				if ($share_price != 25) { // make all $25 shares small and white
break;
					$r = 6;
					$r2 = 7;
					$loan_r = $loan_g = $loan_b = 255;
					$point_color = imagecolorallocatealpha($img, $loan_r, $loan_g, $loan_b, 10);
					$this->drawPoint($img, $x, $y, $point_color, 1);
					break; // nothing else to do here
				}
				else { 
					list($loan_r, $loan_g, $loan_b) = $this->getColor("by_sector", $sector_id);
					//$loan_r = $loan_g = $loan_b = 255;
					//$r = 6 + $share_price / 15;
					$r = max(4, $share_price / 15);
					$r2 = $r + 1;
				}

					
				$path_color = imagecolorallocatealpha($img, $loan_r, $loan_g, $loan_b, $alpha);
				$p = $step / $steps;

				// for first 10% of loan's lifetime, grow it to its share size
				if ($r > 2 && $p < 0.05) {
					$r = max(1, $r * ($p * 20));
					$r2 = $r + 1;
				}

				// for last 10% of loan's lifetime, shrink it from its share size to 0
				if ($r > 2 && $p > 0.95) {
					$scale = (1.0 - $p) * 20;
					$r = max(1, $r * $scale);
					$r2 = $r + 1;
				}

				$black = imagecolorallocatealpha($img, 0, 0, 0, $alpha);
				//$this->drawCircle($img, $x, $y, $black, $r2);
				$this->drawCircle($img, $x, $y, $path_color, $r);

				break;


			case "ThemeTrace": 
			case "FilterTrace":
				// color = white because this layer will be post-processed in after effects
				$loan_r = $loan_g = $loan_b = $borrower_r = $borrower_g = $borrower_b = 0;
					
				$path_color = imagecolorallocatealpha($img, $loan_r, $loan_g, $loan_b, $alpha);
				$borrower_color = imagecolorallocatealpha($img, $borrower_r, $borrower_g, $borrower_b, $alpha);
				$lender_color = imagecolorallocatealpha($img, 255, 255, 255, $alpha);

				switch ($what_to_render) {

					case "lenders":
						if ( ! $this->_lenderAlreadyDrawn($rfaid) ) {
							// we only want to draw each lender once for proper alpha aggregation
							$this->drawLender($img,$x_orig,$y_orig,$frame,$born,$dies, $lender_color);
						}
						break;

					case "borrowers":
						if ( ! $this->_borrowerAlreadyDrawn($kiva_id) ) {
							// we only want to draw each borrower once for proper alpha aggregation
							$this->drawBorrower($img,$x_dest,$y_dest,$frame,$born,$dies, $borrower_color, $loan_price);
							//echo "drew $kiva_id \n";
						}
						break;

					case "loans":
						$this->drawTrail($img,$x,$y,$x2,$y2,$path_color,0);
						break;
				}

				break;


			case "LoanSize":
				// this mode ignores the $what_to_render param -- it just renders loan size at the borrower's location

				list($step, $steps) = $this->calcStepAndSteps($born, $dies, $frame);
				// $loan_r = $loan_g = $loan_b = $borrower_r = $borrower_g = $borrower_b = 255;
				list($borrower_r, $borrower_g, $borrower_b) = $this->getColor("by_sector", $sector_id);
					
				$path_color = imagecolorallocatealpha($img, $borrower_r, $borrower_g, $brrower_b, $alpha);
				$outline_color = imagecolorallocatealpha($img, 255, 255, 255, $alpha);

				if ( ! $this->_borrowerAlreadyDrawn($kiva_id) ) {
					// we only want to draw the borrower once for proper alpha aggregation

					// this assumes loan prices vary from $25 to $2000
					// $25 = 1, $5000 = 100
					$r = max(1, round($loan_price / 50 + 1)); 
					$r2 = $r + 1;
				
					//$p = ChocoTime::getPercentComplete($born, $dies, $frame);
					$p = $step / $steps;

					if ($r > 75) {
						//$r2 = 50;
						//$r = 50 - $r / 10;
						$r = 75;
						$r2 = $r + 1;
					}

					// for first 10% of loan's lifetime, grow it to its share size
					if ($r > 2 && $p < 0.1) {
						//$scale = (0.1 - $p) * 10;
						$r = max(1, $r * ($p * 10));
						//$r2 = max(1, $r2 * ($p * 10));
						$r2 = $r + 1;
					}

					// for last 10% of loan's lifetime, shrink it from its share size to 0
					if ($r > 2 && $p > 0.9) {
						$scale = (1.0 - $p) * 10;
						$r = min(1, $r * $scale);
						//$r2 = min(2, $r2 * $scale);
						$r2 = $r + 1;
					}
					$dmb = $dies - $born;
					$this->drawCircle($img, $x_dest, $y_dest, $path_color, $r, $r2);
					//$this->drawCircle($img, $x_dest, $y_dest, $outline_color, $r2);
					//$this->drawCircle($img, $x_dest, $y_dest, $path_color, $r);
				}

				break;

			case "ShowBizID":
				// prints business ID at x,y (used for debugging)
				$r = $g = $b = 255;

				$text_color = imagecolorallocatealpha($img, $r, $g, $b, 10);
				$this->drawPoint($img, $x, $y, $path_color, 1);
				imagestring($img, 5, $x, $y, $kiva_id, $text_color);
				break;
		}

	}
	
	public function getPaletteColor($palette, $index) {

		$palette_var = "_palette_" . $palette;
		$palette_length = sizeof($this->$palette_var);

		// auto-mod the index
		$idx = $index % $palette_length;

		$p = $this->$palette_var;
		$color = $p[$idx];

		return array($color[0], $color[1], $color[2]);
	}

	public function getColor($object, $index) {
	
		switch ($object) {
			case "by_sector":
				$color = $this->_colors_by_sector[$index];
				return array($color[0], $color[1], $color[2]);
				break;
			case "by_sector_mod":
				$color = $this->_colors_by_sector[$index % 17];
				return array($color[0], $color[1], $color[2]);
				break;
			case "by_business":
				$color = $this->_colors_by_business[$index];
				return array($color[0], $color[1], $color[2]);
				break;
			case "by_palette":
				$color = $this->_colors_by_palette($index);
				return array($r, $g, $b);
				break;
		}

	}

	public function drawBorrower(&$img, $x, $y, $frame, $born, $dies, $color, $size = 0) {
	// providing $size param will cause borrower to be drawn larger

		$final_size = $GLOBALS['final_borrower_size'];
		$min_size = $GLOBALS['min_borrower_size'];

		// uncomment the two lines below for just plotting the borrower with no animation
		//imagefilledellipse($img, $x, $y, $final_size, $final_size, $color); // borrower size in "resting state"
		//return;

		list($step, $steps) = $this->calcStepAndSteps($born, $dies, $frame);

		// size is based on loan amount; keep it reasonable (100 max) for loans < 20K
		if ($size > 20000) {
			if ($size > 50000) { // this is only the $100K loan
				$size = 175;
			}
			else { // between 20K & 50K
				$size = 150;
			}
		}
		else {
			$size = min(max($min_size, $size / 200), 100);
		}

		if ($step <= 30) { // recently "born", so animate
			$circ_size = (16 - abs($step - 8));
			$borrower_alpha = 50 + $step * 3;
			// ignore $color param
			$borrower_color = imagecolorallocate ( $img , 255, 20, 20 );
			$borrower_color_alpha = imagecolorallocatealpha ( $img , 255, 255, 255, 64 );
			imagerectangle($img,$x-1,$y-1,$x+1,$y+1,$color);

			// $diam = $size - $step * 2;
			$diam = max($final_size, ((901 - $step * $step)/900 * $size) );
			$thickness = 10;

			// extract r g b from color
			$rgb = imagecolorsforindex($img, $color);
			$r = $rgb['red'];
			$g = $rgb['green'];
			$b = $rgb['blue'];

			$fade_color = imagecolorallocatealpha($img, $r, $g, $b, 100 - $step * 3);

			imagefilledellipse($img, $x, $y, max(5, $diam), max(5, $diam), $fade_color);  

		}
		else {
			imagefilledellipse($img, $x, $y, $final_size, $final_size, $color); // borrower size in "resting state"
		}

		// halo
		if ($step <= 45) {
			// max halo is at step == 23 
			$halo_alpha = min(abs($step - 23) * 5.8, 127);
			// $halo_alpha = $step * 14;
			imagesetthickness($img, 3);
			$halo_color = imagecolorallocatealpha($img, 255, 255, 255, $halo_alpha);
			$this->ImageEllipseAA($img, $x, $y, $size + 5, $size + 5, $halo_color);
		}
	}

	public function addToLenderHeatmap($x, $y) {

		$x = round($x);
		$y = round($y);

		$index = $x . " " . $y;
		if ($GLOBALS['lender_heat_map'][$index] > 0) {
			$GLOBALS['lender_heat_map'][$index] = $GLOBALS['lender_heat_map'][$index] + 1;
		}
		else {
			$GLOBALS['lender_heat_map'][$index] = 1;
		}

	}

	public function addToBorrowerFrothmap($x, $y, $frame, $born, $dies, $borrower_r, $borrower_g, $borrower_b) {

		$x = round($x);
		$y = round($y);
		$value = "$frame $born $dies $borrower_r $borrower_g $borrower_b";

		$index = $x . " " . $y;

		// first attempt -- just overwrite whatever's in there for "x y" now with the most recent borrower's data
		$GLOBALS['borrower_froth_map'][$index] = $value;

	}

	public function drawLender(&$img, $x, $y, $frame, $born, $dies, $color) {
		list($step, $steps) = $this->calcStepAndSteps($born, $dies, $frame);

		$final_size = $GLOBALS['lender_size'];
		$thickness = $GLOBALS['lender_thickness'];
		$size= $final_size;
		$min_alpha = $GLOBALS['min_lender_alpha'];

		if ($step <= 20) { // recently "born", so animate

			//$size = $size + (901 - $step * $step)/900 * $size;
			$size = $step / 20 * $size + 2;
			//$lender_alpha = 120 - $step * $alpha_spread / 30;
			$lender_alpha = abs(min($min_alpha/20 * $step + 1, $min_alpha));

		}
		else {
			$lender_alpha = $min_alpha;
			$size = $final_size;
		}

		//$diam = (901 - $step * $step)/900 * $circ_size;
		$lender_color = imagecolorallocatealpha($img, 80, 80, 255, $lender_alpha);

		imagesetthickness($img, $thickness);
		imagerectangle($img,$x - $size, $y - $size, $x + $size, $y + $size, $lender_color);

		// halo
		//if ($step >= 25) {
			$halo_size = $size + 1 + $thickness / 2;
			$halo_alpha = min($lender_alpha + 20, 120);
			imagesetthickness($img, 1);
			$halo_color = imagecolorallocatealpha($img, 255, 255, 255, $halo_alpha);
			imagerectangle($img,$x - $halo_size, $y - $halo_size, $x + $halo_size, $y + $halo_size, $halo_color);
		//}
	}
    
    public function wigglePath($x1, $y1, $x2, $y2, $frame_num, $born, $dies) {
        $pi = 3.1415926535;

        $time = ($frame_num * $this->time->getIntervalPerFrame());
        $tot = $dies - $born;
        $pct = (1.1*$time)/(1.1*$tot);
        $x = $x1 + round( $pct * ($x2-$x1) );
        $y = $y1 + round( sin($pi * $pct * 4.5) * ($y2-$y1) );
        return array($x,$y);
    }

	public function linePath($x1, $y1, $x2, $y2, $frame_num, $born, $dies) {
	// plot point on path based on frame offset from born -> dies
		if ($x1 != $x2) {
			$slope = ($y1 - $y2) / ($x1 - $x2);
		}

		$ipf = $this->time->getIntervalPerFrame();

		$time_alive = $dies - $born;
		$steps = $time_alive / $ipf;

		$xvel = ($x2 - $x1)/$steps;
		$yvel = ($y2 - $y1)/$steps;

		$abs_time = $this->time->getStartTime() + ($frame_num * $ipf);

		if ($abs_time < $born) { return array($x1, $x2); }

		$step = round(($abs_time - $born)/$ipf) - 1;

		$newx = round($x1 + $xvel * $step);
		$newy = round($y1 + $yvel * $step);

		return array($newx, $newy);
	}

	/**
	 * calculate how many steps it will take for a loan to complete, & which step it's on
	 * @param  $born
	 * @param  $dies
	 * @param  $frame
	 * @return void
	 */
	public function calcStepAndSteps($born, $dies, $frame) {
		$time_alive = $dies - $born;
		$steps = $time_alive / $this->time->getIntervalPerFrame();
		$abs_time = $this->time->getStartTime() + ($frame * $this->time->getIntervalPerFrame());
		$step = round(($abs_time - $born)/ $this->time->getIntervalPerFrame()) - 1;
		//$step = round(($abs_time - $born)/ $this->time->getIntervalPerFrame());
		return array ($step, $steps);
	}

	/**
	 * calculates (x,y) on an elliptical path, where the ellipse is rotating about its center 
	*/

	public function swirlPath($x1, $y1, $x2, $y2, $frame_num, $born, $dies, $unique_id = 0) {
	// http://www.uwgb.edu/dutchs/Geometry/HTMLCanvas/ObliqueEllipses5.HTM

		// set up params
		list($step, $steps) = $this->calcStepAndSteps($born, $dies, $frame_num);
		$done = $step/$steps;  

		// direction of loan travel around ellipse
		$loan_direction = 1;
		if ( $born % 2 == 0) { $loan_direction = -1; }
		
		// direction of ellipse rotation
		$ellipse_direction = 1;
		if ( $unique_id % 2 == 0) { $ellipse_direction = -1; }

		// scale $a and $b by percentage-lifespan-is-complete
		// < 10%, increase 0 to 100 %
		// 11% - 89%, don't scale
		// > 90%, decrease from 100 to 0 %
		$p = ChocoTime::getPercentComplete($born, $dies, $frame_num);
		if ($p <= 0.1) { $scale = $p * 10; } // linearly increase from 0 to 100
		else if ($p >= 0.9 && $p <= 1.0) { $scale = (1.0 - $p) * 10; } // linearly decrease from 100 to 0
		else { $scale = 1.0; }

		// major (a) and minor (b) axes of ellipse 
		$b = (25 + $unique_id % 10); // 25-35 (was 20 - 35)
	
		// make sure a is larger than b
		//$a = ($b + 10 + ($born % round(120 - $b + 10) ));
		if ( substr(strval($unique_id), -1) == "1" ) { // if point is one of an arbitrary 10% whose unique_id ends in "1"
			$a = 125 + $unique_id % 25; // give it a slightly bigger major axis (for better aesthetics)
		}
		else {
			$a = 40 + $born % 85; // 40 - 125 
		}

		$a *= $scale;
		$b *= $scale;

		// displace loan & ellipse along their orbits for a more organic result
		$initial_t = $unique_id % 360;
		$initial_theta = ($unique_id + $born) % 360;

		// assign a deterministic velocity to the loan
		$velocity = 6 + ($born % 350) / 100;  // X + (20 -- 100) / 60 >>> X + (0.33 -- 1.66) >>> 10.33 - 11.66 vs. 5.33 - 6.66

		$min_velocity_angle = 5;
		$max_velocity_angle = 25;
		$velocity_angle = $min_velocity_angle + $unique_id % ($max_velocity_angle - $min_velocity_angle);
		$velocity_angle_radians = $velocity_angle * 3.14159 / 180;
		$path_velocity = $velocity * cos($velocity_angle_radians); // how fast the point moves along its ellipse
		$orbital_velocity = $velocity * sin($velocity_angle_radians); // how fast the ellipse rotates

		// rotational velocity of loan
		//$vt = $loan_direction * (8 + ($unique_id % 45) / 10);
		$vt = $loan_direction * (1.5 + ($unique_id % 10) / 10);

		// rotational velocity of ellipse
		$ve = $loan_direction * (15 + $born % 90)/100;

		// now calc x and y
		
		// t is the current position on the ellipse, ranging between 0 and 2 * PI (360 degrees)

		$t = ($initial_t + $step * $path_velocity) % 360;
		$t_radians = $t * 3.14159 / 180;
		
		// theta is the angle by which the ellipse is rotated
		$theta = ($initial_theta + $step * $orbital_velocity) % 360;
		$theta_radians = $theta * 3.14159 / 180;

		$x = $x1 + $a*cos($t_radians)*cos($theta_radians) - $b*sin($t_radians)*sin($theta_radians);
		$y = $y1 + $a*cos($t_radians)*sin($theta_radians) + $b*sin($t_radians)*cos($theta_radians);

		return array($x, $y);

	}

	/**
	 * calculate bezier path
	 * @param  $born
	 * @param  $dies
	 * @param  $frame
	 * @return $x, $y
	 * http://www.coderanch.com/t/480970/Game-Development/java/Here-plot-curve-between-two
	 */

	public function bezierPath($x1, $y1, $x2, $y2, $frame_num, $born, $dies, $unique_id = 0) {
	// plot point on path based on frame offset from born -> dies

		list($step, $steps) = $this->calcStepAndSteps($born, $dies, $frame_num);

		// $done == how far along is the loan between lender -> borrower -> lender ?
		// proceeds from 0.0 to 1.0.  When we're at 0.5, we've reached the borrower.
		// To simulate the loan "pausing" at the borrower: 
		// 1. do the full lender -> borrower traversal from 0.0 to 0.4
		// 2. draw the loan at the borrower from 0.4 to 0.6
		// 3. do the full borrower -> lender return traversal from 0.6 to 1.0

		$done = $step/$steps;  

		if ($done <= 0.4) { // LOAN
			// we're on the loan (lender-to-borrower) path
			// first, stretch the range 0.0 to 0.4 so that it covers 0.0 to 0.5
			$scaled = $done * 1.25;
			// next, re-map $scaled to a 0-1 scale
			$scaled *= 2;
			$t = max(0, $this->easeOutQuad($scaled)); 

			list($bezier_x, $bezier_y) = $this->calcBezierControlPoint($x1, $y1, $x2, $y2, $born, $dies, $frame_num, $unique_id);

			$x = (1-$t)*(1-$t)*$x1 + 2*(1-$t)*$t*$bezier_x+$t*$t*$x2;
			$y = (1-$t)*(1-$t)*$y1 + 2*(1-$t)*$t*$bezier_y+$t*$t*$y2;

		}
		else if ($done >= 0.6) { // REPAYMENT
			// we're on the repayment (borrower-to-lender) path
			// first, stretch the range 0.6 to 1.0 so that it covers 0.5 to 1.0
			$scaled = ($done - 0.6) * 1.25 + 0.5;
			// next, re-map $scaled to a 0-1 scale
			$scaled = ($scaled - 0.5) * 2;
			$t = $this->easeOutQuad($scaled);

			list($bezier_x, $bezier_y) = $this->calcBezierControlPoint($x1, $y1, $x2, $y2, $dies, $born, $frame_num, $unique_id);

			$x = (1-$t)*(1-$t)*$x2 + 2*(1-$t)*$t*$bezier_x+$t*$t*$x1;
			$y = (1-$t)*(1-$t)*$y2 + 2*(1-$t)*$t*$bezier_y+$t*$t*$y1;

		}
		else { // PAUSING AT BORROWER
			// we're at the borrower, so just return borrower coordinates
			$x = $x2;
			$y = $y2;
		}
		return array($x, $y);

	}

	public function easeOutQuad($t) {
		// quadratic-only easing; slightly faster than variable exponent
		$t2 = 1 - $t;
		$r = 1 - ($t2 * $t2);
		return 1 - ($t2 * $t2);
	}

	public function easeOut($t, $p) {
		return  1 - $this->easeIn(1-$t, $p);
	}

	public function easeIn($t, $p) {
		return pow($t,$p);
	}


	public function bezierPoint ($x1, $y1, $x2, $y2, $bx, $by, $pct) {

		if ($pct > 1) {
			return array($x2, $y2);
		}
		else {
			$x = (1-$pct)*(1-$pct)*$x1 + 2*(1-$pct) * $pct * $bx + $pct*$pct*$x2;
			$y = (1-$pct)*(1-$pct)*$y1 + 2*(1-$pct) * $pct * $by + $pct*$pct*$y2;
		}

		return array($x, $y);
	}

	public function calcBezierControlPoint($x1, $y1, $x2, $y2, $born, $dies, $frame_num, $unique_id = 0) {

		 // figure out random bezier control point offset.  use xorig, ydest, etc. as pseudo-random seeds
		$midx = ($x2 - $x1)/ 2 + $x1;
		$midy = ($y2 - $y1)/ 2 + $y1;

		$spice = 0;
		$direction = 1;
		// if origin & destination are the same, give some spin, and make half of them clockwise & the other half counter-clockwise 
		if (abs($x1 - $x2) < 2 && abs($y1 - $y2) < 2) {
			$spice = $frame_num / 20; 
			if ( $born % 2 == 0) { $direction = -1; }
		}


		$xdist = $x1 - $x2;
		$ydist = $y2 - $y1;
		$angle = atan2($y2 - $y1,$x2 - $x1); // angle from lender to borrower in radians
		$angle_degrees = $angle * 180 / 3.14159265; // angle in degrees

		$unique_seed = $born + $dies; 
		$random_seed = $dies - $born;

		// pseudo-deterministic-randomness to put the bezier control point on one side of the line or the other
		if ( $unique_seed / 2 == intval($unique_seed / 2) ) { $direction = 1; }
		else { $direction = -1; }

		if ($GLOBALS['loan_select'] == 'global') {

			$distance = sqrt($xdist * $xdist + $ydist * $ydist);

			// 90 would be right-angle perpendicular; give it a spread
			$angle_offset = $angle_degrees + (90 + ($unique_seed % 80) - 40) * $direction;  
			$angle_offset_radians = $angle_offset * 3.14159265 / 180;

			if ($distance == 0) { $bezier_offset = 0; }
			else { $bezier_offset = $unique_seed % ($distance/2 + 2) ; } // how far away is anchor point

			$bezier_x = round($midx + $bezier_offset * cos($angle_offset_radians));
			$bezier_y = round($midy + $bezier_offset * sin($angle_offset_radians));
		}
		else {
			// local-only -- make loans swirl around lender/borrower location
			$velocity = (750 + $unique_id % 1750) / 1000;
			$random_angle = (($x1 + $x2 + $born + $unique_id) % 360 + $spice * $velocity) * $direction; 
			$random_offset = (($y1 + $y2 + $born + $unique_id) % 200) + 50;
//			$random_offset = (($y1 + $y2 + $born) % 500) - 250;
//			$random_offset = (($y1 + $y2 + $born) % 150) + 20;
			$bezier_x = round($midx + $random_offset * cos($random_angle));
			$bezier_y = round($midy + $random_offset * sin($random_angle));
		}


		return array($bezier_x, $bezier_y);
	}


	/**
	 * draws a single frame
	 * @return void
	 */
	public function drawFrame($frame, &$background, &$pdb, $callback, $filter_id, $anim_style, $what_to_render, $alpha, $debug) {
		// calc frame time window
		list($frame_time_start,$frame_time_end) = $this->time->getFrameBoundaries($frame);
		$total_frames = $this->time->getFrameCount();

		// if the $anim_style is Trails, is a filter attached?
		if ( substr($anim_style, 0, 6) == 'Trails' and strlen($anim_style) > strlen('Trails') ) {
			$anim_tokens = explode(":", $anim_style);
			$token = $anim_tokens[1];
			$anim_style = $anim_tokens[0]; // "Trails"
			if ( ! in_array($token, array('theme','sector','team_id','rfaid','filter','biz_id'))) {
				echo "Trails filter '$token' is not valid.  Exiting.\n";
				echo "Valid filters are: theme, sector, team_id, rfaid, filter, biz_id \n";
				die;
			}
		}
		else {
			// nothing to filter on
			$token = null;
		}

		$sector = $team_id = $rfaid = $theme = $filter = null;

		// what are we rendering
		switch ($anim_style) {
			case "ColorBySector":
				$sector = $filter_id;
				break;
			case "Trails":
				$$token = $filter_id; // Trails can handle multiple filters
				break;
			case "TeamTrace":
				$team_id = $filter_id;
				break;
			case "ThemeTrace":
				$theme = $filter_id;
				break;
			case "FundAccountTrace":
				$rfaid = $filter_id;
				break;
			case "FilterTrace":
				$filter = $filter_id;
				break;
			case "LoanTrace":
				$biz_id = $filter_id; // hack to make renders of a specific biz_id go faster
				break;
		}
		echo "rendering frame $frame/$total_frames, start/end =  $frame_time_start, $frame_time_end, alpha = $alpha, filter_id = $filter_id \n";
		$nTotalPaths = $pdb->getPathCount($frame_time_start, $frame_time_end, $sector, $team_id, $rfaid, $theme, $filter, $biz_id);
		$nPathsDone = 0;

		while ($nPathsDone < $nTotalPaths) {
			echo "DID $nPathsDone / $nTotalPaths paths for frame $frame\n";
			$paths = $pdb->getPaths(
				$frame_time_start, 
				$frame_time_end,
				$sector,
				$team_id,
				$rfaid,
				$theme,
				$filter,
				$biz_id,
				$limit = 60000, 
				$offset = $nPathsDone
									);

			foreach ($paths as $path) {
				$this->drawPath($path, $frame, $background, $callback, $nTotalPaths, $anim_style, $what_to_render, $alpha );
			}
			$nPathsDone += count($paths);
		}
		
		// $this->drawTimeline($background, $frame);

		if ($debug) {
		
			$width = $this->geo->getWidth();
			$height = $this->geo->getHeight();
			$white=imagecolorallocatealpha($background, 250, 250, 250, 0);
			$ts = date("m.d.y, g:i a", $frame_time_start);

			$debug_text = "$frame  $nPathsDone  $ts  $frame_time_start";
			imagestring($background, 5, $width - 500, $height - 15, $debug_text, $white);
		}

		echo "done frame $frame\n";

	}

	/**
	 * draws timeline & progress bar
	 * @param  $frame (frame number)
	 * @return void
	 */
	public function drawTimeline(&$img, $frame) {
		$blue=imagecolorallocate($img, 70, 70, 255);

		$timeline = imagecreatefrompng( ChocoRenderer::relativeFn('img/timeline2.png') );
		$legend = imagecreatefrompng( ChocoRenderer::relativeFn('img/legend2.png') );
		$tl_width = imagesx($timeline);
		$tl_height = imagesy($timeline);
		$leg_width = imagesx($legend);
		$leg_height = imagesy($legend);
		$left = round(($this->geo->getWidth() / 2) - ($tl_width / 2));
		$right = round(($this->geo->getWidth() / 2) + ($tl_width / 2) - 118); // 118 = last date in dataset offset from end of timeline
		$bottom = $this->geo->getHeight() - 50;
		$dest_x = $this->geo->getWidth() / 2 - $tl_width / 2;
		$dest_y = $this->geo->getHeight() - $tl_height - 50;

		$pos = 2 + round($left + ($right - $left) * ($frame / $this->time->getFrameCount()));
		// print_r("left right frame fc  $left + ($right - $left) * ($frame /" . $this->time->getFrameCount() . "\n");
		$triangle = array(
            $pos,  $bottom,  // Point 1 (x, y)
            $pos - 10,  $bottom + 20, // Point 2 (x, y)
            $pos + 10,  $bottom + 20  // Point 3 (x, y)
            );

		// draw timeline
		imagecopy($img, $timeline, $dest_x, $dest_y, 0, 0, $tl_width, $tl_height);

		//imagecopy($img, $legend, 55, 905, 0, 0, $leg_width, $leg_height);

		// draw current position/date
		// imagefilledpolygon($img, $triangle, 3, $blue);
		imagefilledrectangle($img, $pos-2, $dest_y - 15, $pos+2, $dest_y + 95, $blue);
	}


	/**
	 * given two points, reduces their connecting line to $maxlen units (starting at x1 y1) if it exceeds $maxlen
	 * @param  $x1
	 * @param  $y1
	 * @param  $x2
	 * @param  $y2
	 * @param  $maxlen
	 * @return new x2, y2 (trail end)
	 */
	public function trimTrail(&$img, $x1, $y1, $x2, $y2, $maxlen) {

		$a = $x2 - $x1;
		$b = $y2 - $y1;

		if ($a == 0 && $b == 0) { 
			return array($x1, $y1);
		}

		$c = sqrt($a * $a + $b * $b); // current length of trail
		//if ($c > $maxlen) {
		if (true) {
			$angle = $this->getAngle("radians", $x1, $y1, $x2, $y2);
			$x2 = $x1 + round(sin($angle) * $maxlen);
			$y2 = $y1 + round(cos($angle) * $maxlen);

		}

		return array($x2, $y2);

	}


	public function getAngle($units = "radians", $x1, $y1, $x2, $y2) {
		$o = $y2 - $y1;
		$a = $x2 - $x1;

		$angle_radians = atan2($a, $o);

		$angle_degrees = round(rad2deg($angle_radians));

		if ($units == "radians") {
			return $angle_radians;
		}
		else {
			return $angle_degrees;
		}
	}

	public function ImageEllipseAA( &$img, $x, $y, $w, $h,$color,$segments=70) {
		$w=$w/2;
		$h=$h/2;
		$jump=2*M_PI/$segments;
		$oldx=$x+sin(-$jump)*$w;
		$oldy=$y+cos(-$jump)*$h;

		for($i=0;$i<2*(M_PI);$i+=$jump) {
			$newx=$x+sin($i)*$w;
			$newy=$y+cos($i)*$h;
			ImageLine($img,$newx,$newy,$oldx,$oldy,$color);
			$oldx=$newx;
			$oldy=$newy;
		}
	}

}
