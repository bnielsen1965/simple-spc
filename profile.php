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
include HEADERS . 'authenticated_header.php';


// make sure not in demo mode
if( !DEMO_MODE ) {
	// change password
	if( isset($_REQUEST['change_password']) ) {
		$user->changePassword($_REQUEST['new_password']);
	}
}

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />

<link rel="stylesheet" type="text/css" href="css/style.css" />

<title>Profile</title>

</head>
<body>
<?php include 'fragments/menu.php'; ?>

<div id="content">

	<form method="post" style="padding:50px 10px;"><input type="password" name="new_password"><input type="submit" name="change_password" value="Change Password"></form>

</div>

<?php include 'fragments/footer.php'; ?>

</body>
</html>