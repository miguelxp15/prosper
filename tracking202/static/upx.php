<?php
header('P3P: CP="Prosper202 does not have a P3P policy"');
include_once(substr(dirname( __FILE__ ), 0,-19) . '/202-config/connect2.php');
include_once(substr(dirname( __FILE__ ), 0,-19) . '/202-config/class-dataengine-slim.php');
include_once(substr(dirname( __FILE__ ), 0,-19) . '/202-config/convlogs.php');

$mysql['user_id'] = 1;
$mysql['cid'] = 0;
$mysql['use_pixel_payout'] = 0;

if (isset($_GET['subid']) && !empty($_GET['subid']) && is_numeric($_GET['subid'])) {
	$mysql['click_id'] = $db->real_escape_string($_GET['subid']);
} else if (isset($_GET['cid']) && !empty($_GET['cid']) && is_numeric($_GET['cid']) && isset($_COOKIE['tracking202subid_a_' . $_GET['cid']])) {
	$mysql['click_id'] = $db->real_escape_string($_COOKIE['tracking202subid_a_' . $_GET['cid']]);
} else if (isset($_COOKIE['tracking202subid'])) {
	$mysql['click_id'] = $db->real_escape_string($_COOKIE['tracking202subid']);
} else {
    $mysql['ip_address'] = $db->real_escape_string($_SERVER['REMOTE_ADDR']);
    $daysago = time() - 2592000; // 30 days ago
    $sql = "SELECT 202_clicks.click_id
				   FROM 202_clicks
				   LEFT JOIN 202_clicks_advance USING (click_id)
				   LEFT JOIN 202_ips USING (ip_id) 
				   WHERE 202_ips.ip_address='" . $mysql['ip_address'] . "'
				   AND 202_clicks.user_id='" . $mysql['user_id'] . "'  
				   AND 202_clicks.click_time >= '" . $daysago . "'
				   ORDER BY 202_clicks.click_id DESC LIMIT 1";
    $result = $db->query($sql);
    $row = $result->fetch_assoc();
            
    $mysql['click_id'] = $db->real_escape_string($row['click_id']);
}

//if this is a duplicate conversion and dedupe is on then stop processing
if(ignoreDuplicates()){
    header('HTTP/1.1 400 Not Found', true, 400);
    header('Content-Type: application/json');
    $response = array('error' => true, 'code' => 400, 'msg' => 'Duplicate Conversion Found');
    print_r(json_encode($response));
    die();
}

