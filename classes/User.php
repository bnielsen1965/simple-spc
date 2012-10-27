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
 * A basic user class to provide user authentication, administration, etc.
 *
 */
class User {
	private $userArray;
  
	/*
	 * User constructor
	 */
	public function __construct($username = NULL) {
		$this->userArray = array();
		if( !empty($username) ) $this->loadUser($username);
	}
	
	
	/*
	 * Load session with user information
	 */
	public function loadUser($username) {
		$db = new DB;
		
		$sql = "SELECT * FROM Users WHERE username = '" . $db->escapeString($username) . "'";
		
		$result = $db->runQuery($sql);
		if( !empty($result) && count($result) > 0 ) {
			// fields to exclude from the user array
			$excludeFields = array('password');
			
			foreach( $result[0] as $field => $value ) if( !in_array($field, $excludeFields) ) $this->userArray[$field] = $value;
			$_SESSION['userArray'] = $this->userArray;
		}
	}
	
	
	// attempt authentication of user
	public function authenticate($username, $password, $loadUser = TRUE) {
		$db = new DB;
		
		$sql = "SELECT * FROM Users WHERE username = '" . $db->escapeString($username) . "'";
		
		$result = $db->runQuery($sql);
		
		if( !empty($result) && count($result) > 0 ) {
			if( $result[0]['password'] == $this->hashPassword($password, $result[0]['password']) ) {
				// make sure account is enabled
				if( $this->isACLFlagSet(ACL_ENABLED_MASK, $username) == FALSE ) return FALSE;
				
				// load user if required
				if( $loadUser ) {
					$this->loadUser($username);
				}
				
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	
	// determine if user is authenticated and has ACL flag if specified
	public function authenticated($ACLFlags = NULL) {
		// check session for user info
		if( empty($_SESSION['userArray']) ) return FALSE;
		
		$db = new DB;
		
		// if ACL flags specified then check user's flags
		if( !is_null($ACLFlags) ) {
			
			// get the current flag settings
			$sql = "SELECT * FROM Users WHERE username = '" . $db->escapeString($_SESSION['userArray']['username']) . "'";
			
			$result = $db->runQuery($sql);
			
			// if flags returned then update with new flag setting
			if( !empty($result) && count($result) > 0 ) {
				if( !($ACLFlags & $result[0]['acl_flags']) ) return FALSE;
			}
			else return FALSE;
		}
		
		return TRUE;
	}
	
	
	// check to see if user's session contains admin flag
	public function isAdmin($username = NULL) {
		// check flags against admin mask
		return $this->isACLFlagSet(ACL_ADMIN_MASK, $username);
	}
	
	
	// create a new user
	public function createUser($username, $password) {
		// verify user has admin permission
		if( $this->isAdmin() && !empty($username) ) {
			$db = new DB;
			
			$sql = "INSERT INTO Users(username, password) VALUES (" .
			       "'" . $db->escapeString($username) . "', " .
			       "'" . $db->escapeString($this->hashPassword($password)) . 
			       "')";
			
			if( $db->runQuery($sql) !== FALSE ) {
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	
	// delete user
	public function deleteUser($username) {
		// verify user has admin permission
		if( $this->isAdmin() && !empty($username) ) {
			$db = new DB;
			
			$sql = "DELETE FROM Users WHERE username='" . $db->escapeString($username) . "'";
			
			if( $db->runQuery($sql) !== FALSE ) {
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	
	// determine if username is available
	public function usernameAvailable($username) {
		if( empty($username) ) return FALSE;
		
		$db = new DB;
		
		$sql = "SELECT * FROM Users WHERE username='" . $db->escapeString($username) . "'";
		$rs = $db->runQuery($sql);
		
		if( is_array($rs) && count($rs) > 0 ) return FALSE;
		else return TRUE;
	}
	
	
	// determine if username exists
	public function usernameExists($username) {
		return !$this->usernameAvailable($username);
	}
	
	
	// set a user's ACL flag
	public function setACLFlag($username, $flagMask) {
		// verify user has admin permission
		if( $this->isAdmin() ) {
			$db = new DB;
			
			// get the current flag settings
			$sql = "SELECT * FROM Users WHERE username = '" . $db->escapeString($username) . "'";
			
			$result = $db->runQuery($sql);
  	    
			// if flags returned then update with new flag setting
			if( !empty($result) && count($result) > 0 ) {
				$newFlags = $result[0]['acl_flags'] | $flagMask;
				$sql = "UPDATE Users SET acl_flags = " . $newFlags . " WHERE username = '" . $db->escapeString($username) . "'";
				
				return $db->runQuery($sql);
			}
			else return FALSE;
		}
		else return FALSE;
	}
	
	
	// set a user's ACL flags
	public function setACLFlags($username, $flagsMask) {
		// verify user has admin permission
		if( $this->isAdmin() ) {
			$db = new DB;
			
			$sql = "UPDATE Users SET acl_flags = " . $flagsMask . " WHERE username = '" . $db->escapeString($username) . "'";
				
			return $db->runQuery($sql);
		}
		else return FALSE;
	}
	
	
	// test if user ACL flag is set
	public function isACLFlagSet($flagMask, $username = NULL) {
		// proceed with flag test if username set
		if( !empty($username) ) {
			$db = new DB;
			
			$sql = "SELECT acl_flags FROM Users WHERE username = '" . $db->escapeString($username) . "'";
			
			if( ($rs = $db->runQuery($sql)) === FALSE || count($rs) == 0 ) return FALSE;
			else {
				return (($flagMask & intval($rs[0]['acl_flags'])) > 0 ? TRUE : FALSE);
			}
		}
		else {
			// username not set, use the session flags for the test
			if( empty($_SESSION['userArray']['acl_flags']) ) return FALSE;
			else return (($flagMask & intval($_SESSION['userArray']['acl_flags'])) > 0 ? TRUE : FALSE);
		}
		
		return FALSE;
	}
	
	
	// change user password
	public function changePassword($password, $username = NULL) {
		// only admin can change other user's password
		if( !is_null($username) && !$this->isAdmin() ) {
			return FALSE;
		}
		else {
			if( is_null($username) && !empty($_SESSION['userArray']['username']) ) $username = $_SESSION['userArray']['username'];
		}
		
		if( !empty($username) ) {
			$db = new DB;
			
			$sql = "UPDATE Users SET password = '" . $db->escapeString($this->hashPassword($password)) . "' " .
			       "WHERE username = '" . $db->escapeString($username) . "'";
			
			if( $db->runQuery($sql) === FALSE ) return FALSE;
			else return TRUE;
		}
		
		return FALSE;
	}
	
	
	public function getUsername() {
		if( isset($_SESSION['userArray']['username']) ) return $_SESSION['userArray']['username'];
		else return FALSE;
	}
	
	
	// get user count
	public function getUserCount() {
		$db = new DB;
		
		$sql = "SELECT count(username) AS count FROM Users";
		$result = $db->runQuery($sql);
		
		if( $result === FALSE || count($result) == 0 ) return FALSE;
		
		return $result[0]['count'];
	}
	
	
	// get user list
	public function getUserList($offset = 0, $limit = 10) {
		$db = new DB;
		
		$sql = "SELECT * FROM Users ORDER BY username ASC LIMIT " . $offset . ", " . $limit;
		$result = $db->runQuery($sql);
		
		if( $result === FALSE ) return FALSE;
		
		$list = array();
		foreach( $result as $row ) $list[] = array('username' => $row['username'], 'acl_flags' => $row['acl_flags']);
		
		return $list;
	}
	
	
	// end a user's session, i.e. log out
	public function endSession() {
		// clear session variables
		session_regenerate_id(true);
		session_unset();
		session_destroy();
		session_write_close();
		setcookie(session_name(),'',0,'/');
	}
	
	
	// hash the provided password, create salt if not provided  
	public function hashPassword($password, $salt = NULL) {
		// if password empty then return empty
		if( empty($password) ) return '';
		
		if( is_null($salt) ) $salt = $this->bestSalt();
		
		return crypt($password, $salt);
	}
	
	
	// check for the existence of a user id
	public function userIDExists($user_id) {
		$db = new DB;
		
		$sql = "SELECT * FROM Users WHERE id = " . $db->escapeString($user_id);
		
		$result = $db->runQuery($sql);
		if( empty($result) || count($result) == 0 ) return FALSE;
		
		return TRUE;
	}
	
	
	// make a random phrase
	public function makephrase($len = 64, $alphanumeric = TRUE) {
		if( $alphanumeric === TRUE ) {
			$charlist = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
		}
		else $charlist = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!@#&*(),.{}[];:";
		$phrase = "";
		
		do {
			$phrase .= substr($charlist, mt_rand(0, strlen($charlist) - 1), 1);
		} while( --$len > 0 );
		
		return $phrase;
	}
	
	
	// determine the best salt to use for crypt function
	public function bestSalt() {
		if( defined('CRYPT_SHA512') && CRYPT_SHA512 == 1 ) return '$6$' . $this->makePhrase(16) . '$';
		if( defined('CRYPT_SHA256') && CRYPT_SHA256 == 1 ) return '$5$' . $this->makePhrase(16) . '$';
		if( defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH == 1 ) return '$2a$07$' . base64_encode($this->makePhrase(22)) . '$';
		if( defined('CRYPT_MD5') && CRYPT_MD5 == 1 ) return '$1$' . $this->makePhrase(12) . '$';
		if( defined('CRYPT_EXT_DES') && CRYPT_EXT_DES == 1 ) return '_' . $this->makePhrase(8);
		if( defined('CRYPT_STD_DES') && CRYPT_STD_DES == 1 ) return $this->makePhrase(2);
		return '';
	}
  
}

?>
