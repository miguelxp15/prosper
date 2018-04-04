<?php include_once(str_repeat("../", 2).'202-config/connect.php');
include_once(str_repeat("../", 2).'202-config/class-dataengine.php');

AUTH::require_user();


//set the timezone for the user, for entering their dates.
	AUTH::set_timezone($_SESSION['user_timezone']);

//grab user time range preference
	$time = grab_timeframe();
	$mysql['to'] = $db->real_escape_string($time['to']);
	$mysql['from'] = $db->real_escape_string($time['from']);


//show real or filtered clicks
	$mysql['user_id'] = $db->real_escape_string($_SESSION['user_id']);
	$user_sql = "SELECT user_pref_breakdown, user_pref_show, user_cpc_or_cpv FROM 202_users_pref WHERE user_id=".$mysql['user_id'];
	$user_result = _mysqli_query($user_sql, $dbGlobalLink); //($user_sql);
	$user_row = $user_result->fetch_assoc();
	$breakdown = $user_row['user_pref_breakdown'];
	$de = new DataEngine();
	$data=($de->getReportData('keyword', $mysql['from'], $mysql['to'],$cpv));
	
	$dr= new DisplayData();
	$dr->displayReport('keyword', $data, $de->foundRows());

	?>
	
	<script type="text/javascript">
		new Tablesort(document.getElementById('stats-table'), {
		  descending: true
		});
	</script>