<?php 
header('Content-type: application/javascript');
header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
header('Expires: Sun, 03 Feb 2008 05:00:00 GMT');
header("Pragma: no-cache");
include_once(substr(dirname( __FILE__ ), 0,-7) . '/202-config/connect.php');
?>

var validated_lp;

$(document).ready(function() {
	$('[data-toggle="radio"]').radiocheck();
	$('[data-toggle="checkbox"]').radiocheck();
	$('[data-toggle="tooltip"]').tooltip();
	$('[data-toggle="dropdown"]').dropdown();
    
	var validator = $("#survey-form").validate({
	  	    ignore:[],
	  	    focusCleanup: true,
	  	    errorPlacement: function(error, element) {},
		  	highlight: function(element) {
			    $(element).closest('.form-group').addClass('has-error');
			},
			unhighlight: function(element, errorClass) {
		        $(element).closest('.form-group').removeClass('has-error');
		    }
	});

	var select_id = 0;

    //P202 update check
	$.ajax({
		url: "<?php echo get_absolute_url();?>202-account/ajax/check-for-update.php",
	})
	.done(function() {
		$.get("<?php echo get_absolute_url();?>202-account/ajax/update-needed.php", function(data) {
		  	$("#update_needed").html(data);
		});
	});
	
	$(":radio[name=autocron]").on("change.radiocheck", function () {
		var autocron = $(this).val();
		$.post("<?php echo get_absolute_url();?>202-account/administration.php", {autocron: autocron});
    });

	//show/hide help text
	$("#help-text-trigger").click(function() {
		var element = $("#help-text");
		if (element.is(':hidden')) {
			element.fadeIn();
		} else {
			element.fadeOut();
		}
	});

	//Campaign rotator No/Yes
	$(":radio[name=aff_campaign_rotate]").on("change.radiocheck", function () {
		var element = $("#rotateUrls");
        
        if ($(this).val() == 1) {
        	element.show();
        } else {
        	element.hide();
        }
    });

	//Direct/Simple/Advanced landing page radio buttons
    $("#radio-select input:radio").on("change.radiocheck", function () {
		var element = $("#aff-campaign-div");
		var leave_behind = $("#leave_behind_div");
		var lp_element = $("#lp_landing_page");
		var placeholders = $("#placeholderslp");
        
        if ($(this).val() == 0) {
        	element.show();
        	leave_behind.show();
        	lp_element.hide();
        	load_aff_network_id();
        	$("#aff_campaign_id").html("<option>--</option>").prop( "disabled", true );
        	placeholders.show();
        } else {
        	element.hide();
        	lp_element.show();
        	placeholders.hide();
        }
    });

    //Ad Preview update from input
    $('#text_ad_headline').keyup(function () { $("#ad-preview-headline").html($(this).val()); });
    $('#text_ad_description').keyup(function () { $("#ad-preview-body").html($(this).val()); });
    $('#text_ad_display_url').keyup(function () { $("#ad-preview-url").html($(this).val()); });

    //Placeholder select buttons
    $('#placeholders input[type=button]').click(function() {
            var value = $(this).val();
            var input = $("#aff_campaign_url");
            input.caret(value);
            return false;
    });

       $('#placeholderslp input[type=button]').click(function() {
        var value = $(this).val();
        var input = $("#landing_page_url");
        input.caret(value);
        return false;
});
       
    //triger simple LP tracking link generate function
    $("#generate-tracking-link-simple").click(function() {
    	generate_simple_lp_tracking_links();
	});

    //triger add new offer function on ADV LP page
    $("#add-more-offers").click(function() {
    	load_new_aff_campaign();
	});

    //triger ADV LP tracking link generate function
    $("#generate-tracking-link-adv").click(function() {
    	generate_adv_lp_tracking_links();
	});

	//triger Get Links function
    $("#get-links").click(function() {
    	getTrackingLinks();
	});

    
    //Tracker type select on Get Links
	$("#tracker-type input:radio").on("change.radiocheck", function () {
        var element1 = $('#tracker_aff_network');
        var element2 = $('#tracker_aff_campaign');
        var element3 = $('#tracker_method_of_promotion');
        var element4 = $('#tracker_lp');
        var element5 = $('#tracker_ad_copy');
        var element6 = $('#tracker_ad_preview');
        var element7 = $('#tracker_cloaking');
        var element8 = $('#tracker_rotator');

        if ($(this).val() == 0) {
        	element1.show();
			element2.show();
			element3.show();
			element4.show();
			element5.show();
			element6.show();
			element7.show();
			element8.hide();

			load_aff_network_id(0);
			load_aff_campaign_id(0,0);
			load_landing_page(0, 0, '');
        } else if($(this).val() == 1) {
        	element1.hide();
			element2.hide();
			element3.hide();
			element4.show();
			element5.show();
			element6.show();
			element7.show();
			element8.hide();

			load_aff_network_id(0);
			load_aff_campaign_id(0,0);
			load_landing_page(0, 0, 'advlandingpage');
        } else if($(this).val() == 2) {
			element1.hide();
			element2.hide();
			element3.hide();
			element4.hide();
			element5.hide();
			element6.hide();
			element7.hide();
			element8.show();
			load_rotator_id(0);
		}
    });

	//Pixel Type select
	$("#pixel-type input:radio").on("change.radiocheck", function () {
		var pixel_type = $(this).val();

		var element1 = $('#pixel_type_simple_id');
        var element2 = $('#pixel_type_advanced_id');
        var element3 = $('#advanced_pixel_type');
        var element4 = $('#pixel_type_universal_id');

		if (pixel_type == '0') { 
			element1.show();
			element2.hide();
			element3.hide();
			element4.hide();
		} else if (pixel_type == '1') {
			element1.hide();
			element2.show();
			load_aff_network_id();
			element3.show();
			element4.hide();
		} else if (pixel_type == '2') {
			element1.hide();
			element2.hide();
			element3.hide();
			element4.show();
			
		}	
	});

	//Search preferences datepicker
	$('#preferences-wrapper .datepicker input:text').datepicker({
	    dateFormat: 'mm/dd/yy',

	    onSelect: function(datetext){
	    	var id = $(this).attr("id");
	    	$(this).val(datetext);
	    	unset_user_pref_time_predefined();
	    },
	});

	//More/Less Options in search preferences
	$("#s-toogleAdv").click(function() {
		$('#text_ad_id').val(0);
		$('#method_of_promotion').val(0);
		$('#landing_page_id').val(0);
		$('#ad_preview_div').html("");
		$('#country').val("");
		$('#referer').val("");

		if($('#more-options').is(':hidden')){
			$("#more-options").fadeToggle( "fast" );
			$('#user_pref_adv').val("1");
			$('#s-toogleAdv').text('Less Options');
		} else {
			$("#more-options").fadeToggle( "fast" );
			$('#user_pref_adv').val("0");
			$('#s-toogleAdv').text('More Options');
		}
	});

	//Update CPC date picker
	$("#update-cpc-dates input").datepicker({dateFormat: 'mm/dd/yy'});

	//Update CPC button
	$("#update-cpc").click(function() {
		var element = $("#confirm-cpc-update-content");
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/update_cpc.php", $('#cpc_form').serialize(true))
		  .done(function(data) {
		  	element.css("opacity", "1");
		  	element.html(data);
		});
		
	});

	//Clear SUBIDs button
	$("#clear-subids").click(function() {
		var element = $("#response");
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/clear_subids.php", $('#clear_subids_form').serialize(true))
		  .done(function(data) {
		  	element.html(data);
		});
		
	});

	//Update Survey questions
	$("#survey-form-submit").click(function() {
		$('#perks-loading').show();

		$("#survey-form").validate().resetForm();

		if ($("#survey-form").valid()) {
			$.post("<?php echo get_absolute_url();?>202-account/ajax/survey.php", $('#survey-form').serialize(true))
			  .done(function(data) {
			  	if (!data) {
			  		$('#perks-error').hide();
					$('#perks-success').show();
			  		$('#survey-modal').modal('hide');
			  		$('#notification').remove();
			  		$('#notification-perks').remove();
			  		$('#perks-loading').hide();
			  		$("html, body").animate({ scrollTop: 0 }, "slow");
			  	} else {
			  		$('#perks-error').html(data).show();
			  		$('#perks-loading').hide();
					$("html, body").animate({ scrollTop: 0 }, "slow");
			  	}
			});
		} else {
			$('#perks-success').hide();
			$('#perks-error').show();
			$('#perks-loading').hide();
			$("html, body").animate({ scrollTop: 0 }, "slow");
		}
	});

	//Skip Survey questions
	$("#survey-form-skip").click(function() {
		$('#perks-loading').show();

		$.post("<?php echo get_absolute_url();?>202-account/ajax/survey.php", {skip: true})
			  .done(function(data) {
			  	$('#survey-modal').modal('hide');
		});
	});

	$("#survey-form :radio").on("change.radiocheck", function () {
		$(this).prop('checked', true);
    });

	$('#account-dropdown').on('shown.bs.dropdown', function () {
	  $('#notification').hide();
	})

	$('#account-dropdown').on('hidden.bs.dropdown', function () {
	  $('#notification').show();
	})

	//Add more rotator rules
	$("#add_more_rules").click(function() {
		var id;
		var select_id = Math.round(Math.random()*1000);
		$('#addmore_loading').show();
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/rotator.php", {add_more_rules: 1})
		  .done(function(data) {
		  	var html = $(data);
		  	var select = html.filter('.rule_added').find('#tags_select').find('input');
		  	html.find('.rules').attr('id', select_id);
		  	html.find('#rule_name').attr('name', 'rule_name_' + select_id);
		  	html.find('input[type=radio]').attr('name', 'redirect_type_' + select_id);
		  	html.find('div.inactive input[type=checkbox]').attr('name', 'inactive_' + select_id);
		  	html.find('div.splittest input[type=checkbox]').attr('name', 'splittest_' + select_id);
		  	html.find('div.splittest label.checkbox').attr('for', 'splittest_' + select_id);
		  	html.find('div.splittest input[type=checkbox]').attr('id', 'splittest_' + select_id);
		  	id = 'tag_input_' + select_id;
		  	select.attr('id', id);

		  	$('#rotator_rules_container').append(html);
		  	$('#addmore_loading').hide();
		  	rotator_tags_autocomplete(id, 'country');

		});  
		
	});

	//Post rotator rules
	$("#post_rules").click(function() {
		$('#addmore_loading').show();
		//$(this).prop('disabled', true);
		//$('#add_more_rules').prop('disabled', true);

		var rules = [];
		var rotator_id = $('select[name=rotator_id]').val();
		var default_type = $('select[name=default_type]').val();
		var defaults;

		if (default_type == 'campaign') {
			defaults = $('select[name=default_campaign]').val();
		} else if (default_type == 'url') {
			defaults = $('input[name=default_url]').val();
		} else if (default_type == 'lp') {
			defaults = $('select[name=default_lp]').val();
		} else if (default_type == 'monetizer') {
			defaults = 'true';
		}

		$('.rules').each(function(ruleI, ruleObj){
			var rule_id = $(ruleObj).data("rule-id");
			var select_id;
			
			if (rule_id == 'none') {
				select_id = $(ruleObj).attr("id");
			} else {
				select_id = $(ruleObj).data("rule-id");
			}

			var rule_name = $(ruleObj).find('#rule_name').val();
			var inactive = $(ruleObj).find(':checkbox[name=inactive_'+ select_id +']');
			var split = $(ruleObj).find(':checkbox[name=splittest_'+ select_id +']');
			if(inactive.is(':checked')) {status = 'inactive';} else {status = 'active';}
			if(split.is(':checked')) {splittest = true;} else {splittest = false;}
			var redirects = [];
			var criteria = [];
			var redirect_type;
			var redirect;
			var weight;

			if (splittest) {
				$(ruleObj).find('#splittest-redirects > div.row').each(function(redirectI, redirectObj) {

					var redirect_id = $(redirectObj).data("redirect-id");
					redirect_type = $(redirectObj).find('#redirect_type_select').val();

					if (redirect_type == 'campaign') {
						redirect = $(redirectObj).find('select[name=redirect_campaign]').val();
					} else if (redirect_type == 'url') {
						redirect = $(redirectObj).find('input[name=redirect_url]').val();
					} else if (redirect_type == 'lp') {
						redirect = $(redirectObj).find('select[name=redirect_lp]').val();
					} else if (redirect_type == 'monetizer') {
						redirect = 'true';
					}

					weight = $(redirectObj).find('input[name=split_weight]').val();

					redirects.push({
						id: redirect_id,
				        type: redirect_type,
				        value: redirect,
				        weight: weight
				    });
				});
			} else {
				var redirect_id = $(ruleObj).find('#simple-redirect').data("redirect-id");
				redirect_type = $(ruleObj).find('#redirect_type_select').val();

				if (redirect_type == 'campaign') {
					redirect = $(ruleObj).find('select[name=redirect_campaign]').val();
				} else if (redirect_type == 'url') {
					redirect = $(ruleObj).find('input[name=redirect_url]').val();
				} else if (redirect_type == 'lp') {
					redirect = $(ruleObj).find('select[name=redirect_lp]').val();
				} else if (redirect_type == 'monetizer') {
					redirect = 'true';
				}

				redirects.push({
					id: redirect_id,
				    type: redirect_type,
				    value: redirect
				});
			}

			$(ruleObj).find('.criteria').each(function(criteriaI, criteriaObj) {
				var criteria_id = $(criteriaObj).data("criteria-id");
			    var type = $(criteriaObj).find('select[name=rule_type]').val();
				var statement = $(criteriaObj).find('select[name=rule_statement]').val();
				var value = $(criteriaObj).find('input[name=value]').tokenfield('getTokensList', ',', false, false);

				criteria.push({
					criteria_id: criteria_id,
			        type: type,
			        statement: statement,
			        value: value
			    });
			});
			
			rules.push({
				rule_id: rule_id,
		        rule_name: rule_name,
		        status: status,
		        split: splittest,
		        redirects: redirects,
		        criteria: criteria
		    });
		});

		//console.log(rules);

		$.post("<?php echo get_absolute_url();?>tracking202/ajax/rotator.php", 
			{
				post_rules: 1,  
				rotator_id: rotator_id, 
				data: rules,
				default_type: default_type,
				defaults: defaults
			})

		  	.done(function(data) {
		  		//console.log(data);
		  		var result = $.trim(data);
		  		if (result == 'ERROR') {
		  			$('#form_response').hide();
		  			$('#addmore_loading').hide();
					$('#form_erors').show();
					$("html, body").animate({ scrollTop: 0 }, "slow");
		  		} else if(result == 'DONE') {
		  			window.location = "<?php echo get_absolute_url();?>tracking202/setup/rotator.php?rules_added=1";
		  		}
		}); 
	});

	$('select[name=rotator_id]').change(function () {
		var loading = $('#rules_loading');
		loading.show();
		var elt = $(this).val();
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/rotator.php", {rule_defaults: 1, rotator_id: elt})
		  .done(function(data) {
		  	$('#defaults_container').html(data);

		  	$.post("<?php echo get_absolute_url();?>tracking202/ajax/rotator.php", {generate_rules: 1, rotator_id: elt})
			  .done(function(data) {
			  	$('#rotator_rules_container').html(data);
			});
		  	loading.hide();
		});

	    if (elt > 0) {
	    	$('#defaults_container').css('opacity', '1');
	    	$('#rotator_rules_container').css('opacity', '1');
	    	$('#add_more_rules').prop('disabled', false);
	    	$('#post_rules').prop('disabled', false);
	    } else {
	    	$('#defaults_container').css('opacity', '0.5');
	    	$('#rotator_rules_container').css('opacity', '0.5');
	    	$('#add_more_rules').prop('disabled', true);
	    	$('#post_rules').prop('disabled', true);
	    }
 	});
	
	//triger add new offer function on ADV LP page
	$("#app-placeholder").click(function() {
		show_api_needed_message();
	});

	//Cost type
	$(":radio[name=cost_type]").on("change.radiocheck", function () {
		var cpc = $("#cpc_costs");
		var cpa = $("#cpa_costs"); 
        
        if ($(this).val() == 'cpc') {
        	cpc.show();
        	cpa.hide();
        } else {
        	cpa.show();
        	cpc.hide();
        }
    });

	$(".delete_tracker").click(function(e) {
		e.preventDefault();
		var obj = $(this);
		var id = obj.data('id');
		if (confirm('Are you sure you want to delete tracker? Cant be undone!')) {
			$.post("<?php echo get_absolute_url();?>tracking202/ajax/delete_tracker.php", {tracker_id: id})
			  .done(function(data) {
			  	obj.parent().remove();
			});
	    }
	});

	$(".custom.variables").click(function(e) {
		e.preventDefault();
		var obj = $("#variable-group");
		var ppc_network_id = $(this).data('id');
		$(this).text('loading...');
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/custom_variables.php", {get_vars: true, ppc_network_id: ppc_network_id})
			.done(function(data) {
			  	obj.html(data);
			  	if ($('.old-variable').length > 0) {
			  		$("#add_variables_form_submit").text("Update variables");
			  	} else {
			  		$("#add_variables_form_submit").text("Add variables");
			  	}
		});
		$('#ppc_network_id').val(ppc_network_id);
		$('.variables_validate_alert').hide();
		$('#variablesModel').modal();
		$(this).text('variables');
	});

	$("#add_more_variables").click(function(e) {
		e.preventDefault();
		var obj = $("#variable-group");
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/custom_variables.php", {add_more_variables: true})
			.done(function(data) {
			  	obj.append(data);
		});
	});

	$("#add_variables_form_submit").click(function(e) {
		e.preventDefault();
		var $btn = $(this).button('loading')
		var var_id;
		var vars = [];

		$('.var-field-group').each(function(){
			var_id = false;
			if ($(this).data("var-id")) {
				var_id = $(this).data("var-id");
			}

			vars.push({
				id: var_id,
				name: $(this).find('input[name=name]').val(),
			    parameter: $(this).find('input[name=parameter]').val(),
			    placeholder: $(this).find('input[name=placeholder]').val()
			});
		});

		if (vars.length == 0) {
			$.post("<?php echo get_absolute_url();?>tracking202/ajax/custom_variables.php", {delete_vars: true, ppc_network_id: $('#ppc_network_id').val()})
				.done(function(data) {
					$('.variables_validate_alert').hide();
					$btn.button('reset')
					$('#variablesModel').modal('hide');
			});
		} else {
			$.post("<?php echo get_absolute_url();?>tracking202/ajax/custom_variables.php", {post_vars: true, ppc_network_id: $('#ppc_network_id').val(), vars: vars})
				.done(function(data) {
					$('.variables_validate_alert').hide();
					
					if (data == 'VALIDATION FAILD!') {
						$btn.button('reset')
						$('.variables_validate_alert').show();
					} else if (data == 'DONE!') {
						$btn.button('reset')
						$('#variablesModel').modal('hide');
					}
			});
		}
	});

	$("#add_more_pixels").click(function(e) {
		var $btn = $(this);
    	$btn.button('loading');
		e.preventDefault();
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/add_more_pixels.php")
		  .done(function(data) {
		  	$(".pixel-container").append(data);
		  	$btn.button('reset');
		});
	});

	$('select[name=dni_network]').change(function () {
		var apiKeyObj = $('#dni_api_key_input_group');
		var affIdObj = $('#dni_affiliate_id_input_group');
		var netType = $(this).find(':selected').data('type');
		var netTypeInput = $('#dni_network_type');
		var netNameInput = $('#dni_network_name');
		if (netType == 'HasOffers') {
			apiKeyObj.addClass('col-xs-7').removeClass('col-xs-5');
			affIdObj.hide();
			$('#dni_network_affiliate_id').val('null');
		} else if (netType == 'Cake') {
			apiKeyObj.addClass('col-xs-5').removeClass('col-xs-7');
			$('#dni_network_affiliate_id').val('');
			affIdObj.show();
		}

		netTypeInput.val(netType);
		netNameInput.val($(this).find(':selected').text());
        dni();
 	});

	$('select').select2();

	$("#get-logs").click(function() {
		var element = $("#logs_table");
		element.css("opacity", "0.5");
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/conversion_logs.php", $('#logs_from').serialize(true))
		  .done(function(data) {
		  	element.css("opacity", "1");
		  	element.html(data);
		});
	});

	$(".img-check").click(function(){
		$(this).toggleClass("imgchecked");
		$(this).parent().find("i").toggleClass("imgcheckediconshow");
	});

	$('#rapid_builder').submit(function(e){
		e.preventDefault();
		var $btn = $('#previewFeeds');
    	$btn.button('loading');
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/rapidbuilder_preview.php", $('#rapid_builder').serialize())
			.done(function(data) {
			  	//console.log(data);
			  	$('#rapidbuilderTabsContainer').hide();
			  	$('#rapidbuilderTabsContainer').html(data);
			  	$('#rapidbuilderTabsContainer').show();
			  	$btn.button('reset');
			});
	});
});

