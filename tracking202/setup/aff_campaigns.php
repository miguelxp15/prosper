<?php
include_once(str_repeat("../", 2).'202-config/connect.php');

AUTH::require_user();

if (!$userObj->hasPermission("access_to_setup_section")) {
	header('location: '.get_absolute_url().'tracking202/');
	die();
}

$slack = false;
$mysql['user_id'] = $db->real_escape_string($_SESSION['user_own_id']);
$mysql['user_own_id'] = $db->real_escape_string($_SESSION['user_own_id']);
$user_sql = "SELECT 2u.user_name as username, 2up.user_slack_incoming_webhook AS url, 2u.install_hash, 2up.user_account_currency FROM 202_users AS 2u INNER JOIN 202_users_pref AS 2up ON (2up.user_id = 1) WHERE 2u.user_id = '".$mysql['user_own_id']."'";
$user_results = $db->query($user_sql);
$user_row = $user_results->fetch_assoc();
$user_account_currency = $user_row['user_account_currency'];
$dniNetworks = getAllDniNetworks($user_row['install_hash']);

$rotateUrlCampaignsSql = "SELECT * FROM 202_aff_campaigns WHERE user_id = '".$mysql['user_id']."' AND aff_campaign_deleted = 0 AND aff_campaign_rotate = 1";
$rotateUrlCampaignsResults = $db->query($rotateUrlCampaignsSql);

if (!empty($user_row['url'])) 
	$slack = new Slack($user_row['url']);

if ($_GET ['edit_aff_campaign_id']) {
	$editing = true;
}

if ($_GET ['copy_aff_campaign_id']) {
	$copying = true;
}

