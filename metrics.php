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
include HEADERS . 'engineer_header.php';


// AJAX action request
if( isset($_REQUEST['action']) ) {
	$metric = new Metric;
	$errorMessages = array();
	$json = array();
	$success = FALSE;
	
	switch( $_REQUEST['action'] ) {
		case 'createMetric':
		// make sure name is available
		if( $metric->nameAvailable($_REQUEST['Name']) ) {
			// make sure not in demo mode
			if( !DEMO_MODE ) {
				// attempt to create the metric
				if( $metric->createMetric($_REQUEST['Name'], 
				                          $_REQUEST['Description'], 
				                          $_REQUEST['Centerline'], 
				                          $_REQUEST['UCL'], 
				                          $_REQUEST['LCL'],
				                          $_REQUEST['DecimalPrecision']) ) {
					// successfully created
					$success = TRUE;
				}
				else $errorMessages[] = 'Error occurred creating metric';
			}
			else {
				$errorMessages[] = 'Cannot create metric in demo mode';
			}
		}
		else $errorMessages[] = 'Name not available';
		break;
		
		
		case 'deleteMetric':
		// make sure not in demo mode
		if( !DEMO_MODE ) {
			// attempt to delete the metric
			if( $metric->deleteMetric($_REQUEST['Name']) ) {
				// successfully deleted
				$success = TRUE;
			}
			else $errorMessages[] = 'Error occurred deleting metric';
		}
		else {
			$errorMessages[] = 'Cannot delete metric in demo mode';
		}
		break;

		
		case 'updateMetric':
		// make sure not in demo mode
		if( !DEMO_MODE ) {
			// if metric name and old metric name do not match then update
			if( $_REQUEST['oldName'] != $_REQUEST['Name'] ) {
				// try changing name
				if( $metric->changeName($_REQUEST['oldName'], $_REQUEST['Name']) === FALSE ) {
					$errorMessages[] = 'Error changing metric name';
					break;
				}
			}
			
			// update details
			if( $metric->updateMetric($_REQUEST['Name'], 
			                          $_REQUEST['Description'], 
				                       $_REQUEST['Centerline'], 
				                       $_REQUEST['UCL'], 
				                       $_REQUEST['LCL'],
				                       $_REQUEST['DecimalPrecision']) === FALSE ) {
				$errorMessages[] = 'Error updating metric';
				break;
			}
			
			$success = TRUE;
		}
		else {
			$errorMessages[] = 'Cannot update metric in demo mode';
		}
		break;
		
		
		case 'getMetricList':
		// get user count
		$count = $metric->getMetricCount();
		
		// if database has users then create a list
		if( !empty($count) ) {
			// start with an empty array
			$list = array();
			
			// determine the limit for the number of names in the list
			$limit = (!empty($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 3);
			
			// determine the offset into the table for pagination
			$offset = (!empty($_REQUEST['offset']) ? intval($_REQUEST['offset']) : 0);
			
			// get the user list
			$list = $metric->getMetricList($offset, $limit);
			
			// note any errors or store the list of users in the JSON array
			if( $list === FALSE ) $errorMessages[] = 'Error getting metric list';
			else if( is_array($list) ) $json['metricList'] = $list;
			
			// store the user count
			$json['metricCount'] = $count;
			
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

?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="css/style.css" />

<title>Metrics</title>

<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript">
var listLimit = <?php echo METRIC_LIST_LIMIT; ?>;
var listOffset = 0;
var metricList = null;
var metricCount = 0;

$(document).ready(function () {
	// save user form submission action
	$('form#save_metric').submit(function () {
		
		// make the save user AJAX call
		callJSON(

			$(this).serialize(),
			
			function() {
				if( this.success ) {
					// clear the form
					clearForm();

					getMetricList(listOffset);
				}
			}
		);
		
		return false;
	});
	
	// enable save user button now that page is loaded
	$('input[name="save_metric"]').removeAttr('disabled');
	
	// load list
	getMetricList();
});


function clearForm() {
	// clear the form
	$('form#save_metric').not(':checkbox').each(function() { this.reset(); });
	$('form#save_metric').find(':checkbox').each(function() { $(this).removeAttr('checked'); });
	
	// set action to create
	$('form#save_metric').find('input[name="action"]').val('createMetric');
	$('form#save_metric').removeClass('updating');
}


function deleteMetric(metricIndex) {
	// get the name from the element that was clicked
	deleteMetricName = metricList[metricIndex].Name
	
	// make the delete user AJAX call
	callJSON(
		{
			'action': 'deleteMetric',
			'Name': deleteMetricName
		},
		function() {
			// reset offset
			listOffset = 0;
			
			// reload the list
			getMetricList(listOffset);
		}
	);
}


function editMetric(metricIndex) {
	// clear the form
	clearForm();
	
	// set action to update
	$('form#save_metric').find('input[name="action"]').val('updateMetric');
	
	// set the name in the form using the selected metric
	$('form#save_metric').find('input[name="Name"]').val(metricList[metricIndex].Name);
	$('form#save_metric').find('input[name="oldName"]').val(metricList[metricIndex].Name);
	
	// set the description
	$('form#save_metric').find('textarea[name="Description"]').val(metricList[metricIndex].Description);
	
	$('form#save_metric').find('input[name="Centerline"]').val(metricList[metricIndex].Centerline);
	$('form#save_metric').find('input[name="UCL"]').val(metricList[metricIndex].UCL);
	$('form#save_metric').find('input[name="LCL"]').val(metricList[metricIndex].LCL);
	$('form#save_metric').find('input[name="DecimalPrecision"]').val(metricList[metricIndex].DecimalPrecision);
	
	$('form#save_metric').addClass('updating');
}


function getMetricList(newOffset) {
	// if not specified then use the previous pagination offset, otherwise set the current to the specified offset
	if( typeof(newOffset) == 'undefined' ) newOffset = listOffset;
	else listOffset = newOffset;
	
	// make the AJAX call to get the paginated user list
	callJSON(
		{
			'action': 'getMetricList',
			'offset': listOffset,
			'limit': listLimit
		},
		function () {
			// if a user list was returned then save results and show the list
			if( this.metricList ) {
				metricList = this.metricList;
				metricCount = this.metricCount;
			}
			else {
				metricList = null;
			}
			
			showMetricList();
		}
	);
}


function showMetricList() {
	// if empty list then display empty list
	if( !metricList || metricList.length == 0 ) {
		$('#paged_list').html('No metrics');
		return;
	}
	
	
	// construct pagination controls if needed
	controlsHTML = '<li class="pagination_controls">';
	
	if( listOffset > 0 ) {
		if( listOffset - listLimit < 0 ) controlsHTML += '<a href="" onclick="getMetricList(0); return false;">Previous</a>&nbsp;&nbsp;&nbsp;';
		else controlsHTML += '<a href="" onclick="getMetricList(' + (listOffset - listLimit) + '); return false;">Previous</a>&nbsp;&nbsp;&nbsp;';
	}
	
	if( listOffset + listLimit < metricCount ) controlsHTML += '<a href="" onclick="getMetricList(' + (listOffset + listLimit) + '); return false;">Next</a>';
	
	controlsHTML += '</li>';
	
	
	// construct the unordered list
	listHTML = '<ul>';
	listHTML += controlsHTML;
	
	for(i = 0; i < metricList.length; i++) {
		listHTML += '<li class="paged_item ' + (i%2 > 0 ? 'odd' : 'even') + '">';
		listHTML += '<label>' + metricList[i].Name + '</label>';
		listHTML += '<div style="float: right;">';
		listHTML += '<a href="rules.php?metricName=' + metricList[i].Name + '">Rules</a>';
		listHTML += '&nbsp;&nbsp;<a href="" onclick="editMetric(' + i + '); return false;">Edit</a>';
		listHTML += '&nbsp;&nbsp;<a href="" onclick="deleteMetric(' + i + '); return false;">Delete</a>';

		listHTML += '</div>';
		listHTML += '</li>';
	}
	
	listHTML += controlsHTML;
	listHTML += '</ul>';
	
	// show the new list
	$('#paged_list').html(listHTML);
}


function callJSON(calldata, callback) {
	$.getJSON(
		'metrics.php', calldata, function(response) {
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


</head>
<body>
<?php include 'fragments/menu.php'; ?>

<div id="content">
	<form method="post" id="save_metric" onsubmit="return false;">
	<input type="hidden" name="action" value="createMetric">
	<input type="hidden" name="oldName" value="">
	<table>
		<tr>
			<td>Metric Name: </td>
			<td><input type="text" name="Name"></td>
			<td>&nbsp;&nbsp;<a class="helptip" href="#"><img src="images/helptip.png"><span>Select a name that is both unique and descriptive.</span></a></td>
		</tr>
		<tr>
			<td>Metric Description: </td>
			<td><textarea name="Description"></textarea></td>
			<td></td>
		</tr>
		<tr>
			<td>Centerline: </td>
			<td><input type="text" name="Centerline"></td>
			<td>&nbsp;&nbsp;<a class="helptip" href="#"><img src="images/helptip.png"><span>This is the target value for your measurement.</span></a></td>
		</tr>
		<tr>
			<td>Upper Control Limit: </td>
			<td><input type="text" name="UCL"></td>
			<td>&nbsp;&nbsp;<a class="helptip" href="#"><img src="images/helptip.png"><span>This is your upper 3 sigma value where applicable.</span></a></td>
		</tr>
		<tr>
			<td>Lower Control Limit: </td>
			<td><input type="text" name="LCL"></td>
			<td>&nbsp;&nbsp;<a class="helptip" href="#"><img src="images/helptip.png"><span>This is your lower 3 sigma value where applicable.</span></a></td>
		</tr>
		<tr>
			<td>Precision: </td>
			<td><input type="text" name="DecimalPrecision"></td>
			<td>&nbsp;&nbsp;<a class="helptip" href="#"><img src="images/helptip.png"><span>The number of decimal points to use.</span></a></td>
		</tr>
		<tr>
			<td></td>
			<td><input type="submit" value="Save Metric"> <input type="button" value="Cancel" onclick="clearForm(); return false;"></td>
			<td></td>
		</tr>
	</table>
	</form>
	
	<div style="float:left; width: 30em; clear: left; text-align: center;"><span class="large_title">Metric List</span></div>
	<div style="float:left; width: 30em; clear: left;" id="paged_list"></div>
</div>

<?php include 'fragments/footer.php'; ?>

</body>
</html>