$(document).on('click', '#generateFeeds', function(e) {
	e.preventDefault();
	var $btn = $(this);
	//console.log($btn);
    $btn.button('loading');
    var data = $('#rapid_builder').serializeArray();
    
    var variations = new Array();
    $('.previewAd').each(function(i, obj) {
        variations[i] = new Array($(obj).find('.adTitle').text(), $(obj).find('.adBody').text(), $(obj).find('img').attr("src"));
	});

    $.post("<?php echo get_absolute_url();?>tracking202/ajax/rapidbuilder.php", {creative_group:$('input[name=creative_group]').val(), story_url:$('input[name=story_url]').val(), titles:$('textarea[name=titles]').val(), bodies:$('textarea[name=bodies]').val(), edit_feed_id:$('input[name=edit_feed_id]').val(), ads:$('input[name="ads[]"]').serializeArray(), variations: variations})
		.done(function(data) {
			//console.log(data);
			$('#rapidbuilderTabsContainer').hide();
			$('#rapidbuilderTabsContainer').html(data);
			$('#rapidbuilderTabsContainer').show();
			$btn.button('reset');
	});
	
});

$(document).on('click', '.removeAd', function(e) {
	e.preventDefault();
	var ad = $(this).parent().find('img').attr("src");
	var parent = $(this).parent().parent();
	$.post("<?php echo get_absolute_url();?>tracking202/ajax/ads.php", {ad: ad})
		.done(function(data) {
			$(parent).remove();
	});
});

