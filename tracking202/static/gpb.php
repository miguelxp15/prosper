<?php
header('P3P: CP="Prosper202 does not have a P3P policy"');
include_once(substr(dirname(__FILE__), 0, - 19).'/202-config/connect2.php');
include_once(substr(dirname(__FILE__), 0, - 19).'/202-config/class-dataengine-slim.php');
include_once(substr(dirname(__FILE__), 0, - 19).'/202-config/convlogs.php');


$mysql['user_id'] = 1;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $the_request = & $_GET;
        break;
    case 'POST':
        $the_request = & $_POST;
        break;
}

if (isset($the_request['subid']) && is_numeric($the_request['subid'])) {
    $mysql['click_id'] = $db->real_escape_string($the_request['subid']);
} else if (isset($the_request['sid']) && is_numeric($the_request['sid'])) {
    $mysql['click_id'] = $db->real_escape_string($the_request['sid']);
} else {
    header('HTTP/1.1 404 Not Found', true, 404);
    header('Content-Type: application/json');
    $response = array('error' => true, 'code' => 404, 'msg' => 'SubID not found');
    print_r(json_encode($response));
    die();
}

//if this is a duplicate conversion and dedupe is on then stop processing
if(ignoreDuplicates()){
    header('HTTP/1.1 400 Not Found', true, 400);
    header('Content-Type: application/json');
    $response = array('error' => true, 'code' => 400, 'msg' => 'Duplicate Conversion Found');
    print_r(json_encode($response));
    die();
}

if (isset($the_request['cid']) && !empty($the_request['cid']) && is_numeric($the_request['cid'])) {
    $mysql['cid'] = $db->real_escape_string($the_request['cid']);
    $cid_query = " LEFT JOIN 202_aff_campaigns ON (202_aff_campaigns.aff_campaign_id =  ".$mysql['cid'].")";
} else {
    $cid_query = " LEFT JOIN 202_aff_campaigns ON (202_aff_campaigns.aff_campaign_id =  202_clicks.aff_campaign_id)";
}

