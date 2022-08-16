<?php
// usage: php ChocoMain.php numWorkers thisWorker loans|lenders|borrowers startFrame endFrame renderMode palette alpha debug|nodebug (filter_id)
// example: php ChocoMain.php 1 0 loans 1900 2300 ColorBySector 64 chartreuse debug 7
date_default_timezone_set("America/Los_Angeles");

require_once "ChocoApp.php";

/********************
*
* BEGIN CONFIG SECTION
*
********************/

// $debug = false; //controls frame/date/etc. info written to lower-right of frame

// this determines the path followed by loans
// for every case other than "local", use bezierPath
$callback = "bezierPath";
//$callback = "swirlPath";

$GLOBALS['loan_select'] = 'global'; // (global|local)
$GLOBALS['force_white'] = false;  // (true|false)

$GLOBALS['final_borrower_size'] = 25;
$GLOBALS['min_borrower_size'] = 30;

// size (diameter) & line thickness of final lender square for lender renders (for non-HeatMap animated views)
// these are related; for larger lender_size, you probably want a larger lender_thickness.  
// @todo make these one parameter
$GLOBALS['lender_size'] = 15; // default = 6, to highlight, set to 15
$GLOBALS['lender_thickness'] = 8; // default = 4, highlight = 8

// the lowest alpha setting (transparency) used for lenders in lender renders.  0 = no transparency, 127 = fully transparent
// for frames with large numbers of lenders, increase this so that it begins to resemble a heatmap
$GLOBALS['min_lender_alpha'] = 16; // default = 32, max = 96

// TRAILS:
$GLOBALS['trail_outline_min_alpha'] = 60; // initial alpha for trail outlines (scale = 0-127) use 60 for outlines

// how many segments does each loan have?  4 is a good default for medium volume or less.  Use 3 or 2 for higher volume.
// note that higher trail lengths will increase render times linearly [ each loan is drawn (trail_length * 2) times. ]
$GLOBALS['trail_length'] = 4; 

// how thick is the first trail segment? (head of the trail).  size decreases linearly for subsequent segments
// 6 is a good default for medium volume or less.  Use 5,4, or 3 for higher volume.
$GLOBALS['trail_thickness'] = 7;

/********************
*
* END CONFIG SECTION
*
********************/

$app = new ChocoApp();

//$app->drawFullPath('linePath');
//$app->drawFullPath('wigglePath');

// @todo fix this jank -- cmd line params or config
// currently used to render one-off non-sequential frames; bypasses the frame looping code; 
// change false to true to force a LatLong test
if (false) {
	$app->drawLatLongTest();
	//$app->drawFullPath('bezierPath');
	//$app->drawActors("borrowers");

	} 
else {
	$nWorkers = $argv[1];
	$nThisOne = $argv[2];
	$what_to_render = $argv[3];
	$startFrame = $argv[4];
	$endFrame = $argv[5];
	$anim_style = $argv[6];
	$alpha = $argv[7];
	$palette = $argv[8];
	$debug = $argv[9];
	$filter_id = $argv[10];

	if ( count($argv) < 10 ) {
		echo "usage: php <script>" .
				" nWorkers nThisOne [loans|lenders|borrowers] startFrame(1) endFrame(5600) anim_style(ColorBySector) alpha(0-127) palette(sectors|green|etc.) debug(debug|nodebug) filter_id(null) \n";
		die();
	}

	if (empty($nWorkers)) $nWorkers = 1;
	if (empty($nThisOne)) $nThisOne = 0;
	if (empty($startFrame)) $startFrame = 1;
	if (empty($endFrame)) $endFrame = 5000;
	if (empty($anim_style)) $anim_style = 'ColorBySector';
	if (empty($alpha)) $alpha = 64; // 50%

	$GLOBALS['palette'] = $palette; 	// which palette to use


	// are we rendering sector colors?
	if ( $palette == "sectors" ) {
		//$filter_id = null;
		$GLOBALS['sector_colors'] = true; // color loans by sector
	}
	else {
		$GLOBALS['sector_colors'] = false; // sector specified, so draw loans in white
	}

	if (empty($debug)) $debug = true;
	if ($debug === 'nodebug') $debug = false;

	$outdir = $anim_style . ($anim_style == "ColorBySector" ? (int)($sector) : "") . ($debug ? 'debug' : 'nodebug');

	for($i=$startFrame; $i <= $endFrame; $i++) {
		if ( ($i%$nWorkers) == $nThisOne ) {
			//$output = $app->doFrame('./img/clean_mercator_5000.png',5000,$i,0,$callback,$sector,$anim_style,$debug);
			//$output = $app->doFrame('./img/equirectangular.png',ChocoTime::FRAMES,$i,0,$callback,$sector,$anim_style,$debug);
			$output = $app->doFrame("",ChocoTime::FRAMES,$i,0,$callback,$filter_id,$anim_style, $what_to_render, $alpha, $debug);
			
		}
	}
	

}


