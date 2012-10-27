<?php
/* Copyright 2012 Bryan Nielsen <bnielsen1965@gmail.com>
 * 
 * This file is part of Simple SPC.
 *
 * Simple SPC is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Simple SPC is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Simple SPC.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
include 'config.php';
$user = new User;
include HEADERS . 'operator_header.php';


$metric = new Metric;
$rule = new Rule;
$db = new DB;


if( isset($_REQUEST['action']) ) $action = $_REQUEST['action'];
else $action = 'ChartData';


$errorMessages = array();
$json = array();


switch($action) {
	case 'ChartData':
	
	if( isset($_REQUEST['Name']) ) {
		// query the last 50 data points
		$sql = "SELECT * FROM Measurements WHERE Metric_Name='" . $db->escapeString($_REQUEST['Name']) . "' " .
   	    "ORDER BY Timestamp DESC LIMIT 0, 50";
		$rs = $db->runQuery($sql);

		if( $rs ) {
			// reverse the array so we have ascending order
			$rs = array_reverse($rs);
	
			$metric = new Metric($_REQUEST['Name']);
	
			// get control settings
			$centerLine = $metric->getCenterline();
			$UCL = $metric->getUCL();
			$LCL = $metric->getLCL();
			
			// make sure values are numbers if numeric
			$centerLine = (is_numeric($centerLine) ? floatval($centerLine) : $centerLine);
			$UCL = (is_numeric($UCL) ? floatval($UCL) : $UCL);
			$LCL = (is_numeric($LCL) ? floatval($LCL) : $LCL);
		
		
			$data = array();
					
			// process each data row
			$dataMax = 0;
			$dataMin = 0;
			$decimalPoints = $metric->getPrecision(); // 0;
			
			foreach( $rs as $i => $row ) {
				// convert timestamp string to time value
				$ts = strtotime($row['Timestamp']);
				
				$value = floatval($row['Value']);
				
				// store data point in array
				$data[] = array(date('Y-m-d h:i:sA', $ts), $value);
				
				if( $value > $dataMax ) $dataMax = $value;
				if( $value < $dataMin ) $dataMin = $value;
				
				/*
				$d = abs($value) - floor(abs($value));
				if( $d > 0 ) {
					$dparts = explode('.', strval($d));
					if( isset($dparts[1]) ) $decimalPoints = strlen($dparts[1]);
				}
				*/
			}
		
			// determine start and end dates for line
			$startdate = strtotime($rs[0]['Timestamp']);
			$enddate = strtotime($rs[count($rs) - 1]['Timestamp']);
		
			// create control lines
			$center = array(array(date('Y-m-d h:i:sA', $startdate), $centerLine), array(date('Y-m-d h:i:sA', $enddate), $centerLine));
			$json['centerLine'] = $center;
			
			if( is_numeric($UCL) ) $json['UCL'] = array(array(date('Y-m-d h:i:sA', $startdate), $UCL), array(date('Y-m-d h:i:sA', $enddate), $UCL));
			if( is_numeric($LCL) ) $json['LCL'] = array(array(date('Y-m-d h:i:sA', $startdate), $LCL), array(date('Y-m-d h:i:sA', $enddate), $LCL));
		
			$json['data'] = $data;
			
			$json['range'] = abs($dataMax - $dataMin);
			$json['decimalPoints'] = $decimalPoints;
			
			// run SPC rule checks
			$rule = new Rule;
			$spcResult = $rule->runSPC($_REQUEST['Name']);
			
			if( $spcResult->violationData && count($spcResult->violationData) > 0 ) {
				$violationData = array();
				
				foreach( $spcResult->violationData as $i => $row ) {
					// convert timestamp string to time value
					$ts = strtotime($row['Timestamp']);
				
					$value = floatval($row['Value']);
				
					// store data point in array
					$violationData[] = array(date('Y-m-d h:i:sA', $ts), $value);
				}
				
				$json['violationData'] = $violationData;
			}
			
			if( $spcResult->violationMessages && count($spcResult->violationMessages) > 0 ) {
				$json['violationMessages'] = $spcResult->violationMessages;
			}
			
			$json['spc'] = $spcResult;
			
			
			$json['description'] = $metric->getDescription();
		}
	}
	
//	exit();
}

$json['errorMessages'] = $errorMessages;
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
//header('Content-type: application/json');

echo json_encode($json);
exit();

?>