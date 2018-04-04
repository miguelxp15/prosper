<?php 
include_once(str_repeat("../", 2).'202-config/connect.php');

AUTH::require_user();

if (!$userObj->hasPermission("access_to_setup_section")) {
	header('location: '.get_absolute_url().'tracking202/');
	die();
}

template_top('Pixel And Postback URLs');

//the pixels
$unSecuredPixel = '<img height="1" width="1" border="0" style="display: none;" src="http://'. getTrackingDomain() .get_absolute_url().'tracking202/static/gpx.php?amount=&t202txid=&t202dedupe=" />';
$unSecuredPixel_2 = '<img height="1" width="1" border="0" style="display: none;" src="http://'. getTrackingDomain() .get_absolute_url().'tracking202/static/gpx.php?amount=&cid=&t202txid=&t202dedupe=" />';

//post back urls
$unSecuredPostBackUrl = 'http://'. getTrackingDomain() .get_absolute_url().'tracking202/static/gpb.php?amount=&subid=&t202txid=&t202dedupe=';
$unSecuredPostBackUrl_2 = 'http://'. getTrackingDomain() .get_absolute_url().'tracking202/static/gpb.php?amount=&subid=&t202txid=&t202dedupe=';

//universal pixel
$unSecuredUniversalPixel = '<iframe height="1" width="1" border="0" style="display: none;" frameborder="0" scrolling="no" src="http://'. getTrackingDomain() .get_absolute_url().'tracking202/static/upx.php?amount=&t202txid=&t202dedupe=" seamless></iframe>';

