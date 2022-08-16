<?php

// generate background animation for ICBMF video
// fade-in kiva countries on their first-fundraising dates
// this script needs to run to completion.  if interrupted, you need to start over.
// could take multiple hours for long durations

const UNPROCESSED = 	0;
const INPROGRESS = 		1;
const DONE = 			2;

const MIN_OPACITY = 	25;
const MAX_OPACITY = 	85;

$data = array();
$todo = array();
$start_frame = 1;
$end_frame = 3 * 60 * 30 + 200; // 5600 frames (about 3 minutes 7 seconds)
$start_ts = 1112342400; // 4/01/2005
$end_ts = 1441090800; // 09/01/2015
$alpha_delta = 5; // how much we change an in-progress country's opacity each frame

$interval_per_frame = ($end_ts - $start_ts) / $end_frame;

echo "ipf = " . $interval_per_frame . "\n";

// init vars
$data = load_data();

$curr_frame = $start_frame;
$curr_time = $start_ts;
// init our overlay image 
$orig = imagecreatefrompng('./img/5000cropped_bg.png');
imagepng($orig, './img/overlay.png');

// main loop
while ($curr_frame <= $end_frame) {

	// open the current overlay image
	$img = imagecreatefrompng('./img/overlay.png');
	// save it as the next frame, & re-open it
	$outfile = sprintf('./background/%04s.png',$curr_frame);
	echo "working on frame " . $outfile . "\n";
	imagepng($img, $outfile);
	$curr_img = imagecreatefrompng($outfile);

	// check if any new countries should be moved to in-progress
	// loop through array for entries where 'processed' == 0 and ts < $curr_time 
	for ($j = 0; $j <= sizeof($data); $j++) {

		$country_id 	= $data[$j][0];
		$status 		= $data[$j][1];
		$curr_alpha 	= $data[$j][2];
		$alpha_dir 		= $data[$j][3];
		$ts 			= $data[$j][4];

		// echo $country_id . " " .  $status . " " . $curr_alpha . " " . $alpha_dir . " " . $ts .  "\n";
		// check if they are unprocessed & if they need to go live now
		// start each country's fade-in 20 frames before the first loan
		if ($status == UNPROCESSED && $ts > 0 && ($ts - 20 * $interval_per_frame) <= $curr_time) {
			// set 'processed' to INPROGRESS & set initial alpha 
			$data[$j][1] = INPROGRESS;
			echo "set " . $data[$j][0] . " to in-progress \n";
		}
	}


	// update & render any countries that are in-progress (i.e. being faded in)
	for ($c = 0; $c <= sizeof($data); $c++) {
		if ($data[$c][1] == INPROGRESS) {
			echo "working on country " . $data[$c][0] . " in frame " . $curr_frame . "\n";

			// increase alpha
			$data[$c][2] += $alpha_delta * $data[$c][3]; 

			// check if alpha_increment should be flipped 
			if ($data[$c][2] >= MAX_OPACITY) {
				$data[$c][3] = -1;
			}

			// check if country's fade-in is finished -- if so, flip it from in-progress to done
			if ($data[$c][3] == -1 && $data[$c][2] < MIN_OPACITY) {
				// country is done
				$data[$c][1] = DONE;
				// add country's ID to to-do array
				$todo[] = $data[$c];
			}


			// add the country's overlay to $curr_img 
			echo "getting country " . $data[$c][0] . " at alpha " . $data[$c][2] . "\n";
			$country = imagecreatefrompng("./countries/" . $data[$c][0] . ".png");
			imagecopymerge_alpha($curr_img, $country, 0, 0, 0, 931, 5000, 2760, $data[$c][2]); // 1107
		}
	}


	// check if any countries finished fading in this frame -- if so, we need to write a new base image
	if (sizeof($todo) > 0) {
		// loop through to-do array, & overlay any finished countries at their final opacity
		for ($d = 0; $d < sizeof($todo); $d++) {
			echo "merging " . $todo[$d][0] . "\n";
			$country_to_merge = imagecreatefrompng("./countries/" . $todo[$d][0] . ".png");
			imagecopymerge_alpha($img, $country_to_merge, 0, 0, 0, 931, 5000, 2760, MIN_OPACITY); // 1107
			imagepng($img, './img/overlay.png'); // update it
			imagedestroy($img);
			$img = imagecreatefrompng('./img/overlay.png');
			}

		imagepng($img, './img/overlay.png'); // update it
		$todo = array(); // blank it out
	}


	// resample
	$image_resampled = imagecreatetruecolor(2000, 1107);
	imagecopyresampled($image_resampled, $curr_img, 0, 0, 0, 0, 2000, 1107, 5000, 2760);
	// now create the current frame from $curr_img
	//echo $outfile . "\n";
	// write $outfile
	
	//imagepng($curr_img, $outfile);
	imagepng($image_resampled, $outfile);

	imagedestroy($img);
	imagedestroy($curr_img);
	imagedestroy($image_resampled);
	$curr_frame++;
	$curr_time += $interval_per_frame;
}

