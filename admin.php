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
include HEADERS . 'admin_header.php';


// AJAX action request
if( isset($_REQUEST['action']) ) {
	$errorMessages = array();
	$json = array();
	$success = FALSE;
	
	switch( $_REQUEST['action'] ) {
		case 'createUser':
		// make sure username is available
		if( $user->usernameAvailable($_REQUEST['username']) ) {
			// make sure not in demo mode
			if( !DEMO_MODE ) {
				// attempt to create the user
				if( $user->createUser($_REQUEST['username'], $_REQUEST['password']) ) {
					// successfully created user
					$success = TRUE;
					
					// set the user's access control list flags
					$user->setACLFlags($_REQUEST['username'], $_REQUEST['acl_flags']);
				}
				else $errorMessages[] = 'Error occurred creating user';
			}
			else {
				$errorMessages[] = 'Cannot create user in demo mode';
			}
		}
		else $errorMessages[] = 'Username not available';
		break;
		
		
		case 'deleteUser':
		// make sure not in demo mode
		if( !DEMO_MODE ) {
			// attempt to delete the user
			if( $user->deleteUser($_REQUEST['username']) ) {
				// successfully deleted user
				$success = TRUE;
			}
			else $errorMessages[] = 'Error occurred deleting user';
		}
		else {
			$errorMessages[] = 'Cannot delete user in demo mode';
		}
		break;

		
		case 'updateUser':
		// make sure not in demo mode
		if( !DEMO_MODE ) {
			// if a new password was provided then change password
			if( !empty($_REQUEST['password']) ) {
				$user->changePassword($_REQUEST['password'], $_REQUEST['username']);
			}
			
			// set new access control list flags
			$user->setACLFlags($_REQUEST['username'], $_REQUEST['acl_flags']);
			$success = TRUE;
		}
		else {
			$errorMessages[] = 'Cannot update user in demo mode';
		}
		break;
		
		
		case 'getUserList':
		// get user count
		$count = $user->getUserCount();
		
		// if database has users then create a list
		if( !empty($count) ) {
			// start with an empty array
			$list = array();
			
			// determine the limit for the number of names in the list
			$limit = (!empty($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 3);
			
			// determine the offset into the table for pagination
			$offset = (!empty($_REQUEST['offset']) ? intval($_REQUEST['offset']) : 0);
			
			// get the user list
			$list = $user->getUserList($offset, $limit);
			
			// note any errors or store the list of users in the JSON array
			if( $list === FALSE ) $errorMessages[] = 'Error getting user list';
			else if( is_array($list) ) $json['userList'] = $list;
			
			// store the user count
			$json['userCount'] = $count;
			
			// store the limit that was used
			$json['limit'] = $limit;
			
			// store the pagination offset that was used
			$json['offset'] = $offset;
		}
		break;
		

		default:
		// the AJAX action request is unknown
		$errorMessages[] = 'Action unknown';
		break;
	}
	
	// send JSON
	$json['errorMessages'] = $errorMessages;
	$json['success'] = $success;
	
	// make sure json is not cached
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	
	echo json_encode($json);
	
	// script must end after echoing out JSON
	exit();
}


header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />

<link rel="stylesheet" type="text/css" href="css/style.css" />

<title>Admin</title>

<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript">
var listLimit = <?php echo USER_LIST_LIMIT; ?>;
var listOffset = 0;
var userList = null;
var userCount = 0;

$(document).ready(function () {
	// save user form submission action
	$('form#save_user').submit(function () {
		// calculate acl flags
		acl_flags = 0;
		$('input[name="acl_flags\\[\\]"]:checked').each(function () {
			acl_flags = acl_flags | parseInt($(this).val());
		});
		
		// make the save user AJAX call
		callJSON(
			{
				'save_user': 1,
				'action': $('input[name="action"]').val(),
				'username': $('input[name="username"]').val(),
				'password': $('input[name="password"]').val(),
				'acl_flags': acl_flags
			},
			function() {
				if( this.success ) {
					// clear the form
					clearForm();

					getUserList(listOffset);
				}
			}
		);
		
		return false;
	});
	
	// enable save user button now that page is loaded
	$('input[name="save_user"]').removeAttr('disabled');
	
	// load user list
	getUserList();
});


function clearForm() {
	// clear the form
	$('form#save_user').not(':checkbox').each(function() { this.reset(); });
	$('form#save_user').find(':checkbox').each(function() { $(this).removeAttr('checked'); });
	
	// set action to create
	$('form#save_user').find('input[name="action"]').val('createUser');
	$('form#save_user').removeClass('updating');
}


function deleteUser(elm) {
	// get the username from the element that was clicked
	deleteUsername = $(elm).attr('data');
	
	// make the delete user AJAX call
	callJSON(
		{
			'action': 'deleteUser',
			'username': deleteUsername
		},
		function() {
			// reload the user list
			getUserList(listOffset);
		}
	);
}


function editUser(elm) {
	// get the user JSON object from the element that was clicked
	userObj = eval('(' + $(elm).attr('data') + ')');
	
	// clear the form
	clearForm();
	
	// set save user action to update user
	$('form#save_user').find('input[name="action"]').val('updateUser');
	
	// set the user name in the form using the selected user
	$('form#save_user').find('input[name="username"]').val(userObj.username);
	
	// set the access control list check boxes in the form using the selected user
	$('input[name="acl_flags\\[\\]"]').each(function () {
		if( (parseInt(userObj.acl_flags) & parseInt($(this).val())) > 0 ) {
			$(this).attr('checked', 'checked');
		}
	});
	
	$('form#save_user').addClass('updating');
}


function getUserList(newOffset) {
	// if not specified then use the previous pagination offset, otherwise set the current to the specified offset
	if( typeof(newOffset) == 'undefined' ) newOffset = listOffset;
	else listOffset = newOffset;
	
	// make the AJAX call to get the paginated user list
	callJSON(
		{
			'action': 'getUserList',
			'offset': listOffset,
			'limit': listLimit
		},
		function () {
			// if a user list was returned then save results and show the list
			if( this.userList ) {
				userList = this.userList;
				userCount = this.userCount;
				
				showUserList();
			}
		}
	);
}


function showUserList() {
	// construct pagination controls if needed
	controlsHTML = '<li class="pagination_controls">';
	
	if( listOffset > 0 ) {
		if( listOffset - listLimit < 0 ) controlsHTML += '<a href="" onclick="getUserList(0); return false;">Previous</a>';
		else controlsHTML += '<a href="" onclick="getUserList(' + (listOffset - listLimit) + '); return false;">Previous</a>';
	}
	
	if( listOffset + listLimit < userCount ) controlsHTML += '<a href="" onclick="getUserList(' + (listOffset + listLimit) + '); return false;">Next</a>';
	
	controlsHTML += '</li>';
	
	
	// construct the unordered list of users
	listHTML = '<ul>';
	listHTML += controlsHTML;
	
	for(i = 0; i < userList.length; i++) {
		listHTML += '<li class="paged_item ' + (i%2 > 0 ? 'odd' : 'even') + '">';
		listHTML += '<label>' + userList[i].username + '</label>';
		listHTML += '<div style="float: right;">';
		listHTML += ' <a href="" data="{username: \'' + userList[i].username + '\', acl_flags: \'' + userList[i].acl_flags + '\'}" onclick="editUser(this); return false;">Edit</a>';
		listHTML += '&nbsp;&nbsp;<a href="" data="' + userList[i].username + '" onclick="deleteUser(this); return false;">Delete</a>';
		listHTML += '</div>';
		listHTML += '</li>';
	}
	
	listHTML += controlsHTML;
	listHTML += '</ul>';
	
	// show the new user list
	$('#paged_list').html(listHTML);
}


function callJSON(calldata, callback) {
	$.getJSON(
		'admin.php', calldata, function(response) {
			if( response.errorMessages.length > 0 ) {
				errMsg = '';
				for(i = 0; i < response.errorMessages.length; i++) errMsg += response.errorMessages[i] + "\n";
				alert(errMsg);
			}
			
			callback.call(response);
		}
	);
}

</script>

<style type="text/css">
</style>
</head>
<body>
<?php include 'fragments/menu.php'; ?>

<div id="content">
	<form method="post" id="save_user" onsubmit="return false;">
	<input type="hidden" name="action" value="createUser">
	<div style="float: left; width: 25em;">
		<table>
			<tr>
				<td>Username: </td><td><input type="text" name="username" value=""></td>
			</tr>
			<tr>
				<td>Password: </td><td><input type="password" name="password" value=""></td>
			</tr>
		</table>
	</div>
	<div style="float: left; width: 20em;">
		<input type="checkbox" name="acl_flags[]" value="<?php echo ACL_ADMIN_MASK; ?>"> Administrator<br>
		<input type="checkbox" name="acl_flags[]" value="<?php echo ACL_ENABLED_MASK; ?>"> Enabled<br>
		<input type="checkbox" name="acl_flags[]" value="<?php echo ACL_OPERATOR_MASK; ?>"> Operator<br>
		<input type="checkbox" name="acl_flags[]" value="<?php echo ACL_TECHNICIAN_MASK; ?>"> Technician<br>
		<input type="checkbox" name="acl_flags[]" value="<?php echo ACL_ENGINEER_MASK; ?>"> Engineer<br>
	</div>
	<div style="float: left; clear: left;">
		<input type="submit" name="save_user" value="Save User"> <input type="button" value="Cancel" onclick="clearForm(); return false;">
	</div>
	</form>
	
	<div style="float:left; width: 30em; clear: left; text-align: center;"><span class="large_title">User List</span></div>
	<div style="float:left; width: 30em; clear: left;" id="paged_list"></div>
</div>

<?php include 'fragments/footer.php'; ?>

</body>
</html>