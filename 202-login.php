<?php include_once(dirname( __FILE__ ) . '/202-config/connect.php');
   include_once(dirname( __FILE__ ) . '/202-config/Mobile_Detect.php');
   include_once(dirname( __FILE__ ) . '/202-config/vendor/autoload.php');
   
   use UAParser\Parser;


 $user_sql = "SET session sql_mode= ''";
 $user_results = $db->query($user_sql);

 $user_sql = "SELECT user_pref_ad_settings, p202_customer_api_key from 202_users_pref left join 202_users ON (202_users_pref.user_id = 202_users.user_id) WHERE 202_users_pref.user_id='1'";
 $user_result = _mysqli_query($user_sql);
 $user_row = $user_result->fetch_assoc();
 
$detect = new Mobile_Detect;
$parser = Parser::create();
$result = $parser->parse($detect->getUserAgent());

function logged_in_redirect() {
	global $detect;
	global $db;
	//Detect if users is mobile or tablet - if mobile, redirect to mobile view. If tablet, show main view.
	if($detect->isMobile() && !$detect->isTablet()){
		//redirect to mini stats
		$dni_success = false;
		if (isset($_GET['redirect'])) {
			$urlQuery = parse_url(urldecode($_GET['redirect']));
			parse_str($urlQuery['query'], $vars);
			if (isset($vars['dl_dni']) && isset($vars['dl_offer_id']) && isset($vars['ddlci'])) {
				$mysql['user_id'] = $db->real_escape_string($_SESSION['user_id']);
				$mysql['dni'] = $db->real_escape_string($vars['dl_dni']);
				$dni_sql = "SELECT dni.id, dni.networkId, dni.apiKey, dni.affiliateId, 2u.install_hash, 2af.aff_network_id FROM 202_dni_networks AS dni LEFT JOIN 202_users AS 2u USING (user_id) LEFT JOIN 202_aff_networks AS 2af ON (2af.dni_network_id = dni.id) WHERE dni.user_id = '".$mysql['user_id']."' AND dni.networkId = '".$mysql['dni']."' LIMIT 1";
				$dni_result = _mysqli_query($dni_sql);
				if ($dni_result->num_rows > 0) {
					$dni_row = $dni_result->fetch_assoc();
					$offerData = setupDniOffer($dni_row['install_hash'], $dni_row['networkId'], $dni_row['apiKey'], $dni_row['affiliateId'], $vars['dl_offer_id'], $vars['ddlci']);
					$data = json_decode($offerData, true);

					if (!empty($data)) {
						$mysql['aff_network_id'] = $db->real_escape_string($dni_row['aff_network_id']);
						$mysql['aff_campaign_name'] = $db->real_escape_string($data['name']);
						$mysql['aff_campaign_url'] = $db->real_escape_string($data['trk_url']);
						$mysql['aff_campaign_payout'] = $db->real_escape_string($data['payout']);
						$mysql['aff_campaign_time'] = time();
						$affSql = "INSERT INTO 202_aff_campaigns 
								   SET 
								   user_id = '".$mysql['user_id']."',
								   aff_network_id = '".$mysql['aff_network_id']."',
								   aff_campaign_name = '".$mysql['aff_campaign_name']."',
								   aff_campaign_url = '".$mysql['aff_campaign_url']."',
								   aff_campaign_payout = '".$mysql['aff_campaign_payout']."',
								   aff_campaign_time = '".$mysql['aff_campaign_time']."'";
						$db->query($affSql);
						$aff_campaign_id = $db->insert_id;
						$aff_campaign_id_public = rand(1,9).$aff_campaign_id.rand(1,9);
						$aff_campaign_sql = "UPDATE 202_aff_campaigns SET aff_campaign_id_public = '".$aff_campaign_id_public."' WHERE aff_campaign_id = '".$aff_campaign_id."'";
						$db->query($aff_campaign_sql);
						setupDniOfferTrack($dni_row['install_hash'], $dni_row['networkId'], $dni_row['apiKey'], $dni_row['affiliateId'], $vars['dl_offer_id'], $vars['ddlci']);
						$dni_success = true;		   
					}
				}
			}
		}
		header('location: '.get_absolute_url().'202-Mobile/mini-stats/?dni='.$dni_success);
	} else {

		if (isset($_GET['redirect'])) {
			header('location: '.urldecode($_GET['redirect']));
			die();
		}

		//redirect to account screen
		header('location: '.get_absolute_url().'202-account');
	}
}

if (AUTH::logged_in() || AUTH::remember_me_on_logged_out()) {
	logged_in_redirect();
}