$(document).on('click', '.removeVariation', function(e) {
	e.preventDefault();
	$(this).parent().parent().remove();
});

$(document).on('click', '#copytaboolabutton', function(e) {
	e.preventDefault();
	$('#copytaboola').select();
	document.execCommand('copy');
});

$(document).on('click', '#copyoutbrainbutton', function(e) {
	e.preventDefault();
	$('#copyoutbrain').select();
	document.execCommand('copy');
});

$(document).on('click', '#copycontentadbutton', function(e) {
	e.preventDefault();
	$('#copycontentad').select();
	document.execCommand('copy');
});

$(document).on('click', '.pushToRevcontent', function(e) {
	e.preventDefault();
	var $btn = $(this);
    $btn.button('loading');
	var feed_id = $(this).data('id');
	$.post("<?php echo get_absolute_url();?>tracking202/ajax/rapidbuilder_revcontent.php", {feed_id: feed_id})
		.done(function(data) {
		 // 	console.log(data);
		  	$btn.button('reset');
		  	alert(data);
    });
});

$(document).on('click', '.pushToFacebook', function(e) {
	e.preventDefault();
	var feed_id = $(this).data('id');
	var ad_set_id = $("#fb_ad_sets").val();
	if (ad_set_id == "") {
		alert('Select Ad Set!');
		throw 'Select Ad Set!';
	}
	var $btn = $(this);
    $btn.button('loading');
	$.post("<?php echo get_absolute_url();?>tracking202/ajax/rapidbuilder_facebook.php", {feed_id: feed_id, ad_set_id: ad_set_id})
		.done(function(data) {
		  //	console.log(data);
		  	$btn.button('reset');
		  	alert(data);
    });
});

$(document).on("submit", "#upgradeAlertApiKey", function (e) {
    e.preventDefault();
    var $btn = $('#submitApiKey');
    $btn.button('loading');
    $.post("<?php echo get_absolute_url();?>202-account/ajax/upgrade_submit_api_key.php", $('#upgradeAlertApiKey').serialize(true))
		.done(function(data) {
		  	$btn.button('reset');
		  	var parsedJson = $.parseJSON(data);
		  //	console.log(parsedJson);
		  	if(parsedJson.error == false) {
		  		$.post("<?php echo get_absolute_url();?>202-account/ajax/upgrade_submit_api_key.php", {get_alert_body: true})
					.done(function(data) {
						$('#noKeyBody').html(data);
					  	$btn.button('reset');
			    });
		  	} else {
		  		$btn.button('reset');
		  		alert(parsedJson.msg);
		  	}
    }); 
});

$(document).on('click', 'a.showFullDniApikey', function(e) {
	e.preventDefault();
	var long = $(this).data('long');
	var short = $(this).data('short');
    $(this).parent().html(long + ' <a href="#" class="link showShortDniApikey" data-long="'+long+'" data-short="'+short+'">hide</a>');
});

$(document).on('click', 'a.showShortDniApikey', function(e) {
	e.preventDefault();
	var long = $(this).data('long');
	var short = $(this).data('short');
	$(this).parent().html(short + '... <a href="#" class="link showFullDniApikey" data-long="'+long+'" data-short="'+short+'">show</a>');
});

$(document).on('change.radiocheck', '.splittest-checkbox', function() {
	var container = $(this).parents().eq(4);
	var obj1 = container.find('#simple-redirect');
	var obj2 = container.find('#splittest-redirects');

	if ($(this).is(':checked')) {
		obj1.hide();
		obj2.show();
	} else {
		obj1.show();
		obj2.hide();
	}
});

$(document).on('click', '.remove_variable', function() {
	$(this).parents().eq(2).remove();
});

$(document).on('click', '#build_chart', function() {
	$('#buildChartModal').modal();
});

$(document).on('change', ':radio[name=chart_time_range]', function() {
	var element = $('#chart');
	element.css("opacity", "0.5");
	$.post("<?php echo get_absolute_url();?>tracking202/ajax/charts.php", {chart_time_range: $(':radio[name=chart_time_range]:checked').val()})
		.done(function(data) {
			var chart = new Highcharts.Chart({
				chart: {
					renderTo: 'chart',	
		            type: 'line'
		        },
		        title: {
		            text: data.title
		        },
		        xAxis: {
		            categories: data.categories
		        },
		        plotOptions: {
		            line: {
		                dataLabels: {
		                    enabled: true
		                }
		            }
		        },
		        series: data.json.series
			});
			element.css("opacity", "1");
			$(".modal-backdrop.fade.in").remove();
	$('#buildChartModal').modal('toggle');	
	
	});

});

$(document).on('click', '#add_more_chart_data_type', function(e) {
	e.preventDefault();
	var obj = $('#build_chart_form').find('.col-xs-12:first').clone();
	obj.append('<span class="small"><a href="#" class="remove_chart_data_type" style="color:#a1a6a9"><i class="fa fa-close"></i></a></span>');
	obj.appendTo('#build_chart_form');
});

$(document).on('click', '.remove_chart_data_type', function(e) {
	e.preventDefault();
	$(this).parents().eq(1).remove();
});

$(document).on('click', '#build_chart_form_submit', function() {
	var levels = [];
	var types = [];

	$(':input[name="data_level[]"]').each(function() {
		levels.push({
			id: $(this).val()
		});
	});

	$(':input[name="data_type[]"]').each(function() {
		types.push({
			type: $(this).val()
		});
	});

	$.post("/tracking202/ajax/charts.php", {levels: levels, types: types})
		.done(function(data) {
			set_user_prefs('<?php echo get_absolute_url();?>tracking202/ajax/account_overview.php');
			
	});
		$(".modal-backdrop.fade.in").remove();

	$('#buildChartModal').modal('toggle');
});