if ($_SERVER ['REQUEST_METHOD'] == 'POST') {
	
	$aff_network_id = trim ( $_POST ['aff_network_id'] );
	if (empty ( $aff_network_id )) {
		$error ['aff_network_id'] = '<div class="error">Select a category.</div>';
	}
	
	$aff_campaign_name = trim ( $_POST ['aff_campaign_name'] );
	if (empty ( $aff_campaign_name )) {
		$error ['aff_campaign_name'] = '<div class="error">What is the name of this campaign.</div>';
	}
	
	$aff_campaign_url = trim ( $_POST ['aff_campaign_url'] );
	if (empty ( $aff_campaign_url )) {
		$error ['aff_campaign_url'] = '<div class="error">What is your affiliate link? Make sure subids can be added to it.</div>';
	}
	

	if ((strpos ( $_POST ['aff_campaign_url'], '://' ) === 'false')) {
		$error ['aff_campaign_url'] .= '<div class="error">Your Campaign URL be a valid webpage or deeplink</div>';
	}
	
	$aff_campaign_payout = trim ( $_POST ['aff_campaign_payout'] );
	if (! is_numeric ( $aff_campaign_payout )) {
		$error ['aff_campaign_payout'] .= '<div class="error">Please enter in a numeric number for the payout.</div>';
	}
	
	//check to see if they are the owners of this affiliate network
	$mysql ['aff_network_id'] = $db->real_escape_string ( $_POST ['aff_network_id'] );
	$mysql ['user_id'] = $db->real_escape_string ( $_SESSION ['user_id'] );
	$aff_network_sql = "SELECT * FROM `202_aff_networks` WHERE `user_id`='" . $mysql ['user_id'] . "' AND `aff_network_id`='" . $mysql ['aff_network_id'] . "'";
	$aff_network_result = $db->query ( $aff_network_sql ) or record_mysql_error ( $aff_network_sql );
	if ($aff_network_result->num_rows == 0) {
		$error ['wrong_user'] = '<div class="error">You are not authorized to add an campaign to another users network</div>';
	} else {
		$aff_network_row = $aff_network_result->fetch_assoc();
	}
	 
	//if editing, check to make sure the own the campaign they are editing
	if ($editing == true) {
		$mysql ['aff_campaign_id'] = $db->real_escape_string ( $_POST ['aff_campaign_id'] );
		$mysql ['user_id'] = $db->real_escape_string ( $_SESSION ['user_id'] );
		$aff_campaign_sql = "SELECT * FROM 202_aff_campaigns AS 2cp LEFT JOIN 202_aff_networks AS 2an USING (aff_network_id) WHERE 2cp.user_id='" . $mysql ['user_id'] . "' AND 2cp.aff_campaign_id='" . $mysql ['aff_campaign_id'] . "'";
		$aff_campaign_result = $db->query ( $aff_campaign_sql ) or record_mysql_error ( $aff_campaign_sql );
		if ($aff_campaign_result->num_rows == 0) {
			$error ['wrong_user'] .= '<div class="error">You are not authorized to modify another users campaign</div>';
		} else {
			$aff_campaign_row = $aff_campaign_result->fetch_assoc();
		}
	}

	if (! $error) {
		$mysql ['aff_campaign_id'] = $db->real_escape_string ( $_POST ['aff_campaign_id'] );
		$mysql ['aff_network_id'] = $db->real_escape_string ( $_POST ['aff_network_id'] );
		$mysql ['aff_campaign_name'] = $db->real_escape_string ( trim($_POST ['aff_campaign_name']) );
		$mysql ['aff_campaign_url'] = $db->real_escape_string ( trim($_POST ['aff_campaign_url']) );
		$mysql ['aff_campaign_url_2'] = $db->real_escape_string ( trim($_POST ['aff_campaign_url_2']) );
		$mysql ['aff_campaign_url_3'] = $db->real_escape_string ( trim($_POST ['aff_campaign_url_3']) );
		$mysql ['aff_campaign_url_4'] = $db->real_escape_string ( trim($_POST ['aff_campaign_url_4']) );
		$mysql ['aff_campaign_url_5'] = $db->real_escape_string ( trim($_POST ['aff_campaign_url_5']) );
		$mysql ['aff_campaign_rotate'] = $db->real_escape_string ( $_POST ['aff_campaign_rotate'] );
		$mysql ['aff_campaign_cloaking'] = $db->real_escape_string ( $_POST ['aff_campaign_cloaking'] );
		$mysql ['user_id'] = $db->real_escape_string ( $_SESSION ['user_id'] );
		$mysql ['aff_campaign_time'] = time ();
		$mysql['aff_campaign_currency'] = $db->real_escape_string ( $_POST ['aff_campaign_currency'] );
		
		if ($mysql['aff_campaign_currency'] != $user_account_currency) {
			$exchangePayout = getForeignPayout($user_account_currency, $mysql['aff_campaign_currency'], trim($_POST['aff_campaign_payout']));
			$mysql['aff_campaign_payout'] = $db->real_escape_string($exchangePayout['exchange_payout']);
			$mysql['aff_campaign_foreign_payout'] = $db->real_escape_string(trim($_POST['aff_campaign_payout']));
		} else {
			$mysql['aff_campaign_payout'] = $db->real_escape_string(trim($_POST['aff_campaign_payout']));
		}
		
		if ($editing == true) {
			$aff_campaign_sql = "UPDATE `202_aff_campaigns` SET";
		} else {
			$aff_campaign_sql = "INSERT INTO `202_aff_campaigns` SET";
		}
		
		$aff_campaign_sql .= "`aff_network_id`='" . $mysql ['aff_network_id'] . "',
													  `user_id`='" . $mysql ['user_id'] . "',
													  `aff_campaign_name`='" . $mysql ['aff_campaign_name'] . "',
													  `aff_campaign_url`='" . $mysql ['aff_campaign_url'] . "',
													  `aff_campaign_url_2`='" . $mysql ['aff_campaign_url_2'] . "',
													  `aff_campaign_url_3`='" . $mysql ['aff_campaign_url_3'] . "',
													  `aff_campaign_url_4`='" . $mysql ['aff_campaign_url_4'] . "',
													  `aff_campaign_url_5`='" . $mysql ['aff_campaign_url_5'] . "',
													  `aff_campaign_rotate`='" . $mysql ['aff_campaign_rotate'] . "',
													  `aff_campaign_payout`='" . $mysql ['aff_campaign_payout'] . "',
													  `aff_campaign_cloaking`='" . $mysql ['aff_campaign_cloaking'] . "',
													  `aff_campaign_time`='" . $mysql ['aff_campaign_time'] . "',
													  aff_campaign_currency = '".$mysql['aff_campaign_currency']."',
													  aff_campaign_foreign_payout = '".$mysql['aff_campaign_foreign_payout']."'";
		
		if ($editing == true) {
			$aff_campaign_sql .= "WHERE `aff_campaign_id`='" . $mysql ['aff_campaign_id'] . "'";
		}

		$aff_campaign_result = $db->query ( $aff_campaign_sql ) or record_mysql_error ( $aff_campaign_sql );
		$add_success = true;
		
		if ($slack) {
			if ($editing == true) {
				if ($aff_campaign_row['aff_campaign_name'] != $_POST['aff_campaign_name']) {
					$slack->push('campaign_name_changed', array('old_name' => $aff_campaign_row['aff_campaign_name'], 'new_name' => $_POST['aff_campaign_name'], 'user' => $user_row['username']));
				}

				if ($aff_campaign_row['aff_network_id'] != $_POST['aff_network_id']) {
					$slack->push('campaign_category_changed', array('name' => $_POST['aff_campaign_name'], 'old_category' => $aff_campaign_row['aff_network_name'], 'new_category' => $aff_network_row['aff_network_name'], 'user' => $user_row['username']));
				}

				if ($aff_campaign_row['aff_campaign_rotate'] != $_POST['aff_campaign_rotate']) {
					if ($_POST['aff_campaign_rotate'] == true) {
						$rotation_status = 'on';
					} else {
						$rotation_status = 'off';
					}

					$slack->push('campaign_category_rotation_changed', array('name' => $_POST['aff_campaign_name'], 'status' => $rotation_status, 'user' => $user_row['username']));
				}

				if ($aff_campaign_row['aff_campaign_url'] != $_POST['aff_campaign_url']) {
					$slack->push('campaign_url_changed', array('name' => $_POST['aff_campaign_name'], 'old_url' => $aff_campaign_row['aff_campaign_url'], 'new_url' => $_POST['aff_campaign_url'], 'user' => $user_row['username']));
				}

				if ($aff_campaign_row['aff_campaign_payout'] != $_POST['aff_campaign_payout']) {
					$slack->push('campaign_payout_changed', array('name' => $_POST['aff_campaign_name'], 'old_payout' => $aff_campaign_row['aff_campaign_payout'], 'new_payout' => $_POST['aff_campaign_payout'], 'user' => $user_row['username']));
				}

				if ($aff_campaign_row['aff_campaign_cloaking'] != $_POST['aff_campaign_cloaking']) {
					if ($_POST['aff_campaign_cloaking'] == true) {
						$claoking_status = 'on';
					} else {
						$claoking_status = 'off';
					}

					$slack->push('campaign_cloaking_changed', array('name' => $_POST['aff_campaign_name'], 'status' => $claoking_status, 'user' => $user_row['username']));
				}
			}
		}
		

		if ($editing != true) {
			//if this landing page is brand new, add on a landing_page_id_public
			$aff_campaign_row ['aff_campaign_id'] = $db->insert_id;
			$aff_campaign_id_public = rand ( 1, 9 ) . $aff_campaign_row ['aff_campaign_id'] . rand ( 1, 9 );
			$mysql ['aff_campaign_id_public'] = $db->real_escape_string ( $aff_campaign_id_public );
			$mysql ['aff_campaign_id'] = $db->real_escape_string ( $aff_campaign_row ['aff_campaign_id'] );
			
			$aff_campaign_sql = "	UPDATE       `202_aff_campaigns`
								 	SET          	 `aff_campaign_id_public`='" . $mysql ['aff_campaign_id_public'] . "'
								 	WHERE        `aff_campaign_id`='" . $mysql ['aff_campaign_id'] . "'";
			$aff_campaign_result = $db->query ( $aff_campaign_sql ) or record_mysql_error ( $aff_campaign_sql );

			if (isset($_POST['dni_id']) && isset($_POST['dni_offer_id'])) {
				$ddlci = false;
				if (isset($_GET['ddlci']) && is_numeric($_GET['ddlci'])) {
					$ddlci = $_GET['ddlci'];
				}

				$mysql['dni_id'] = $db->real_escape_string($_POST['dni_id']);
				$dniSql = 'SELECT networkId, apiKey, affiliateId FROM 202_dni_networks WHERE user_id = "'.$mysql['user_id'].'" AND id = "'.$mysql['dni_id'].'"';
				$dniResult = $db->query($dniSql);

				if ($dniResult->num_rows > 0) {
					$dniRow = $dniResult->fetch_assoc();
					setupDniOfferTrack($user_row['install_hash'], $dniRow['networkId'], $dniRow['apiKey'], $dniRow['affiliateId'], $_POST['dni_offer_id'], $ddlci);
				}
			}

			if($slack)
				$slack->push('campaign_created', array('name' => $_POST ['aff_campaign_name'], 'user' => $user_row['username']));
		}
	
		$_GET['copy_aff_campaign_id'] = false;
	}
}

