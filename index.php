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

// process logout
if( !empty($_REQUEST['logout']) ) {
	$user->endSession();
}

// process login
if( isset($_REQUEST['login']) ) {
	$user->authenticate($_REQUEST['username'], $_REQUEST['password']);
}

// change password
if( isset($_REQUEST['change_password']) ) {
	$user->changePassword($_REQUEST['new_password']);
}

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />

<title>Home</title>
<link rel="stylesheet" type="text/css" href="css/style.css" />
<link rel="shortcut icon" href="images/favicon.ico" />
</head>
<body>

<?php

if( $user->authenticated() ) {

	include 'fragments/menu.php';
?>

<div id="content">
	<div class="login_box">
		<br />
		<br />
		<span class="welcome">Welcome <?php echo $user->getUsername(); ?></span>
	</div>
</div>

<?php

}
else {
	// show the login form is user is not authenticated
?>

<div id="content">
	<div class="login_box">
		<form method="post" action="<?php echo LOGIN_URL; ?>">
		<table>
		<tr><th colspan="2">Simple SPC Log In</th></tr>
		<tr><td style="width:8em;">Username: </td><td style="width:15em;"><input type="text" name="username"></td></tr>
		<tr><td>Password: </td><td><input type="password" name="password"></td></tr>
		<tr><td></td><td><input type="submit" name="login" value="Log In"></td></tr>
		</table>
		</form>
	</div>
</div>

<?php

}

?>

<?php include 'fragments/footer.php'; ?>

</body>
</html>