//Rotator redirect type
$(document).on('change', '#redirect_type_select', function() {
    var redirect_campaign = $(this).parents().eq(1).find('#redirect_campaign_select');
    var redirect_lp = $(this).parents().eq(1).find('#redirect_lp_select');
    var redirect_url = $(this).parents().eq(1).find('#redirect_url_input');
    var monetizer = $(this).parents().eq(1).find('#redirect_monetizer');
    
    if ($(this).val() == 'campaign') {
    	redirect_campaign.show();
    	redirect_url.hide();
    	redirect_lp.hide();
    	monetizer.hide();
    } else if ($(this).val() == 'lp') {
    	redirect_lp.show();
    	redirect_campaign.hide();
    	redirect_url.hide();
    	monetizer.hide();
    } else if($(this).val() == 'url') {
    	redirect_url.show();
    	redirect_campaign.hide();
    	redirect_lp.hide();
    	monetizer.hide();
    } else if ($(this).val() == 'monetizer') {
    	monetizer.show();
    	redirect_campaign.hide();
    	redirect_lp.hide();
    	redirect_url.hide();
    }
});

//Rotator defaults type
$(document).on('change', '#default_type_select', function() {
    var redirect_campaign = $('#default_campaign_select');
    var redirect_url = $('#default_url_input');
    var redirect_lp = $('#default_lp_select');
    var auto_monetizer = $('#default_monetizer');

    if ($(this).val() == 'campaign') {
    	redirect_campaign.show();
    	redirect_url.hide();
    	redirect_lp.hide();
    	auto_monetizer.hide();
    } else if ($(this).val() == 'url') {
    	redirect_url.show();
    	redirect_campaign.hide();
    	redirect_lp.hide();
    	auto_monetizer.hide();
    } else if($(this).val() == 'lp') {
    	redirect_lp.show();
    	redirect_url.hide();
    	redirect_campaign.hide();
    	auto_monetizer.hide();
    } else if ($(this).val() == 'monetizer') {
    	auto_monetizer.show();
    	redirect_lp.hide();
    	redirect_url.hide();
    	redirect_campaign.hide();
    }
});

//Add more rotator rules
$(document).on('click', '#add_more_criteria', function() {
		var id;
		var loading = $(this).parent().find('img');
		var container = $(this).parents().eq(2).find('#criteria_container');
		loading.show();
		//$('#addmore_criteria_loading').show();
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/rotator.php", {add_more_criteria: 1})
		  .done(function(data) {
		  	var html = $(data);
		  	var select = html.find('#tag');
		  	select_id = Math.round(Math.random()*1000);
		  	id = 'tag_input_' + select_id;
		  	select.attr('id', id);

		  	container.append(html);
		  	loading.hide();
		  	rotator_tags_autocomplete(id, 'country');
		}); 
});

$(document).on('click', '#add_more_redirects', function() {
		var loading = $(this).parent().find('img');
		var container = $(this).parents().eq(3);
		loading.show();
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/rotator.php", {add_more_redirects: 1})
		  .done(function(data) {
		  	container.append(data);
		  	loading.hide();
		}); 
});

$(document).on('click', '#remove_redirect', function() {
	$(this).parent().remove();
});

//Remove rule
$(document).on('click', '#remove_rule', function() {
	$(this).parents().eq(2).remove();
});

//Remove criteria
$(document).on('click', '#remove_criteria', function() {
	$(this).parents().eq(2).remove();
});

//Rotator details modal (in report)
$(document).on('click', '#rule_details', function(e) {
	e.preventDefault();
	var id = $(this).data('id');

	$.post("<?php echo get_absolute_url();?>tracking202/ajax/rotator.php", {rule_details: 1, rule_id: id})
		.done(function(data) {
		$('#rotator_details_body').html(data);
		$('#rule_values_modal').modal('show');
	});
});

//On rule type change, generate new autocomplate
$(document).on('change', 'select[name=rule_type]', function() {
	var val = $(this).val();
	var parent = $(this).parent().parent();
	var select = parent.find('.value_select');
	var select_id = select.attr('id');
	switch(val) {
		case 'country':
			select.tokenfield('destroy');
			select.attr('placeholder', 'Type in country and hit Enter');
			rotator_tags_autocomplete(select_id, 'country');
		break;

	  	case 'region':
	  		select.tokenfield('destroy');
			select.attr('placeholder', 'Type in state/region and hit Enter');
			rotator_tags_autocomplete(select_id, 'region');
	  	break;

	  	case 'city':
			select.tokenfield('destroy');
			select.attr('placeholder', 'Type in city and hit Enter');
			rotator_tags_autocomplete(select_id, 'city');
	  	break;

	  	case 'isp':
			select.tokenfield('destroy');
			select.attr('placeholder', 'Type in ISP/Carrier and hit Enter');
			rotator_tags_autocomplete(select_id, 'isp');
	  	break;

	  	case 'ip':
	  		select.tokenfield('destroy');
			select.attr('placeholder', 'Type in IP address and hit Enter');
			rotator_tags_autocomplete_ip(select_id);
	  	break;

	  	case 'browser':
	  		select.tokenfield('destroy');
			select.attr('placeholder', 'Type in browser and hit Enter');
			rotator_tags_autocomplete(select_id, 'browser');
	  	break;

	  	case 'platform':
	  		select.tokenfield('destroy');
			select.attr('placeholder', 'Type in OS and hit Enter');
			rotator_tags_autocomplete(select_id, 'platform');
	  	break;

	  	case 'device':
	  		select.tokenfield('destroy');
	  		select.attr('placeholder', '');
			rotator_tags_autocomplete_devices(select_id);
	  	break;

	  	case 'visitor':
	  		select.tokenfield('destroy');
	  		select.attr('placeholder', '');
			rotator_tags_autocomplete_visitor(select_id);
	  	break;

	  	case 'c1':
	  	case 'c2':
	  	case 'c3':
	  	case 'c4':
	  		select.tokenfield('destroy');
			select.attr('placeholder', 'Type in C variable content and hit Enter');
			rotator_tags_autocomplete_ip(select_id);
	  	break;

	  	case 't202kw':
	  		select.tokenfield('destroy');
			select.attr('placeholder', 'Type in keyword and hit Enter');
			rotator_tags_autocomplete_ip(select_id);
	  	break;

	  	case 'utm_source':
	  	case 'utm_medium':
	  	case 'utm_campaign':
	  	case 'utm_term':
	  	case 'utm_content':
	  		select.tokenfield('destroy');
			select.attr('placeholder', 'Type in _utm variable content and hit Enter');
			rotator_tags_autocomplete_ip(select_id);
	  	break;

	  	case 'referer':
	  		select.tokenfield('destroy');
			select.attr('placeholder', 'Type in referer url and hit Enter');
			rotator_tags_autocomplete_ip(select_id);
	  	break;

	  	case 'iprange':
	  		select.tokenfield('destroy');
			select.attr('placeholder', 'Type in IP range and hit Enter');
			rotator_tags_autocomplete_ip(select_id);
	  	break;
	}
});

$(document).on('change.radiocheck', '.offer-type-radio', function(e) {
	if ($(this).val() == 'campaign') {
		$(this).parents().eq(4).find('.campaign_select').show();
		$(this).parents().eq(4).find('.rotator_select').hide();
	} else if ($(this).val() == 'rotator') {
		$(this).parents().eq(4).find('.rotator_select').show();
		$(this).parents().eq(4).find('.campaign_select').hide();
	}
	
});

$(document).on('click', '#remove_pixel', function(e) {
	e.preventDefault();
	$(this).parents().eq(2).remove();
});

// Load affiliate networks
function load_aff_network_id(aff_network_id){
	var element = $("#aff_network_id_div");
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/aff_networks.php", {aff_network_id: aff_network_id})
		  .done(function(data) {
		  	$('#aff_network_id_div_loading').hide();
		  	element.html(data);
		  	$("#aff_network_id").select2();
		});

        
}

// Load rotators
function load_rotator_id(rotator_id){
	var element = $("#rotator_id_div");
	$('#rotator_id_div_loading').show();
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/rotator.php", {get_rotators:1, rotator_id: rotator_id})
		  .done(function(data) {
		  	$('#rotator_id_div_loading').hide();
		  	element.html(data);
		  	$("#the_tracker_rotator").select2();
		});
}

// Load affiliate campaigns
function load_aff_campaign_id(aff_network_id, aff_campaign_id){
	var element = $("#aff_campaign_id_div");
		$('#aff_campaign_id').hide();
		$('#aff_campaign_id_div_loading').show();

		$.post("<?php echo get_absolute_url();?>tracking202/ajax/aff_campaigns.php", {aff_network_id: aff_network_id, aff_campaign_id: aff_campaign_id})
		  .done(function(data) {
		  	$('#aff_campaign_id_div_loading').hide();
		  	element.html(data);
		  	$("#aff_campaign_id").select2();
		});
		
		
}