if ( $_SERVER['REQUEST_METHOD'] == 'POST' or isset($_REQUEST['sig']) ) {

    $slack = false;

	$mysql['user_name'] = $db->real_escape_string($_POST['user_name']);
	
	$user_pass = salt_user_pass($_POST['user_pass']);
	$mysql['user_pass'] = $db->real_escape_string($user_pass);
	
	$mysql['user_api'] = $db->real_escape_string($_POST['user_api']);
	
	if($mysql['user_api']!=''){
	        $api_validate= json_decode(api_key_validate($mysql['user_api']),true);
	      
	        if($api_validate['msg']=="Key valid"){
	            //update the api key
	            $user_sql = "	UPDATE 	202_users
						SET		p202_customer_api_key='".$mysql['user_api']."'
					 	WHERE 	user_name='".$mysql['user_name']."'
						AND     		user_pass='".$mysql['user_pass']."'";
	            $user_result = _mysqli_query($user_sql);
	            
	        }
	        else{
	            $error['api'] = 'Your API Key is incorrect. <a href="https://my.tracking202.com/api/customers/login?redirect=get-api">Click Here To Get Your API Key</a>';
	        }
	}

	if ( $do_sso ) {
		//check to see if this user exists
		$user_sql = "SELECT * FROM 202_users LEFT JOIN 202_users_pref USING (user_id) WHERE user_deleted!='1' AND user_active='1' ORDER BY user_id LIMIT 1";
		$user_result = _mysqli_query($user_sql);
		$user_row = $user_result->fetch_assoc();
	} else {
		//check to see if this user exists
		$user_sql = "SELECT * FROM 202_users LEFT JOIN 202_users_pref USING (user_id) LEFT JOIN 202_user_role USING (user_id) WHERE user_name='".$mysql['user_name']."' AND user_pass='".$mysql['user_pass']."' AND user_deleted!='1' AND user_active='1'";

		$user_result = _mysqli_query($user_sql);
		$user_row = $user_result->fetch_assoc();
	}

	if (!$user_row) { 
		$error['user'] = 'Your username or password is incorrect.';
	} else {
		$mysql['user_name'] = $user_row['user_name'];
		$mysql['user_pass'] = $user_row['user_pass'];
	}

	//RECORD THIS USER LOGIN, into user_logs
		$mysql['login_server'] = $db->real_escape_string ( serialize($_SERVER) );
		$mysql['login_session'] = $db->real_escape_string ( serialize($_SESSION) );
		$mysql['login_error'] = $db->real_escape_string ( serialize($error) );
		$mysql['ip_address'] = $db->real_escape_string ( $_SERVER['REMOTE_ADDR'] ); 
		$mysql['login_time'] = time();
		
		if ($error) { 
			$mysql['login_success'] = 0;

			if (!empty($user_row['user_slack_incoming_webhook'])) 
				$slack = new Slack($user_row['user_slack_incoming_webhook']);

			if ($slack) 
				$slack->push('failed_login', array('username' => $_POST['user_name'], 'ip' => $_SERVER['REMOTE_ADDR']));
		} else {
			$mysql['login_success'] = 1;	
		}
	//record everything that happend during this crime scene.
		$user_log_sql = "INSERT INTO 			202_users_log
								   SET			user_name='".$mysql['user_name']."',
												user_pass='".$mysql['user_pass']."',
												ip_address='".$mysql['ip_address']."',
												login_time='".$mysql['login_time']."',
												login_success = '".$mysql['login_success']."',
												login_error='".$mysql['login_error']."',
												login_server='".$mysql['login_server']."',
												login_session='".$mysql['login_session']."'";
		$user_log_result = $db->query($user_log_sql) or record_mysql_error($user_log_sql);
	
	if (!$error) {

		AUTH::delete_old_auth_hash();

		$ip_id = INDEXES::get_ip_id($_SERVER['HTTP_X_FORWARDED_FOR']);
		$survey_data = getSurveyData($user_row['install_hash']);

		if ($survey_data['modal']) {
			$mysql['modal_status'] = 0;
		} else {
			$mysql['modal_status'] = 1;
		}

		if ($survey_data['vip_perks']) {
			$mysql['vip_perks_status'] = 1;
		} else {
			$mysql['vip_perks_status'] = 0;
		}

		$mysql['ip_id'] = $db->real_escape_string($ip_id);

		$api_sql = "user_last_login_ip_id='".$mysql['ip_id']."', modal_status='".$mysql['modal_status']."', vip_perks_status='".$mysql['vip_perks_status']."'";
   
		//update this users last login_ip_address
		$user_sql = "	UPDATE 	202_users 
						SET		".$api_sql."
					 	WHERE 	user_name='".$mysql['user_name']."'
						AND     		user_pass='".$mysql['user_pass']."'";
		$user_result = _mysqli_query($user_sql);
		

		//set session variables			
		$_SESSION['session_fingerprint'] = md5('session_fingerprint' . $_SERVER['HTTP_USER_AGENT'] . session_id());
		$_SESSION['session_time'] = time();
		$_SESSION['user_name'] = $user_row['user_name'];
		$_SESSION['user_id'] = 1; //$user_row['user_id'];
		$_SESSION['user_own_id'] = $user_row['user_id'];
		$_SESSION['user_hash'] = $user_row['user_hash'];
		$_SESSION['user_api_key'] = $user_row['user_api_key'];
		$_SESSION['user_timezone'] = $user_row['user_timezone'];
		$_SESSION['user_pref_ad_settings'] = $user_row['user_pref_ad_settings'];
		if($user_row['role_id']==6){
		    $_SESSION['publisher'] = true;
		}
		else{
		    $_SESSION['publisher'] = false;
		}
		

		if(isset($_POST['remember_me'])) {
			AUTH::remember_me_on_auth();
		}

		logged_in_redirect();
		
	}
		
	$html['user_name'] = htmlentities($_POST['user_name'], ENT_QUOTES, 'UTF-8');
	
}	
	
	info_top(); ?>
	<div class="row" style="position:absolute;left:1em;">
	<div class="main col-xs-4" style="left:5em;width:344px;box-shadow: 0 0 12px 0 rgba(0, 0, 0, 0.1), 0 10px 30px 0 rgba(0, 0, 0, 0.2);">
	  <center><img src="202-img/prosper202.png"></center>
	  
