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
 
/**
 * 
 *
 */
class Rule {
	private $ruleArray; // array of valid rule names and descriptions
	private $maxData; // maximum number of data points needed for any rule analysis
	public $ruleTypes;
	
	/*
	 * attributes for a rule: metric name, name, type, status, violation status, centerline, upper limit, lower limit
	 */
	
	
	/*
	 * Rule constructor
	 */
	public function __construct($ruleName = NULL) {
		$this->ruleTypes = array(
			'WE1' => 'Western Electric 1',
			'WE2' => 'Western Electric 2',
			'WE3' => 'Western Electric 3',
			'WE4' => 'Western Electric 4',
			'WE5' => 'Western Electric 5',
			'WE6' => 'Western Electric 6',
			'WE7' => 'Western Electric 7',
			'WE8' => 'Western Electric 8'
		);
		
		// set the maximum number of data points that may need to be analyzed for the known set of rules
		$this->maxData = 16;
		
		$this->ruleArray = array();
		if( !empty($ruleName) ) $this->loadRule($ruleName);
	}
	
	
	/*
	 * Return HTML option tag string for rule type options
	 */
	public function ruleTypeOptions() {
		$optionString = '';
		
		foreach( $this->ruleTypes as $type => $description ) {
			$optionString .= '<option value="' . $type . '">' . $description . '</option>' . "\n";
		}
		
		return $optionString;
	}
	
	
	/*
	 * Load rule
	 */
	public function loadRule($ruleName) {
		$db = new DB;
		
		$sql = "SELECT * FROM Rules WHERE Name = '" . $db->escapeString($ruleName) . "'";
		
		$result = $db->runQuery($sql);
		if( !empty($result) && count($result) > 0 ) {
			foreach( $result[0] as $field => $value ) $this->ruleArray[$field] = $value;
		}
	}
	
	
	// create a new rule
	public function createRule($ruleType, $metricName, $ruleStatus, $ruleViolationStatus) {
		$db = new DB;
		
		// check if name is available
		
			$sql = "INSERT INTO Rules(Type, Metric_Name, Status, Violation_Status) VALUES (" .
			       "'" . $db->escapeString($ruleType) . "', " .
			       "'" . $db->escapeString($metricName) . "', " .
			       "'" . $db->escapeString($ruleStatus) . "', " .
			       "'" . $db->escapeString($ruleViolationStatus) . "'" .
			       ")";
			
			if( $db->runQuery($sql) !== FALSE ) {
				return TRUE;
			}
		//}
		
		return FALSE;
	}
	
	
	// delete
	public function deleteRule($ruleType) {
		// verify user has admin permission
			$db = new DB;
			
			$sql = "DELETE FROM Rules WHERE Type='" . $db->escapeString($ruleType) . "'";
			
			if( $db->runQuery($sql) !== FALSE ) {
				return TRUE;
			}
		
		return FALSE;
	}
	
	
	// determine if type is available
	public function typeAvailable($ruleType, $metricName) {
		if( empty($ruleType) || empty($metricName) ) return FALSE;
		
		$db = new DB;
		
		$sql = "SELECT * FROM Rules WHERE Type='" . $db->escapeString($ruleType) . "' AND Metric_Name='" . $db->escapeString($metricName) . "'";
		$rs = $db->runQuery($sql);
		
		if( is_array($rs) && count($rs) > 0 ) return FALSE;
		else return TRUE;
	}
	
	
	// determine if type exists
	public function typeExists($ruleType, $metricName) {
		return !$this->typeAvailable($ruleType, $metricName);
	}
	
	
	// rule type change
	public function changeType($oldRuleType, $ruleType, $metricName) {
		// make sure type is available for metric
		if( $this->typeAvailable($ruleType, $metricName) === FALSE ) return FALSE;
		
		$db = new DB;
		
		$sql = "UPDATE Rules SET Type='" . $db->escapeString($ruleType) . "' WHERE " .
		       "Metric_Name='" . $db->escapeString($metricName) . "' AND " .
		       "Type='" . $db->escapeString($oldRuleType) . "'";
		
		if( $db->runQuery($sql) === FALSE ) return FALSE;
		else return TRUE;
	}
	
	
	// update rule
	public function updateRule($ruleType, $metricName, $ruleViolationStatus) {
		// validation
		if( !$this->typeExists($ruleType, $metricName) ) return FALSE;
		
		$db = new DB;
		
		$sql = "UPDATE Rules SET Violation_Status = '" . $db->escapeString($ruleViolationStatus) . "' " .
		       "WHERE Metric_Name='" . $db->escapeString($metricName) . "' AND " .
		       "Type='" . $db->escapeString($ruleType) . "'";
		
		if( $db->runQuery($sql) === FALSE ) return FALSE;
		else return TRUE;
	}
	
	
	// get metric count
	public function getRuleCount($metricName = NULL) {
		if( is_null($metricName) ) $metricName = $this->ruleArray['Metric_Name'];
		
		$db = new DB;
		
		$sql = "SELECT count(Type) AS count FROM Rules " .
		       "WHERE Metric_Name = '" . $db->escapeString($metricName) . "'";
		$result = $db->runQuery($sql);
		
		if( $result === FALSE || count($result) == 0 ) return FALSE;
		
		return $result[0]['count'];
	}
	
	
	// get rule list
	public function getRuleList($metricName = NULL, $offset = 0, $limit = 10) {
		if( is_null($metricName) ) $metricName = $this->ruleArray['Metric_Name'];
		
		$db = new DB;
		
		$sql = "SELECT * FROM Rules WHERE Metric_Name = '" . $db->escapeString($metricName) . "' " .
		       "ORDER BY Type ASC LIMIT " . $offset . ", " . $limit;
		$result = $db->runQuery($sql);
		
		if( $result === FALSE ) return FALSE;
		
		$list = array();
		foreach( $result as $row ) {
			$tempArray = array();
			foreach( $row as $field => $value ) $tempArray[$field] = $value;
			if( isset($this->ruleTypes[$tempArray['Type']]) ) {
				$tempArray['Type_Description'] = $this->ruleTypes[$tempArray['Type']];
			}
			else {
				$tempArray['Type_Description'] = $tempArray['Type'];
			}
			$list[] = $tempArray;
		}
		
		return $list;
	}
	
	
	
