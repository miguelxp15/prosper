<?php include_once(str_repeat("../", 2).'202-config/connect.php'); 

AUTH::require_user();
$mysql['user_id'] = $db->real_escape_string($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	
	$time = grab_timeframe(); 
	$mysql['to'] = $db->real_escape_string($time['to']);
	$mysql['from'] = $db->real_escape_string($time['from']); 
	$mysql['click_id'] = $db->real_escape_string($_POST['logs_subid']);
	$mysql['campaign_id'] = $db->real_escape_string($_POST['logs_campaign']);
	
	$logs_sql = "SELECT 2ca.aff_campaign_name, 2l.click_id, 2l.transaction_id, 2l.click_payout, 2l.click_time, 2l.conv_time, 2l.time_difference, 2l.ip, 2l.pixel_type, 2l.user_agent, 2l.deleted FROM 202_conversion_logs AS 2l LEFT JOIN 202_aff_campaigns AS 2ca ON (2l.campaign_id = 2ca.aff_campaign_id) WHERE 2l.user_id=".$mysql['user_id']." AND 2l.click_time >= ".$mysql['from']." AND 2l.click_time < ".$mysql['to'];
	
	if ($_POST['logs_campaign']) {
		$logs_sql .= " AND campaign_id = '".$mysql['campaign_id']."'";
	}

	if ($_POST['logs_subid']) {
		$logs_sql .= " AND click_id = '".$mysql['click_id']."'";
	}

	if ($_POST['user_pref_time_predefined'] != '') {
	    switch ($_POST['user_pref_time_predefined']) {
	        case 'today':
	        case 'yesterday':
	        case 'last7':
	        case 'last14':
	        case 'last30':
	        case 'thismonth':
	        case 'lastmonth':
	        case 'thisyear':
	        case 'lastyear':
	        case 'alltime':
	            $mysql['user_pref_time_predefined'] = $db->real_escape_string($_POST['user_pref_time_predefined']);
	            break;
	    }
	} else {
	    
	    $from = explode('/', $_POST['from']);
	    $from_month = trim($from[0]);
	    $from_day = trim($from[1]);
	    $from_year = trim($from[2]);
	    
	    $to = explode('/', $_POST['to']);
	    $to_month = trim($to[0]);
	    $to_day = trim($to[1]);
	    $to_year = trim($to[2]);
	    
	    $mysql['user_pref_time_from'] = mktime(0, 00, 0, $from_month, $from_day, $from_year);
	    $mysql['user_pref_time_to'] = mktime(23, 59, 59, $to_month, $to_day, $to_year);			
	}

	$user_sql = "UPDATE  `202_users_pref`
					 SET `user_pref_time_predefined`='" . $mysql['user_pref_time_predefined'] . "',
					`user_pref_time_from`='" . $mysql['user_pref_time_from'] . "',
					`user_pref_time_to`='" . $mysql['user_pref_time_to'] . "'
					WHERE   `user_id`='" . $mysql['user_id'] . "'";
	_mysqli_query($user_sql);

	$logs_result = _mysqli_query($logs_sql);

} ?>  
 
<div class="row" style="margin-top: 10px; margin-bottom: 10px;">
	<div class="col-xs-6">
		<span class="infotext"><?php printf('<div class="results">Results <b>%s</b></div>',$logs_result->num_rows);  ?></span>
	</div>
</div>

<div class="row">
	<div class="col-xs-12">
	<table class="table table-bordered" id="stats-table">
		<thead>
		    <tr style="background-color: #f2fbfa;">
		        <th>SubID</th>
		        <th>TxID</th>
		        <th>Campaign</th>
		        <th>Payout</th>
		        <th>Click Time</th>
		        <th>Conversion Time</th>
		        <th>Time Difference</th>
		        <th>IP Address</th>
		        <th>Pixel Type</th>
		    </tr>
		</thead>
		<tbody>
		<?php while ($logs_row = $logs_result->fetch_assoc()) { ?>
			<tr>
				<td><?php echo $logs_row['click_id'];?> <?php if ($logs_row['deleted'] == true) { ?><span class="label label-danger">deleted</span><?php } ?></td>
				<td><?php echo $logs_row['transaction_id'];?></td>
				<td><?php echo $logs_row['aff_campaign_name'];?></td>
				<td><?php echo dollar_format($logs_row['click_payout']);?></td>
				<td><?php echo date('m/d/y g:ia', $logs_row['click_time']);?></td>
				<td><?php echo date('m/d/y g:ia', $logs_row['conv_time']);?></td>
				<td><?php echo $logs_row['time_difference'];?></td>
				<td><?php echo $logs_row['ip'];?></td>
				<td><?php if ($logs_row['pixel_type'] == '1') { 
							  echo "Pixel";
						  } else if ($logs_row['pixel_type'] == '2') {
						  	  echo "Postback";
						  } else if ($logs_row['pixel_type'] == '3') {
						  	  echo "Universal Pixel";
						  } 
					?>
				</td>
				<tr>
					<td colspan="2">User agent:</td>
					<td colspan="8"><code style="white-space: inherit;"><?php echo $logs_row['user_agent'];?></code></td>
				</tr>
			</tr>
		<?php } ?>
		</tbody>
	</table>
	</div>
</div> 