<?php 
				if(($user_row['p202_customer_api_key'] == '' && !$error) && ($_GET['customers_api_key'] == '') ){
echo "<br><center><p>Your Prosper202 ClickServer API Key Is Missing</p></center><br>
       <a class='btn btn-lg btn-p202 btn-block' type='submit' href='https://my.tracking202.com/api/customers/login?redirect=get-api'>Click Here To Get Locate & Save Your API Key into Prosper202 ClickServer</a><br><br><br><br><br><br>
       <img src='https://my.tracking202.com/api/v2/dni/deeplink/cookie/set/".base64_encode($strProtocol .  $_SERVER['SERVER_NAME'] . get_absolute_url())."'>	
       ";
 }
				else{ ?>
				
      <form class="form-signin form-horizontal" role="form" method="post" action="" id="login-form">
	      <div class="form-group <?php if ($error['user'] || $error['api']) echo "has-error";?>">
	      <?php if ($error['user']) { ?>
		            <div class="tooltip right in login_tooltip"><div class="tooltip-arrow"></div>
		            <div class="tooltip-inner"><?php echo $error['user'];?></div></div>
	      <?php } 
	               if ($error['api']) { ?>
		            <div class="tooltip right in api_tooltip"><div class="tooltip-arrow"></div>
		            <div class="tooltip-inner"><?php echo $error['api'];?></div></div>
	      <?php } ?>
	        	<input type="text" class="form-control first" name="user_name" placeholder="Username">
				<input type="password" class="form-control middle" name="user_pass" placeholder="Password">
				<?php 
				
				if(!$user_row['p202_customer_api_key'] && ($_GET['customers_api_key'] != '') ){?>
				<input type="text" class="form-control middle" name="user_api" placeholder="Password" id="user_api" value="<?php echo(htmlspecialchars($_GET['customers_api_key'], ENT_QUOTES)); ?>" readonly>
				<?php }?>
				<label class="form-control last">
				  <input type="checkbox"  name="remember_me"> Remember me
				</label>
	        	<?php echo $forgotapi;?>
	        	<a href="<?php echo get_absolute_url();?>202-lost-pass.php" class="text-info forgot-text">I forgot my password/username</a>
	      <button class="btn btn-lg btn-p202 btn-block" type="submit">Sign in</button>
	      </div>
      </form>
      <?php }?>
      
      <?php if($user_row['user_pref_ad_settings']!='hide_login' && $user_row['user_pref_ad_settings']!='hide_all'){?>
     <!-- P202_CS_Login_Page_288x200 -->
<div id='div-gpt-ad-1398648278789-0' style='width:288px; height:200px;'><script type='text/javascript'>googletag.cmd.push(function() { googletag.display('div-gpt-ad-1398648278789-0'); });</script></div>
<?php }?>
</div>
    </div>
    </div>


    <?php if($result->ua->family == "IE") { ?>
	<script type="text/javascript">
		$(window).load(function(){
		    $('#browser_modal').modal({
		      backdrop: 'static',
		      show: true,
		  	})
		});
	</script>
    <!-- Browser detect modal-->
    <div class="modal fade" id="browser_modal">
	  <div class="modal-dialog">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	        <h4 class="modal-title">Internet Explorer Detected</h4>
	      </div>
	      <div class="modal-body">
	        <p>Internet Explorer is not supported by Prosper202.</p>
	        <p>Recommended browsers:</p>
	        <p>
	        	<a href="http://www.google.com/chrome/" target="_blank">Google Chrome <img src="../202-img/chrome.png"></a>
				<a href="http://www.mozilla.org/en-US/firefox/new/‎" target="_blank">Mozilla Firefox <img src="../202-img/firefox.png"></a>
				<a href="http://www.apple.com/safari" target="_blank">Safari (Mac OS X) <img src="../202-img/safari.png"></a>
	        </p>
	      </div>
	      <div class="modal-footer">
	        <button type="button" class="btn btn-default" data-dismiss="modal">Got it!</button>
	      </div>
	    </div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
<?php }   

info_bottom(); ?>