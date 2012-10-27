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
class Metric {
	private $metricArray;
  
	/*
	 * User constructor
	 */
	public function __construct($metricName = NULL) {
		$this->metricArray = array();
		if( !empty($metricName) ) $this->loadMetric($metricName);
	}
	
	
	/*
	 * Load metric
	 */
	public function loadMetric($metricName) {
		$db = new DB;
		
		$sql = "SELECT * FROM Metrics WHERE Name = '" . $db->escapeString($metricName) . "'";
		
		$result = $db->runQuery($sql);
		if( !empty($result) && count($result) > 0 ) {
			foreach( $result[0] as $field => $value ) $this->metricArray[$field] = $value;
		}
	}
	
	
	// create a new metric
	public function createMetric($metricName, $metricDescription, $metricCenterline, $metricUCL, $metricLCL, $metricPrecision) {
		$db = new DB;
		
		// check if name is available
		
			$sql = "INSERT INTO Metrics(Name, Description, Centerline, UCL, LCL, DecimalPrecision) VALUES (" .
			       "'" . $db->escapeString($metricName) . "', " .
			       "'" . $db->escapeString($metricDescription) . "', " .
			       (!is_numeric($metricCenterline) ? '0' : $db->escapeString($metricCenterline)) . ", " .
			       (!is_numeric($metricUCL) ? 'NULL' : $db->escapeString($metricUCL)) . ", " .
			       (!is_numeric($metricLCL) ? 'NULL' : $db->escapeString($metricLCL)) . ", " .
			       (!is_numeric($metricPrecision) ? '4' : $db->escapeString($metricPrecision)) . 
			       ")";
			
			if( $db->runQuery($sql) !== FALSE ) {
				return TRUE;
			}
		//}
		
		return FALSE;
	}
	
	
	// delete
	public function deleteMetric($metricName) {
			$db = new DB;
			
			$sql = "DELETE FROM Metrics WHERE Name='" . $db->escapeString($metricName) . "'";
			
			if( $db->runQuery($sql) !== FALSE ) {
				return TRUE;
			}
		
		return FALSE;
	}
	
	
	// determine if name is available
	public function nameAvailable($metricName) {
		if( empty($metricName) ) return FALSE;
		
		$db = new DB;
		
		$sql = "SELECT * FROM Metrics WHERE Name='" . $db->escapeString($metricName) . "'";
		$rs = $db->runQuery($sql);
		
		if( is_array($rs) && count($rs) > 0 ) return FALSE;
		else return TRUE;
	}
	
	
	// determine if name exists
	public function nameExists($metricName) {
		return !$this->nameAvailable($metricName);
	}
	
	
	// change metric name
	public function changeName($oldMetricName, $newMetricName) {
		// validation
		if( !$this->nameAvailable($newMetricName) ) return FALSE;
		if( !$this->nameExists($oldMetricName) ) return FALSE;
		
			$db = new DB;
			
			$sql = "UPDATE Metrics SET Name = '" . $db->escapeString($newMetricName) . "' " .
			       "WHERE Name = '" . $db->escapeString($oldMetricName) . "'";
			
			if( $db->runQuery($sql) === FALSE ) return FALSE;
			else return TRUE;
		
		return FALSE;
	}
	
/*	
	// change metric description
	public function changeDescription($metricName, $metricDescription) {
		// validation
		if( !$this->nameExists($metricName) ) return FALSE;
		
		$db = new DB;
		
		$sql = "UPDATE Metrics SET Description = '" . $db->escapeString($metricDescription) . "' " .
		       "WHERE Name = '" . $db->escapeString($metricName) . "'";
		
		if( $db->runQuery($sql) === FALSE ) return FALSE;
		else return TRUE;
	}
	*/
	