var_dump($data);
exit;


    function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){ 
        // creating a cut resource 
        $cut = imagecreatetruecolor($src_w, $src_h); 

        // copying relevant section from background to the cut resource 
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h); 
        
        // copying relevant section from watermark to the cut resource 
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h); 
        
        // insert cut resource to destination image 
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct); 
    } 



	/*
	first dates of loans in each country (proxy for "kiva began lending in this country on X date")

	select distinct(country_id), "0", 1, min(loan.fund_raising_time) as d 
	from town, country, business, loan 
	where business_id = business.id 
	and loan.status in ('fundraising','raised','payingback','ended') 
	and country.id = town.country_id 
	and town_id = town.id 
	group by country_id order by d;

	data format is:  kivadb.country.id, processed, curr_alpha, alpha_direction, start_time

	*/
	function load_data() {

		$input = array(
array (   1 , 0 , 0 , 1 , 1113584400  ) ,
array (   3 , 0 , 0 , 1 , 1132077600  ) ,
array (   5 , 0 , 0 , 1 , 1140026400  ) ,
array (   6 , 0 , 0 , 1 , 1140026400  ) ,
array (   7 , 0 , 0 , 1 , 1140026400  ) ,
array (   8 , 0 , 0 , 1 , 1140026400  ) ,
array (   2 , 0 , 0 , 1 , 1142445600  ) ,
array (   9 , 0 , 0 , 1 , 1145819547  ) ,
array (  10 , 0 , 0 , 1 , 1146671814  ) ,
array (  12 , 0 , 0 , 1 , 1154395342  ) ,
array (  13 , 0 , 0 , 1 , 1157052048  ) ,
array (  14 , 0 , 0 , 1 , 1161908449  ) ,
array ( 161 , 0 , 0 , 1 , 1162539535  ) ,
array ( 215 , 0 , 0 , 1 , 1162862169  ) ,
array (  92 , 0 , 0 , 1 , 1162949265  ) ,
array ( 151 , 0 , 0 , 1 , 1163875016  ) ,
array (  29 , 0 , 0 , 1 , 1165646385  ) ,
array ( 224 , 0 , 0 , 1 , 1166319593  ) ,
array (  15 , 0 , 0 , 1 , 1169157099  ) ,
array ( 109 , 0 , 0 , 1 , 1171526039  ) ,
array (  49 , 0 , 0 , 1 , 1171849229  ) ,
array (  73 , 0 , 0 , 1 , 1171948663  ) ,
array (  62 , 0 , 0 , 1 , 1172126791  ) ,
array ( 233 , 0 , 0 , 1 , 1173219472  ) ,
array ( 212 , 0 , 0 , 1 , 1173220966  ) ,
array (  40 , 0 , 0 , 1 , 1174890330  ) ,
array ( 103 , 0 , 0 , 1 , 1176220882  ) ,
array (  65 , 0 , 0 , 1 , 1177614479  ) ,
array (  99 , 0 , 0 , 1 , 1178549246  ) ,
array ( 111 , 0 , 0 , 1 , 1178569661  ) ,
array ( 194 , 0 , 0 , 1 , 1179795038  ) ,
array ( 172 , 0 , 0 , 1 , 1179946424  ) ,
array ( 173 , 0 , 0 , 1 , 1184794865  ) ,
array ( 167 , 0 , 0 , 1 , 1186047303  ) ,
array ( 126 , 0 , 0 , 1 , 1189595703  ) ,
array ( 155 , 0 , 0 , 1 , 1190870498  ) ,
array (  41 , 0 , 0 , 1 , 1197997782  ) ,
array (  75 , 0 , 0 , 1 , 1198046405  ) ,
array (  37 , 0 , 0 , 1 , 1201659905  ) ,
array ( 139 , 0 , 0 , 1 , 1202870406  ) ,
array ( 244 , 0 , 0 , 1 , 1204390811  ) ,
array ( 183 , 0 , 0 , 1 , 1211637011  ) ,
array ( 174 , 0 , 0 , 1 , 1226704222  ) ,
array ( 169 , 0 , 0 , 1 , 1227570012  ) ,
array ( 148 , 0 , 0 , 1 , 1231483207  ) ,
array (  64 , 0 , 0 , 1 , 1234228806  ) ,
array ( 227 , 0 , 0 , 1 , 1244628964  ) ,
array ( 123 , 0 , 0 , 1 , 1245036605  ) ,
array ( 128 , 0 , 0 , 1 , 1245964805  ) ,
array (  25 , 0 , 0 , 1 , 1249969806  ) ,
array (  59 , 0 , 0 , 1 , 1261210215  ) ,
array (  55 , 0 , 0 , 1 , 1262016018  ) ,
array ( 203 , 0 , 0 , 1 , 1265307007  ) ,
array (  48 , 0 , 0 , 1 , 1269699602  ) ,
array (  61 , 0 , 0 , 1 , 1275511203  ) ,
array ( 200 , 0 , 0 , 1 , 1291054810  ) ,
array ( 117 , 0 , 0 , 1 , 1291312211  ) ,
array ( 113 , 0 , 0 , 1 , 1291811407  ) ,
array (  90 , 0 , 0 , 1 , 1295803208  ) ,
array ( 240 , 0 , 0 , 1 , 1301508605  ) ,
array (  47 , 0 , 0 , 1 , 1303419002  ) ,
array ( 220 , 0 , 0 , 1 , 1313192404  ) ,
array ( 238 , 0 , 0 , 1 , 1320219005  ) ,
array ( 239 , 0 , 0 , 1 , 1327702804  ) ,
array (  16 , 0 , 0 , 1 , 1336987203  ) ,
array ( 214 , 0 , 0 , 1 , 1338390010  ) ,
array ( 241 , 0 , 0 , 1 , 1339790403  ) ,
array (  11 , 0 , 0 , 1 , 1345028402  ) ,
array ( 213 , 0 , 0 , 1 , 1355949001  ) ,
array (  36 , 0 , 0 , 1 , 1356043802  ) ,
array ( 153 , 0 , 0 , 1 , 1357171801  ) ,
array (  42 , 0 , 0 , 1 , 1357397402  ) ,
array ( 170 , 0 , 0 , 1 , 1372035002  ) ,
array ( 136 , 0 , 0 , 1 , 1372650003  ) ,
array ( 205 , 0 , 0 , 1 , 1375308602  ) ,
array ( 199 , 0 , 0 , 1 , 1376071202  ) ,
array (  44 , 0 , 0 , 1 , 1382024404  ) ,
array ( 171 , 0 , 0 , 1 , 1382578802  ) ,
array ( 143 , 0 , 0 , 1 , 1396420202  ) ,
array ( 231 , 0 , 0 , 1 , 1400535605  ) ,
array ( 188 , 0 , 0 , 1 , 1402951804  ) ,
array ( 124 , 0 , 0 , 1 , 1403811005  ) ,
array ( 152 , 0 , 0 , 1 , 1406827828  ) ,
array (  56 , 0 , 0 , 1 , 1415727605  ) ,
array ( 198 , 0 , 0 , 1 , 1415731804  ) ,
array (  74 , 0 , 0 , 1 , 1417639202  ) ,
array ( 135 , 0 , 0 , 1 , 1423486202  ) ,
array ( 127 , 0 , 0 , 1 , 1437394203  )
	);
	
	return $input;
	}