//Load landing pages
function load_landing_page(aff_campaign_id, landing_page_id, type, validate_lp) {
    validate_lp = typeof validate_lp !== 'undefined' ? 1 : 0;
//console.log(validate_lp);
	var element = $("#landing_page_div");
    
		$('#landing_page_div_loading').show();

		$.post("<?php echo get_absolute_url();?>tracking202/ajax/landing_pages.php", {aff_campaign_id: aff_campaign_id, landing_page_id: landing_page_id, type: type, validate: validate_lp})
		  .done(function(data) {
		  	$('#landing_page_div_loading').hide();
		  	element.html(data);
		  	$("#landing_page_id").select2();
		});
}

// Load countries
function load_country_id(country_id){
	var element = $("#country_id_div");
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/countries.php", {country_id: country_id})
		  .done(function(data) {
		  	$('#country_id_div_loading').hide();
		  	element.html(data);
		  	$("#country_id").select2();
		});
}

// Load regions
function load_region_id(region_id){
	var element = $("#region_id_div");
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/regions.php", {region_id: region_id})
		  .done(function(data) {
		  	$('#region_id_div_loading').hide();
		  	element.html(data);
		  	$("#region_id").select2();
		});
}

// Load isp's carriers
function load_isp_id(isp_id){
	var element = $("#isp_id_div");
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/isp.php", {isp_id: isp_id})
		  .done(function(data) {
		  	$('#isp_id_div_loading').hide();
		  	element.html(data);
		  	$("#isp_id").select2();
		});
}

// Load device types
function load_device_id(device_id){
	var element = $("#device_id_div");
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/device_type.php", {device_id: device_id})
		  .done(function(data) {
		  	$('#device_id_div_loading').hide();
		  	element.html(data);
		  	$("#device_id").select2();
		});
}

// Load browser types
function load_browser_id(browser_id){
	var element = $("#browser_id_div");
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/browser.php", {browser_id: browser_id})
		  .done(function(data) {
		  	$('#browser_id_div_loading').hide();
		  	element.html(data);
		  	$("#browser_id").select2();
		});
}

// Load platform types
function load_platform_id(platform_id){
	var element = $("#platform_id_div");
		$.post("<?php echo get_absolute_url();?>tracking202/ajax/platform.php", {platform_id: platform_id})
		  .done(function(data) {
		  	$('#platform_id_div_loading').hide();
		  	element.html(data);
		  	$("#platform_id").select2();
		});
}

//Generate simple landing page tracking links
function generate_simple_lp_tracking_links() {
	var element = $("#tracking-links");

	var spinner_html = '<center><img id="get_code_loading" src="/202-img/loader-small.gif"/></center>'
	element.css("opacity", "0.5");
	element.html(spinner_html);

	$.post("<?php echo get_absolute_url();?>tracking202/ajax/get_landing_code.php", $("#tracking_form").serialize(true))
		  .done(function(data) {
		  	element.css("opacity", "1");
		  	element.html(data);
		});
}

//Remove offer on ADV LP
function remove_new_campaign(counter) {
	$("#area_"+counter).remove();
}

//Load more offers on adv lp code page
function load_new_aff_campaign() {
	var counter = $("#counter").val();
	counter++;

	var element = $("#load_aff_campaign_"+counter);
	$("#counter").val(counter);

	$("#load_aff_campaign_"+counter+"_loading").show();

	$.post("<?php echo get_absolute_url();?>tracking202/ajax/adv_landing_pages.php", $("#tracking_form").serialize(true))
		.done(function(data) {
			$("#load_aff_campaign_"+counter+"_loading").hide();
			element.html(data);
		});
}

//Generate advanced landing page tracking links
function generate_adv_lp_tracking_links() {
	var element = $("#tracking-links");
	var spinner_html = '<center><img id="get_code_loading" src="/202-img/loader-small.gif"/></center>'
	element.css("opacity", "0.5");
	element.html(spinner_html);
	$.post("<?php echo get_absolute_url();?>tracking202/ajax/get_adv_landing_code.php", $("#tracking_form").serialize(true))
		  .done(function(data) {
		  	element.css("opacity", "1");
		  	element.html(data);
		});
}

//Load methods of promotion (direct/lp)
function load_method_of_promotion(method_of_promotion, on_page, validate_lp) {
    var validate_lp = typeof validate_lp !== 'undefined' ? 1 : 0;
	var element = $("#method_of_promotion_div");
    var on_page = typeof on_page !== '0' ? on_page : 0; //set default value of on_page to 0 if nothing was passed in
		$('#method_of_promotion_div_loading').show();

		$.post("<?php echo get_absolute_url();?>tracking202/ajax/method_of_promotion.php", {method_of_promotion: method_of_promotion, page: on_page, validate: validate_lp})
		  .done(function(data) {
		  	$('#method_of_promotion_div_loading').hide();
		  	element.html(data);
		  	$("#method_of_promotion").select2();
		});
}

//Load publishers
function load_publisher_id(user_id) {
	var element = $("#publisher_id_div");
    
		$('#publisher_id_div_loading').show();

		$.post("<?php echo get_absolute_url();?>tracking202/ajax/publishers.php", {user_id: user_id})
		  .done(function(data) {
		  	$('#publisher_id_div_loading').hide();
		  	element.html(data);
		  	$("#publisher_id").select2();
		});
}


//Load text ads
function load_text_ad_id(aff_campaign_id, text_ad_id) {
	var element = $("#text_ad_id_div");
    
		$('#text_ad_id_div_loading').show();

		$.post("<?php echo get_absolute_url();?>tracking202/ajax/text_ads.php", {aff_campaign_id: aff_campaign_id, text_ad_id: text_ad_id})
		  .done(function(data) {
		  	$('#text_ad_id_div_loading').hide();
		  	element.html(data);
		  	$("#text_ad_id").select2();
		});
}

//Load adv lp text ads
function load_adv_text_ad_id(landing_page_id, text_ad_id) {
	var element = $("#text_ad_id_div");
    
		$('#text_ad_id_div_loading').show();

		$.post("<?php echo get_absolute_url();?>tracking202/ajax/adv_text_ads.php", {landing_page_id: landing_page_id, text_ad_id: text_ad_id})
		  .done(function(data) {
		  	$('#text_ad_id_div_loading').hide();
		  	element.html(data);
		});
}

//Load text ad preview
function load_ad_preview(text_ad_id) {
	var element = $("#ad_preview_div");
    
		$('#ad_preview_div_loading').show();

		$.post("<?php echo get_absolute_url();?>tracking202/ajax/ad_preview.php", {text_ad_id: text_ad_id})
		  .done(function(data) {
		  	$('#ad_preview_div_loading').hide();
		  	element.html(data);
		});
}

//Load ppc networks
function load_ppc_network_id(ppc_network_id) {
	var element = $("#ppc_network_id_div");
    
		$('#ppc_network_id_div_loading').show();

		$.post("<?php echo get_absolute_url();?>tracking202/ajax/ppc_networks.php", {ppc_network_id: ppc_network_id})
		  .done(function(data) {
		  	$('#ppc_network_id_div_loading').hide();
		  	element.html(data);
		  	$("#ppc_network_id").select2();
		});
}

//Load ppc networks
function load_ppc_account_id(ppc_network_id, ppc_account_id) {
	var element = $("#ppc_account_id_div");
    
		$('#ppc_account_id_div_loading').show();

		$.post("<?php echo get_absolute_url();?>tracking202/ajax/ppc_accounts.php", {ppc_network_id: ppc_network_id, ppc_account_id: ppc_account_id})
		  .done(function(data) {
		  	$('#ppc_account_id_div_loading').hide();
		  	element.html(data);
		  	$("#ppc_account_id").select2();
		});
}

function tempLoadMethodOfPromotion(select,validate_lp) {
	//console.log(select.value);
	// validate_lp = typeof validate_lp !== 'undefined' ? 1 : 0;
	 
	if (select.value == 'directlink') {
		if ($('#aff_campaign_id').val() > 0) { 
		   	load_text_ad_id( $('#aff_campaign_id').val());
		}
			load_landing_page( 0, 0, '','');

	} else if(select.value == 'landingpage' || select.value == 'landingpages') {
		load_landing_page( $('#aff_campaign_id').val(), 0, select.value, validate_lp);
		if ($('#landing_page_id').val() >= 0) {
			load_text_ad_id( $('#aff_campaign_id').val());
		}
	}
}	

//Get Links function
function getTrackingLinks() { 
  	var element = $("#tracking-links");
	var spinner_html = '<center><img id="get_code_loading" src="/202-img/loader-small.gif"/></center>'
	element.css("opacity", "0.5");
	element.html(spinner_html);

	$.post("<?php echo get_absolute_url();?>tracking202/ajax/generate_tracking_link.php", $("#tracking_form").serialize(true))
		  .done(function(data) {
		  	element.css("opacity", "1");
		  	element.html(data);
		});
}

//validate landing page for js placement

function validate_lpjs(lp_id) { 
  	
  	//console.log("landing page id:"+lp_id);

	$.post("<?php echo get_absolute_url();?>tracking202/ajax/validate_landing_code.php", {landing_page_id: lp_id})
		  .done(function(data) {
		  validated_lp =data
		});
}

// Confirms alert
function confirmAlert(text){
	var c = confirm(text);
	if(c == false) {
	    event.preventDefault();
	}
}

