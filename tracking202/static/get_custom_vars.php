<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
include_once(substr(dirname( __FILE__ ), 0,-19) . '/202-config/connect2.php');
$data = array();
$tracker_id_public = $db->real_escape_string($_GET['t202id']); 
if($tracker_id_public ){
$sql = "SELECT 
		2cv.parameters 
		FROM 202_trackers 
		LEFT JOIN 202_ppc_accounts USING (ppc_account_id)
		LEFT JOIN (SELECT ppc_network_id, GROUP_CONCAT(CONCAT(parameter,':',name)) AS parameters, name FROM 202_ppc_network_variables WHERE deleted='0' GROUP BY ppc_network_id) AS 2cv USING (ppc_network_id) 
		WHERE tracker_id_public = '".$tracker_id_public."'";
}
else{
$sql = "SELECT 202_ppc_network_variables.ppc_network_id, GROUP_CONCAT(CONCAT(parameter,':',name)) AS parameters, name FROM 202_ppc_network_variables WHERE deleted='0' AND ppc_network_id= (SELECT `ppc_network_id` from 202_ppc_accounts where ppc_account_default = 1) GROUP BY ppc_network_id";
}

$result = $db->query($sql);
if ($result->num_rows > 0) {
	$row = $result->fetch_assoc();
	$parameters = explode(',', $row['parameters']);

	foreach ($parameters as $parameter) {
		$data[] = $parameter;
	}
}

echo json_encode($data, true);
?>