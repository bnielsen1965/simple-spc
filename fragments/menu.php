
<div class="menu">
<ul>
<?php
/**
 * NOTE: we are assuming the $user variable already contains a User object
 */
 
$username = $user->getUsername();

if( !empty($username) ) echo '<li><a href="profile.php">[' . $username . ']</a></li>';

echo '<li><a href="' . SITE_URL . '">[Home]</a></li>';

if( $user->isAdmin() ) {
	echo '<li><a href="admin.php">[Admin]</a></li>';
}

if( $user->authenticated(ACL_OPERATOR_MASK | ACL_ENGINEER_MASK) ) {
	echo '<li><a href="entry.php">[Entry]</a></li>';
	echo '<li><a href="chart.php">[Charts]</a></li>';
}

if( $user->authenticated(ACL_ENGINEER_MASK) ) {
	echo '<li><a href="metrics.php">[Metrics]</a></li>';
}

if( $user->authenticated() ) echo '<li><a href="' . SITE_URL . '?logout=1">[Log Out]</a></li>';

?>
</ul>
</div>