function change_pixel_data(){
	var trackingDomain = '<?php echo getTrackingDomain(); ?>';
	var absoluteUrl = '<?php echo get_absolute_url();?>';
	var secureTypeValue = $("input[name=secure_type]:checked").val();
	var http_val = 'http';
	var dedupe_val = $("input[name=dedupe_type]:checked").val();
	
	if(secureTypeValue == 1) {
		http_val = 'https';
	}

	var amount_value = $('#amount_value').val();
	var campaign_id_value = '';
	var subid_value = $('#subid_value').val();
	var tid_value = $('#tid_value').val();
	if($('#aff_campaign_id').val()!='') {
			campaign_id_value = $('#aff_campaign_id').val();
	}

	var global_pixel = '<img height="1" width="1" border="0" style="display: none;" src="{{0}}://' + trackingDomain + absoluteUrl + 'tracking202/static/gpx.php?amount={{1}}&subid={{2}}&cid={{3}}&t202txid={{4}}&t202dedupe={{5}}" />';
	var global_postback = '{{0}}://' + trackingDomain + absoluteUrl + 'tracking202/static/gpb.php?amount={{1}}&subid={{2}}&t202txid={{3}}&t202dedupe={{5}}'
	var universal_js = '<script>\n var vars202={amount:"{{1}}", subid:"{{2}}", cid:"{{3}}", t202txid:"{{4}}", t202dedupe:"{{5}}"};(function(d, s) {\n 	var js, upxf = d.getElementsByTagName(s)[0], load = function(url, id) {\n 		if (d.getElementById(id)) {return;}\n 		if202 = d.createElement("iframe");if202.src = url;if202.id = id;if202.height = 1;if202.width = 0;if202.frameBorder = 1;if202.scrolling = "no";if202.noResize = true;\n 		upxf.parentNode.insertBefore(if202, upxf);\n 	};\n 	load("{{0}}://' + trackingDomain + absoluteUrl + 'tracking202/static/upx.php?amount="+vars202[\'amount\']+"&subid="+vars202[\'subid\']+"&cid="+vars202[\'cid\']+"&t202txid="+vars202[\'t202txid\'], "upxif");\n }(document, "script"));</\script>\n<noscript>\n 	<iframe height="1" width="1" border="0" style="display: none;" frameborder="0" scrolling="no" src="{{0}}://' + trackingDomain + absoluteUrl + 'tracking202/static/upx.php?amount={{1}}&subid={{2}}&cid={{3}}&t202txid={{4}}&t202dedupe={{5}}" seamless></iframe>\n</noscript>';
	var universal_iframe = '<iframe height="1" width="1" border="0" style="display: none;" frameborder="0" scrolling="no" src="{{0}}://' + trackingDomain + absoluteUrl + 'tracking202/static/upx.php?amount={{1}}&subid={{2}}&cid={{3}}&t202txid={{4}}&t202dedupe={{5}}" seamless></iframe>';
	
	$('#global_pixel').val(global_pixel.replace('{{0}}',http_val).replace('{{1}}',amount_value).replace('{{2}}',subid_value).replace('{{3}}',campaign_id_value).replace('{{4}}',tid_value).replace('{{5}}',dedupe_val));
	$('#global_postback').val(global_postback.replace('{{0}}',http_val).replace('{{1}}',amount_value).replace('{{2}}',subid_value).replace('{{3}}',tid_value).replace('{{5}}',dedupe_val));
	$('#universal_js').val(universal_js.replace('{{0}}',http_val).replace('{{1}}',amount_value).replace('{{2}}',subid_value).replace('{{3}}',campaign_id_value).replace('{{4}}',tid_value).replace('{{0}}',http_val).replace('{{1}}',amount_value).replace('{{2}}',subid_value).replace('{{3}}',campaign_id_value).replace('{{4}}',tid_value).replace('{{5}}',dedupe_val).replace('{{5}}',dedupe_val));
	$('#universal_iframe').val(universal_iframe.replace('{{0}}',http_val).replace('{{1}}',amount_value).replace('{{2}}',subid_value).replace('{{3}}',campaign_id_value).replace('{{4}}',tid_value).replace('{{5}}',dedupe_val));

}	

function loadContent(page, offset, order){
	var element = $('#m-content');
	var chartWidth = element.width();

	$.post(page, { offset: offset, order: order, chartWidth:chartWidth})
		  .done(function(data) {
		  	element.html(data);
		  	element.css("opacity", "1");
		});
}

function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";

}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;

}

function eraseCookie(name) {
	createCookie(name,"",-1);
}

function set_user_prefs(page, offset) {     
	var element = $('#m-content');
	element.html('<div class="loading-stats"><span class="infotext">Loading stats...</span> <img src="<?php echo get_absolute_url();?>202-img/loader-small.gif"></div>');
	

	$.post("<?php echo get_absolute_url();?>tracking202/ajax/set_user_prefs.php", $("#user_prefs").serialize(true))
		.done(function(data) {
		  	loadContent(page, offset); 
		});	
}

function unset_user_pref_time_predefined() {
	$("#user_pref_time_predefined").select2().val($("#user_pref_time_predefined option:first").val()).trigger("change");
}

function runSpy() {

	$.get("<?php echo get_absolute_url();?>tracking202/ajax/click_history.php", {spy: '1'})
		.done(function(data) {
			$("#m-content").html(data); 
		  	goSpy();
		});
}

function goSpy() {
	setTimeout(appearSpy,1);  
} 

function appearSpy(){
    $('.new-click').fadeIn(1000);
}

function rotator_tags_autocomplete(selector, type){
	var elt = $('#' + selector);

	var tags = new Bloodhound({
	  remote: '<?php echo get_absolute_url();?>tracking202/ajax/rotator.php?autocomplete=true&type='+ type +'&query=%QUERY',
	  datumTokenizer: function(d) { 
	      return Bloodhound.tokenizers.whitespace(d.val); 
	  },
	  queryTokenizer: Bloodhound.tokenizers.whitespace,
	  limit: 10
	});

	tags.initialize();

  	elt.on('tokenfield:initialize', function (e) {
	    elt.parent().find('.token').remove();
	})

  	.tokenfield({
	  createTokensOnBlur: true,
	  typeahead: {
	  	displayKey: 'label',
	    source: tags.ttAdapter()
	  }
	});
}

function rotator_tags_autocomplete_ip(selector){
	var elt = $('#' + selector);

  	elt.tokenfield({
	  createTokensOnBlur: true,
	});
}

function rotator_tags_autocomplete_devices(selector){
	var elt = $('#' + selector);

	var tags = new Bloodhound({
	  local: [{value: 'bot', label: 'Bot'}, {value: 'mobile', label: 'Mobile'}, {value: 'tablet', label: 'Tablet'} , {value: 'desktop', label: 'Desktop'}],
	  datumTokenizer: function(d) {
	    return Bloodhound.tokenizers.whitespace(d.value); 
	  },
	  queryTokenizer: Bloodhound.tokenizers.whitespace    
	});

	tags.initialize();
	
	elt.tokenfield({
	  createTokensOnBlur: true,
	  typeahead: {
	  	displayKey: 'label',
	    source: tags.ttAdapter()
	  }
	});

	elt.tokenfield('setTokens', [{value: 'bot', label: 'Bot'}, {value: 'mobile', label: 'Mobile'}, {value: 'tablet', label: 'Tablet'} , {value: 'desktop', label: 'Desktop'}]);

}

function rotator_tags_autocomplete_visitor(selector){
	var elt = $('#' + selector);

	var tags = new Bloodhound({
	  local: [{value: 'filtered', label: 'Filtered'}, {value: 'unique', label: 'Unique'}],
	  datumTokenizer: function(d) {
	    return Bloodhound.tokenizers.whitespace(d.value); 
	  },
	  queryTokenizer: Bloodhound.tokenizers.whitespace    
	});

	tags.initialize();
	
	elt.tokenfield({
	  createTokensOnBlur: true,
	  typeahead: {
	  	displayKey: 'label',
	    source: tags.ttAdapter()
	  }
	});

	elt.tokenfield('setTokens', [{value: 'filtered', label: 'Filtered'}, {value: 'unique', label: 'Unique'}]);
}


function subid_autocomplete(selector){
	var elt = $('#' + selector);

	var tags = new Bloodhound({
	  local: [{value: '#s2#', label: 'Cake Marketing'}, {value: '{aff_sub}', label: 'HasOffers'}, {value: 'xxC1xx', label: 'HitPath'} , {value: '[=SID=]', label: 'LinkTrust'}],
	  datumTokenizer: function(d) {
	    return Bloodhound.tokenizers.whitespace(d.value); 
	  },
	  queryTokenizer: Bloodhound.tokenizers.whitespace    
	});

	tags.initialize();
	
	elt.tokenfield({
	  createTokensOnBlur: true,
	  typeahead: {
	  	displayKey: 'label',
	    source: tags.ttAdapter()
	  }
	});

	elt.tokenfield('setTokens', [{value: '#s2#', label: 'Cake Marketing'}, {value: '{aff_sub}', label: 'HasOffers'}, {value: 'xxC1xx', label: 'HitPath'} , {value: '[=SID=]', label: 'LinkTrust'}]);

}

function show_api_needed_message(){
	$('#myModal').modal('show');
}


