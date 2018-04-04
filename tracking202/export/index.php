<?php 
include_once(str_repeat("../", 2).'202-config/connect.php');

AUTH::require_user();

$mysql['user_id'] = $db->real_escape_string($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$error = false;
	if (empty($_POST['aff_campaign']) || empty($_POST['conversion_name']) || empty($_POST['time_from']) || empty($_POST['time_to'])) {
		$error = true;
	} else {

		$user_sql = "SELECT user_timezone FROM 202_users WHERE user_id = '".$mysql['user_id']."'";
		$user_results = $db->query($user_sql);
		$user_row = $user_results->fetch_assoc();

		$from = explode('/', $_POST['time_from']); 
	    $from_month = trim($from[0]);
		$from_day = trim($from[1]);
		$from_year = trim($from[2]);

	    $to = explode('/', $_POST['time_to']); 
	    $to_month = trim($to[0]);
	    $to_day = trim($to[1]);
	    $to_year = trim($to[2]);

	    $time_from = mktime(0,00,0,$from_month,$from_day,$from_year);
	    $time_to = mktime(23,59,59,$to_month,$to_day,$to_year); 

	    $time = new DateTime(date('Y-m-d H:i:s'), new DateTimeZone($user_row['user_timezone']));
		$timezoneOffset = $time->format('O');

		$output = fopen("php://output", "w");
		fputcsv($output, array('Parameters:EntityType=OFFLINECONVERSION;TimeZone='.$timezoneOffset.';'));

		$mysql['aff_campaign'] = $db->real_escape_string($_POST['aff_campaign']);
		$gclid_sql = "SELECT aff_campaign_name, click_time, gclid, click_payout, aff_campaign_currency FROM 202_clicks
					  JOIN 202_aff_campaigns USING (aff_campaign_id)
				      JOIN 202_google USING (click_id)
					  WHERE 202_clicks.user_id = 1 
					  AND aff_campaign_id = '".$mysql['aff_campaign']."'
					  AND gclid != ''
					  AND click_lead = 1 
					  AND click_time >= '".$time_from."' 
					  AND click_time <= '".$time_to."'";
		$gclid_results = $db->query($gclid_sql);
		
		while ($gclid_row = $gclid_results->fetch_assoc()) {
			$campaign_name = $gclid_row['aff_campaign_name'];
			fputcsv($output, array($gclid_row['gclid'], $_POST['conversion_name'], date('m/d/Y H:i:s', $gclid_row['click_time']), number_format((float)$gclid_row['click_payout'], 2, '.', ''), $gclid_row['aff_campaign_currency']));
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=gclid_'.$campaign_name.'_'.$_POST['time_from'].'_'.$_POST['time_to'].'.csv');
		header("Pragma: no-cache");
		header("Expires: -1");

		fclose($output);
		die();
	}
}

$aff_campaign_sql = "SELECT *
					FROM `202_aff_campaigns`
					WHERE `user_id`='".$mysql['user_id']."'
					AND `aff_campaign_deleted`='0'
					ORDER BY `aff_campaign_name` ASC";
$aff_campaign_results = $db->query($aff_campaign_sql);

template_top('Export GCLID', NULL, NULL, NULL); ?>

<div class="row">
	<div class="col-xs-12">
		<h6>Export GCLID to .CSV</h6>
		<small>Sometimes, a click on an AdWords ad doesn't lead directly to an online sale, but instead starts a customer down a path that ultimately leads to a sale in the offline world, such as at your office or over the phone. By importing offline conversions, you can measure what happens in the offline world after a click on one of your ads.</small>
		<div class="<?php if($error) echo "error";?>" style="margin-top: 20px;">
			<small>
				<?php if ($error) { ?> 
					<span class="fui-alert"></span> All fields are required!
				<?php } ?>
			</small>
		</div>
	</div>
</div>

<div class="row form_seperator" style="margin-bottom:15px; margin-top:15px;">
	<div class="col-xs-12"></div>
</div>

<div class="row">
	<div class="col-xs-12">
		<form class="form-horizontal" method="post" role="form">
		  <div class="form-group" style="margin-bottom: 0px;">
		    <div class="col-xs-12">
			    <label for="aff_campaign">Campaign</label>
			    <select class="form-control input-sm" id="aff_campaign" name="aff_campaign">
				    <option>--</option>
				    <?php
				    while ($aff_campaign_row = $aff_campaign_results->fetch_array(MYSQLI_ASSOC)) { ?>
				    	<option value="<?php echo $aff_campaign_row['aff_campaign_id']?>"><?php echo $aff_campaign_row['aff_campaign_name']?></option>
				    <?php } ?>
				</select>
			</div>
		  </div>
		  <div class="form-group" style="margin-bottom: 0px;">
			  <div class="col-xs-12">
			    <label for="conversion_name">Conversion Name <span class="fui-info-circle" style="font-size: 12px;" data-toggle="tooltip" title="" data-original-title="the name of the conversion action (for example, 'lead qualified' or 'contract signed'). It's important that you use the exact same spelling and capitalization that you did when you created this conversion action in your AdWords account."></span></label>
			    <input type="text" class="form-control input-sm" id="conversion_name" name="conversion_name" placeholder="lead qualified, contract signed, etc">
			  </div>
		  </div>
		  <div class="form-group datepicker" style="margin-bottom: 0px;">
			  <div class="col-xs-12">
			    <label for="time_from">From</label>
			    <input type="text" class="form-control input-sm" id="time_from" name="time_from" placeholder="<?php echo date('m/d/Y ', time() - 86400);?>">
			  </div>
		  </div>
		  <div class="form-group datepicker">
			  <div class="col-xs-12">
			    <label for="time_to">To</label>
			    <input type="text" class="form-control input-sm" id="time_to" name="time_to" placeholder="<?php echo date('m/d/Y ', time());?>">
			  </div>
		  </div>
		  <div class="form-group">
			  <div class="col-xs-3">
			    <button type="submit" class="btn btn-sm btn-p202">Export</button>
			  </div>
		  </div>
		</form>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		$('.datepicker input:text').datepicker({
		    dateFormat: 'mm/dd/yy',

		    onSelect: function(datetext){
		    	$(this).val(datetext);
		    },
		});
	});
</script>

<?php template_bottom(); ?>