if (isset ( $_GET ['delete_aff_campaign_id'] )) {
	
	if ($userObj->hasPermission("remove_campaign")) {
		$mysql ['user_id'] = $db->real_escape_string ( $_SESSION ['user_id'] );
		$mysql ['aff_campaign_id'] = $db->real_escape_string ( $_GET ['delete_aff_campaign_id'] );
		$mysql ['date_deleted'] = time ();
		
		$delete_sql = " UPDATE  `202_aff_campaigns`
						SET     `aff_campaign_deleted`='1',
								`aff_campaign_time`='" . $mysql ['aff_campaign_time'] . "'
						WHERE   `user_id`='" . $mysql ['user_id'] . "'
						AND     `aff_campaign_id`='" . $mysql ['aff_campaign_id'] . "'";
		if ($delete_result = $db->query ( $delete_sql ) or record_mysql_error ( $delete_result )) {
			$delete_success = true;
		}
	} else {
		header('location: '.get_absolute_url().'tracking202/setup/aff_campaigns.php');
	}
}

if ($_GET ['edit_aff_campaign_id']) {
	
	$mysql ['user_id'] = $db->real_escape_string ( $_SESSION ['user_id'] );
	$mysql ['aff_campaign_id'] = $db->real_escape_string ( $_GET ['edit_aff_campaign_id'] );
	
	$aff_campaign_sql = "SELECT 	* 
						 FROM   	`202_aff_campaigns`
						 WHERE  	`aff_campaign_id`='" . $mysql ['aff_campaign_id'] . "'
						 AND    		`user_id`='" . $mysql ['user_id'] . "'";
	
	$aff_campaign_result = $db->query ( $aff_campaign_sql ) or record_mysql_error ( $aff_campaign_sql );
	$aff_campaign_row = $aff_campaign_result->fetch_assoc();
	
	$selected ['aff_network_id'] = $aff_campaign_row ['aff_network_id'];
	$html = array_map ( 'htmlentities', $aff_campaign_row );
	$html ['aff_campaign_id'] = htmlentities ( $_GET ['edit_aff_campaign_id'], ENT_QUOTES, 'UTF-8' );
	if ($aff_campaign_row['aff_campaign_currency'] != $user_account_currency) {
		$html['user_account_currency'] = $aff_campaign_row['aff_campaign_currency'];
		$html['aff_campaign_payout'] = $aff_campaign_row['aff_campaign_foreign_payout'];
	}
}

if ($_GET ['copy_aff_campaign_id']) {
	
	$mysql ['user_id'] = $db->real_escape_string ( $_SESSION ['user_id'] );
	$mysql ['aff_campaign_id'] = $db->real_escape_string ( $_GET ['copy_aff_campaign_id'] );
	
	$aff_campaign_sql = "SELECT 	* 
						 FROM   	`202_aff_campaigns`
						 WHERE  	`aff_campaign_id`='" . $mysql ['aff_campaign_id'] . "'
						 AND    		`user_id`='" . $mysql ['user_id'] . "'";
	
	$aff_campaign_result = $db->query ( $aff_campaign_sql ) or record_mysql_error ( $aff_campaign_sql );
	$aff_campaign_row = $aff_campaign_result->fetch_assoc();
	
	$selected ['aff_network_id'] = $aff_campaign_row ['aff_network_id'];
	$html = array_map ( 'htmlentities', $aff_campaign_row );
	$html ['aff_campaign_id'] = htmlentities ( $_GET ['copy_aff_campaign_id'], ENT_QUOTES, 'UTF-8' );
	$html ['aff_campaign_name'] .= " (Copy)"; //append (Copy) to the campaign name so the user knows its a copy 

}

//this will override the edit, if posting and edit fail
if (($_SERVER ['REQUEST_METHOD'] == 'POST') and ($add_success != true)) {
	
	$selected ['aff_network_id'] = $_POST ['aff_network_id'];
	$html = array_map ( 'htmlentities', $_POST );
}

template_top ( 'Affiliate Campaigns Setup', NULL, NULL, NULL );
?>

<div class="row" style="margin-bottom: 15px;">
	<div class="col-xs-12">
		<div class="row">
			<div class="col-xs-5">
				<h6>Campaign Setup <?php showHelp("step3"); ?></h6>
			</div>
			<div class="col-xs-8">
				<div class="<?php if($error) echo "error"; else echo "success";?> pull-right" style="margin-top: 20px;">
					<small>
						<?php if ($error) { ?> 
							<span class="fui-alert"></span> There were errors with your submission. <?php echo $error['token']; ?>
						<?php } ?>
						<?php if ($add_success == true) { ?>
							<span class="fui-check-inverted"></span> Your submission was successful. Your changes have been saved.
						<?php } ?>
						<?php if ($delete_success == true) { ?>
							<span class="fui-check-inverted"></span> You deletion was successful. You have successfully removed a campaign.
						<?php } ?>
						
					</small>
				</div>
			</div>
		</div>
	</div>
	<div class="col-xs-12">
		<small>Add the campaigns you want to run. <span class="fui-info-circle" style="cursor:pointer;" id="help-text-trigger"></span></small>
		<span style="display:none" id="help-text"><br/>
			<span class="infotext">
				<em>If you do not understand how subids work at your network, stop, and contact your affiliate manager.<br/>
					Prosper202 supports the ability to cloak your traffic; cloaking will
					prevent your advertisers and the affiliate networks who you work with
					from seeing your keywords. Please note if you are doing direct linking
					with Google Adwords, a cloaked direct linking setup can kill your
					qualitly score. Don't understand cloaking? Leave it off for now and
					learn more about it in our help section later.
				</em>
		</span></span>
	</div>
</div>

<div class="row form_seperator" style="margin-bottom:15px;">
	<div class="col-xs-12"></div>
</div>

