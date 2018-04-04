<?php
use GuzzleHttp\json_encode;
include_once (substr(dirname(__FILE__), 0, - 17) . '/202-config/connect.php');

AUTH::require_user();

// show tracking code

if (! empty($_POST['landing_page_id'])) {
    
    // get lp the url
    $mysql['landing_page_id'] = $db->real_escape_string($_POST['landing_page_id']);
    $landing_page_sql = "SELECT landing_page_url FROM 202_landing_pages WHERE landing_page_id='" . $mysql['landing_page_id'] . "'";
    $landing_page_result = $db->query($landing_page_sql) or record_mysql_error($landing_page_sql);
    $landing_page_row = $landing_page_result->fetch_assoc();
    $html['landing_page_url'] = htmlentities($landing_page_row['landing_page_url']);
    
    // delete tokens
    $pattern = '/\[\[(\w+)\]\]/i';
    $replacement = '';
    $html['landing_page_url'] = preg_replace($pattern, $replacement, $html['landing_page_url']);
    
    // get page
    $lpcontent = getData($html['landing_page_url']);
    
    // regex for js code structure
    $pattern = '/\/static\/landing\.php\?lpip=[0-9]+/i';
    preg_match($pattern, $lpcontent, $validate_lp);
    
    // return success or failure
    header('Content-Type: application/json');
    if ($validate_lp) {
        // worked
        $result = array(
            "validated" => "true"
        );
        echo json_encode($result);
    } else {
        // fail
        $result = array(
            "validated" => "false"
        );
        echo json_encode($result);
    }
} else {
    // fail
    $result = array(
        "validated" => "false"
    );
    echo json_encode($result);
}

?>