if (is_numeric($mysql['click_id'])) {
    $sql = "SELECT 
                    202_cpa_trackers.tracker_id_public , 
                    202_trackers.click_cpa, 
                    202_clicks.aff_campaign_id, 
                    202_clicks.click_lead, 
                    202_clicks.click_payout,
                    202_clicks.click_time,
                    202_clicks.click_lead,
                    202_aff_campaigns.aff_campaign_payout
                FROM 202_clicks
                LEFT JOIN 202_cpa_trackers USING (click_id) 
                LEFT JOIN 202_trackers USING (tracker_id_public )".$cid_query."WHERE click_id = '".$mysql['click_id']."'";
    $result = $db->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $mysql['original_click_payout'] = $row['click_payout'];
        $mysql['campaign_id'] = $db->real_escape_string($row['aff_campaign_id']);
        $mysql['click_time'] = $db->real_escape_string($row['click_time']);
        $mysql['click_cpa'] = $db->real_escape_string($row['click_cpa']);

        if (isset($_GET['amount']) && is_numeric($_GET['amount'])) {
            $mysql['click_payout'] = $db->real_escape_string($_GET['amount']);
            $mysql['click_payout_added'] = $mysql['click_payout'];
        } else {
            $mysql['click_payout_added'] = $row['aff_campaign_payout'];
            $mysql['click_payout'] = $row['click_payout'];
        }

        if (isset($_GET['t202txid']) && !empty($_GET['t202txid'])) {
            $mysql['txid'] = $db->real_escape_string($_GET['t202txid']);
        }
        else{
            $mysql['txid'] = '';
        }

        if (!empty($mysql['txid'])) {
            if ($row['click_lead']) {
                $mysql['click_payout'] = $mysql['original_click_payout'] + $mysql['click_payout_added'];
            } else {
                $mysql['click_payout'] = $mysql['click_payout_added'];
            }
        }
        
        
        if (empty($mysql['txid'])) {
            
            if ($row['click_lead'] == 0) {
                $mysql['click_lead'] = 1;
            } else if ($row['click_lead'] > 0) {
                $mysql['click_lead'] = $row['click_lead'];
            }

            if ($mysql['click_cpa']) {
                $sql_set = "click_cpc='".$mysql['click_cpa']."', click_lead='".$mysql['click_lead']."', click_filtered='0', click_payout='".$mysql['click_payout']."'";
            } else {
                $sql_set = "click_lead='".$mysql['click_lead']."', click_filtered='0', click_payout='".$mysql['click_payout']."'";
            }

            $click_sql = "
                UPDATE
                    202_clicks 
                SET
                    ".$sql_set."
                WHERE
                    click_id='".$mysql['click_id']."'";
            $db->query($click_sql);

            if ($row['click_lead'] == 0) {
                addConversionLog($mysql['click_id'], $mysql['txid'], $mysql['campaign_id'], $mysql['click_payout_added'], $mysql['user_id'], $mysql['click_time'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_USER_AGENT'], null, '2');
            } else {
                $click_sql = "
                    UPDATE
                        202_conversion_logs
                    SET
                        click_payout = '".$mysql['click_payout']."'
                    WHERE
                        click_id='".$mysql['click_id']."'";
                $db->query($click_sql);
            }
        } else {
            
            $mysql['click_lead'] = $row['click_lead'] + 1;

            if ($mysql['click_cpa']) {
                $sql_set = "click_cpc='".$mysql['click_cpa']."', click_lead='".$mysql['click_lead']."', click_filtered='0', click_payout='".$mysql['click_payout']."'";
            } else {
                $sql_set = "click_lead='".$mysql['click_lead']."', click_filtered='0', click_payout='".$mysql['click_payout']."'";
            }

            $sql = "SELECT * FROM 202_conversion_logs 
                    WHERE click_id = '".$mysql['click_id']."' 
                    AND transaction_id = '".$mysql['txid']."' AND deleted = 0";
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


                addConversionLog($mysql['click_id'], $mysql['txid'], $mysql['campaign_id'], $mysql['click_payout_added'], $mysql['user_id'], $mysql['click_time'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_USER_AGENT'], null, '2');
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
        }
        
        //set dirty hour
        $de = new DataEngine();
        $data = ($de->setDirtyHour($mysql['click_id']));

        $site_urls = " LEFT JOIN `202_clicks_site` AS 2cs ON (2c.click_id=2cs.click_id)
                     LEFT JOIN `202_site_urls` AS 2su ON (2cs.click_referer_site_url_id=2su.site_url_id) ";

        $cvar_sql = "
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
            2su.site_url_address
        FROM `202_clicks_tracking` AS 2cid
        LEFT JOIN `202_clicks_advance` AS 2ca USING (`click_id`)
        LEFT JOIN `202_google` AS 2g USING (`click_id`)
        LEFT JOIN `202_clicks` AS 2c USING (`click_id`)
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
        if ($db->real_escape_string($cvar_sql_row['ppc_account_id']) == '0') {
            $mysql['ppc_account_id'] = '';
        } else {
            $mysql['ppc_account_id'] = $db->real_escape_string($cvar_sql_row['ppc_account_id']);
        }

        if ($the_request['amount'] && is_numeric($the_request['amount'])) {
            $mysql['use_pixel_payout'] = 1;
            $mysql['payout'] = $db->real_escape_string($the_request['amount']);
            $mysql['click_payout'] = $db->real_escape_string($the_request['amount']);
        }
        $tokens = getTokens($mysql);

        $account_id_sql = "SELECT 202_clicks.ppc_account_id
        FROM 202_clicks
        WHERE click_id={$mysql['click_id']}";

        $account_id_result = $db->query($account_id_sql);
        $account_id_row = $account_id_result->fetch_assoc();
        $mysql['ppc_account_id'] = $db->real_escape_string($account_id_row['ppc_account_id']);

        if ($mysql['ppc_account_id']) {
            $pixel_sql = 'SELECT 202_ppc_account_pixels.pixel_code,202_ppc_account_pixels.pixel_type_id FROM 202_ppc_account_pixels WHERE 202_ppc_account_pixels.ppc_account_id='.$mysql['ppc_account_id'];
            $pixel_result = $db->query($pixel_sql);

            $pixel_result_row = $pixel_result->fetch_assoc();
            $mysql['pixel_type_id'] = $db->real_escape_string($pixel_result_row['pixel_type_id']);
            if ($mysql['pixel_type_id'] == 5) {
                $mysql['pixel_code'] = stripslashes($pixel_result_row['pixel_code']);
            } else {
                $mysql['pixel_code'] = $db->real_escape_string($pixel_result_row['pixel_code']);
            }

            //get the list of pixel urls
            if ($mysql['pixel_type_id'] != 5) $pixel_urls = explode(' ', $mysql['pixel_code']);

            switch ($mysql['pixel_type_id']) {
                case 1:


                    break;
                case 2:

                    break;
                case 3:


                    break;
                case 4:
         
                    foreach($pixel_urls as $pixel_url) {
                        if (isset($pixel_url))
                        $pixel_url = replaceTokens($pixel_url, $tokens,1);
                        getData($pixel_url);
                        header('HTTP/1.1 202 Accepted', true, 202);
                        header('Content-Type: application/json');
                        $response = array('error' => false, 'code' => 202, 'msg' => 'Postback successful');
                        print_r(json_encode($response));

                    }
                    break;

                case 5:

                    break;

            }
        }
    }
}
?>