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

// allow only operator permissions
include HEADERS . 'operator_header.php';

// metric object needed for the metric selection form
$metric = new Metric;

?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="css/style.css" />

<title>Charts</title>

<script type="text/javascript" src="js/jquery.min.js"></script>

<?php include 'fragments/controlchart_resources.php'; ?>

<script type="text/javascript">
$(document).ready(function() {
	$('form#view_chart').find('select[name="Name"]').change(function() {
		viewChart();
	});
	
});


// view the selected chart
function viewChart() {
	// get selected chart name
	chartName = $('form#view_chart').find('select[name="Name"]').val();
	
	// if we have a name then load the chart
	if( chartName.length > 0 ) {
		// get chart details
		charturl = 'dataJSON.php?action=ChartData&Name=' + chartName;
		chartTitle = 'Control Chart: ' + chartName;
			
		// load the chart
		$('#chart2').controlchart({'chartURL': charturl, 'chartTitle': chartTitle});
	}
	else {
		$('#chart_description').html('No chart selected');
	}
}
</script>

</head>
<body>
<?php include 'fragments/menu.php'; ?>

<div id="content">
	<form method="post" id="view_chart">
	<table>
		<tr><td>Metric Name: </td>
		<td><select name="Name">
		<option value="">Select Metric</option>
		<?php echo $metric->metricOptions((!empty($_REQUEST['Name']) ? $_REQUEST['Name'] : NULL)); ?>
		</select></td></tr>
		<tr><td></td><td><input type="button" name="view_chart" value="View Chart" onclick="viewChart(); return false;"></td></tr>
	</table>
	</form>
	
	<div id="chart2" class="control_chart"></div>
	
	<div id="chart_description"></div>
	
	<div id="violation_messages"></div>
</div>

<?php include 'fragments/footer.php'; ?>

</body>
</html>