<?php

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
