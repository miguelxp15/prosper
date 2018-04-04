<?php

include_once(str_repeat("../", 2).'202-config/connect.php'); 
include_once(str_repeat("../", 2).'202-config/ReportSummaryForm.class.php');
AUTH::require_user();
	
//set the timezone for this user.
AUTH::set_timezone($_SESSION['user_timezone']);
	
//grab the users date range preferences
	$time = grab_timeframe(); 
	$mysql['to'] = $db->real_escape_string($time['to']);
	$mysql['from'] = $db->real_escape_string($time['from']); 
	
	
//show real or filtered clicks
	$mysql['user_id'] = $db->real_escape_string($_SESSION['user_id']);
	$user_sql = "SELECT * FROM 202_users_pref WHERE user_id=".$mysql['user_id'];
	$user_result = _mysqli_query($user_sql, $dbGlobalLink); //($user_sql);
	$user_row = $user_result->fetch_assoc();	
	
	$html['user_pref_group_1'] = htmlentities($user_row['user_pref_group_1'], ENT_QUOTES, 'UTF-8');
	$html['user_pref_group_2'] = htmlentities($user_row['user_pref_group_2'], ENT_QUOTES, 'UTF-8');
	$html['user_pref_group_3'] = htmlentities($user_row['user_pref_group_3'], ENT_QUOTES, 'UTF-8');
	$html['user_pref_group_4'] = htmlentities($user_row['user_pref_group_4'], ENT_QUOTES, 'UTF-8');
	
	if ($user_row['user_cpc_or_cpv'] == 'cpv') {
		$cpv = true;
	} else {
		$cpv = false;
	}
	
	$summary_form = new ReportSummaryForm();
	$summary_form->setDetails(array($user_row['user_pref_group_1'],$user_row['user_pref_group_2'],$user_row['user_pref_group_3'],$user_row['user_pref_group_4']));
	$summary_form->setDetailsSort(array(ReportBasicForm::SORT_NAME));
	$summary_form->setDisplayType(array(ReportBasicForm::DISPLAY_TYPE_TABLE));
	$summary_form->setStartTime($mysql['from']);
	$summary_form->setEndTime($mysql['to']);
	$summary_form->setAccountCurrency($user_row['user_account_currency']);
	
?>

<div class="row">
	<div class="col-xs-8">
		<h6>Group Overview</h6>
		<small>The group overview screen gives you a quick glance at all of your traffic across all dimensions.</small>
	</div>
	<div class="col-xs-4 text-right" style="margin-top:15px;">
		<img style="margin-bottom:2px;" src="<?php echo get_absolute_url();?>202-img/icons/16x16/page_white_excel.png"/>
		<a style="font-size:12px;" target="_new" href="<?php echo get_absolute_url();?>tracking202/overview/group_overview_download.php">
			<strong>Download to excel</strong>
		</a>
	</div>
</div>

<?php 

$mysql['user_id'] = $db->real_escape_string($_SESSION['user_id']);
$info_result = _mysqli_query($summary_form->getQuery($mysql['user_id'],$user_row));
if(!is_numeric($_POST['offset']))
    $offset=0;
else
    $offset=$_POST['offset']*$user_row['user_pref_limit'];

$info_result = _mysqli_query($summary_form->getQuery($mysql['user_id'],$user_row)." LIMIT ".$user_row['user_pref_limit']." OFFSET ".$offset);
$info_result1 = _mysqli_query('SELECT FOUND_ROWS() AS count');

while ($row = $info_result1->fetch_assoc()) {
    $results_count=$row['count'];
}

unset($row);

while ($row = $info_result->fetch_assoc()) {
	$summary_form->addReportData($row);
}

echo $summary_form->getHtmlReportResults('summary report');
?>

<?php 

$query =array(
    'offset' => $_POST['offset'],
    'pages'=>ceil($results_count/$user_row['user_pref_limit'])
);
?>




<div class="row">
<div class="col-xs-12 text-center">
	<div class="pagination" id="table-pages">
	    <ul>
			<?php if ($query['offset'] > 0) {
					printf(' <li class="previous"><a class="fui-arrow-left" onclick="loadContent(\'%tracking202/ajax/group_overview.php\',\'%s\',\'%s\');"></a></li>',get_absolute_url(), $query['offset'] - 1, $html['order']);
				}

				if ($query['pages'] > 1) {
					for ($i=0; $i < $query['pages']; $i++) {
						if (($i >= $query['offset'] - 10) and ($i < $query['offset'] + 11)) {
							if ($query['offset'] == $i) { $class = 'class="active"'; }
							printf(' <li %s><a onclick="loadContent(\'%stracking202/ajax/group_overview.php\',\'%s\',\'%s\');">%s</a></li>', $class, get_absolute_url(), $i, $html['order'], $i+1);
							unset($class);
						}
					}
				}

				if ($query['pages'] > 12 && $query['offset'] != $query['pages']-1) {
					printf(' <li class="next"><a class="fui-arrow-right" onclick="loadContent(\'%stracking202/ajax/group_overview.php\',\'%s\',\'%s\');"></a></li>', get_absolute_url(), $query['offset'] + 1, $html['order']);
				}
			?>
		</ul>
	</div>
	</div>
</div>