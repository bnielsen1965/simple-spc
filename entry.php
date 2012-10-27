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
include HEADERS . 'operator_header.php';

$metric = new Metric;
$rule = new Rule;


if( isset($_REQUEST['save_entry']) ) {
	if( !is_numeric($_REQUEST['Value']) ) {
		$errorMessage =  '<span class="error">Value is not numeric!</span>';
	}
	else {
		$success = $metric->saveMeasurement($_REQUEST['Name'], $_REQUEST['Value']);
		
		if( $success === FALSE ) $errorMessage = '<span class="error">Error occured saving entry!</span>';
	}
}


?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="css/style.css" />

<title>Enter Measurement</title>


<script type="text/javascript" src="js/jquery.min.js"></script>

<?php include 'fragments/controlchart_resources.php'; ?>

<?php
// if metric name was posted then load chart
if( !empty($_REQUEST['Name']) ) {
	?>
<script type="text/javascript">
$(document).ready(function() {
	charturl = 'dataJSON.php?action=ChartData&Name=<?php echo $_REQUEST['Name']; ?>'; // + $(this).val();
	chartTitle = 'Control Chart: <?php echo $_REQUEST['Name']; ?>'; // + $(this).val();
		
	$('#chart2').controlchart({'chartURL': charturl, 'chartTitle': chartTitle});
});
</script>
	<?php
}
?>


</head>
<body>
<?php include 'fragments/menu.php'; ?>

<div id="content">
	<form method="post" id="save_entry">
	<table>
		<tr><td>Metric Name: </td>
		<td><select name="Name">
			<option value="">Select Metric</option>
			<?php echo $metric->metricOptions((!empty($_REQUEST['Name']) ? $_REQUEST['Name'] : NULL)); ?>
		</select></td></tr>
		<tr><td>Value: </td><td><input type="text" name="Value" value="<?php echo (!empty($_REQUEST['Value']) && empty($success) ? $_REQUEST['Value'] : ''); ?>"></td></tr>
		<tr><td></td><td><input type="submit" name="save_entry" value="Save Entry"></td></tr>
	</table>
	</form>
	
	<div id="chart2" class="control_chart"></div>
	
	<div id="chart_description"></div>
	
	<div id="violation_messages"></div>
</div>

<?php include 'fragments/footer.php'; ?>

</body>
</html>