function autocomplete_names(selector, type){
	var elt = $('#' + selector);

	var tags = new Bloodhound({
	  remote: 'http://my.tracking202.com/api/v2/'+ type +'/%QUERY',
	  datumTokenizer: function(d) { 
	      return Bloodhound.tokenizers.whitespace(d.val); 
	  },
	  queryTokenizer: Bloodhound.tokenizers.whitespace,
	  limit: 10
	});

	tags.initialize();

  	elt.on('tokenfield:initialize', function (e) {
	    elt.parent().find('.token').remove();
	})

  	.tokenfield({
	  createTokensOnBlur: true,
	  limit: 1,
	  typeahead: {
	  	displayKey: 'label',
	    source: tags.ttAdapter()
	  }
	});

	$(".tt-hint").css('width', '100%');
	$(".tt-hint").css('top', '5px');
	$(".tt-input").css('top', '5px');
}

$.fn.editToken = function() {
	var replaceWith = $('<input name="new_token_value" type="text" />');
    $(this).hover(function() {
    	$(this).tooltip('show');
    }, function() {
        $(this).tooltip('hide');
    });

    $(this).click(function() {
        var elem = $(this);
        replaceWith.val($(this).text());
        elem.hide();
        elem.after(replaceWith);
        replaceWith.focus();

        replaceWith.blur(function() {

            if ($(this).val() != "Click To Edit" ) {
            	elem.text("Saving...");
                alert($(this).val());
                alert($(this).val().indexOf("Click To Edit"));
            	var new_token = $(this).val();
            	var feed_id = elem.data('id');
            	var network = elem.data('network');
            	var token_type = elem.data('type');

                $.post("<?php echo get_absolute_url();?>tracking202/ajax/rapidbuilder.php", {update_token: true, feed_id: feed_id, network: network, token: new_token, type: token_type})
				  .done(function(data) {
				  	//console.log(data);
				  	if(new_token=""){
				  		elem.text('New');
				  	}
				  	else{
				  		elem.text(new_token);
					 }				  		
					  
				});
            }

            $(this).remove();
            elem.show();
        });
    });
};

/**
 * bootstrap-switch - Turn checkboxes and radio buttons into toggle switches.
 *
 * @version v3.3.4
 * @homepage https://bttstrp.github.io/bootstrap-switch
 * @author Mattia Larentis <mattia@larentis.eu> (http://larentis.eu)
 * @license Apache-2.0
 */