if (is_numeric($mysql['click_id'])) {
	$site_urls=" LEFT JOIN `202_clicks_site` AS 2cs ON (2c.click_id=2cs.click_id)
             LEFT JOIN `202_site_urls` AS 2su ON (2cs.click_referer_site_url_id=2su.site_url_id) ";

	//get c1-c4 values etc
	$cvar_sql ="
	SELECT 
		2cid.click_id,
		2c.user_id,
		2c.aff_campaign_id,
		2c.click_payout,
		2c.click_cpc,
		2c.click_lead,
		2c.click_time,
	    2c.ppc_account_id,
		2c1.c1,
		2c2.c2,
		2c3.c3,
		2c4.c4,
		2kw.keyword,
		2g.gclid,
		2us.utm_source,
		2um.utm_medium,
		2uca.utm_campaign,
		2ut.utm_term,
		2uco.utm_content,
		2trc.click_cpa,
	    2su.site_url_address,
	    202_aff_campaigns.aff_campaign_payout
	FROM `202_clicks_tracking` AS 2cid
	LEFT JOIN `202_clicks_advance` AS 2ca USING (`click_id`)
	LEFT JOIN `202_google` AS 2g USING (`click_id`)
	LEFT JOIN `202_clicks` AS 2c USING (`click_id`)
	LEFT JOIN 202_aff_campaigns ON (202_aff_campaigns.aff_campaign_id =  2c.aff_campaign_id) 
	LEFT JOIN `202_tracking_c1` AS 2c1 USING (`c1_id`)
	LEFT JOIN `202_tracking_c2` AS 2c2 USING (`c2_id`)
	LEFT JOIN `202_tracking_c3` AS 2c3 USING (`c3_id`)
	LEFT JOIN `202_tracking_c4` AS 2c4 USING (`c4_id`)
	LEFT JOIN `202_utm_source` AS 2us USING (`utm_source_id`)
	LEFT JOIN `202_utm_medium` AS 2um USING (`utm_medium_id`)
	LEFT JOIN `202_utm_campaign` AS 2uca USING (`utm_campaign_id`)
	LEFT JOIN `202_utm_term` AS 2ut USING (`utm_term_id`)
	LEFT JOIN `202_utm_content` AS 2uco USING (`utm_content_id`)
	LEFT JOIN `202_keywords` AS 2kw ON (2ca.`keyword_id` = 2kw.`keyword_id`)
	LEFT JOIN `202_cpa_trackers` AS 2cpa USING (`click_id`)
	LEFT JOIN `202_trackers` AS 2trc ON (2cpa.`tracker_id_public` = 2trc.`tracker_id_public`)".$site_urls."
	WHERE 2c.`click_id` = {$mysql['click_id']}
	LIMIT 1";
	$cvar_sql_result = $db->query($cvar_sql);
	$cvar_sql_row = $cvar_sql_result->fetch_assoc();

	$mysql['original_click_payout'] = $cvar_sql_row['click_payout'];
	$mysql['t202kw'] = $db->real_escape_string($cvar_sql_row['keyword']);
	$mysql['c1'] = $db->real_escape_string($cvar_sql_row['c1']);
	$mysql['c2'] = $db->real_escape_string($cvar_sql_row['c2']);
	$mysql['c3'] = $db->real_escape_string($cvar_sql_row['c3']);
	$mysql['c4'] = $db->real_escape_string($cvar_sql_row['c4']);
	$mysql['gclid'] = $db->real_escape_string($cvar_sql_row['gclid']);
	$mysql['utm_source'] = $db->real_escape_string($cvar_sql_row['utm_source']);
	$mysql['utm_medium'] = $db->real_escape_string($cvar_sql_row['utm_medium']);
	$mysql['utm_campaign'] = $db->real_escape_string($cvar_sql_row['utm_campaign']);
	$mysql['utm_term'] = $db->real_escape_string($cvar_sql_row['utm_term']);
	$mysql['utm_content'] = $db->real_escape_string($cvar_sql_row['utm_content']);
	$mysql['click_user_id'] = $db->real_escape_string($cvar_sql_row['user_id']);
	$mysql['campaign_id'] = $db->real_escape_string($cvar_sql_row['aff_campaign_id']);
	$mysql['payout'] = $db->real_escape_string($cvar_sql_row['click_payout']);
	$mysql['cpc'] = $db->real_escape_string($cvar_sql_row['click_cpc']);
	$mysql['click_cpa'] = $db->real_escape_string($cvar_sql_row['click_cpa']);
	$mysql['click_lead'] = $db->real_escape_string($cvar_sql_row['click_lead']);
	$mysql['click_time'] = $db->real_escape_string($cvar_sql_row['click_time']);
	$mysql['referer'] = urlencode($db->real_escape_string($cvar_sql_row['site_url_address']));

	if($cvar_sql_row['ppc_account_id'] == '0'){
	    $mysql['ppc_account_id'] = '';    
	} else{
		$mysql['ppc_account_id'] = $db->real_escape_string($cvar_sql_row['ppc_account_id']);
	}

	//if (!$cvar_sql_row['click_lead'] || (isset($_GET['t202txid'])) && !empty($_GET['t202txid'])) {
	{

		if (isset($_GET['t202txid']) && !empty($_GET['t202txid'])) {
			$mysql['txid'] =  $db->real_escape_string($_GET['t202txid']);
		}

		if (isset($_GET['amount']) && is_numeric($_GET['amount'])) {
			$mysql['click_payout'] = $db->real_escape_string($_GET['amount']);
			$mysql['click_payout_added'] = $mysql['click_payout'];
		} else {
			$mysql['click_payout_added'] = $cvar_sql_row['aff_campaign_payout'];
			$mysql['click_payout'] = $cvar_sql_row['click_payout'];
		}

		if (!empty($mysql['txid'])) {
			if ($cvar_sql_row['click_lead']) {
				$mysql['click_payout'] = $mysql['original_click_payout'] + $mysql['click_payout_added'];
			} else {
				$mysql['click_payout'] = $mysql['click_payout_added'];
			}
		}

		$tokens = getTokens($mysql);
		
		$account_id_sql="SELECT 202_clicks.ppc_account_id
				 FROM 202_clicks 
				 WHERE click_id={$mysql['click_id']}";

		$account_id_result = $db->query($account_id_sql);
		$account_id_row = $account_id_result->fetch_assoc();
		$mysql['ppc_account_id'] = $db->real_escape_string($account_id_row['ppc_account_id']);

		if ($mysql['ppc_account_id']) {
			$pixel_sql='SELECT 202_ppc_account_pixels.pixel_code,202_ppc_account_pixels.pixel_type_id FROM 202_ppc_account_pixels WHERE 202_ppc_account_pixels.ppc_account_id='.$mysql['ppc_account_id'];
			$pixel_result = $db->query($pixel_sql);
			if ($pixel_result->num_rows > 0) {
				while ($pixel_result_row = $pixel_result->fetch_assoc()) {
					$mysql['pixel_type_id'] = $db->real_escape_string($pixel_result_row['pixel_type_id']);
					if ($mysql['pixel_type_id'] == 5) {
						$mysql['pixel_code'] = stripslashes($pixel_result_row['pixel_code']);
					}else{
						$mysql['pixel_code'] = $db->real_escape_string($pixel_result_row['pixel_code']);
					}

					if($mysql['pixel_type_id'] != 5) $pixel_urls = explode(' ',$mysql['pixel_code']);

					switch ($mysql['pixel_type_id']) {
						case 1:
							
							foreach($pixel_urls as $pixel_url){
							  if(isset($pixel_url))
							    $pixel_url=replaceTokens($pixel_url,$tokens);
							    echo "<img src='{$pixel_url}' height='0' width='0' style='display:none' />\n";  
							}
						
							break;
						case 2:
						  
					        foreach($pixel_urls as $pixel_url){
							  if(isset($pixel_url))
							    $pixel_url=replaceTokens($pixel_url,$tokens);
							    echo "<iframe src='{$pixel_url}' height='0' width='0'></iframe>\n";  
							}
						
							break;
						case 3:
						  
					        foreach($pixel_urls as $pixel_url){
							  if(isset($pixel_url))
							   $pixel_url=replaceTokens($pixel_url,$tokens);
							   echo "<script async src='{$pixel_url}'></script>\n";
							}
					
							break;
						case 4:

				        	foreach($pixel_urls as $pixel_url){
							  if(isset($pixel_url))
							    $pixel_url=replaceTokens($pixel_url,$tokens,1);
							    getData($pixel_url);
							    header('HTTP/1.1 202 Accepted', true, 202);
							    header('Content-Type: application/json');
							    $response = array('error' => false, 'code' => 202, 'msg' => 'Postback successful');
							    print_r(json_encode($response));
							}
							break;

						case 5:
							echo replaceTokens($mysql['pixel_code'],$tokens);

							break;
						
					}
				}
			}
		}
		
		$mysql['click_cpa'] = $db->real_escape_string($cvar_sql_row['click_cpa']);

		$mysql['click_lead_count'] = $cvar_sql_row['click_lead'] + 1;

		if ($mysql['click_cpa']) {
			$sql_set = "click_cpc='".$mysql['click_cpa']."', click_lead='".$mysql['click_lead_count']."', click_filtered='0', click_payout='".$mysql['click_payout']."'";
		} else {
			$sql_set = "click_lead='".$mysql['click_lead_count']."', click_filtered='0', click_payout='".$mysql['click_payout']."'";
		}

		$sql = "SELECT * FROM 202_conversion_logs 
				WHERE click_id = '".$mysql['click_id']."' 
				AND transaction_id = '".$mysql['txid']."'";
		$result = $db->query($sql);	

		if ($result->num_rows == 0) {
			$click_sql = "
				UPDATE
					202_clicks 
				SET
					".$sql_set."
				WHERE
					click_id='".$mysql['click_id']."'";
			$db->query($click_sql);
			
			addConversionLog($mysql['click_id'], $mysql['txid'], $mysql['campaign_id'], $mysql['click_payout_added'], $mysql['user_id'], $mysql['click_time'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_USER_AGENT'], null, '3');
		} else {
			$row = $result->fetch_assoc();
			$mysql['adjusted_payout'] = $mysql['original_click_payout'] - $row['click_payout'] + $mysql['click_payout_added'];

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

		//set dirty hour
		$de = new DataEngine();
		$data=($de->setDirtyHour($mysql['click_id']));
	}
}