<div class="row">
	<div class="col-xs-7">
		<small><strong>Add A Campaign</strong></small><br/>
		<span class="infotext">Here you add each of the campaigns you are running.</span>
				
		<form method="post" class="form-horizontal" action="<?php if ($delete_success == true) { echo $_SERVER ['REDIRECT_URL']; } ?>" role="form" style="margin:15px 0px;">
			<input name="aff_campaign_id" type="hidden" value="<?php echo $html ['aff_campaign_id'];?>" />
			<input name="dni_id" type="hidden" value="" />
			<input name="dni_offer_id" type="hidden" value="" />
			
			
			<div class="form-group " style="margin-bottom: 0px;">
				<label for="link_assist" class="col-xs-4 control-label" style="text-align: left;">Bot202 Link Assist:</label>
				<div class="col-xs-6">
                    <input type="checkbox" checked data-toggle="switch" name="link_assist" id="link_assist" data-on-color="success" />
				</div>
			</div>
						
			<div class="form-group <?php if($error['aff_network_id']) echo "has-error"; ?>" style="margin-bottom: 0px;">
				<label for="aff_network_id" class="col-xs-4 control-label" style="text-align: left;">Category:</label>
				<div class="col-xs-6">
				    <select class="form-control  select select-primary select-block mbl"input-sm" name="aff_network_id" id="aff_network_id">
				    	<option value="">--</option>
				    	<?php
								$mysql ['user_id'] = $db->real_escape_string ( $_SESSION ['user_id'] );
								$aff_network_sql = "
										SELECT *
										FROM `202_aff_networks`
										WHERE `user_id`='" . $mysql ['user_id'] . "'
										AND `aff_network_deleted`='0'
										ORDER BY `aff_network_name` ASC
									";
								$aff_network_result = $db->query ( $aff_network_sql ) or record_mysql_error ( $aff_network_sql );
								
								while ( $aff_network_row = $aff_network_result->fetch_array (MYSQLI_ASSOC) ) {
									
									$html ['aff_network_name'] = htmlentities ( $aff_network_row ['aff_network_name'], ENT_QUOTES, 'UTF-8' );
									$html ['aff_network_id'] = htmlentities ( $aff_network_row ['aff_network_id'], ENT_QUOTES, 'UTF-8' );
									
									if ($selected ['aff_network_id'] == $aff_network_row ['aff_network_id']) {
										printf ( '<option selected="selected" value="%s">%s</option>', $html ['aff_network_id'], $html ['aff_network_name'] );
									} else {
										printf ( '<option value="%s">%s</option>', $html ['aff_network_id'], $html ['aff_network_name'] );
									}
								}
								?>
				    </select>
				</div>
			</div>

			<div class="form-group <?php if($error['aff_campaign_name']) echo "has-error";?>" style="margin-bottom: 0px;">
				<label class="col-xs-4 control-label" for="aff_campaign_name" style="text-align: left;">Campaign Name:</label>
				<div class="col-xs-6">
					<input type="text" class="form-control input-sm" id="aff_campaign_name" name="aff_campaign_name" value="<?php echo $html['aff_campaign_name']; ?>">
				</div>
			</div>

			<?php if ($rotateUrlCampaignsResults->num_rows > 0) { ?>
			<div class="form-group" style="margin-bottom: 0px;">
			<label class="col-xs-4 control-label" style="text-align: left;">Rotate Urls:</label>

				<div class="col-xs-2" style="margin-top: 10px;">
					<label class="radio">
	            		<input type="radio" name="aff_campaign_rotate" id="aff_campaign_rotate1" value="0" data-toggle="radio" <?php if ($html ['aff_campaign_rotate'] == 0) echo 'checked';?>>
	            			No
	          		</label>
	          	</div>
	          	<div class="col-xs-2" style="margin-top: 10px;">
		            <label class="radio">
		            	<input type="radio" name="aff_campaign_rotate" id="aff_campaign_rotate2" value="1" data-toggle="radio" <?php if ($html ['aff_campaign_rotate'] == 1) echo 'checked';?>>
		            		Yes
		            </label>
		        </div>
			</div>
			<?php } ?>
			<div class="form-group <?php if($error['aff_campaign_url']) echo "has-error";?>" style="margin-bottom: 0px;">
				<label class="col-xs-4 control-label" for="aff_campaign_url" style="text-align: left;">Campaign URL <span class="fui-info" data-toggle="tooltip" title="This is where people will be sent when yout tracking link is clicked. If you are running an affiliate campaign, this will be where to put your affiliate url."></span></label>
				<div class="col-xs-6">
					<textarea name="aff_campaign_url" id="aff_campaign_url" class="form-control input-sm" rows="3" placeholder="http://"><?php echo $html['aff_campaign_url']; ?></textarea>
				</div>
			</div>

			<div class="form-group" style="margin-bottom: 10px;">
				<div class="col-xs-6 col-xs-offset-4" id="placeholders">
				    <span class="help-block" style="font-size: 12px;">The following tracking placeholders can be used:<br/></span>
					<input style="margin-left: 1px;" type="button" class="btn btn-xs btn-primary" value="[[subid]]"/>
					<input type="button" class="btn btn-xs btn-primary" value="[[t202pubid]]"/><br/><br/>
					<input type="button" class="btn btn-xs btn-primary" value="[[c1]]"/> 
				    <input type="button" class="btn btn-xs btn-primary" value="[[c2]]"/> 
				    <input type="button" class="btn btn-xs btn-primary" value="[[c3]]"/> 
				    <input type="button" class="btn btn-xs btn-primary" value="[[c4]]"/>
				    <input type="button" class="btn btn-xs btn-primary" value="[[t202kw]]"/>
				    <br/><br/>
				    <input type="button" class="btn btn-xs btn-primary" value="[[random]]"/>
				    <input type="button" class="btn btn-xs btn-primary" value="[[referer]]"/>
				    <input type="button" class="btn btn-xs btn-primary" value="[[sourceid]]"/>
				    <br/><br/>
				    <input type="button" class="btn btn-xs btn-primary" value="[[gclid]]"/>
				    <input type="button" class="btn btn-xs btn-primary" value="[[utm_source]]"/>
				    <input type="button" class="btn btn-xs btn-primary" value="[[utm_medium]]"/>
				    <br/><br/>
				    <input type="button" class="btn btn-xs btn-primary" value="[[utm_campaign]]"/>
				    <input type="button" class="btn btn-xs btn-primary" value="[[utm_term]]"/>
				    <br/><br/>
				    <input type="button" class="btn btn-xs btn-primary" value="[[utm_content]]"/>
				    <input type="button" class="btn btn-xs btn-primary" value="[[payout]]"/>
				    <input type="button" class="btn btn-xs btn-primary" value="[[cpc]]"/>
				    <br/><br/>
				    <input type="button" class="btn btn-xs btn-primary" value="[[cpc2]]"/>
				    <input type="button" class="btn btn-xs btn-primary" value="[[timestamp]]"/>
					
				</div>
			</div>

			<?php if ($rotateUrlCampaignsResults->num_rows > 0) { ?>
			<div id="rotateUrls" <?php if ($html ['aff_campaign_rotate'] == 0) echo 'style="display:none;"';?> >
				<div id="rotateUrl2" class="form-group <?php if($error['aff_campaign_url_2']) echo "has-error";?>" style="margin-bottom: 0px;">
					<label class="col-xs-4 control-label" for="aff_campaign_url_2" style="text-align: left;">Rotate Url #2:</label>
					<div class="col-xs-6">
						<input type="text" class="form-control input-sm" id="aff_campaign_url_2" name="aff_campaign_url_2" value="<?php echo $html['aff_campaign_url_2']; ?>">
					</div>
				</div>

				<div id="rotateUrl3" class="form-group <?php if($error['aff_campaign_url_3']) echo "has-error";?>" style="margin-bottom: 0px;">
					<label class="col-xs-4 control-label" for="aff_campaign_url_3" style="text-align: left;">Rotate Url #3:</label>
					<div class="col-xs-6">
						<input type="text" class="form-control input-sm" id="aff_campaign_url_3" name="aff_campaign_url_3" value="<?php echo $html['aff_campaign_url_3']; ?>">
					</div>
				</div>

				<div id="rotateUrl4" class="form-group <?php if($error['aff_campaign_url_4']) echo "has-error";?>" style="margin-bottom: 0px;">
					<label class="col-xs-4 control-label" for="aff_campaign_url_4" style="text-align: left;">Rotate Url #4:</label>
					<div class="col-xs-6">
						<input type="text" class="form-control input-sm" id="aff_campaign_url_4" name="aff_campaign_url_4" value="<?php echo $html['aff_campaign_url_4']; ?>">
					</div>
				</div>

				<div id="rotateUrl5" class="form-group <?php if($error['aff_campaign_url_5']) echo "has-error";?>" style="margin-bottom: 0px;">
					<label class="col-xs-4 control-label" for="aff_campaign_url_2" style="text-align: left;">Rotate Url #5:</label>
					<div class="col-xs-6">
						<input type="text" class="form-control input-sm" id="aff_campaign_url_5" name="aff_campaign_url_5" value="<?php echo $html['aff_campaign_url_5']; ?>">
					</div>
				</div>
			</div>
			<?php } ?>
			<div class="form-group" style="margin-bottom: 0px;">
				<label for="aff_campaign_currency" class="col-xs-4 control-label" style="text-align: left;">Currency:</label>
				<div class="col-xs-6">
				    <select class="form-control input-sm" name="aff_campaign_currency" id="aff_campaign_currency">
				    	<option value="USD" <?php if ($html['user_account_currency'] == 'USD') echo 'selected=""'; ?>>U.S. Dollar</option>
						<option value="AUD" <?php if ($html['user_account_currency'] == 'AUD') echo 'selected=""'; ?>>Australian Dollar</option>
						<option value="BRL" <?php if ($html['user_account_currency'] == 'BRL') echo 'selected=""'; ?>>Brazilian Real</option>
						<option value="CAD" <?php if ($html['user_account_currency'] == 'CAD') echo 'selected=""'; ?>>Canadian Dollar</option>
						<option value="CZK" <?php if ($html['user_account_currency'] == 'CZK') echo 'selected=""'; ?>>Czech Koruna</option>
						<option value="DKK" <?php if ($html['user_account_currency'] == 'DKK') echo 'selected=""'; ?>>Danish Krone</option>
						<option value="EUR" <?php if ($html['user_account_currency'] == 'EUR') echo 'selected=""'; ?>>Euro</option>
						<option value="HKD" <?php if ($html['user_account_currency'] == 'HKD') echo 'selected=""'; ?>>Hong Kong Dollar</option>
						<option value="HUF" <?php if ($html['user_account_currency'] == 'HUF') echo 'selected=""'; ?>>Hungarian Forint</option>
						<option value="ILS" <?php if ($html['user_account_currency'] == 'ILS') echo 'selected=""'; ?>>Israeli New Sheqel</option>
						<option value="JPY" <?php if ($html['user_account_currency'] == 'JPY') echo 'selected=""'; ?>>Japanese Yen</option>
						<option value="MYR" <?php if ($html['user_account_currency'] == 'MYR') echo 'selected=""'; ?>>Malaysian Ringgit</option>
						<option value="MXN" <?php if ($html['user_account_currency'] == 'MXN') echo 'selected=""'; ?>>Mexican Peso</option>
						<option value="NOK" <?php if ($html['user_account_currency'] == 'NOK') echo 'selected=""'; ?>>Norwegian Krone</option>
						<option value="NZD" <?php if ($html['user_account_currency'] == 'NZD') echo 'selected=""'; ?>>New Zealand Dollar</option>
						<option value="PHP" <?php if ($html['user_account_currency'] == 'PHP') echo 'selected=""'; ?>>Philippine Peso</option>
						<option value="PLN" <?php if ($html['user_account_currency'] == 'PLN') echo 'selected=""'; ?>>Polish Zloty</option>
						<option value="GBP" <?php if ($html['user_account_currency'] == 'GBP') echo 'selected=""'; ?>>Pound Sterling</option>
						<option value="SGD" <?php if ($html['user_account_currency'] == 'SGD') echo 'selected=""'; ?>>Singapore Dollar</option>
						<option value="SEK" <?php if ($html['user_account_currency'] == 'SEK') echo 'selected=""'; ?>>Swedish Krona</option>
						<option value="CHF" <?php if ($html['user_account_currency'] == 'CHF') echo 'selected=""'; ?>>Swiss Franc</option>
						<option value="TWD" <?php if ($html['user_account_currency'] == 'TWD') echo 'selected=""'; ?>>Taiwan New Dollar</option>
						<option value="THB" <?php if ($html['user_account_currency'] == 'THB') echo 'selected=""'; ?>>Thai Baht</option>
						<option value="TRY" <?php if ($html['user_account_currency'] == 'TRY') echo 'selected=""'; ?>>Turkish Lira</option>
						<option value="CNY" <?php if ($html['user_account_currency'] == 'CNY') echo 'selected=""'; ?>>Chinese Yuan</option>
						<option value="INR" <?php if ($html['user_account_currency'] == 'INR') echo 'selected=""'; ?>>Indian Rupee</option>
						<option value="RUB" <?php if ($html['user_account_currency'] == 'RUB') echo 'selected=""'; ?>>Russian ruble</option>
				    </select>
				</div>
			</div>
			<div class="form-group <?php if($error['aff_campaign_payout']) echo "has-error";?>" style="margin-bottom: 0px;">
				<label class="col-xs-4 control-label" for="aff_campaign_payout" style="text-align: left;">Payout:</label>
				<div class="col-xs-2">
					<input type="text" size="4" class="form-control input-sm" id="aff_campaign_payout" name="aff_campaign_payout" value="<?php echo $html['aff_campaign_payout']; ?>">
				</div>
			</div>

			<div class="form-group" style="margin-bottom: 0px;">
				<label for="aff_campaign_cloaking" class="col-xs-4 control-label" style="text-align: left;">Cloaking:</label>
				<div class="col-xs-6">
				    <select class="form-control input-sm" name="aff_campaign_cloaking" id="aff_campaign_cloaking">
				    	<option <?php if ($html ['aff_campaign_cloaking'] == '0') { echo 'selected=""'; } ?> value="0">Off by default</option>
						<option <?php if ($html ['aff_campaign_cloaking'] == '1') { echo 'selected=""'; } ?> value="1">On by default</option>
				    </select>
				</div>
			</div>

			<div class="form-group">
				<div class="col-xs-6 col-xs-offset-4">
				    <?php if ($editing == true) { ?>
					    <div class="row">
					    	<div class="col-xs-6">
					    		<button class="btn btn-sm btn-p202 btn-block" type="submit">Edit</button>					
					    	</div>
					    	<div class="col-xs-6">
								<input type="hidden" name="pixel_id" value="<?php echo $selected['pixel_id'];?>">
								<button type="submit" class="btn btn-sm btn-danger btn-block" onclick="window.location='<?php echo get_absolute_url();?>tracking202/setup/aff_campaigns.php'; return false;">Cancel</button>					    		</div>
					    	</div>
				    <?php } else { ?>
				    		<button class="btn btn-sm btn-p202 btn-block" type="submit" id="addCampaign">Add</button>					
					<?php } ?>
				</div>
			</div>

		</form>
	</div>
	<div class="col-xs-4 col-xs-offset-1">
		<div class="panel panel-default">
			<div class="panel-heading">My Campaigns</div>
			<div class="panel-body">
			<div id="campaignList">
			<?php 
			function checkNetworks(&$item1, $key, $dni)
			{
			    $name=$item1['networkId'];
			    if($name===$dni){
			        $item1['networkId']="skip";
			    }
			}			
			?>
			<input class="form-control input-sm search" style="margin-bottom: 10px; height: 30px;" placeholder="Filter">
				<ul class="list">        
					<?php
					
					$mysql ['user_id'] = $db->real_escape_string ( $_SESSION ['user_id'] );
					$aff_network_sql = "SELECT 2af.user_id, 2af.aff_network_id, 2dni.networkid, 2af.aff_network_name, 2af.dni_network_id, 2dni.favicon, 2dni.processed FROM 202_aff_networks AS 2af LEFT JOIN 202_dni_networks AS 2dni ON (2af.dni_network_id = 2dni.id) WHERE 2af.user_id='" . $mysql ['user_id'] . "' AND 2af.aff_network_deleted='0' ORDER BY 2af.dni_network_id desc,2af.aff_network_name ASC";
				
					$aff_network_result = $db->query ( $aff_network_sql ) or record_mysql_error ( $aff_network_sql );
					if ($aff_network_result->num_rows == 0) {
						?><li>You have not activated any networks.</li><?php
					}
					
					
                   
					while ( $aff_network_row = $aff_network_result->fetch_array (MYSQLI_ASSOC) ) {
						$html ['aff_network_name'] = htmlentities ( $aff_network_row ['aff_network_name'], ENT_QUOTES, 'UTF-8' );
						$url ['aff_network_id'] = urlencode ( $aff_network_row ['aff_network_id'] );
						
						if ($aff_network_row['dni_network_id'] != null) {
							if ($aff_network_row['processed'] == false) { 
								$dni_is_live = "<span style='font-size:10px'>processing... <img src='".get_absolute_url()."202-img/loader-small.gif'></span>";
							} else {
								$dni_is_live = '<a href="#" class="openDniSearchOffersModal" data-dni-id="'.$aff_network_row['dni_network_id'].'">Search Offers</a>';
								 
							}
							$dni_logo = '<img src="'.$aff_network_row['favicon'].'" width=16>&nbsp;&nbsp;';
							printf ( '<li>%s<strong>%s</strong> - %s</li>',$dni_logo, $html['aff_network_name'], $dni_is_live);
							@array_walk($dniNetworks, 'checkNetworks', $aff_network_row ['networkid']);
						} else {
							printf ( '<li><strong>%s</strong></li>', $html['aff_network_name']);
						}

						?><ul style="margin-top: 0px;"><?php
						
						//print out the individual accounts per each PPC network
						$mysql ['aff_network_id'] = $db->real_escape_string ( $aff_network_row ['aff_network_id'] );
						$aff_campaign_sql = "SELECT * FROM `202_aff_campaigns` WHERE `user_id`='" . $mysql ['user_id'] . "' AND `aff_network_id`='" . $mysql ['aff_network_id'] . "' AND `aff_campaign_deleted`='0' ORDER BY `aff_campaign_name` ASC";
						$aff_campaign_result = $db->query ( $aff_campaign_sql ) or record_mysql_error ( $aff_campaign_sql );
						
						while ( $aff_campaign_row = $aff_campaign_result->fetch_array (MYSQLI_ASSOC) ) {
							
							$html ['aff_campaign_name'] = htmlentities ( $aff_campaign_row ['aff_campaign_name'], ENT_QUOTES, 'UTF-8' );
							$html ['aff_campaign_payout'] = htmlentities ( $aff_campaign_row ['aff_campaign_payout'], ENT_QUOTES, 'UTF-8' );
							$html ['aff_campaign_url'] = htmlentities ( $aff_campaign_row ['aff_campaign_url'], ENT_QUOTES, 'UTF-8' );
							$html ['aff_campaign_id'] = htmlentities ( $aff_campaign_row ['aff_campaign_id'], ENT_QUOTES, 'UTF-8' );
							$html ['aff_campaign_rotate'] = htmlentities ( $aff_campaign_row ['aff_campaign_rotate'], ENT_QUOTES, 'UTF-8' );
							$html ['aff_campaign_foreign_payout'] = htmlentities ( $aff_campaign_row ['aff_campaign_foreign_payout'], ENT_QUOTES, 'UTF-8' );

							if ($aff_campaign_row['aff_campaign_currency'] != $user_account_currency) {
								$aff_campaign_payout = dollar_format('', $user_account_currency) . $html ['aff_campaign_payout'] . ' ('.dollar_format('', $aff_campaign_row['aff_campaign_currency']) . $html ['aff_campaign_foreign_payout'].')';
							} else {
								$aff_campaign_payout = dollar_format('', $user_account_currency) . $html ['aff_campaign_payout'];
							}

							if($html ['aff_campaign_rotate']) {
								if ($userObj->hasPermission("remove_campaign")) {
									printf ( '<li> <span class="glyphicon glyphicon-repeat" style="font-size: 12px;"></span> <span class="filter_campaign_name">%s</span> &middot; %s - <a href="%s" target="_new">link</a> - <a href="?edit_aff_campaign_id=%s">edit</a> - <a href="?copy_aff_campaign_id=%s">copy</a> - <a href="?delete_aff_campaign_id=%s" onclick="return confirmAlert(\'Are You Sure You Want To Delete This Campaign?\');">remove</a></li>', $html ['aff_campaign_name'], $aff_campaign_payout, $html ['aff_campaign_url'], $html ['aff_campaign_id'], $html ['aff_campaign_id'], $html ['aff_campaign_id'] );
								} else {
									printf ( '<li> <span class="glyphicon glyphicon-repeat" style="font-size: 12px;"></span> <span class="filter_campaign_name">%s</span> &middot; %s - <a href="%s" target="_new">link</a> - <a href="?edit_aff_campaign_id=%s">edit</a> - <a href="?copy_aff_campaign_id=%s">copy</a></li>', $html ['aff_campaign_name'], $aff_campaign_payout, $html ['aff_campaign_url'], $html ['aff_campaign_id'], $html ['aff_campaign_id']);
								}
							} else { 
								if ($userObj->hasPermission("remove_campaign")) {
									printf ( '<li><span class="filter_campaign_name">%s</span> &middot; %s - <a href="%s" target="_new">link</a> - <a href="?edit_aff_campaign_id=%s">edit</a> - <a href="?copy_aff_campaign_id=%s">copy</a> - <a href="?delete_aff_campaign_id=%s" onclick="return confirmAlert(\'Are You Sure You Want To Delete This Campaign?\');">remove</a></li>', $html ['aff_campaign_name'], $aff_campaign_payout, $html ['aff_campaign_url'], $html ['aff_campaign_id'], $html ['aff_campaign_id'], $html ['aff_campaign_id'] );
								} else {
									printf ( '<li><span class="filter_campaign_name">%s</span> &middot; %s - <a href="%s" target="_new">link</a> - <a href="?edit_aff_campaign_id=%s">edit</a> - <a href="?copy_aff_campaign_id=%s">copy</a></li>', $html ['aff_campaign_name'], $aff_campaign_payout, $html ['aff_campaign_url'], $html ['aff_campaign_id'], $html ['aff_campaign_id']);
								}
							}
						}

						?></ul><?php
					
					}
					if($dniNetworks){
					arsort($dniNetworks);
					
					foreach ($dniNetworks as $dninetwork) {
					    if($dninetwork['networkId']!='skip')
					        echo "<li><img src='".$dninetwork['favIconUrl']."' width=16>&nbsp;&nbsp;<strong>".$dninetwork['name']." (DNI)</strong> - <a href=".get_absolute_url()."202-account/api-integrations.php?add_dni_network=".$dninetwork['networkId'].">Activate</a></li>";
					}}
					?>
				</ul>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="modal" id="dniSearchOffersModal" tabindex="-1" role="dialog" aria-labelledby="dniSearchOffersModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
      	<h4 class="modal-title"><span id="inProgress" style="display:none"> Processing... <img src="<?php echo get_absolute_url();?>202-img/loader-small.gif"></span></h4>
      </div>
      <div class="modal-body">
      	<table id="stats-table" class="tablesorter">
		  <thead>
		    <tr>
		      <th>ID</th>
		      <th>Name</th>
		      <th>Payout</th>
		      <th>Type</th>
		      <th data-sorter="false">Preview</th>
		      <th>Status</th>
		    </tr>
		  </thead>
		  <tbody>
		  </tbody>
		  <tfoot>
            <tr>
                  <th colspan="6" class="ts-pager form-horizontal">
                    <button type="button" class="btn first btn-xs"><i class="icon-step-backward glyphicon glyphicon-step-backward"></i></button>
                    <button type="button" class="btn prev btn-xs"><i class="icon-arrow-left glyphicon glyphicon-backward"></i></button>
                    <span class="pagedisplay"></span> <!-- this can be any element, including an input -->
                    <button type="button" class="btn next btn-xs"><i class="icon-arrow-right glyphicon glyphicon-forward"></i></button>
                    <button type="button" class="btn last btn-xs"><i class="icon-step-forward glyphicon glyphicon-step-forward"></i></button>
                    <select class="pagesize input-mini" title="Select page size">
                      <option value="10">10</option>
                      <option selected="selected" value="25">25</option>
                      <option value="50">50</option>
                      <option value="100">100</option>
                      <option value="200">200</option>
                      <option value="300">300</option>
                      <option value="400">400</option>
                      <option value="500">500</option>
                    </select>
                    <select class="pagenum input-mini" title="Select page number"></select>
                  </th>
            </tr>
          </tfoot>
		</table>
      </div>
      <div class="modal-footer">
        <span id="inProgressFooter" style="display:none"> Processing... <img src="<?php echo get_absolute_url();?>202-img/loader-small.gif"></span>
      </div>
    </div>
  </div>
