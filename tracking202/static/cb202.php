<?php

include_once(substr(dirname( __FILE__ ), 0,-19) . '/202-config/connect.php');
include_once(substr(dirname( __FILE__ ), 0,-19) . '/202-config/class-dataengine-slim.php');

$mysql['user_id'] = 1;

$slack = false;
$mysql['user_id'] = 1;
$user_sql = "SELECT 2u.user_name as username, 2up.user_slack_incoming_webhook as url, 2up.cb_key AS cb_key FROM 202_users AS 2u INNER JOIN 202_users_pref AS 2up ON (2up.user_id = 1) WHERE 2u.user_id = '".$mysql['user_id']."'";
$user_results = $db->query($user_sql);
$user_row = $user_results->fetch_assoc();

if (!empty($user_row['url'])) 
    $slack = new Slack($user_row['url']);

if(function_exists("mcrypt_encrypt") || function_exists("openssl_decrypt")) {
   // $test='{"notification":"EyKWi6J/hp0Fbm1IsfTaeLMXiB9PxBj9V9Is4WbhagDdf5GZ7XPC8+GQGLy0Hdrcrw5LRj7tmOLv7RGsIKCaOGrV/kyup446mlvaxgnDKVGAND8apVLog050J6wPIVAMrqkheGAw9QlmJ6v1kNzfjUQOn93gjHm3MH29zCHBfuA2yOgPTwsXbR/0zWhr3/Q7hp3VhJ0ZDhtkqDe4GoJ2bE0UBRqt3nj8/5JdQsTXNAN/nJohX1n8WBRBMLRc06F128w8OPOy01tebJ4HBqC9qYYakPeuoASOWj1h0G2mB+gc2R94mrVXvEiNo8WDhSeXeCz62d9jknnCadxdEX3ee/Zic0JG6xyxldkkamxCcS0/khqabAmSTN6SW/Vt0Y9TKI7tQ7o7LlCdTrSFe50sPCehPBFiEtEDwk4bzf0Y2tVbE3eCwXOG/gWauaAoZ3INghc39OTr5tHfhMOGRZY5tORMDOvSsSH8d0WCbZ5mJ6Cx3VBhPfSkoEKe8+sY+/DlMh/+LDYgQrxu7MmqdusuIvqgZZF1I70BZ0rPG8yNyWk+1P88FR9ZuBaGyhXA6dRYfHfZ99rjG7POVoucNuEPFDhO4yE8VOTXIld3FvYYpbbcxNSUesjlyZWe/jGoMAaUXeV8/2YAGSlL2Iq/oqGWqBNzoroAkfziraN3LirGz54rviXYBmOYw04cqcXi8/aWjx5lEOY3wKs4MeGxARXS4L+4YZnEuVyntyyoarC/CM8kaAzBHSEIE7mnLevtDoceHUD78yuu2oF6m+JhVpE7On4i8DRjazwA6zFc81MMiej9zfCdwmVdLwaPPBQCZhASEFpmshn94QUVb26Z/hQF4Xs0lNdy/46DSZa9/WFplKS26/RZ9abct9fGizSkFXjl9uDogIrtRpFMTkuo2CEB4VaJ1RTbzCR9Kw3mSvIf+Czv0A8fCQUYWJE/DzEe2pqv","iv":"RkJGMkRGM0RGMjVEQzQ2Mg=="}';
   // $user_row['cb_key']='202';
    $message = json_decode(file_get_contents('php://input'));
    $encrypted = $message->{'notification'};
    $iv = $message->{'iv'};
    if(function_exists("mcrypt_encrypt")){
       $decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128,
           substr(sha1($user_row['cb_key']), 0, 32),
           base64_decode($encrypted),
           MCRYPT_MODE_CBC,
           base64_decode($iv)), "\0..\32");
       }
        elseif (function_exists("openssl_decrypt")){
       define('AES_256_CBC', 'aes-256-cbc');
       $decrypted = trim(openssl_decrypt(base64_decode($encrypted),
           AES_256_CBC, 
           substr(sha1($user_row['cb_key']), 0, 32),
           OPENSSL_RAW_DATA, 
           base64_decode($iv) ),"\0..\32");
       
   }
    
    $order = json_decode($decrypted, true);
    $mysql['click_id'] = $db->real_escape_string($order['trackingCodes'][0]);
    if ($order['transactionType'] == 'SALE' || $order['transactionType'] == 'BILL' || $order['transactionType'] == 'RFND') {
        $click_sql = "SELECT click_payout, click_lead FROM 202_clicks WHERE click_id = '".$mysql['click_id']."'";
        $click_result = $db->query($click_sql);
        if ($click_result->num_rows > 0) {
            $click_row = $click_result->fetch_assoc();
        }
    }

    if ($order['transactionType'] == 'TEST') {
        $user_sql = "UPDATE 202_users_pref
                     SET cb_verified=1
                     WHERE user_id='".$mysql['user_id']."'";
        $user_results = $db->query($user_sql);

        if ($slack) 
            $slack->push('cb_key_verified', array());

    } else if($order['transactionType'] == 'SALE' || $order['transactionType'] == 'BILL') {
        if ($click_row['click_lead']) {
            $mysql['click_payout'] = $db->real_escape_string($click_row['click_payout'] + $order['totalAccountAmount']);
        } else {
            $mysql['click_payout'] = $db->real_escape_string($order['totalAccountAmount']);
        }
        
        $cpa_sql = "SELECT 202_cpa_trackers.tracker_id_public, 202_trackers.click_cpa FROM 202_cpa_trackers LEFT JOIN 202_trackers USING (tracker_id_public) WHERE click_id = '".$mysql['click_id']."'";
        $cpa_result = $db->query($cpa_sql);
        $cpa_row = $cpa_result->fetch_assoc();

        $mysql['click_cpa'] = $db->real_escape_string($cpa_row['click_cpa']);
                
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

    } else if ($order['transactionType'] == 'RFND') {
        if ($click_row['click_lead']) {

            $mysql['click_payout'] = $db->real_escape_string($click_row['click_payout'] - $order['totalAccountAmount']);

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

} else {
    die("Missing Mcrypt or OpenSSL!");
}

?>