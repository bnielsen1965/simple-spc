<?php

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

?>
<html>
<head>
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