</div>
<?php if(isset($_GET['dl_dni']) && isset($_GET['dl_offer_id']) && !isset($_POST['aff_network_id'])) {
		$mysql['dl_dni'] = $db->real_escape_string($_GET['dl_dni']); 
		$getDlDniSql = "SELECT id FROM 202_dni_networks WHERE networkId = '".$mysql['dl_dni']."' AND user_id = '".$mysql['user_id']."'";
		$getDlDniResult = $db->query($getDlDniSql);
		if ($getDlDniResult->num_rows > 0) {
			$getDlDniRow = $getDlDniResult->fetch_assoc();
		}
} ?>
<script type="text/javascript">
$(document).ready(function() {
    $(function () {
        // Switches
        if ($('[data-toggle="switch"]').length) {
          $('[data-toggle="switch"]').bootstrapSwitch();
        }
      });
	<?php if($getDlDniRow) { ?>
		$('#dniSearchOffersModal').modal('show');
		dni = <?php echo $getDlDniRow['id'];?>;
		
		tablesorterPagerOptions.ajaxUrl = "<?php echo get_absolute_url();?>tracking202/ajax/dni_get_offers.php?all_offers&dni="+dni+"&offset=0&limit=25&column&filter[0]=<?php echo $_GET['dl_offer_id'];?>";
		tablesorterOptions.triggerToggle = true;
		tablesorterOptions.toggleId = <?php echo $_GET['dl_offer_id'];?>;
		var $table1 = $('table.tablesorter').tablesorter(tablesorterOptions).tablesorterPager(tablesorterPagerOptions);
	<?php } ?>
	var campaignOptions = {
	    valueNames: ['filter_campaign_name'],
	    plugins: [
	      ListFuzzySearch()
	    ]
	};


	var linkAssist = function (url) {

	    var regexStr = [
	    ['lnk.asp\\?o=[0-9]+&c=[0-9]+&a=[0-9]+(&l=[0-9]+)?((&s\\d)=(.*))*', 's2'],
	    ['aff_c\\?offer_id=[0-9]+&aff_id=[0-9]+(&source=(.*))?', 'aff_sub'],
	    ['\\?a=[0-9]+&c=[0-9]+(&s2=(.*))?', 's2'],
	    ['\\?cid=[0-9]+&afid=[0-9]+(&sid=(.*))?', 'sid'],
	    ['trkur[0-9]*\\.com\\/[0-9]+\\/[0-9]+', 's1'],
	    ['\\/redirect\\.html\\?ad=(\\w*)(&add1=)?', 'add1'],
	    ['iluv.clickbooth.com\\/\\?E=(\\w*)?(&s1=)?', 's1'],
	    ['\\/click-[0-9]+-[0-9]+(\\?)?(sid=)?', 'sid'],
	    ['\\/rd\\/r\\.php\\?sid=(.*?)&pub=(.*?)(&)', 'c2'],
	    ['hop\\.clickbank\\.net(\\/)?(\\?)?(tid=)?', 'tid'],
	    ['click\\.linksynergy\\.com\\/fs-bin\\/click\\?id=(\\w*)', 'u1'],
	    ['shareasale\\.com\\/r\\.cfm\\?B=(\\w*)&U=(\\w*)&M=(\\w*)', 'afftrack'],
	    ['\\/click\\?aid=(\\w*)&linkid=(\\w*)', 's1'],
	    ['\\/click\\?pid=(\\w*)&offer_id=(\\w*)', 'sub1'],
	    ['\\/a\\.ashx\\?foid=(.*)&foc=(\\w*)&fot=(\\w*)&fos=(\\w*)', 'fobs'],
	    ['jvzoo\\.com\\/c\\/[0-9]+\\/[0-9]+\\/', 'tid'],
	    ['\\/c\\/(\\w*)\\/(\\w*)\\/(\\w*)', 'subId1'],
	    ['\\/#aid=', 'sid1'],
	    ['\\/\\?a_aid=(\\w*)&a_bid=(\\w*)', 'data1'],
	    ['\\?idev_id=(\\w*)', 'idev_subid'],
	    ['cread\\.php\\?s=(\\w*)', 'clickref'],
	    ['\\/t\\/[0-9]+-[0-9]+-[0-9]+-[0-9]+', 'sid'],
	    ['avantlink\\.com\\/click\\.php\\?(.*)&pw=', 'ctc'],
	    ['linkconnector\\.com\\/ta\\.php\\?lc=', 'atid'],
	    ['rover\\.ebay\\.com\\/rover\\/[0-9]+\\/[0-9]+-[0-9]+-[0-9]+-[0-9]+\\/[0-9]+\\??', 'customid'],
	    ['prf\\.hn\\/click\\/camref:(\\w*)', 'pubref'],
	    ['\\/z\\/[0-9]+\\/CD[0-9]+', 'subid1'],
	    ['zaxaa\\.com\\/o\\/[0-9]+\\/[0-1]\\/?', 'none-zaxxa'],
	    ['zaxaa\\.com\\/s\\/[0-9]+\\/?', 'none-zaxxa'],
	    ['ad\\.zanox\\.com\\/(\\w)+\\/\\?(\\w)+', 'zpar0'],
	    ['warriorplus\\.com\\/o2\\/v\\/\\w+\\/[0-9]+\\/?(.*)?', 'none-warriorplus'],
	    ['\\/ct\\/[0-9]+\\??', 't1']
	    ];
	    
	    regexStr.some(function (re) {
	        var affLinkRe = new RegExp(re[0], 'ig');
	        afflink = affLinkRe.test($(url).val());
	        console.log($(url).val())
	        console.log(afflink)
	        console.log(affLinkRe)
	        if (afflink) {
	            var affSubidRe = new RegExp(re[1] + '=(.*?)(&)|' + re[1] + '=(.*)|' + re[1] + '(:)(\\w*)|(warriorplus\\.com\\/o2\\/v\\/\\w+\\/[0-9]+\\/?)(.*)?|(zaxaa\\.com\\/o\\/[0-9]+\\/[0-1]\\/?)(.*)?', 'i');
	            subid = affSubidRe.exec($(url).val());
	            if (subid || re[1].indexOf('none-') > 0) {
		            console.log(subid)
	                if (subid[2] == '&') {
	                    var newlink = $(url).val().replace(affSubidRe, re[1] + '=[[subid]]&');
	                    $(url).val(newlink)
	                }
	                else if (subid[4] == ':') {
	                    var newlink = $(url).val().replace(affSubidRe, re[1] + ':[[subid]]');
	                    $(url).val(newlink)
	                }
	                else if (subid[6] !==undefined) {
	                	var connecter = ""
	                		var lastChar = $(url).val().length - 1	
	                	if($(url).val().charAt(lastChar) !== "/"){
	                		connecter = "/"
	                	}	
	                    var newlink = $(url).val().replace(affSubidRe, '$6'+connecter+'[[subid]]');
	                	newlink=newlink.replace('//[','/[')
	                    $(url).val(newlink)
	                }
	                else if (subid[8] !==undefined) {
	                	var connecter = ""
	                		var lastChar = $(url).val().length - 1	
	                	if($(url).val().charAt(lastChar) !== "/"){
	                		connecter = "/"
	                	}	
	                    var newlink = $(url).val().replace(affSubidRe, '$8'+connecter+'[[subid]]');
	                	newlink=newlink.replace('//[','/[')
	                    $(url).val(newlink)
	                }
	                else {
	                    var newlink = $(url).val().replace(affSubidRe, re[1] + '=[[subid]]');
	                    $(url).val(newlink)
	                }
	            }
	            else {
	                link = $(url).val();
	                var lastChar = $(url).val().length - 1
	                var prefix = "="
	                switch (true) {
	                    case link.charAt(lastChar) === "?":
	                        var connecter = ""
	                        break;
	                    case link.indexOf('?') > 0:
	                        var connecter = "&"
	                        break;
	                    case link.indexOf('camref:') > 0 && link.charAt(lastChar) === "?":
	                        var connecter = ""
	                        break;
	                    case link.indexOf('camref:') > 0:
	                        var connecter = "/"
	                        var prefix = ":"
	                        break;
	                    default:
	                        var connecter = "?"
	                }

	                var newlink = $(url).val() + connecter + re[1] + prefix + "[[subid]]"
	                $(url).val(newlink)
	            }
	            return true
	        }
	    })
	}
	
	var campaignList = new List('campaignList', campaignOptions);
	$("#aff_campaign_url").on("input change", function() {
		if($("#link_assist").is(':checked')){ //only run the link assis if it's turned on
		  linkAssist($("#aff_campaign_url"));
		}
	});
	
});
</script>
<script type="text/javascript" src="<?php echo get_absolute_url();?>202-js/jquery.caret.js"></script>

<?php template_bottom(); ?>			