<?php
require_once ( dirname(dirname(dirname(dirname(__FILE__)))).'/httpdocs/include/www_kiva.php' );


/**
 * queries the db to create a data file representing each lender-loan pair as:
 *
 * borrower_lat,borrower_long,lender_lat,lender_long,start_time,end_time
 *
 * - loans to consider : 'ended' loans that are fully paid back;
 *
 * - shares to consider : lenders that have display = 'public' and have geocoded info
 *
 */
class PathMaker {

	function __construct() {
		$this->_db = Bc_Db::factory();
	}

	function dumpLenderData() {
		$sql = Bc_DbQuery::create()
				->select(
					"distinct(concat(lp.display_location_city,', ',lp.display_location_state,', ',bc.name)) as address"
				)
				->from('business b')
				->innerJoin('loan l','l.business_id = b.id')
				->innerJoin('lender_loan_purchase llp','llp.loan_id = l.id')
				->innerJoin('lender_page lp','lp.id = u.lender_page_id')
				->innerJoin('country bc','bc.id = lp.display_location_country_id')
				->innerJoin('login u','u.id = llp.lender_fund_account_id')
				->where(array(
							'l.status' => 'ended',
							'u.visibility' => 'public'
						))
				->groupBy('llp.loan_id,llp.lender_fund_account_id')
				->comment("both_loans_safe"); // We should see loans ending soon regardless of partner vs direct
		echo $sql->sql("\n");

	}

	function filename($business_id) {
		$ext = $business_id % 1000;
		$pre = (int)(($business_id - $ext) / 1000);

		$fn = dirname(__FILE__) . sprintf('/paths/%04s/%03s.csv',$pre,$ext);

		if (!file_exists(dirname($fn))) {
			mkdir(dirname($fn),0777,true);
		}
		
		return $fn;
	}

	function getLoanPaths($business_id) {

		if (file_exists($this->filename($business_id))) return;

		$db = $this->_db;

		$countries = $db->indexedStrings('select id, name from country');

		// @todo: add conditions for raised & payingback loans (use c_last_repayment_time for ts_end in those cases)
		// @todo: replace per-loan loop with batches-of-loans loop, geocoding each of those

		$sql = Bc_DbQuery::create()
				->select('b.id as biz_id')
				->select('l.price as price')
				->select('l.fund_raising_time as ts_start')
				->select('l.ended_time as ts_end')
				->select('llp.purchase_amt as amount')
				->select('am.sector_id as sector_id')
				->select('l.c_last_repayment_time as ts_last_repayment')
				->select('llp.team_id as team_id')
				->select('llp.repayment_fund_account_id as repayment_fund_account')
				->select('llp.lender_fund_account_id as lender_fund_account')
				->select('ltfm.loan_theme_filter_id as loan_theme_filter_id')
				->select(
					"concat(lp.display_location_city,', ',lp.display_location_state) as from_town",
					"lp.display_location_country_id as from_country_id",
					'bc.iso_code as from_iso')
				->select(
					't.name as to_town',
					'c.id as to_country_id',
					'c.iso_code as to_iso',
					't.lat as to_lat',
					't.lng as to_lng'
				)
				->from('business b')
				->innerJoin('loan l','l.business_id = b.id')
				->innerJoin('town t','b.town_id = t.id')
				->innerJoin('country c','c.id = t.country_id')
				->innerJoin('lender_loan_purchase llp','llp.loan_id = l.id')
				->innerJoin('login u','llp.lender_fund_account_id = u.id')
				->innerJoin('lender_page lp','lp.id = u.lender_page_id')
				->innerJoin('activity_mapper am','am.activity_id = b.activity_id')
				->innerJoin('country bc','bc.id = lp.display_location_country_id')
				->leftOuterJoin('loan_theme_filter_mapper ltfm','l.loan_theme_instance_id = ltfm.loan_theme_instance_id')
				->where(array(
							'l.status' => 'ended',
							'u.visibility' => 'public',
							'b.id' => $business_id
						))
				->groupBy('llp.loan_id,llp.lender_fund_account_id')
				->comment("both_loans_safe"); // We should see loans ending soon regardless of partner vs direct
		$data = $this->_db->rowset($sql);

		$geocode = new Geocoder();

		$out_data = array();

		$rows = array();

		foreach($data as $i => $d) {

			$from_loc = sprintf("%s, %s", $d['from_town'], $countries[ $d['from_country_id'] ]);

			$from_geo = $geocode->getGeocode($from_loc);

			// $geo = $db->row('select lat, lon as lng from geocode where address = ?', array($loc));
			$data[$i]['from_lng'] = count($from_geo) ? $from_geo['lon'] : null;
			$data[$i]['from_lat'] = count($from_geo) ? $from_geo['lat'] : null;

			$to_loc = sprintf("%s, %s", $d['to_town'], $countries[ $d['to_country_id'] ]);

			$to_geo = $geocode->getGeocode($to_loc);

			// $geo = $db->row('select lat, lon as lng from geocode where address = ?', array($loc));
			$data[$i]['to_lng'] = count($to_geo) ? $to_geo['lon'] : null;
			$data[$i]['to_lat'] = count($to_geo) ? $to_geo['lat'] : null;


			$txt = sprintf('FROM %s TO %s', Bc_Inflector::camelize($from_loc), Bc_Inflector::camelize($to_loc));
			$row = array(
				$data[$i]['to_lat'], $data[$i]['to_lng'], // borrower
				$data[$i]['from_lat'], $data[$i]['from_lng'], // lender
				$data[$i]['ts_start'],$data[$i]['ts_end'], $data[$i]['ts_last_repayment'],
				$data[$i]['biz_id'], $data[$i]['price'], $data[$i]['amount'], $data[$i]['sector_id'],
				$data[$i]['lender_fund_account'],$data[$i]['repayment_fund_account'],
				$data[$i]['team_id'],$data[$i]['loan_theme_filter_id']
			);

//$txt
			for($j = 0; $j < 4; $j++) {
				$dat = trim($row[$j]);
				if ( empty ($dat) ) {
					continue;
				}
			}

			$rows[] = implode(",",$row);

		}

		$txt = implode("\n",$rows);
		file_put_contents( $this->filename($business_id), $txt);
		
	}

}


set_time_limit(0);

$max = Bc_Db::factory()->string("select max(id) from business");

$ids = Bc_Db::factory()->strings("
	select b.id from business b
	inner join loan l on l.business_id = b.id
	where l.status = 'ended'
	order by b.id limit 500 /* both_loans_safe */
");

$lastid = 1;
while ($lastid <= $max - 5000) {

	$ids = Bc_Db::factory()->strings("
		select b.id from business b
		inner join loan l on l.business_id = b.id
		where l.status = 'ended'
        and b.id >= $lastid
		order by b.id limit 500 /* both_loans_safe */
	"); 

	$lastid = max($ids);

	$pm = new PathMaker();
	foreach($ids as $id) {
		$pm->getLoanPaths($id);
	}

}