$unSecuredUniversalPixelJS = '
<script>
 var vars202={amount:"", t202txid:"", t202dedupe:"0"};(function(d, s) {
 	var js, upxf = d.getElementsByTagName(s)[0], load = function(url, id) {
 		if (d.getElementById(id)) {return;}
 		if202 = d.createElement("iframe");if202.src = url;if202.id = id;if202.height = 1;if202.width = 0;if202.frameBorder = 1;if202.scrolling = "no";if202.noResize = true;
 		upxf.parentNode.insertBefore(if202, upxf);
 	};
 	load("http://'. getTrackingDomain() .get_absolute_url().'tracking202/static/upx.php?amount="+vars202[\'amount\']+"&t202txid="+vars202[\'t202txid\']+"&t202dedupe="+vars202[\'t202dedupe\'], "upxif");
 }(document, "script"));</script>
<noscript>
 	<iframe height="1" width="1" border="0" style="display: none;" frameborder="0" scrolling="no" src="http://'. getTrackingDomain() .get_absolute_url().'tracking202/static/upx.php?amount=&t202txid=&t202dedupe=" seamless></iframe>
</noscript>';

?>

<div class="row" style="margin-bottom: 15px;">
	<div class="col-xs-12">
		<h6>Get your Pixel or Post Back URL <?php showHelp("step9"); ?></h6>
	</div>
	<div class="col-xs-12">
		<small>By placing a conversion pixel on the advertiser page, everytime you get a
				conversion it will fire and update your conversions
				automatically.<br />
				Watch Conversions in real0time in your spy view! The Post Back URL is
				supported by some networks, this is a Server to Server call.<br />

				Use the options below to generate the type of Pixel or Post Back URL to
				be placed.<br />
		</small>
	</div>
</div>	

<div class="row form_seperator" style="margin-bottom:15px;">
	<div class="col-xs-12"></div>
</div>

<div class="row">
	<div class="col-xs-12">
		<form method="post" id="tracking_form" class="form-horizontal" role="form" style="margin:0px 0px 15px 0px;">
	        <div class="form-group" style="margin-bottom: 0px;" id="secure-pixels">
				<label class="col-xs-2 control-label" style="text-align: left;">Secure Link:</label>

				<div class="col-xs-10" style="margin-top: 15px;">
					<div class="row">
						<div class="col-xs-4">
							<label class="radio">
		            			<input type="radio" name="secure_type" value="0" data-toggle="radio" checked="">
		            				No <span class="label label-primary">http://</span>
		          			</label>
						</div>

						<div class="col-xs-4">
							<label class="radio">
			            		<input type="radio" name="secure_type" value="1" data-toggle="radio">
			            			Yes <span class="label label-primary">https://</span>
			          		</label>
						</div>
					</div>
	          	</div>
	        </div>

	        <div class="form-group" style="margin-bottom: 0px;" id="dedupe-pixels">
				<label class="col-xs-2 control-label" style="text-align: left;">Deduper:</label>

				<div class="col-xs-10" style="margin-top: 15px;">
					<div class="row">
						<div class="col-xs-4">
							<label class="radio">
		            			<input type="radio" name="dedupe_type" value="0" data-toggle="radio" checked="">
		            				No <span class="label label-primary">Process Duplicate Subids</span>
		          			</label>
						</div>

						<div class="col-xs-4">
							<label class="radio">
			            		<input type="radio" name="dedupe_type" value="1" data-toggle="radio">
			            			Yes <span class="label label-primary">Ignore Duplicate Subids</span>
			          		</label>
						</div>
					</div>
	          	</div>
	        </div>
	        	        
			<div id="advanced_pixel_type">
				<div class="form-group" style="margin-bottom: 0px;">
			        <label for="aff_network_id" class="col-xs-2 control-label" style="text-align: left;">Category:</label>
			        <div class="col-xs-4" style="margin-top: 10px;">
			        	<img id="aff_network_id_div_loading" src="/202-img/loader-small.gif" />
						<div id="aff_network_id_div"></div>
			        </div>
			    </div>
			    <div class="form-group" style="margin-bottom: 0px;">
			        <label for="aff_campaign_id" class="col-xs-2 control-label" style="text-align: left;">Campaign:</label>
			        <div class="col-xs-4" style="margin-top: 10px;">
			        	<img id="aff_campaign_id_div_loading" src="/202-img/loader-small.gif" style="display: none;" />
						<div id="aff_campaign_id_div">
							<select class="form-control input-sm" id="aff_campaign_id" disabled="">
			                	<option value="">--</option>
			            	</select>
						</div>
			        </div>
			    </div>
		    </div>
		    <div class="form-group" style="margin-bottom: 0px;">
				<label class="col-xs-2 control-label" for="amount_value" style="text-align: left;">Amount:</label>
				<div class="col-xs-4" style="margin-top: 10px;">
					<input class="form-control input-sm" type="text" name="amount_value" id="amount_value"/>
					<span class="help-block" style="font-size: 10px;">Enter an amount to override the affiliate campaign default</span>
				</div>
			</div>
		   	<div class="form-group" style="margin-bottom: 0px;">
				<label class="col-xs-2 control-label" for="subid_value" style="text-align: left;">Subid:</label>
				<div class="col-xs-4" style="margin-top: 10px;">
				<input class="form-control input-sm" type="text" name="subid_value" id="subid_value"/>	
					<span class="help-block" style="font-size: 10px;">Enter a subid value for the network you are working with, e.g.<br><br> <span class="label label-primary" style="font-size: 10px;">%subid1%</span>, <span class="label label-primary" style="font-size: 10px;">#s1#</span> , <span class="label label-primary" style="font-size: 10px;">{aff_sub}</span>
				</div>
			</div>

			<div class="form-group" style="margin-bottom: 0px;">
				<label class="col-xs-2 control-label" for="tid_value" style="text-align: left;">Transaction ID:</label>
				<div class="col-xs-4" style="margin-top: 10px;">
				<input class="form-control input-sm" type="text" name="tid_value" id="tid_value"/>	
					<span class="help-block" style="font-size: 10px;">Enter a transactionID for the network you are working with, e.g.<br><br> <span class="label label-primary" style="font-size: 10px;">%transactionid1%</span>, <span class="label label-primary" style="font-size: 10px;">#tid1#</span> , <span class="label label-primary" style="font-size: 10px;">{transaction_id}</span>
				</div>
			</div>
			
		</form>
	</div>
</div>

	<div class="row form_seperator" style="margin-bottom:15px;">
		<div class="col-xs-12"></div>
	</div>
<div class="row">
	<div class="col-xs-12">
		<div id="pixel_type_simple_id">
			<div class="panel panel-default">
				<div class="panel-heading"><center>Global Tracking Pixel</center></div>
				<div class="panel-body">
					<span class="infotext">Here is the tracking pixel for your p202 account. Give this to the network or advertiser you are working with and ask them to place it on the confirmation page.
					Once placed, it will fire the update your leads automatically when it is fired. Only use a secure https pixel if you have SSL installed.</span><br><br/>
					<textarea id="global_pixel" class="form-control" rows="2" style="background-color: #f5f5f5; font-size: 12px;"></textarea>
				</div>
			</div>
			<div class="panel panel-default">
				<div class="panel-heading"><center>Global Post Back URL</center></div>
				<div class="panel-body">
					<span class="infotext">If the network you work with supports post back URLs, you can use this URL. The network should use this post-back URL and call it when a lead or sale takes place
					and they should put the SUBID at the end of the url. Once called, it will automatically update your subids and conversion for you.
					Only use a secure https pixel if you have SSL installed.<br/>
					If the network you are working with can only pass the ?sid= variable, you can replace ?subid= with ?sid= </span><br><br/>
					<textarea id="global_postback" class="form-control" rows="2" style="background-color: #f5f5f5; font-size: 12px;"></textarea>
				</div>
			</div>
			<div class="panel panel-default">
				<div class="panel-heading"><center>Javascript Universal Smart Tracking Pixel</center></div>
				<div class="panel-body">
					<span class="infotext">Here is the  Universal Smart Tracking Pixel for your p202 account. Give this to the network or advertiser you are working with and ask them to place it on the confirmation page.
						Once placed, it will fire the update your leads automatically when it is fired. Additionally, it will fire the pixel for the traffic source that genearted this sale or lead.
		Only use a secure https pixel if you have SSL installed.<br/>
		If the network you are working with can only pass the ?sid= variable, you can replace ?subid= with ?sid=</span><br><br/>
					<textarea id="universal_js" class="form-control" rows="14" style="background-color: #f5f5f5; font-size: 12px;"></textarea>
				</div>
			</div>
			<div class="panel panel-default">
				<div class="panel-heading"><center>Iframe Universal Smart Tracking Pixel</center></div>
				<div class="panel-body">
					<textarea id="universal_iframe" class="form-control" rows="2" style="background-color: #f5f5f5; font-size: 12px;"></textarea>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function() {
	load_aff_network_id();
	change_pixel_data();
    $("#secure-pixels input:radio").on("change.radiocheck", function () {
		change_pixel_data();
    });

    $("#dedupe-pixels input:radio").on("change.radiocheck", function () {
		change_pixel_data();
    });
    $('#amount_value').keyup(function () { change_pixel_data(); });
    $('#subid_value').keyup(function () { change_pixel_data(); });
    $('#tid_value').keyup(function () { change_pixel_data(); });
});
</script>

<?php template_bottom($server_row); ?>