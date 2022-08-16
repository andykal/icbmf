<?php



class ChocoPathDb extends ChocoPathDbStub {

	public function load() {
		if (defined("CHOCO_DB_PATH")) {
			$choco_db_path = CHOCO_DB_PATH;
		} else {
//			$choco_db_path = dirname(__FILE__) . '/data/japaraguay.db';
			$choco_db_path = dirname(__FILE__) . '/data/icbmf_3.db';
//			$choco_db_path = dirname(__FILE__) . '/data/icbmf_4.db';
//			$choco_db_path = dirname(__FILE__) . '/data/volunteers.db';
//			$choco_db_path = dirname(__FILE__) . '/data/zip.db';
		}
		$this->dbh = new PDO('sqlite:' . $choco_db_path);		
		$this->_maxTime = $this->_dbstring("select max(start_ts) from paths");
		$this->_minTime = $this->_dbstring("select min(start_ts) from paths");
		$this->_defaultLimit = 1000;
	}

	public function getMaxTime() {
		return $this->_maxTime;
	}

	public function getMinTime() {
		return $this->_minTime;
	}
	
	private function _where($start_ts,$end_ts,$sector, $team_id, $rfaid, $theme, $filter, $biz_id) {
		$where = " where ";

		if (empty($start_ts) || empty($end_ts)) { return $where; }

		if ($biz_id && !empty($biz_id)) { $where .= " biz_id = $biz_id and "; }
		if ($filter && !empty($filter)) { $where .= " theme_filter like '%," . $filter . ",%' and "; }
		if ($team_id && !empty($team_id)) { $where .= " team_id = $team_id and "; }
		if ($rfaid && !empty($rfaid)) { $where .= " rfaid = $rfaid and "; }
		if ($theme && !empty($theme)) { $where .= " theme_type = $theme and "; }
		if ($sector && !empty($sector)) { $where .= " sector_id = $sector and "; }

		if ($GLOBALS['loan_select'] == 'global') {
			return $where . " start_ts <= $end_ts and end_ts >= $start_ts ";
		}
		else {
			// local -- return loans where borrower & lender are close together
			return $where . " start_ts <= $end_ts and end_ts >= $start_ts and  abs(borrower_lat - lender_lat) < 0.1 and abs(borrower_lon - lender_lon) < 0.1 ";
		}
	}
	
	private function _limit($limit = null, $offset = null) {
		if (!$limit) $limit = $this->_defaultLimit;
		$limit = (int)$limit;
		$offset = (int)$offset;
		if (empty($limit)) { return ""; }
		return " limit $limit offset $offset ";
	}
	
	public function getPaths($start_ts = null, $end_ts = null, $sector = null, $team_id = null, $rfaid = null, $theme = null, $filter = null, $biz_id = null, $limit = null, $offset = null) {
		$sql = 'select borrower_lat, borrower_lon, lender_lat, lender_lon, start_ts, end_ts, biz_id, sector_id, team_id, share_price, loan_price, theme_type, theme_filter, gender, rfaid '
			. ' from paths ' . $this->_where($start_ts,$end_ts,$sector,$team_id,$rfaid,$theme, $filter, $biz_id) . $this->_limit($limit,$offset);
//			. ' from paths ' . $this->_where($start_ts,$end_ts,$sector,$team_id,$rfaid,$theme, $filter, $biz_id) . ' order by share_price desc ' . $this->_limit($limit,$offset);
//			. ' from paths ' . $this->_where($start_ts,$end_ts,$sector,$team_id,$rfaid,$theme, $filter, $biz_id) . ' order by start_ts desc ' . $this->_limit($limit,$offset);
//			. ' from paths ' . $this->_where($start_ts,$end_ts,$sector,$team_id,$rfaid,$theme, $filter, $biz_id) . ' order by share_price ' . $this->_limit($limit,$offset);

		return $this->_dbrows($sql);
	}
	private function _dbrows($sql) {
		$stmt = $this->dbh->query($sql);
echo $sql . "\n";
//var_dump($stmt);
		return $stmt->fetchall(PDO::FETCH_NUM);
	}
	private function _dbstring($sql) {
		$stmt = $this->dbh->query($sql);
		$data = $this->_dbrows($sql);
		foreach($data as $i => $row) {
			foreach($row as $j => $colval) {
				return $colval;
			}
		}
	}

	public function getPathCount($start_ts, $end_ts, $sector, $team_id, $rfaid, $theme, $filter, $biz_id) {
		$sql = "select count(1) from paths " . $this->_where($start_ts,$end_ts, $sector, $team_id, $rfaid, $theme, $filter, $biz_id);
		$val = (int)$this->_dbstring($sql);
		return $val;
	}


  }


