<?php
include_once(str_repeat("../", 2).'202-config/connect.php');

AUTH::require_user();
	
//set the timezone for this user.
AUTH::set_timezone($_SESSION['user_timezone']);

template_top('Rapid Builder',NULL,NULL,NULL);  ?>

<div class="row" style="margin-bottom: 15px;">
	<div class="col-xs-12">
		<div class="row">
			<div class="col-xs-5">
				<h6>Rapid Builder</h6>
			</div>
			<div class="col-xs-7">
			</div>
		</div>
	</div>
	<div class="col-xs-12">
		<small>Rapid Builder Quickly Builds Your Native Ads Campaigns and allows you to bulk upload them automaticly into your accounts.</small>
		
		<p>
		<h4>The Rapid Builder An Advanced Feature For Paid Prosper202 Marketing Cloud Clients. </h4>
<a href="#" onclick="Intercom('showNewMessage', 'Please Tell Me More About The Rapid Ad Builder')" class="btn btn-primary btn-lg btn-block">Click Here To Learn More</a>
		
		</p>
	</div>
</div>

<div class="row form_seperator" style="margin-bottom:15px;">
	<div class="col-xs-12">
	<?php 
//Initiate curl
$ch = curl_init();
// Disable SSL verification 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// Will return the response, if false it print the response
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Set the url
curl_setopt($ch, CURLOPT_URL, 'http://my.tracking202.com/feed/adbots/');
// Execute
$result = curl_exec($ch);
curl_close($ch);


if ($result) {

    echo $result;

} else {
    
} ?> 
	
	</div>
</div>


<?php template_bottom(); ?>