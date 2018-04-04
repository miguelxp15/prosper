<?php 
set_time_limit(0);
include_once(str_repeat("../", 2).'202-config/connect.php');
include_once(str_repeat("../", 2).'202-config/class-dataengine-slim.php');
include_once(str_repeat("../", 2).'202-config/convlogs.php');

AUTH::require_user();

$utc = new DateTimeZone('UTC');
$dt = new DateTime('now', $utc);

if (!$userObj->hasPermission("access_to_update_section")) {
	header('location: '.get_absolute_url().'tracking202/');
	die();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
	$mysql['user_id'] = $db->real_escape_string($_SESSION['user_id']);
	$mysql['click_update_type'] = 'upload';
	$mysql['click_update_time'] = time();
		
    $subids = $_POST['subids']; 
	$subids = trim($subids); 
	$subids = explode("\r",$subids);
	$subids = str_replace("\n",'',$subids);
	$de = new DataEngine();
	foreach($subids as $subid) {
		$data = explode(',', $subid);

		//subid,payout,txid,timestamp

		$mysql['click_id'] = $db->real_escape_string($data[0]);
	
		$click_sql = "
			SELECT 
				2c.click_id,
				2c.aff_campaign_id,
				2c.click_payout,
				2c.click_time,
				202_aff_campaigns.aff_campaign_payout,
				202_trackers.click_cpa,
				202_cpa_trackers.tracker_id_public,
				2c.click_lead
			FROM
				202_clicks AS 2c
				LEFT JOIN 202_cpa_trackers USING (click_id) 
				LEFT JOIN 202_trackers USING (tracker_id_public)
				LEFT JOIN 202_aff_campaigns ON (202_aff_campaigns.aff_campaign_id =  2c.aff_campaign_id) 	
			WHERE
				2c.click_id ='". $mysql['click_id']."'
			 
		";
		//die($click_sql);
		$click_result = $db->query($click_sql) or record_mysql_error($click_sql);
		$click_row = $click_result->fetch_assoc();
		$mysql['click_id'] = $db->real_escape_string($click_row['click_id']);
		$mysql['aff_campaign_id'] = $db->real_escape_string($click_row['aff_campaign_id']);
		$mysql['click_payout'] = $db->real_escape_string($click_row['click_payout']);
		$mysql['click_time'] = $db->real_escape_string($click_row['click_time']);
		$mysql['click_cpa'] = $db->real_escape_string($click_row['click_cpa']);

		if(is_numeric($mysql['click_id'])) {
			$mysql['original_click_payout'] = $click_row['click_payout'];

			$mysql['txid'] = '';
			$mysql['conv_time'] = null;

			if (isset($data[1]) && !empty($data[1])) {
				$mysql['click_payout'] = $db->real_escape_string($data[1]);
				$mysql['click_payout_added'] = $mysql['click_payout'];
			} else if (!empty($_POST['subid_payout'])) {
				$mysql['click_payout'] = $db->real_escape_string($_POST['subid_payout']);
				$mysql['click_payout_added'] = $mysql['click_payout'];
			}  else {
				$mysql['click_payout_added'] = $click_row['aff_campaign_payout'];
				$mysql['click_payout'] = $click_row['click_payout'];
			}

			if (isset($data[2]) && !empty($data[2])) {
				$mysql['txid'] = $db->real_escape_string($data[2]);
				if ($click_row['click_lead']) {
					$mysql['click_payout'] = $mysql['original_click_payout'] + $mysql['click_payout_added'];
				} else {
					$mysql['click_payout'] = $mysql['click_payout_added'];
				}
			}

			if (isset($data[3]) && !empty($data[3])) {
				$conv_time = strtotime($data[3]);
				$mysql['conv_time'] = $db->real_escape_string($conv_time);
			} else {
				$mysql['conv_time'] = time();
			}

			if (!empty($_POST['subid_timezone'])) {
				$conv_date = new DateTime();
				$conv_date->setTimezone(new DateTimeZone($_POST['subid_timezone']));
				$conv_date->setTimestamp($mysql['conv_time']); 
				$mysql['conv_time'] = strtotime($conv_date->format('Y-m-d H:i:s'));
			}

			$sql = "SELECT * FROM 202_conversion_logs 
				WHERE click_id = '".$mysql['click_id']."' 
				AND transaction_id = '".$mysql['txid']."'";
			$result = $db->query($sql);

			if ($result->num_rows == 0) {
				addConversionLog($mysql['click_id'], $mysql['txid'], $mysql['aff_campaign_id'], $mysql['click_payout_added'], $mysql['user_id'], $mysql['click_time'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_USER_AGENT'], $mysql['conv_time'], 0);
				
				$mysql['click_lead'] = $click_row['click_lead'] + 1;

				if ($mysql['click_cpa']) {
					$sql_set = "click_cpc='".$mysql['click_cpa']."', click_lead='".$mysql['click_lead']."', click_filtered='0', click_payout='".$mysql['click_payout']."'";
				} else {
					$sql_set = "click_lead='".$mysql['click_lead']."', click_filtered='0', click_payout='".$mysql['click_payout']."'";
				}

				$update_sql = "
					UPDATE
						202_clicks
					SET
						".$sql_set."
					WHERE
						click_id='" . $mysql['click_id'] ."'
						AND user_id='".$mysql['user_id']."'
				";
				$update_result = $db->query($update_sql) or die(mysql_error($update_sql));
				
			} else {
				$row = $result->fetch_assoc();
				$mysql['adjusted_payout'] = $mysql['original_click_payout'] - $click_row['click_payout'] + $mysql['click_payout_added'];

				$db->query("UPDATE 202_clicks SET click_payout='".$mysql['adjusted_payout']."' WHERE click_id = '".$mysql['click_id']."'");

				$click_sql = "
					UPDATE
						202_conversion_logs
					SET
						click_payout = '".$mysql['click_payout_added']."'
					WHERE
						click_id='".$mysql['click_id']."' AND transaction_id = '".$mysql['txid']."'";
				$db->query($click_sql);
			}	
			
			$de->setDirtyHour($mysql['click_id']);
		}
	} 
	
    	$success = true;
	
}

//show the template
template_top('Update Subids'); ?>
<div class="row" style="margin-bottom: 15px;">
	<div class="col-xs-12">
		<div class="row">
			<div class="col-xs-4">
				<h6>Update Your Subids <?php showHelp("update"); ?></h6>
			</div>
			<div class="col-xs-8">
				<div class="success pull-right" style="margin-top: 20px;">
					<small>
						<?php if ($success == true) { ?>
							<span class="fui-check-inverted"></span> Your submission was successful. Your account income now reflects the subids just uploaded.
						<?php } ?>
					</small>
				</div>
			</div>
		</div>
	</div>
	<div class="col-xs-12">
		<small>Here is where you can update your income for Prosper202, by importing your subids from your affiliate marketing reports.</small>
	</div>
</div>

<div class="row form_seperator">
	<div class="col-xs-12"></div>
</div>

<div class="row">
	<div class="col-xs-12">
		<form method="post" action="" class="form-horizontal" role="form">
			<div class="form-group" style="margin:0px 0px 15px 0px;">
			    <label for="subids">Subids</label>
				<textarea rows="5" name="subids" id="subids" placeholder="SUBID, PAYOUT (optional), TRANSACTION ID (optional), TIMESTAMP (m/d/y h:m:s PM/AM optional)&#13;&#10;SUBID, PAYOUT (optional), TRANSACTION ID (optional), TIMESTAMP (m/d/y h:m:s PM/AM optional)&#13;&#10;..." class="form-control"></textarea>			  
			</div>
			<div class="col-xs-3">
				<div class="form-group" style="margin-right: 0px;">
			    	<label for="subid_payout">Payout</label>
			    	<input type="text" class="form-control input-sm" id="subid_payout" name="subid_payout" placeholder="optional">
				</div>
			</div>
			<div class="col-xs-3">
				<div class="form-group" id="subid_timezone_select">
    				<label for="subid_timezone">Timezone</label>
    				<select class="form-control" id="subid_timezone" name="subid_timezone">
					  <option value="">optional</option>
					  <?php
						  foreach(DateTimeZone::listIdentifiers() as $tz) {
							    $current_tz = new DateTimeZone($tz);
							    $offset =  $current_tz->getOffset($dt);
							    $transition =  $current_tz->getTransitions($dt->getTimestamp(), $dt->getTimestamp());
							    $abbr = $transition[0]['abbr'];
							    echo '<option value="' .$tz. '">' .$tz. ' [' .$abbr. ' '. formatOffset($offset). ']</option>';
							}
						?>
					</select>
  				</div>
			</div>
			<button class="btn btn-sm btn-p202 btn-block" type="submit">Update Subids</button>
		</form>
	</div>
</div>

<?php template_bottom();