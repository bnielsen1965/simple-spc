<?php

include 'config.php';
$user = new User;
include HEADERS . 'engineer_header.php';

if( !empty($_REQUEST['metricName']) ) $metricName = $_REQUEST['metricName'];
else $metricName = '';

$rule = new Rule;

// AJAX action request
if( isset($_REQUEST['action']) ) {
	//$rule = new Rule;
	$errorMessages = array();
	$json = array();
	$success = FALSE;
	
	switch( $_REQUEST['action'] ) {
		case 'createRule':
		// make sure type is available
		if( $rule->typeAvailable($_REQUEST['Type'], $_REQUEST['MetricName']) ) {
			// make sure not in demo mode
			if( !DEMO_MODE ) {
				// attempt to create
				if( $rule->createRule($_REQUEST['Type'], $_REQUEST['MetricName'], 'READY', $_REQUEST['ViolationStatus']) ) {
					// successfully created
					$success = TRUE;
				}
				else $errorMessages[] = 'Error occurred creating rule';
			}
			else {
				$errorMessages[] = 'Cannot create rule in demo mode';
			}
		}
		else $errorMessages[] = 'Type not available';
		break;
		
		
		case 'deleteRule':
		// make sure not in demo mode
		if( !DEMO_MODE ) {
			// attempt to delete
			if( $rule->deleteRule($_REQUEST['Type'], $_REQUEST['MetricName']) ) {
				// successfully deleted
				$success = TRUE;
			}
			else $errorMessages[] = 'Error occurred deleting rule';
		}
		else {
			$errorMessages[] = 'Cannot delete rule in demo mode';
		}
		break;

		
		case 'updateRule':
		// make sure not in demo mode
		if( !DEMO_MODE ) {
			// if type and old type do not match then update
			if( $_REQUEST['oldType'] != $_REQUEST['Type'] ) {
				// make sure type is available
				if( $rule->typeAvailable($_REQUEST['Type'], $_REQUEST['MetricName']) ) {
					// try changing type
					if( $rule->changeType($_REQUEST['oldType'], $_REQUEST['Type'], $_REQUEST['MetricName']) === FALSE ) {
						$errorMessages[] = 'Error changing rule type';
						break;
					}
					else $success = TRUE;
				}
				else {
					$errorMessages[] = 'Type not available';
				}
			}
			
			if( $rule->updateRule($_REQUEST['Type'], $_REQUEST['MetricName'], $_REQUEST['ViolationStatus']) === FALSE ) {
				$errorMessages[] = 'Error updating rule';
				break;
			}
			
			$success = TRUE;
		}
		else {
			$errorMessages[] = 'Cannot update rule in demo mode';
		}
		break;
		
		
		case 'getRuleList':
		// get count
		$count = $rule->getRuleCount($_REQUEST['MetricName']);
		
		// if have rules then create a list
		if( !empty($count) ) {
			// start with an empty array
			$list = array();
			
			// determine the limit for the number of names in the list
			$limit = (!empty($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 3);
			
			// determine the offset into the table for pagination
			$offset = (!empty($_REQUEST['offset']) ? intval($_REQUEST['offset']) : 0);
			
			// get the list
			$list = $rule->getRuleList($_REQUEST['MetricName'], $offset, $limit);
			
			// note any errors or store the list in the JSON array
			if( $list === FALSE ) $errorMessages[] = 'Error getting rule list';
			else if( is_array($list) ) $json['ruleList'] = $list;
			
			// store the count
			$json['ruleCount'] = $count;
			
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

<title>Rules</title>

<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript">
var listLimit = 10;
var listOffset = 0;
var ruleList = null;
var ruleCount = 0;

$(document).ready(function () {
	// save user form submission action
	$('form#save_rule').submit(function () {
		
		// make the save AJAX call
		callJSON(
			{
				'action': $('input[name="action"]').val(),
				'MetricName': $('input[name="MetricName"]').val(),
				'oldType': $('input[name="oldType"]').val(),
				'Type': $('select[name="Type"]').val(),
				'ViolationStatus': $('select[name="ViolationStatus"]').val()
			},
			function() {
				if( this.success ) {
					// clear the form
					clearForm();

					getRuleList(listOffset);
				}
			}
		);
		
		return false;
	});
	
	// enable save user button now that page is loaded
	$('input[name="save_rule"]').removeAttr('disabled');
	
	// load list
	getRuleList();
});


function clearForm() {
	// clear the form
	$('form#save_rule').not(':checkbox').each(function() { this.reset(); });
	$('form#save_rule').find(':checkbox').each(function() { $(this).removeAttr('checked'); });

	// set action to create
	$('form#save_rule').find('input[name="action"]').val('createRule');
	$('form#save_rule').removeClass('updating');
}


function deleteRule(ruleIndex) {
	// get the type from the list
	deleteRuleType = ruleList[ruleIndex].Type;
	
	// make the delete AJAX call
	callJSON(
		{
			'action': 'deleteRule',
			'Type': deleteRuleType,
			'MetricName': '<?php echo $metricName; ?>'
		},
		function() {
			// reload the list
			getRuleList(listOffset);
		}
	);
}


function editRule(ruleIndex) {
	// clear the form
	clearForm();
	
	// set action to update
	$('form#save_rule').find('input[name="action"]').val('updateRule');
	
	// set the name in the form using the selected
	$('form#save_rule').find('input[name="oldType"]').val(ruleList[ruleIndex].Type);
	$('form#save_rule').find('select[name="Type"]').val(ruleList[ruleIndex].Type);
	$('form#save_rule').find('select[name="ViolationStatus"]').val(ruleList[ruleIndex].Violation_Status);
	
	$('form#save_rule').addClass('updating');
}


function getRuleList(newOffset) {
	// if not specified then use the previous pagination offset, otherwise set the current to the specified offset
	if( typeof(newOffset) == 'undefined' ) newOffset = listOffset;
	else listOffset = newOffset;
	
	// make the AJAX call to get the paginated list
	callJSON(
		{
			'action': 'getRuleList',
			'MetricName': '<?php echo $metricName; ?>',
			'offset': listOffset,
			'limit': listLimit
		},
		function () {
			// if a list was returned then save results and show the list
			if( this.ruleList ) {
				ruleList = this.ruleList;
				ruleCount = this.ruleCount;
			}
			else {
				ruleList = null;
			}
			
			showRuleList();
		}
	);
}


function showRuleList() {
	// if empty list then display empty list
	if( !ruleList || ruleList.length == 0 ) {
		$('#rule_list').html('No rules');
		return;
	}
	
	// construct pagination controls if needed
	controlsHTML = '<li>';
	
	if( listOffset > 0 ) {
		if( listOffset - listLimit < 0 ) controlsHTML += '<a href="" onclick="getMetricList(0); return false;">Previous</a>';
		else controlsHTML += '<a href="" onclick="getRuleList(' + (listOffset - listLimit) + '); return false;">Previous</a>';
	}
	
	if( listOffset + listLimit < ruleCount ) controlsHTML += '<a href="" onclick="getRuleList(' + (listOffset + listLimit) + '); return false;">Next</a>';
	
	controlsHTML += '</li>';
	
	
	// construct the unordered list
	listHTML = '<ul>';
	listHTML += controlsHTML;
	
	for(i = 0; i < ruleList.length; i++) {
		listHTML += '<li class="' + (i%2 > 0 ? 'odd' : 'even') + '">';
		listHTML += '<label>' + ruleList[i].Type_Description + '</label>';
		listHTML += '<div style="float: right;">';
		
		listHTML += '<a href="" onclick="editRule(' + i + '); return false;">Edit</a>';
		listHTML += '&nbsp;&nbsp;<a href="" onclick="deleteRule(' + i + '); return false;">Delete</a>';
		
		listHTML += '</div>';
		listHTML += '</li>';
	}
	
	listHTML += controlsHTML;
	listHTML += '</ul>';
	
	// show the new list
	$('#rule_list').html(listHTML);
}


function callJSON(calldata, callback) {
	$.getJSON(
		'rules.php', calldata, function(response) {
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
	<form method="post" id="save_rule" onsubmit="return false;">
	<input type="hidden" name="action" value="createRule">
	<input type="hidden" name="MetricName" value="<?php echo $metricName; ?>">
	<input type="hidden" name="oldType" value="">
	<table>
		<tr>
			<td>Metric Name: </td>
			<td><?php echo $metricName; ?></td>
			<td></td>
		</tr>
		<tr>
			<td>Rule Type: </td>
			<td>
			<select name="Type">
			<?php echo $rule->ruleTypeOptions(); ?>
			</select>
			</td>
			<td>&nbsp;&nbsp;<a class="helptip" href="#"><img src="images/helptip.png"><span>Select a rule that you want applied to this chart.</span></a></td>
		</tr>
		<tr>
			<td>Rule Violation Status: </td>
			<td><select name="ViolationStatus">
				<option value="HOLD">HOLD</option>
				<option value="WARN">WARN</option>
			</select></td>
			<td>&nbsp;&nbsp;<a class="helptip" href="#"><img src="images/helptip.png"><span>Select the new status value to apply if this rule is violated.</span></a></td>
		</tr>
		<tr>
			<td></td>
			<td><input type="submit" value="Save Rule"> <input type="button" value="Cancel" onclick="clearForm(); return false;"></td>
			<td></td>
		</tr>
	</table>
	</form>
	
	<div style="float:left; width: 30em; clear: left;" id="rule_list"></div>

</div>

<?php include 'fragments/footer.php'; ?>

</body>
</html>