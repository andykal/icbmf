<?php
// generate per-frame running totals for 1) number of lenders, 2) number of borrowers, 3) amount lent, and 4) number of loans
// yeah this would have been easier to do in the verse/vertex, but no one had bandwidth to point me in the right direction there so this was least effort.
// this script generates $frames sql statements which then need to be run against a reasonably-current kivadb.  
// save the output & then munge it into the proper format (aftereffects expression).

// these 3 vars MUST match the corresponding values in generate_country_background.php and ChocoTime.php
// @todo have all duration-and-frame-related code include ChocoTime.php
$start_time = 1112342400; // 4/1/2005
$end_time = 1441090800; // 9/1/2015  
$frames = 5600; 

$ipf = ($end_time - $start_time) / $frames;  // interval-per-frame in seconds
$ctr = 1;

$time = $start_time;

while ($time <= $end_time) {

// to generate 3) amount lent and 1) number of lenders, uncomment this line before running
//echo "select $ctr, $time, sum(purchase_amt), count(distinct(lender_fund_account_id)) from lender_loan_purchase, loan where loan.id = loan_id and status in ('ended','payingback','raised') and purchase_time < $time ; \n";

// to generate 4) number of loans and 2) number of borrowers, uncomment this line before running
echo "select $ctr, $time, count(1), sum(borrower_count) from business_sort bs where bs.fund_raising_time <= $time and status in ('ended','payingback','raised') ; \n";
//echo "select $ctr, $time, count(1), sum(borrower_count) from business_sort bs where bs.fund_raising_time <= $time ; \n";

$time += $ipf;
$ctr++;
}
