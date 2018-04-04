<?php
include_once(substr(dirname( __FILE__ ), 0,-19) . '/202-config/connect.php');
include_once(substr(dirname( __FILE__ ), 0,-19) . '/202-config/class-dataengine-slim.php');

$click_sql = 'SELECT click_payout, click_lead FROM 202_clicks WHERE click_id = {$sub_id}';
$click_cpa_sql = 'SELECT 202_cpa_trackers.tracker_id_public, 202_trackers.click_cpa FROM 202_cpa_trackers LEFT JOIN 202_trackers USING (tracker_id_public) WHERE click_id = {$click_id}';

$mysql['user_id'] = 1;
$user_sql = "SELECT jvzoo_ipn_secret_key FROM 202_users_pref WHERE user_id = '".$mysql['user_id']."'";
$user_results = $db->query($user_sql);
$user_row = $user_results->fetch_assoc();

$secret_key = $user_row['jvzoo_ipn_secret_key'];

if (isset($_POST['ctransaction']) && !empty($_POST['ctransaction'])) {
    $pop = "";
    $ipnFields = array();
    foreach ($_POST AS $key => $value) {
        if ($key == "cverify") {
            continue;
        }
        $ipnFields[] = $key;
    }
    sort($ipnFields);
    foreach ($ipnFields as $field) {
        $pop = $pop . $_POST[$field] . "|";
    }
    $pop = $pop . $secret_key;
    $calcedVerify = sha1(mb_convert_encoding($pop, "UTF-8"));
    $calcedVerify = strtoupper(substr($calcedVerify,0,8));
    if ($calcedVerify == $_POST["cverify"]) {
  		$mysql['click_id'] = $db->real_escape_string($_POST["caffitid"]);
  		$transAmount = $_POST['ctransamount'];
    	switch ($_POST['ctransaction']) {
    		case 'SALE':
    		case 'BILL':
				$click_result = $db->query(strtr($click_sql, array('{$sub_id}' => $mysql['click_id'])));
				if ($click_result->num_rows > 0) {
			        $click_row = $click_result->fetch_assoc();

			        $cpa_result = $db->query(strtr($click_cpa_sql, array('{$click_id}' => $mysql['click_id'])));
        			$cpa_row = $cpa_result->fetch_assoc();
        			if ($cpa_result->num_rows > 0) {
        				$mysql['click_cpa'] = $db->real_escape_string($cpa_row['click_cpa']);
        			}

        			if ($click_row['click_lead']) {
				        $mysql['click_payout'] = $db->real_escape_string($click_row['click_payout'] + $transAmount);
				    } else {
				        $mysql['click_payout'] = $db->real_escape_string($transAmount);
				    }

				    if ($mysql['click_cpa']) {
				        $sql_set = "click_cpc='".$mysql['click_cpa']."', click_lead='1', click_filtered='0', click_payout='".$mysql['click_payout']."'";
				    } else {
				        $sql_set = "click_lead='1', click_filtered='0', click_payout='".$mysql['click_payout']."'";
				    }

				    $click_sql = "
				        UPDATE
				            202_clicks 
				        SET
				            ".$sql_set."
				        WHERE
				            click_id='".$mysql['click_id']."'    
				        ";

				    $db->query($click_sql);


				    //set dirty hour
				    $de = new DataEngine();
				    $data=($de->setDirtyHour($mysql['click_id']));
			    }
    			break;
    		
    		case 'RFND':
    			$click_result = $db->query(strtr($click_sql, array('{$sub_id}' => $mysql['click_id'])));
				if ($click_result->num_rows > 0) {
					$click_row = $click_result->fetch_assoc();
					if ($click_row['click_lead']) {

						$mysql['click_payout'] = $db->real_escape_string($click_row['click_payout'] - $transAmount);

				        $click_sql = "
				        	UPDATE
				            	202_clicks 
				        	SET
				            	click_payout='".$mysql['click_payout']."'
				        	WHERE
				            	click_id='".$mysql['click_id']."'    
				        ";

				        $db->query($click_sql);


				        //set dirty hour
				        $de = new DataEngine();
				        $data=($de->setDirtyHour($mysql['click_id']));
					}
				}
    			break;
    	}
    }
}

?>