class ChocoPathDbStub {

	const BORROWER_LAT = 0;
	const BORROWER_LON = 1;
	const LENDER_LAT = 2;
	const LENDER_LON = 3;
	const START_TS = 4;
	const END_TS = 5;
	const KIVA_ID = 6;
	const SECTOR_ID = 7;
	const TEAM_ID = 8;
	const SHARE_PRICE = 9;
	const LOAN_PRICE = 10;
	const THEME_TYPE = 11;
	const THEME_FILTER = 12;
	const GENDER = 13;
	const RFAID = 14;


	function load() {
		$this->fn = dirname(__FILE__) . '/path_db_stub.csv';
		$this->rows = self::csvToRows($this->fn);
	}
	function getMaxTime() {
		$maxTime = 0;
		foreach($this->rows as $row) {
			 $maxTime = max($maxTime,$row[self::END_TS]);
		}
		return $maxTime;
	}
	function getMinTime() {
		$minTime = time();
		foreach($this->rows as $row) {
			 $minTime = min($minTime,$row[self::START_TS]);
		}
		return $minTime;
	}
	function getPaths($start_ts, $end_ts, $limit = null, $offset = null) {
		$results = array();
		$currIdx = 0;
		foreach($this->rows as $row) {
			if ($row[self::START_TS] <= $end_ts && $row[self::END_TS] >= $start_ts) {
				$results[] = $row;
			}
		}
		$results = array_slice($results,$limit,$offset);
		return $results;
	}

	function getPathCount($start_ts, $end_ts, $sector, $team_id, $rfaid, $theme, $filter, $biz_id) {

		return count($this->getPaths($start_ts,$end_ts, $sector, $team_id, $rfaid, $theme, $filter, $biz_id));

	}


	/**
	 * Parse a CSV file and return the data contained as a two-dimensional array of rows and columns
	 *
	 * @param string $filename The local, server-side path to the file
	 * @param int $firstLineNo Note - this does NOT determine which is the first line of the file that is parsed. It determines which is the first
	 * 												 populated row of the returned array.  I can't imagine why this is useful.
	 * @param boolean $strict  If true, check to make sure that the file is actually a plain text file. If it isn't throw a Bc_Exception
     * @param boolean $skipLinesWithEmptyFields If true, skips any lines that are blank in data (all empty strings for instance, so ,,, is skipped
	 * @return array
	 */
	public static function csvToRows($filename,$firstLineNo = 0, $strict = false, $skipLinesWithEmptyFields = false, $allowSingleItemLines = false) {

		if (!file_exists($filename)) return array();

		$old_setting = ini_get('auto_detect_line_endings');
		ini_set('auto_detect_line_endings',True);

		if($strict){

			// This package is currently installed in all our environments, but is deprecated
			if(function_exists('mime_content_type')) {
				$mime_type = mime_content_type($filename);

			}
			// This package is the replacement, is not currently installed in our environments, but will be part of PHP 5.3
			else if(function_exists('finfo_file')){
				$finfo = new finfo(FILEINFO_MIME, $filename); // return mime type ala mimetype extension
				$mime_type = $finfo->file($filename);
			}
			// If neither of these work, use the Linux shell
			else {
				$mime_type = shell_exec('file --mime '. $filename);

			}

			if(strstr($mime_type, 'text/plain') === false){
				throw new Bc_Exception("File $filename is not a valid CSV file.  File info returned is " . $file_info);
			}
		}
		setlocale(LC_ALL, 'en_US.UTF-8');

		$handle = fopen($filename, 'r');
		$rows = array();
		$line = (int)$firstLineNo;
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE && $data != null) {
			if (count($data) > 1 || $allowSingleItemLines) {
                if($skipLinesWithEmptyFields) {
                    $hasData = false;
                    foreach($data as $item) {
                        if($item != null && $item != '') {
                            $hasData = true;
                            break;
                        }
                    }

                    //skip anything that is blank...
                    if(!$hasData) {
                        $line++;
                        continue;
                    }
                }

				$rows[$line] = $data;
			}

			$line++;
		}

		ini_set('auto_detect_line_endings',$old_setting);

		return $rows;
	}
		

  }


if (false) {
	$s = new PathDb();
	$s->load();
	echo "\nloaded\n";
	print_r(array(
				'minTime' => $s->getMinTime(),
				'maxTime' => $s->getMaxTime(),
				'paths' => $s->getPathCount($s->getMinTime(), $s->getMaxTime())
				  ));
}

?>