(function(a,b){if('function'==typeof define&&define.amd)define(['jquery'],b);else if('undefined'!=typeof exports)b(require('jquery'));else{b(a.jquery),a.bootstrapSwitch={exports:{}}.exports}})(this,function(a){'use strict';function c(j,k){if(!(j instanceof k))throw new TypeError('Cannot call a class as a function')}var d=function(j){return j&&j.__esModule?j:{default:j}}(a),e=Object.assign||function(j){for(var l,k=1;k<arguments.length;k++)for(var m in l=arguments[k],l)Object.prototype.hasOwnProperty.call(l,m)&&(j[m]=l[m]);return j},f=function(){function j(k,l){for(var n,m=0;m<l.length;m++)n=l[m],n.enumerable=n.enumerable||!1,n.configurable=!0,'value'in n&&(n.writable=!0),Object.defineProperty(k,n.key,n)}return function(k,l,m){return l&&j(k.prototype,l),m&&j(k,m),k}}(),g=d.default||window.jQuery||window.$,h=function(){function j(k){var l=this,m=1<arguments.length&&void 0!==arguments[1]?arguments[1]:{};c(this,j),this.$element=g(k),this.options=g.extend({},g.fn.bootstrapSwitch.defaults,this._getElementOptions(),m),this.prevOptions={},this.$wrapper=g('<div>',{class:function(){var o=[];return o.push(l.options.state?'on':'off'),l.options.size&&o.push(l.options.size),l.options.disabled&&o.push('disabled'),l.options.readonly&&o.push('readonly'),l.options.indeterminate&&o.push('indeterminate'),l.options.inverse&&o.push('inverse'),l.$element.attr('id')&&o.push('id-'+l.$element.attr('id')),o.map(l._getClass.bind(l)).concat([l.options.baseClass],l._getClasses(l.options.wrapperClass)).join(' ')}}),this.$container=g('<div>',{class:this._getClass('container')}),this.$on=g('<span>',{html:this.options.onText,class:this._getClass('handle-on')+' '+this._getClass(this.options.onColor)}),this.$off=g('<span>',{html:this.options.offText,class:this._getClass('handle-off')+' '+this._getClass(this.options.offColor)}),this.$label=g('<span>',{html:this.options.labelText,class:this._getClass('label')}),this.$element.on('init.bootstrapSwitch',this.options.onInit.bind(this,k)),this.$element.on('switchChange.bootstrapSwitch',function(){for(var n=arguments.length,o=Array(n),p=0;p<n;p++)o[p]=arguments[p];!1===l.options.onSwitchChange.apply(k,o)&&(l.$element.is(':radio')?g('[name="'+l.$element.attr('name')+'"]').trigger('previousState.bootstrapSwitch',!0):l.$element.trigger('previousState.bootstrapSwitch',!0))}),this.$container=this.$element.wrap(this.$container).parent(),this.$wrapper=this.$container.wrap(this.$wrapper).parent(),this.$element.before(this.options.inverse?this.$off:this.$on).before(this.$label).before(this.options.inverse?this.$on:this.$off),this.options.indeterminate&&this.$element.prop('indeterminate',!0),this._init(),this._elementHandlers(),this._handleHandlers(),this._labelHandlers(),this._formHandler(),this._externalLabelHandler(),this.$element.trigger('init.bootstrapSwitch',this.options.state)}return f(j,[{key:'setPrevOptions',value:function(){this.prevOptions=e({},this.options)}},{key:'state',value:function(l,m){return'undefined'==typeof l?this.options.state:this.options.disabled||this.options.readonly||this.options.state&&!this.options.radioAllOff&&this.$element.is(':radio')?this.$element:(this.$element.is(':radio')?g('[name="'+this.$element.attr('name')+'"]').trigger('setPreviousOptions.bootstrapSwitch'):this.$element.trigger('setPreviousOptions.bootstrapSwitch'),this.options.indeterminate&&this.indeterminate(!1),this.$element.prop('checked',!!l).trigger('change.bootstrapSwitch',m),this.$element)}},{key:'toggleState',value:function(l){return this.options.disabled||this.options.readonly?this.$element:this.options.indeterminate?(this.indeterminate(!1),this.state(!0)):this.$element.prop('checked',!this.options.state).trigger('change.bootstrapSwitch',l)}},{key:'size',value:function(l){return'undefined'==typeof l?this.options.size:(null!=this.options.size&&this.$wrapper.removeClass(this._getClass(this.options.size)),l&&this.$wrapper.addClass(this._getClass(l)),this._width(),this._containerPosition(),this.options.size=l,this.$element)}},{key:'animate',value:function(l){return'undefined'==typeof l?this.options.animate:this.options.animate===!!l?this.$element:this.toggleAnimate()}},{key:'toggleAnimate',value:function(){return this.options.animate=!this.options.animate,this.$wrapper.toggleClass(this._getClass('animate')),this.$element}},{key:'disabled',value:function(l){return'undefined'==typeof l?this.options.disabled:this.options.disabled===!!l?this.$element:this.toggleDisabled()}},{key:'toggleDisabled',value:function(){return this.options.disabled=!this.options.disabled,this.$element.prop('disabled',this.options.disabled),this.$wrapper.toggleClass(this._getClass('disabled')),this.$element}},{key:'readonly',value:function(l){return'undefined'==typeof l?this.options.readonly:this.options.readonly===!!l?this.$element:this.toggleReadonly()}},{key:'toggleReadonly',value:function(){return this.options.readonly=!this.options.readonly,this.$element.prop('readonly',this.options.readonly),this.$wrapper.toggleClass(this._getClass('readonly')),this.$element}},{key:'indeterminate',value:function(l){return'undefined'==typeof l?this.options.indeterminate:this.options.indeterminate===!!l?this.$element:this.toggleIndeterminate()}},{key:'toggleIndeterminate',value:function(){return this.options.indeterminate=!this.options.indeterminate,this.$element.prop('indeterminate',this.options.indeterminate),this.$wrapper.toggleClass(this._getClass('indeterminate')),this._containerPosition(),this.$element}},{key:'inverse',value:function(l){return'undefined'==typeof l?this.options.inverse:this.options.inverse===!!l?this.$element:this.toggleInverse()}},{key:'toggleInverse',value:function(){this.$wrapper.toggleClass(this._getClass('inverse'));var l=this.$on.clone(!0),m=this.$off.clone(!0);return this.$on.replaceWith(m),this.$off.replaceWith(l),this.$on=m,this.$off=l,this.options.inverse=!this.options.inverse,this.$element}},{key:'onColor',value:function(l){return'undefined'==typeof l?this.options.onColor:(this.options.onColor&&this.$on.removeClass(this._getClass(this.options.onColor)),this.$on.addClass(this._getClass(l)),this.options.onColor=l,this.$element)}},{key:'offColor',value:function(l){return'undefined'==typeof l?this.options.offColor:(this.options.offColor&&this.$off.removeClass(this._getClass(this.options.offColor)),this.$off.addClass(this._getClass(l)),this.options.offColor=l,this.$element)}},{key:'onText',value:function(l){return'undefined'==typeof l?this.options.onText:(this.$on.html(l),this._width(),this._containerPosition(),this.options.onText=l,this.$element)}},{key:'offText',value:function(l){return'undefined'==typeof l?this.options.offText:(this.$off.html(l),this._width(),this._containerPosition(),this.options.offText=l,this.$element)}},{key:'labelText',value:function(l){return'undefined'==typeof l?this.options.labelText:(this.$label.html(l),this._width(),this.options.labelText=l,this.$element)}},{key:'handleWidth',value:function(l){return'undefined'==typeof l?this.options.handleWidth:(this.options.handleWidth=l,this._width(),this._containerPosition(),this.$element)}},{key:'labelWidth',value:function(l){return'undefined'==typeof l?this.options.labelWidth:(this.options.labelWidth=l,this._width(),this._containerPosition(),this.$element)}},{key:'baseClass',value:function(){return this.options.baseClass}},{key:'wrapperClass',value:function(l){return'undefined'==typeof l?this.options.wrapperClass:(l||(l=g.fn.bootstrapSwitch.defaults.wrapperClass),this.$wrapper.removeClass(this._getClasses(this.options.wrapperClass).join(' ')),this.$wrapper.addClass(this._getClasses(l).join(' ')),this.options.wrapperClass=l,this.$element)}},{key:'radioAllOff',value:function(l){if('undefined'==typeof l)return this.options.radioAllOff;var m=!!l;return this.options.radioAllOff===m?this.$element:(this.options.radioAllOff=m,this.$element)}},{key:'onInit',value:function(l){return'undefined'==typeof l?this.options.onInit:(l||(l=g.fn.bootstrapSwitch.defaults.onInit),this.options.onInit=l,this.$element)}},{key:'onSwitchChange',value:function(l){return'undefined'==typeof l?this.options.onSwitchChange:(l||(l=g.fn.bootstrapSwitch.defaults.onSwitchChange),this.options.onSwitchChange=l,this.$element)}},{key:'destroy',value:function(){var l=this.$element.closest('form');return l.length&&l.off('reset.bootstrapSwitch').removeData('bootstrap-switch'),this.$container.children().not(this.$element).remove(),this.$element.unwrap().unwrap().off('.bootstrapSwitch').removeData('bootstrap-switch'),this.$element}},{key:'_getElementOptions',value:function(){return{state:this.$element.is(':checked'),size:this.$element.data('size'),animate:this.$element.data('animate'),disabled:this.$element.is(':disabled'),readonly:this.$element.is('[readonly]'),indeterminate:this.$element.data('indeterminate'),inverse:this.$element.data('inverse'),radioAllOff:this.$element.data('radio-all-off'),onColor:this.$element.data('on-color'),offColor:this.$element.data('off-color'),onText:this.$element.data('on-text'),offText:this.$element.data('off-text'),labelText:this.$element.data('label-text'),handleWidth:this.$element.data('handle-width'),labelWidth:this.$element.data('label-width'),baseClass:this.$element.data('base-class'),wrapperClass:this.$element.data('wrapper-class')}}},{key:'_width',value:function(){var l=this,m=this.$on.add(this.$off).add(this.$label).css('width',''),n='auto'===this.options.handleWidth?Math.round(Math.max(this.$on.width(),this.$off.width())):this.options.handleWidth;return m.width(n),this.$label.width(function(o,p){return'auto'===l.options.labelWidth?p<n?n:p:l.options.labelWidth}),this._handleWidth=this.$on.outerWidth(),this._labelWidth=this.$label.outerWidth(),this.$container.width(2*this._handleWidth+this._labelWidth),this.$wrapper.width(this._handleWidth+this._labelWidth)}},{key:'_containerPosition',value:function(){var l=this,m=0<arguments.length&&void 0!==arguments[0]?arguments[0]:this.options.state,n=arguments[1];this.$container.css('margin-left',function(){var o=[0,'-'+l._handleWidth+'px'];return l.options.indeterminate?'-'+l._handleWidth/2+'px':m?l.options.inverse?o[1]:o[0]:l.options.inverse?o[0]:o[1]})}},{key:'_init',value:function(){var l=this,m=function(){l.setPrevOptions(),l._width(),l._containerPosition(),setTimeout(function(){if(l.options.animate)return l.$wrapper.addClass(l._getClass('animate'))},50)};if(this.$wrapper.is(':visible'))return void m();var n=window.setInterval(function(){if(l.$wrapper.is(':visible'))return m(),window.clearInterval(n)},50)}},{key:'_elementHandlers',value:function(){var l=this;return this.$element.on({'setPreviousOptions.bootstrapSwitch':this.setPrevOptions.bind(this),'previousState.bootstrapSwitch':function(){l.options=l.prevOptions,l.options.indeterminate&&l.$wrapper.addClass(l._getClass('indeterminate')),l.$element.prop('checked',l.options.state).trigger('change.bootstrapSwitch',!0)},'change.bootstrapSwitch':function(n,o){n.preventDefault(),n.stopImmediatePropagation();var p=l.$element.is(':checked');l._containerPosition(p),p===l.options.state||(l.options.state=p,l.$wrapper.toggleClass(l._getClass('off')).toggleClass(l._getClass('on')),!o&&(l.$element.is(':radio')&&g('[name="'+l.$element.attr('name')+'"]').not(l.$element).prop('checked',!1).trigger('change.bootstrapSwitch',!0),l.$element.trigger('switchChange.bootstrapSwitch',[p])))},'focus.bootstrapSwitch':function(n){n.preventDefault(),l.$wrapper.addClass(l._getClass('focused'))},'blur.bootstrapSwitch':function(n){n.preventDefault(),l.$wrapper.removeClass(l._getClass('focused'))},'keydown.bootstrapSwitch':function(n){!n.which||l.options.disabled||l.options.readonly||(37===n.which||39===n.which)&&(n.preventDefault(),n.stopImmediatePropagation(),l.state(39===n.which))}})}},{key:'_handleHandlers',value:function(){var l=this;return this.$on.on('click.bootstrapSwitch',function(m){return m.preventDefault(),m.stopPropagation(),l.state(!1),l.$element.trigger('focus.bootstrapSwitch')}),this.$off.on('click.bootstrapSwitch',function(m){return m.preventDefault(),m.stopPropagation(),l.state(!0),l.$element.trigger('focus.bootstrapSwitch')})}},{key:'_labelHandlers',value:function(){var l=this;this.$label.on({click:function(o){o.stopPropagation()},'mousedown.bootstrapSwitch touchstart.bootstrapSwitch':function(o){l._dragStart||l.options.disabled||l.options.readonly||(o.preventDefault(),o.stopPropagation(),l._dragStart=(o.pageX||o.originalEvent.touches[0].pageX)-parseInt(l.$container.css('margin-left'),10),l.options.animate&&l.$wrapper.removeClass(l._getClass('animate')),l.$element.trigger('focus.bootstrapSwitch'))},'mousemove.bootstrapSwitch touchmove.bootstrapSwitch':function(o){if(null!=l._dragStart){var p=(o.pageX||o.originalEvent.touches[0].pageX)-l._dragStart;o.preventDefault(),p<-l._handleWidth||0<p||(l._dragEnd=p,l.$container.css('margin-left',l._dragEnd+'px'))}},'mouseup.bootstrapSwitch touchend.bootstrapSwitch':function(o){if(l._dragStart){if(o.preventDefault(),l.options.animate&&l.$wrapper.addClass(l._getClass('animate')),l._dragEnd){var p=l._dragEnd>-(l._handleWidth/2);l._dragEnd=!1,l.state(l.options.inverse?!p:p)}else l.state(!l.options.state);l._dragStart=!1}},'mouseleave.bootstrapSwitch':function(){l.$label.trigger('mouseup.bootstrapSwitch')}})}},{key:'_externalLabelHandler',value:function(){var l=this,m=this.$element.closest('label');m.on('click',function(n){n.preventDefault(),n.stopImmediatePropagation(),n.target===m[0]&&l.toggleState()})}},{key:'_formHandler',value:function(){var l=this.$element.closest('form');l.data('bootstrap-switch')||l.on('reset.bootstrapSwitch',function(){window.setTimeout(function(){l.find('input').filter(function(){return g(this).data('bootstrap-switch')}).each(function(){return g(this).bootstrapSwitch('state',this.checked)})},1)}).data('bootstrap-switch',!0)}},{key:'_getClass',value:function(l){return this.options.baseClass+'-'+l}},{key:'_getClasses',value:function(l){return g.isArray(l)?l.map(this._getClass.bind(this)):[this._getClass(l)]}}]),j}();g.fn.bootstrapSwitch=function(j){for(var l=arguments.length,m=Array(1<l?l-1:0),n=1;n<l;n++)m[n-1]=arguments[n];return Array.prototype.reduce.call(this,function(o,p){var q=g(p),r=q.data('bootstrap-switch'),s=r||new h(p,j);return r||q.data('bootstrap-switch',s),'string'==typeof j?s[j].apply(s,m):o},this)},g.fn.bootstrapSwitch.Constructor=h,g.fn.bootstrapSwitch.defaults={state:!0,size:null,animate:!0,disabled:!1,readonly:!1,indeterminate:!1,inverse:!1,radioAllOff:!1,onColor:'primary',offColor:'default',onText:'ON',offText:'OFF',labelText:'&nbsp',handleWidth:'auto',labelWidth:'auto',baseClass:'bootstrap-switch',wrapperClass:'wrapper',onInit:function(){},onSwitchChange:function(){}}});

$.fn.bootstrapSwitch.defaults.onColor = 'success';