	// update metric details
	public function updateMetric($metricName, $metricDescription, $metricCenterline, $metricUCL, $metricLCL, $metricPrecision) {
		// validation
		if( !$this->nameExists($metricName) ) return FALSE;
		
		$db = new DB;
		
		$sql = "UPDATE Metrics SET Description = '" . $db->escapeString($metricDescription) . "', " .
		       "Centerline = " . (!is_numeric($metricCenterline) ? '0' : $db->escapeString($metricCenterline)) . ", " . 
		       "UCL = " . (!is_numeric($metricUCL) ? 'NULL' : $db->escapeString($metricUCL)) . ", " . 
		       "LCL = " . (!is_numeric($metricLCL) ? 'NULL' : $db->escapeString($metricLCL)) . ", " .
		       "DecimalPrecision = " . (!is_numeric($metricPrecision) ? '4' : $db->escapeString($metricPrecision)) . " " . 
		       "WHERE Name = '" . $db->escapeString($metricName) . "'";   
		
		if( $db->runQuery($sql) === FALSE ) return $sql; //return FALSE;
		else return TRUE;
	}
	
	
	// get metric count
	public function getMetricCount() {
		$db = new DB;
		
		$sql = "SELECT count(Name) AS count FROM Metrics";
		$result = $db->runQuery($sql);
		
		if( $result === FALSE || count($result) == 0 ) return FALSE;
		
		return $result[0]['count'];
	}
	
	
	// get metric list
	public function getMetricList($offset = 0, $limit = 10) {
		$db = new DB;
		
		$sql = "SELECT * FROM Metrics ORDER BY Name ASC LIMIT " . $offset . ", " . $limit;
		$result = $db->runQuery($sql);
		
		if( $result === FALSE ) return FALSE;
		
		$list = array();
		foreach( $result as $row ) $list[] = $row;
		
		return $list;
	}
	
	
	/*
	 * Return HTML option tag string for metric names
	 */
	public function metricOptions($selected) {
		$db = new DB;
		
		$sql = "SELECT Name, Description FROM Metrics ORDER BY Name ASC";
		$result = $db->runQuery($sql);
		
		$optionString = '';
		
		if( $result ) {
			foreach( $result as $row ) {
				$optionString .= '<option data="' . htmlspecialchars($row['Description']) . '" value="' . htmlspecialchars($row['Name']) . '"' .
				(!empty($selected) && $selected == $row['Name'] ? ' selected' : '') .
				'>' . htmlspecialchars($row['Name']) . '</option>' . "\n";
			}
		}
		
		return $optionString;
	}
	
	
	
	/*
	 * Save a metric measurement
	 */
	public function saveMeasurement($metricName, $measurementValue) {
		// make sure measurement is numeric
		if( !is_numeric($measurementValue) ) return FALSE;
		
		$db = new DB;
		
		$sql = "INSERT INTO Measurements (Metric_Name, Value) VALUES('" . $db->escapeString($metricName) . "', " . $db->escapeString($measurementValue) . ")";
		
		if( $db->runQuery($sql) === FALSE ) return FALSE;
		
		// run SPC rule checks
		$rule = new Rule;
		$rule->runSPC($metricName);
		
		return TRUE;
	}
	
	
	// get UCL
	public function getUCL($metricName = NULL) {
		if( isset($this->metricArray['UCL']) ) return $this->metricArray['UCL'];
		else return FALSE;
	}
	
  
	// get UCL
	public function getLCL($metricName = NULL) {
		if( isset($this->metricArray['LCL']) ) return $this->metricArray['LCL'];
		else return FALSE;
	}
	
  
	// get UCL
	public function getCenterline($metricName = NULL) {
		if( isset($this->metricArray['Centerline']) ) return $this->metricArray['Centerline'];
		else return FALSE;
	}
	

	// get Precision
	public function getPrecision($metricName = NULL) {
		if( isset($this->metricArray['DecimalPrecision']) ) return $this->metricArray['DecimalPrecision'];
		else return FALSE;
	}


	// get Description
	public function getDescription($metricName = NULL) {
		if( isset($this->metricArray['Description']) ) return $this->metricArray['Description'];
		else return FALSE;
	}
}

?>
