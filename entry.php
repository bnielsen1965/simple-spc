<?php
date_default_timezone_set(@ date_default_timezone_get());


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