	// get rule status for metric
	public function getStatusList($metricName) {
		$db = new DB;
		
		$statusList = array();
		
		$sql = "SELECT * FROM Rules WHERE Metric_Name='" . $db->escapeString($metricName) . "'";
		$result = $db->runQuery($sql);
		
		if( $result ) {
			foreach( $result as $row ) {
				$statusList[$row['Type']] = $row['Status'];
			}
		}
		
		return $statusList;
	}
	
	
	// run SPC checks and return a result object
	public function runSPC($metricName, $updateStatus = TRUE) {
		$spcResult = new stdClass;
		$spcResult->violation = FALSE;
		$spcResult->violationData = array();
		$spcResult->violationRules = array();
		$spcResult->violationMessages = array();
		
		$metric = new Metric($metricName);
		
		$db = new DB;
		
		// get all the rules
		$sql = "SELECT * FROM Rules WHERE Metric_Name='" . $db->escapeString($metricName) . "'";
		$ruleResult = $db->runQuery($sql);
		
		if( $ruleResult ) {
			// get the most recent data points for the analysis
			$sql = "SELECT * FROM Measurements WHERE Metric_Name='" . $db->escapeString($metricName) . "' " .
					 "ORDER BY Timestamp DESC LIMIT 0, " . $this->maxData;
			$measurementResult = $db->runQuery($sql);
			
			// process each rule
			foreach( $ruleResult as $ruleRow ) {
				// each rule should change the $newRuleStatus value on violation 
				// and add the violating datapoints to the $dataPoints array
				// and set the violation message
				$newRuleStatus = 'READY';
				$dataPoints = array();
				$violationMessage = '';
				
				switch($ruleRow['Type']) {
					// Any single data point falls outside the 3σ limit
					case 'WE1':
					
					// validate the latest data point against the rule
					if( $measurementResult && count($measurementResult) >= 1 ) {
						// add data point to data points array
						$dataPoints[] = $measurementResult[0];
						
						// value to be analyzed
						$measurementValue = $measurementResult[0]['Value'];
						
						// test value
						if( $metric->getUCL() !== FALSE && floatval($measurementValue) > $metric->getUCL() || 
						    $metric->getLCL() !== FALSE && floatval($measurementValue) < $metric->getLCL() ) {
						   // violation
							$newRuleStatus = $ruleRow['Violation_Status'];
							
							$violationMessage = 'WE1 Violation: The last data point exceeded the control limit(' . 
							                    (($metric->getUCL() !== FALSE && floatval($measurementValue) > $metric->getUCL()) ? $metric->getUCL() : $metric->getLCL()) . ').';
						} 
					}
					break;
					
					
					// Two out of three consecutive points fall beyond the 2σ limit on the same side of the centerline
					case 'WE2':
					
					if( $measurementResult && count($measurementResult) >= 3 ) {
						// make sure all 3 points are on the same side of centerline
						$aboveCenter = 0;
						$belowCenter = 0;
						foreach( $measurementResult as $i => $measurement ) {
							// stop after 3
							if( $i == 3 ) break;
							
							// add data point to data points array
							$dataPoints[] = $measurement;
							
							// check position relative to centerline
							if( floatval($measurement['Value']) > $metric->getCenterline() ) $aboveCenter += 1;
							else if( floatval($measurement['Value']) < $metric->getCenterline() ) $belowCenter += 1;
						}
						
						// if all three points are on the same side of the centerline
						if( $aboveCenter == 3 || $belowCenter == 3 ) {
							// determine if 2 or more points exceed 2 sigma
							$upperSigma = ($metric->getUCL() !== FALSE ? round($metric->getCenterline() + 2 * (($metric->getUCL() - $metric->getCenterline()) / 3), $metric->getPrecision()) : $metric->getCenterline());
							$lowerSigma = ($metric->getLCL() !== FALSE ? round($metric->getCenterline() - 2 * (($metric->getCenterline() - $metric->getLCL()) / 3), $metric->getPrecision()) : $metric->getCenterline());
							
							$aboveSigma = 0;
							$belowSigma = 0;
							
							foreach( $measurementResult as $i => $measurement ) {
								// stop after 3
								if( $i == 3 ) break;
								
								// check position relative to 2 sigma
								if( $metric->getUCL() !== FALSE && floatval($measurement['Value']) > $upperSigma ) $aboveSigma += 1;
								else if( $metric->getLCL() !== FALSE && floatval($measurement['Value']) < $lowerSigma ) $belowSigma += 1;
							}
							
							// if 2 or more are above or below 2 sigma then it is a violation
							if( $aboveSigma > 1 || $belowSigma > 1 ) { 
								$newRuleStatus = $ruleRow['Violation_Status'];
								
								$violationMessage = 'WE2 Violation: The last 3 data points are on the same side of the centerline and two of the data points ' .
								                    'exceed the 2 sigma value(' . ($aboveSigma > 1 ? $upperSigma : $lowerSigma) . ').';
							}
						} 
					}
					break;
					
					
					// Four out of five consecutive points fall outside 1 sigma on the same side of the centerline
					case 'WE3':
					// we need at least 5 data points
					if( $measurementResult && count($measurementResult) >= 5 ) {
						$aboveSigma = 0;
						$belowSigma = 0;
						
						$upperSigma = ($metric->getUCL() !== FALSE ? round($metric->getCenterline() + (($metric->getUCL() - $metric->getCenterline()) / 3), $metric->getPrecision()) : $metric->getCenterline());
						$lowerSigma = ($metric->getLCL() !== FALSE ? round($metric->getCenterline() - (($metric->getCenterline() - $metric->getLCL()) / 3), $metric->getPrecision()) : $metric->getCenterline());

						foreach( $measurementResult as $i => $measurement ) {
							// stop after 5
							if( $i == 5 ) break;
							
							// add data point to data points array
							$dataPoints[] = $measurement;
							
							// check position relative to 1 sigma
							if( $metric->getUCL() !== FALSE && floatval($measurement['Value']) > $upperSigma ) $aboveSigma += 1;
							else if( $metric->getLCL() !== FALSE && floatval($measurement['Value']) < $lowerSigma ) $belowSigma += 1;
						}
						
						// if 4 or more are above or below 2 sigma then it is a violation
						if( $aboveSigma > 3 || $belowSigma > 3 ) { 
							$newRuleStatus = $ruleRow['Violation_Status'];
								
							$violationMessage = 'WE3 Violation: 4 out of the last 5 data points are on the same side of the centerline and ' .
							                    'exceed the 1 sigma value(' . ($aboveSigma > 1 ? $upperSigma : $lowerSigma) . ').';
						}
					}
					break;
					
					
					// Eight consecutive points fall on the same side of the centerline
					case 'WE4':
					// we need at least 8 data points
					if( $measurementResult && count($measurementResult) >= 8 ) {
						$aboveCenter = 0;
						$belowCenter = 0;
						$centerline = $metric->getCenterline();
						
						foreach( $measurementResult as $i => $measurement ) {
							// stop after 8
							if( $i == 8 ) break;
							
							// add data point to data points array
							$dataPoints[] = $measurement;
							
							// check position relative to 1 sigma
							if( floatval($measurement['Value']) > $centerline ) $aboveCenter += 1;
							else if( floatval($measurement['Value']) < $centerline ) $belowCenter += 1;
						}
						
						// if 8 are above or below center then it is a violation
						if( $aboveCenter > 7 || $belowCenter > 7 ) { 
							$newRuleStatus = $ruleRow['Violation_Status'];
								
							$violationMessage = 'WE4 Violation: The last 8 consecutive data points are on the same side of the centerline(' . $centerline . ').';
							
						}
					}
					break;
					
					
					// Six points in a row increasing or decreasing.
					case 'WE5':
					// we need at least 7 data points to check for 6 sequential increases or decreases
					if( $measurementResult && count($measurementResult) >= 7 ) {
						$increasedCount = 0;
						$decreasedCount = 0;
						$centerline = $metric->getCenterline();
						
						foreach( $measurementResult as $i => $measurement ) {
							// stop after 6
							if( $i == 6 ) break;
							
							// add data point to data points array
							$dataPoints[] = $measurement;
							
							// NOTE: measurementResult values are in descending order!
							// check data point against previous value for increase or decrease
							if( floatval($measurement['Value']) > floatval($measurementResult[$i + 1]['Value']) ) $increasedCount += 1;
							else if( floatval($measurement['Value']) < floatval($measurementResult[$i + 1]['Value']) ) $decreasedCount += 1;
						}
						
						// if 6 increases or decreases in a row then it is a violation
						if( $increasedCount > 5 || $decreasedCount > 5 ) { 
							$newRuleStatus = $ruleRow['Violation_Status'];
								
							$violationMessage = 'WE5 Violation: 6 consecutive data points ' . ($increasedCount > 5 ? 'increased.' : 'decreased.');
						}
					}
					break;
					
					
					// Fifteen points in a row within one sigma limits.
					case 'WE6':
					// we need at least 15 data points
					if( $measurementResult && count($measurementResult) >= 15 ) {
						$withinSigma = 0;
						
						// 1 sigma limits
						$upperSigma = ($metric->getUCL() !== FALSE ? round($metric->getCenterline() + (($metric->getUCL() - $metric->getCenterline()) / 3), $metric->getPrecision()) : $metric->getCenterline());
						$lowerSigma = ($metric->getLCL() !== FALSE ? round($metric->getCenterline() - (($metric->getCenterline() - $metric->getLCL()) / 3), $metric->getPrecision()) : $metric->getCenterline());
							
						foreach( $measurementResult as $i => $measurement ) {
							// stop after 15
							if( $i == 15 ) break;
						
							// add data point to data points array
							$dataPoints[] = $measurement;

							// check to see if data point is within 1 sigma limits
							if( floatval($measurement['Value']) <= $upperSigma && floatval($measurement['Value']) >= $lowerSigma ) {
								$withinSigma += 1;
							}
						}
						
						// if fifteen points within 1 sigma then it is a violation
						if( $withinSigma >= 15 ) {
							$newRuleStatus = $ruleRow['Violation_Status'];
							
							$violationMessage = 'WE6 Violation: The last 15 data points are within 1 sigma(' . $upperSigma . ', ' . $lowerSigma . ').';
						}
					}
					break;
					
					
					// Fourteen points in a row alternating in direction.
					case 'WE7':
					// we need at least 16 data points
					if( $measurementResult && count($measurementResult) >= 16 ) {
						$alternatingPoints = 0;
						$direction = NULL;
						
						foreach( $measurementResult as $i => $measurement ) {
							// stop after 15
							if( $i == 15 ) break;
						
							// add data point to data points array
							$dataPoints[] = $measurement;
							
							// determine direction of change relative to previous data point
							if( floatval($measurement['Value']) > floatval($measurementResult[$i + 1]['Value']) ) {
								// increasing
								$newDirection = 'increase';
							}
							else if( floatval($measurement['Value']) < floatval($measurementResult[$i + 1]['Value']) ) {
								$newDirection = 'decrease';
							}
							else {
								// flat lined
								$newDirection = NULL;
							}
							
							// determine if new direction is alternating
							if( !is_null($direction) && !is_null($newDirection) && $direction != $newDirection ) $alternatingPoints += 1;
							
							// note new direction for next data point
							$direction = $newDirection;
						}
						
						// if fourteen points alternating then it is a violation
						if( $alternatingPoints >= 14 ) {
							$newRuleStatus = $ruleRow['Violation_Status'];
							
							$violationMessage = 'WE7 Violation: The last 14 data points are alternating.';
						}
					}
					break;
					
									
					// Eight points in a row outside one sigma limits.
					case 'WE8':
					// we need at least 8 data points
					if( $measurementResult && count($measurementResult) >= 8 ) {
						$outsideSigma = 0;
						
						// 1 sigma limits
						$upperSigma = ($metric->getUCL() !== FALSE ? round($metric->getCenterline() + (($metric->getUCL() - $metric->getCenterline()) / 3), $metric->getPrecision()) : $metric->getCenterline());
						$lowerSigma = ($metric->getLCL() !== FALSE ? round($metric->getCenterline() - (($metric->getCenterline() - $metric->getLCL()) / 3), $metric->getPrecision()) : $metric->getCenterline());
							
						foreach( $measurementResult as $i => $measurement ) {
							// stop after 8
							if( $i == 8 ) break;
						
							// add data point to data points array
							$dataPoints[] = $measurement;

							// check to see if data point is ouside 1 sigma limits
							if( floatval($measurement['Value']) > $upperSigma || floatval($measurement['Value']) < $lowerSigma ) {
								$outsideSigma += 1;
							}
						}
						
						// if eight points outside 1 sigma then it is a violation
						if( $outsideSigma >= 8 ) {
							$newRuleStatus = $ruleRow['Violation_Status'];
							
							$violationMessage = 'WE8 Violation: The last 8 data points are outside 1 sigma(' . $upperSigma . ', ' . $lowerSigma . ').';
						}
					}
					break;
					
					
				}
				
				
				
				// set the new metric status if changed
//				if( $ruleRow['Status'] != $newRuleStatus ) {
					// if the spc run should update the status then do so
					if( $updateStatus && $ruleRow['Status'] != $newRuleStatus ) {
						// set the new status value
						$this->setStatus($metricName, $ruleRow['Type'], $newRuleStatus);
					}
					
					// if this is a violation then update result
					if( $newRuleStatus != 'READY' ) {
						$spcResult->violation = TRUE;
						
						// add this rule type to the list of violated rules
						if( !in_array($ruleRow['Type'], $spcResult->violationRules) ) $spcResult->violationRules[] = $ruleRow['Type'];
						
						// merge data points with list of violation points
						foreach($dataPoints as $dataPoint) {
							$mergePoint = TRUE;
							
							foreach( $spcResult->violationData as $vd ) {
								if( $vd['Value'] == $dataPoint['Value'] && $vd['Timestamp'] == $dataPoint['Timestamp'] ) {
									// this point is already in the violation array
									$mergePoint = FALSE;
									break;
								}
							}
							
							if( $mergePoint ) $spcResult->violationData[] = $dataPoint;
						}
						
						// add the violation message to the array of messages
						$spcResult->violationMessages[] = $violationMessage;
					}
//				}
			} // end of ruleResult foreach
		} // end of if ruleResult check for rules
		
		// return the spc result object
		
		// reverse violation data so it will be in ascending order
		$spcResult->violationData = array_reverse($spcResult->violationData);
		
		return $spcResult;
	}
	
	
	// set rule status
	public function setStatus($metricName, $ruleType, $ruleStatus) {
		$db = new DB;
		$sql = "UPDATE Rules SET Status='" . $db->escapeString($ruleStatus) . "' " .
		       "WHERE Metric_Name='" . $db->escapeString($metricName) . "' AND Type='" . $db->escapeString($ruleType) . "'";
		return $db->runQuery($sql);
	}
	
  
}

?>
