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
 * A basic database class, currently only supports mysqli
 */
class DB {
	private $mysqli;
	private $errorMessages;
	
	
	public function __construct($dbHost = NULL, $dbUsername = NULL, $dbPassword = NULL, $dbName = NULL) {
		// if values not passed then use constants
		if( is_null($dbHost) ) $dbHost = DATABASE_HOST;
		if( is_null($dbUsername) ) $dbUsername = DATABASE_USERNAME;
		if( is_null($dbPassword) ) $dbPassword = DATABASE_PASSWORD;
		if( is_null($dbName) ) $dbName = DATABASE_NAME;
		
		// start an empty error message array
		$this->errorMessages = array();
		
		// if we have all values then attempt database connection
		if( !is_null($dbHost) && !is_null($dbUsername) && !is_null($dbPassword) && !is_null($dbName) ) {
			$this->mysqli = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
			
			if( $this->mysqli->connect_errno ) {
				$this->setErrorMessage("Failed to connect to MySQL: (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error);
				return FALSE;
			}
			
			return TRUE;
		}
		
		return FALSE;
	}
	
	
	
	public function runQuery($sql) {
		$result = $this->mysqli->query($sql);
		
		if( $result === FALSE ) {
			$this->setErrorMessage($this->mysqli->error);
		}
		
		if( $result === FALSE || $result === TRUE ) return $result;
		
		// build return array from result
		$returnArray = array();
		
		while( $row = $result->fetch_assoc() ) $returnArray[] = $row;
		
		$result->free();
		
		return $returnArray;
	}
	
	
	public function escapeString($str) {
		// handle magic quotes if exists
		if( function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() && is_string($str) ) {
			$str = stripslashes($str);
		}
		
		return $this->mysqli->real_escape_string($str);
	}
	
	
	public function getErrorMessages() {
		return $this->errorMessages;
	}
	
	
	public function clearErrorMessages() {
		$this->errorMessages = array();
	}
	
	
	private function setErrorMessage($errorMessage) {
		$this->errorMessages[] = $errorMessage;
	}
}

?>
