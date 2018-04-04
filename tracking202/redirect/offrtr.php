<?php
use UAParser\Parser;

error_reporting(E_ALL);
ini_set("display_errors", true);
ob_start();

$urlvarslist = $_GET;
$rpi = $_GET['rpi'];

if(!isset($_COOKIE['tracking202subid']) || !is_numeric($_COOKIE['tracking202subid']) || !isset($rpi) || !is_numeric($rpi)) { 
    die();
} 

include_once (substr(dirname( __FILE__ ), 0,-21) . '/202-config/connect2.php');
include_once(str_repeat("../", 2).'202-config/class-dataengine-slim.php');

$mysql['click_id'] = $db->real_escape_string($_COOKIE['tracking202subid']);
$mysql['rpi'] = $db->real_escape_string($_GET['rpi']);


/*$usedCachedRedirect = false;
if (! $db)
    $usedCachedRedirect = true;
    
    // the mysql server is down, use the txt cached redirect
if ($usedCachedRedirect == true) {
    
    $acip = $_GET['acip'];
    
    // if a cached key is found for this acip, redirect to that url
    if ($memcacheWorking) {
        $getUrl = $memcache->get(md5('ac_' . $acip . systemHash()));
        if ($getUrl) {
            
            $new_url = str_replace("[[subid]]", "p202", $getUrl);
            
            // c1 sring replace for cached redirect
            if (isset($_GET['c1']) && $_GET['c1'] != '') {
                $new_url = str_replace("[[c1]]", $_GET['c1'], $new_url);
            } else {
                $new_url = str_replace("[[c1]]", "p202c1", $new_url);
            }
            
            // c2 sring replace for cached redirect
            if (isset($_GET['c2']) && $_GET['c2'] != '') {
                $new_url = str_replace("[[c2]]", $_GET['c2'], $new_url);
            } else {
                $new_url = str_replace("[[c2]]", "p202c2", $new_url);
            }
            
            // c3 sring replace for cached redirect
            if (isset($_GET['c3']) && $_GET['c3'] != '') {
                $new_url = str_replace("[[c3]]", $_GET['c3'], $new_url);
            } else {
                $new_url = str_replace("[[c3]]", "p202c3", $new_url);
            }
            
            // c4 sring replace for cached redirect
            if (isset($_GET['c4']) && $_GET['c4'] != '') {
                $new_url = str_replace("[[c4]]", $_GET['c4'], $new_url);
            } else {
                $new_url = str_replace("[[c4]]", "p202c4", $new_url);
            }
            
            $urlvars = getPrePopVars($urlvarslist);
            
            $new_url = setPrePopVars($urlvars, $redirect_site_url, false);
       
            header('location: ' . $new_url);
            die();
        }
    }
    
    die("<h2>Error establishing a database connection - please contact the webhost</h2>");
}*/

$rotator_sql = "SELECT 
					    rt.user_id,
					    rt.id,
						rt.default_url,
						rt.default_campaign,
						rt.default_lp,
						rt.auto_monetizer,
						ca.aff_campaign_id,
						ca.aff_campaign_rotate,
					    ca.aff_campaign_url,
					    ca.aff_campaign_url_2,
					    ca.aff_campaign_url_3,
					    ca.aff_campaign_url_4,
					    ca.aff_campaign_url_5,
					    ca.aff_campaign_payout,
					    ca.aff_campaign_cloaking,
					   	lp.landing_page_url,
					   	lp.landing_page_id,
					   	lp.landing_page_id_public,
					   	up.user_keyword_searched_or_bidded,
					   	up.user_pref_referer_data,
					   	up.maxmind_isp 	
				FROM 202_rotators AS rt
				LEFT JOIN 202_aff_campaigns AS ca ON ca.aff_campaign_id = rt.default_campaign
				LEFT JOIN 202_landing_pages AS lp ON lp.landing_page_id = rt.default_lp
				LEFT JOIN 202_users_pref AS up ON up.user_id = rt.user_id
				WHERE   rt.public_id='".$mysql['rpi']."'"; 
$rotator_row = memcache_mysql_fetch_assoc($db, $rotator_sql);
if (!$rotator_row) die();

$mysql['rotator_id'] = $db->real_escape_string($rotator_row['id']);
$rule_sql = "SELECT ru.id as rule_id
			 FROM 202_rotator_rules AS ru
			 WHERE rotator_id='".$mysql['rotator_id']."' AND status='1'"; 
$rule_row = foreach_memcache_mysql_fetch_assoc($db, $rule_sql);

$referer_url_parsed = @parse_url($_SERVER['HTTP_REFERER']);
$referer_url_query = $referer_url_parsed['query'];

@parse_str($referer_url_query, $referer_query);

switch ($user_keyword_searched_or_bidded) { 

	case "bidded":
	      #try to get the bidded keyword first
		if ($_GET['OVKEY']) { //if this is a Y! keyword
			$keyword = $db->real_escape_string($_GET['OVKEY']);   
		}  elseif ($_GET['t202kw']) { 
			$keyword = $db->real_escape_string($_GET['t202kw']);  
		} elseif ($_GET['target_passthrough']) { //if this is a mediatraffic! keyword
			$keyword = $db->real_escape_string($_GET['target_passthrough']);   
		} else { //if this is a zango, or more keyword
			$keyword = $db->real_escape_string($_GET['keyword']);   
		} 
		break;
		case "searched":
		#try to get the searched keyword
		if ($referer_query['q']) { 
			$keyword = $db->real_escape_string($referer_query['q']);
		} elseif ($_GET['OVRAW']) { //if this is a Y! keyword
			$keyword = $db->real_escape_string($_GET['OVRAW']);   
		} elseif ($_GET['target_passthrough']) { //if this is a mediatraffic! keyword
			$keyword = $db->real_escape_string($_GET['target_passthrough']);   
		} elseif ($_GET['keyword']) { //if this is a zango, or more keyword
			$keyword = $db->real_escape_string($_GET['keyword']);   
		} elseif ($_GET['search_word']) { //if this is a eniro, or more keyword
			$keyword = $db->real_escape_string($_GET['search_word']);   
		} elseif ($_GET['query']) { //if this is a naver, or more keyword
			$keyword = $db->real_escape_string($_GET['query']);   
		} elseif ($_GET['encquery']) { //if this is a aol, or more keyword
			$keyword = $db->real_escape_string($_GET['encquery']);   
		} elseif ($_GET['terms']) { //if this is a about.com, or more keyword
			$keyword = $db->real_escape_string($_GET['terms']);   
		} elseif ($_GET['rdata']) { //if this is a viola, or more keyword
			$keyword = $db->real_escape_string($_GET['rdata']);   
		} elseif ($_GET['qs']) { //if this is a virgilio, or more keyword
			$keyword = $db->real_escape_string($_GET['qs']);   
		} elseif ($_GET['wd']) { //if this is a baidu, or more keyword
			$keyword = $db->real_escape_string($_GET['wd']);   
		} elseif ($_GET['text']) { //if this is a yandex, or more keyword
			$keyword = $db->real_escape_string($_GET['text']);   
		} elseif ($_GET['szukaj']) { //if this is a wp.pl, or more keyword
			$keyword = $db->real_escape_string($_GET['szukaj']);   
		} elseif ($_GET['qt']) { //if this is a O*net, or more keyword
			$keyword = $db->real_escape_string($_GET['qt']);   
		} elseif ($_GET['k']) { //if this is a yam, or more keyword
			$keyword = $db->real_escape_string($_GET['k']);   
		} elseif ($_GET['words']) { //if this is a Rambler, or more keyword
			$keyword = $db->real_escape_string($_GET['words']);   
		} else { 
			$keyword = $db->real_escape_string($_GET['t202kw']);
		}
		break;
}

if (substr($keyword, 0, 8) == 't202var_') {
	$t202var = substr($keyword, strpos($keyword, "_") + 1);

	if (isset($_GET[$t202var])) {
		$keyword = $_GET[$t202var];
	}
}

$keyword = str_replace('%20',' ',$keyword);

if ($rotator_row['user_pref_referer_data'] == 't202ref') {
    if (isset($_GET['t202ref']) && $_GET['t202ref'] != '') { //check for t202ref value
    	$ref_value = $_GET['t202ref'];
    } else { //if not found revert to what we usually do
        if ($referer_query['url']) {
        	$ref_value = $referer_query['url'];
        } else {
        	$ref_value = $_SERVER['HTTP_REFERER'];
        }
    }
} else { //user wants the real referer first

    // now lets get variables for clicks site
    // so this is going to check the REFERER URL, for a ?url=, which is the ACUTAL URL, instead of the google content, pagead2.google....
    if ($referer_query['url']) {
    	$ref_value = $referer_query['url'];
    } else {
    	$ref_value = $_SERVER['HTTP_REFERER'];
    }
}

$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
$ip_id = INDEXES::get_ip_id($db, $ip_address);
$click_filtered = FILTER::startFilter($db, '', $ip_id, $ip_address, $rotator_row['user_id']);

if ($rotator_row['maxmind_isp'] == '1') {
	$IspData = getIspData($ip_address);
	$IspData = explode(',', $IspData);
	$IspData = $IspData[0];
} else {
	$IspData = null;
}

//GEO Lookup
$GeoData = getGeoData($ip_address);

//User-agent parser
$parser = Parser::create();

//Device type
$detect = new Mobile_Detect;
$ua = $detect->getUserAgent();

$default = true;

foreach ($rule_row as $rule) {
	
	$rotate = array();
	$count = 0;

	$mysql['rule_id'] = $db->real_escape_string($rule['rule_id']);
	$criteria_sql = "SELECT type, statement, value
				 FROM 202_rotator_rules_criteria
				 WHERE rule_id='".$mysql['rule_id']."'"; 
	$criteria_row = foreach_memcache_mysql_fetch_assoc($db, $criteria_sql);

	foreach ($criteria_row as $criteria) {
		switch ($criteria['statement']) {
			case 'is':
				$statement = true;		
			break;
					
			case 'is_not':
				$statement = false;	
			break;
		}

		$values = explode(',', $criteria['value']);

		if (in_array('ALL', $values) || in_array('all', $values)) {
			
			$rotate[] = true;

		} else {

			switch ($criteria['type']) {
				case 'country':
					$country = $GeoData['country']."(".$GeoData['country_code'].")";

					if ($statement) {
						if (in_array($country, $values)) {
							$rotate[] = true;
						}
					} else {
						if (!in_array($country, $values)) {
							$rotate[] = true;
						}
					}
						
				break;
				
				case 'region':
					$region = $GeoData['region']."(".$GeoData['country_code'].")";
					
					if ($statement) {
						if (in_array($region, $values)) {
							$rotate[] = true;
						}
					} else {
						if (!in_array($region, $values)) {
							$rotate[] = true;
						}
					}

				break;

				case 'city':
					$city = $GeoData['city']."(".$GeoData['country_code'].")";
					
					if ($statement) {
						if (in_array($city, $values)) {
							$rotate[] = true;
						}
					} else {
						if (!in_array($city, $values)) {
							$rotate[] = true;
						}
					}
				break;

				case 'isp':
					if ($statement) {
						if (in_array($IspData, $values)) {
							$rotate[] = true;
						}
					} else {
						if (!in_array($IspData, $values)) {
							$rotate[] = true;
						}
					}
				break;

				case 'ip':
					if ($statement) {
						if (in_array($ip_address, $values)) {
							$rotate[] = true;
						}
					} else {
						if (!in_array($ip_address, $values)) {
							$rotate[] = true;
						}
					}

				break;

				case 'platform':
					$result = parseUaForRotatorCriteria($detect, $parser, $ua);
					if ($statement) {
						if (in_array($result->os->family, $values)) {
							$rotate[] = true;
						}
					} else {
						if (!in_array($result->os->family, $values)) {
							$rotate[] = true;
						}
					}
				break;

				case 'device':
					$result = parseUaForRotatorCriteria($detect, $parser, $ua);
					if ($statement) {
						if (in_array(strtolower($result->device->family), $values)) {
							$rotate[] = true;
						}
					} else {
						if (!in_array(strtolower($result->device->family), $values)) {
							$rotate[] = true;
						}
					}
				break;

				case 'browser':
					$result = parseUaForRotatorCriteria($detect, $parser, $ua);
					if ($statement) {
						if (in_array($result->ua->family, $values)) {
							$rotate[] = true;
						}
					} else {
						if (!in_array($result->ua->family, $values)) {
							$rotate[] = true;
						}
					}
				break;

				case 'visitor':
					foreach ($values as $key => $value) {
						if (strtoupper($value) == 'FILTERED') {
							if ($click_filtered && $statement) {
								$rotate[] = true;
							} else if (!$statement && !$click_filtered) {
								$rotate[] = true;
							}
						} else if (strtoupper($value) == 'UNIQUE') {
							if (!$click_filtered && $statement) {
								$rotate[] = true;
							} else if (!$statement && $click_filtered) {
								$rotate[] = true;
							}
						}
					}
				break;

				case 'c1':
					$c1 = str_replace('%20',' ',$_GET['c1']);
					if (isset($c1) && !empty($c1)) {
						if ($statement) {
							if(in_array($_GET['c1'], $values)) {
								$rotate[] = true;
							}
						} else {
							if(!in_array($_GET['c1'], $values)) {
								$rotate[] = true;
							}
						}
					}
				break;

				case 'c2':
					$c2 = str_replace('%20',' ',$_GET['c2']);
					if (isset($c2) && !empty($c2)) {
						if ($statement) {
							if(in_array($_GET['c2'], $values)) {
								$rotate[] = true;
							}
						} else {
							if(!in_array($_GET['c2'], $values)) {
								$rotate[] = true;
							}
						}
					}
				break;

				case 'c3':
					$c3 = str_replace('%20',' ',$_GET['c3']);
					if (isset($c3) && !empty($c3)) {
						if ($statement) {
							if(in_array($_GET['c3'], $values)) {
								$rotate[] = true;
							}
						} else {
							if(!in_array($_GET['c3'], $values)) {
								$rotate[] = true;
							}
						}
					}
				break;

				case 'c4':
					$c4 = str_replace('%20',' ',$_GET['c4']);
					if (isset($c4) && !empty($c4)) {
						if ($statement) {
							if(in_array($_GET['c4'], $values)) {
								$rotate[] = true;
							}
						} else {
							if(!in_array($_GET['c4'], $values)) {
								$rotate[] = true;
							}
						}
					}
				break;

				case 't202kw':
					if ($statement) {
						if(in_array($keyword, $values)) {
							$rotate[] = true;
						}
					} else {
						if(!in_array($keyword, $values)) {
							$rotate[] = true;
						}
					}
				break;

				case 'utm_source':
					$utm_source = str_replace('%20',' ',$_GET['utm_source']);
					if ($statement) {
						if(in_array($utm_source, $values)) {
							$rotate[] = true;
						}
					} else {
						if(!in_array($utm_source, $values)) {
							$rotate[] = true;
						}
					}
				break;

				case 'utm_medium':
					$utm_medium = str_replace('%20',' ',$_GET['utm_medium']);
					if ($statement) {
						if(in_array($utm_medium, $values)) {
							$rotate[] = true;
						}
					} else {
						if(!in_array($utm_medium, $values)) {
							$rotate[] = true;
						}
					}
				break;

				case 'utm_campaign':
					$utm_campaign = str_replace('%20',' ',$_GET['utm_campaign']);
					if ($statement) {
						if(in_array($utm_campaign, $values)) {
							$rotate[] = true;
						}
					} else {
						if(!in_array($utm_campaign, $values)) {
							$rotate[] = true;
						}
					}
				break;

				case 'utm_term':
					$utm_term = str_replace('%20',' ',$_GET['utm_term']);
					$utm_vars['campaign'] = $utm_campaign_id;
					if ($statement) {
						if(in_array($utm_term, $values)) {
							$rotate[] = true;
						}
					} else {
						if(!in_array($utm_term, $values)) {
							$rotate[] = true;
						}
					}
				break;

				case 'utm_content':
					$utm_content = str_replace('%20',' ',$_GET['utm_content']);
					if ($statement) {
						if(in_array($utm_content, $values)) {
							$rotate[] = true;
						}
					} else {
						if(!in_array($utm_content, $values)) {
							$rotate[] = true;
						}
					}
				break;

				case 'referer':
					if ($statement) {
						if(in_array($ref_value, $values)) {
							$rotate[] = true;
						}
					} else {
						if(!in_array($ref_value, $values)) {
							$rotate[] = true;
						}
					}
				break;
			}
		}

		$count++;

	}

	if ($count == count($rotate)) {
		$default = false;
		$mysql['rule_id'] = $mysql['rule_id'];
		break;
	}
}	

$mysql['click_out'] = 1;

if ($default == false) {

		$rule_redirects_sql = "SELECT
					   2c.click_id, 
					   2c.user_id,
					   2c.click_filtered,
					   2c.landing_page_id,
					   2cr.click_cloaking,
					   2cs.click_cloaking_site_url_id,
					   2cs.click_redirect_site_url_id,
					   rur.id as rule_redirect_id,
					   rur.redirect_url,
					   rur.redirect_campaign,
					   rur.redirect_lp,
					   rur.auto_monetizer,
					   rur.weight,
					   ca.aff_campaign_name,
					   ca.aff_campaign_id,
					   ca.aff_campaign_rotate,
					   ca.aff_campaign_url,
					   ca.aff_campaign_url_2,
					   ca.aff_campaign_url_3,
					   ca.aff_campaign_url_4,
					   ca.aff_campaign_url_5,
					   ca.aff_campaign_payout,
					   ca.aff_campaign_cloaking,
					   lp.landing_page_url,
		               lp.landing_page_id,
		               lp.landing_page_id_public
				FROM 202_clicks AS 2c
				LEFT JOIN 202_clicks_record AS 2cr ON 2cr.click_id = 2c.click_id
				LEFT JOIN 202_clicks_site AS 2cs ON 2cs.click_id = 2c.click_id	   
				LEFT JOIN 202_rotator_rules_redirects AS rur ON rur.rule_id = '".$mysql['rule_id']."'
				LEFT JOIN 202_aff_campaigns AS ca ON ca.aff_campaign_id = rur.redirect_campaign
				LEFT JOIN 202_landing_pages AS lp ON lp.landing_page_id = rur.redirect_lp
				WHERE 2c.click_id='".$mysql['click_id']."'"; 
		$rule_redirects_row = foreach_memcache_mysql_fetch_assoc($db, $rule_redirects_sql);
		$redirects = array();
		$redirect_values = array();

		foreach ($rule_redirects_row as $rule_redirect_row) {
			
			if ($rule_redirect_row['redirect_campaign'] != null) {
				$redirects[] = array('rule_id' => $mysql['rule_id'], 'redirect_id' => $rule_redirect_row['rule_redirect_id'], 'type' => 'campaign', 'aff_campaign_url' => $rule_redirect_row['aff_campaign_url'], 'aff_campaign_url_2' => $rule_redirect_row['aff_campaign_url_2'], 'aff_campaign_url_3' => $rule_redirect_row['aff_campaign_url_3'], 'aff_campaign_url_4' => $rule_redirect_row['aff_campaign_url_4'], 'aff_campaign_url_5' => $rule_redirect_row['aff_campaign_url_5'], 'weight' => $rule_redirect_row['weight'], 'aff_campaign_id' => $rule_redirect_row['aff_campaign_id'], 'aff_campaign_payout' => $rule_redirect_row['aff_campaign_payout'], 'aff_campaign_cloaking' => $rule_redirect_row['aff_campaign_cloaking']);
			} else if($rule_redirect_row['redirect_url'] != null) {
				$redirects[] = array('rule_id' => $mysql['rule_id'], 'redirect_id' => $rule_redirect_row['rule_redirect_id'], 'type' => 'url', 'redirect_url' => $rule_redirect_row['redirect_url'], 'weight' => $rule_redirect_row['weight'], 'aff_campaign_id' => $rule_redirect_row['aff_campaign_id'], 'aff_campaign_payout' => $rule_redirect_row['aff_campaign_payout'], 'aff_campaign_cloaking' => $rule_redirect_row['aff_campaign_cloaking']);
			} else if ($rule_redirect_row['redirect_lp'] != null) {
				$redirects[] = array('rule_id' => $mysql['rule_id'], 'redirect_id' => $rule_redirect_row['rule_redirect_id'], 'type' => 'lp', 'landing_page_id' => $rule_redirect_row['landing_page_id'], 'landing_page_id_public' => $rule_redirect_row['landing_page_id_public'], 'landing_page_url' => $rule_redirect_row['landing_page_url'], 'weight' => $rule_redirect_row['weight'], 'aff_campaign_id' => $rule_redirect_row['aff_campaign_id'], 'aff_campaign_payout' => $rule_redirect_row['aff_campaign_payout'], 'aff_campaign_cloaking' => $rule_redirect_row['aff_campaign_cloaking']);
			} else if ($rule_redirect_row['auto_monetizer'] != null) {
				$redirects[] = array('rule_id' => $mysql['rule_id'], 'redirect_id' => $rule_redirect_row['rule_redirect_id'], 'type' => 'monetizer', 'monetizer_url' => 'http://prosper202.com', 'weight' => $rule_redirect_row['weight'], 'aff_campaign_id' => $rule_redirect_row['aff_campaign_id'], 'aff_campaign_payout' => $rule_redirect_row['aff_campaign_payout'], 'aff_campaign_cloaking' => $rule_redirect_row['aff_campaign_cloaking']);
			}
		}

		if (count($rule_redirects_row) > 1) {
			$redirect_array = $redirects[getSplitTestValue($redirects)];
		} else {
			$redirect_array = $redirects[0];
		}

		updateLpClickDataForRotator($redirect_array['redirect_id'], $mysql['click_id'], $mysql['rotator_id'], $mysql['rule_id']);

			if ($redirect_array['type'] == 'campaign') {
				$mysql['aff_campaign_id'] = $db->real_escape_string($redirect_array['aff_campaign_id']);
				$mysql['click_payout'] = $db->real_escape_string($redirect_array['aff_campaign_payout']);

				$update_sql = "
					UPDATE
						202_clicks AS 2c
					SET
						2c.aff_campaign_id='" . $mysql['aff_campaign_id'] . "',
						2c.click_payout='" . $mysql['click_payout'] . "'
					WHERE
						2c.click_id='" . $mysql['click_id'] . "'
				";
				$click_result = $db->query($update_sql) or record_mysql_error($db, $update_sql);

				if (($rule_redirects_row['click_cloaking'] == 1) or // if tracker has overrided cloaking on
				(($rule_redirects_row['click_cloaking'] == - 1) and ($redirect_array['aff_campaign_cloaking'] == 1)) or ((! isset($rule_redirects_row['click_cloaking'])) and ($redirect_array['aff_campaign_cloaking'] == 1))) // if no tracker but but by default campaign has cloaking on
				{
				    $cloaking_on = true;
				    $mysql['click_cloaking'] = 1;
				    // if cloaking is on, add in a click_id_public, because we will be forwarding them to a cloaked /cl/xxxx link
				} else {
				    $mysql['click_cloaking'] = 0;
				}

				$update_sql = "
					UPDATE
						202_clicks_record
					SET
						click_out='" . $mysql['click_out'] . "',
						click_cloaking='" . $mysql['click_cloaking'] . "'
					WHERE
						click_id='" . $mysql['click_id'] . "'
				";
				$click_result = $db->query($update_sql) or record_mysql_error($db, $update_sql);

				$outbound_site_url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
				$click_outbound_site_url_id = INDEXES::get_site_url_id($db, $outbound_site_url);
				$mysql['click_outbound_site_url_id'] = $db->real_escape_string($click_outbound_site_url_id);

				if ($cloaking_on == true) {
				    $cloaking_site_url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
				}

				$redirect_site_url = rotateTrackerUrl($db, $rule_redirect_row);
				$redirect_site_url = replaceTrackerPlaceholders($db, $redirect_site_url, $mysql['click_id']);

				$click_redirect_site_url_id = INDEXES::get_site_url_id($db, $redirect_site_url);
				$mysql['click_redirect_site_url_id'] = $db->real_escape_string($click_redirect_site_url_id);

				$update_sql = "
					UPDATE
						202_clicks_site
					SET
						click_outbound_site_url_id='" . $mysql['click_outbound_site_url_id'] . "',
						click_redirect_site_url_id='" . $mysql['click_redirect_site_url_id'] . "'
					WHERE
						click_id='" . $mysql['click_id'] . "'
				";
				$click_result = $db->query($update_sql) or record_mysql_error($db, $update_sql);

				$de = new DataEngine();
				$data = $de->setDirtyHour($mysql['click_id']);

				if ($cloaking_on == true) { ?>
				<html>
				<head>
				<title><?php echo $redirect_array['aff_campaign_name']; ?></title>
				<meta name="robots" content="noindex">
				<meta http-equiv="refresh"
					content="1; url=<?php echo $redirect_site_url; ?>">
				</head>
				<body>
					<form name="form1" id="form1" method="get"
						action=dirname($_SERVER['PHP_SELF'])."/cl2.php">
						<input type="hidden" name="q"
							value="<?php echo $redirect_site_url; ?>" />
					</form>
					<script type="text/javascript">
							document.form1.submit();
						</script>

					<div style="padding: 30px; text-align: center;">
							You are being automatically redirected to <?php echo $redirect_array['aff_campaign_name']; ?>.<br />
						<br /> Page Stuck? <a href="<?php echo $redirect_site_url; ?>">Click
							Here</a>.
					</div>
				</body>
				</html>
				<?php } else {

				    header('location: ' . $redirect_site_url);
				    die();
				}

			} else if ($redirect_array['type'] == 'lp') {
				$redirect_site_url = replaceTrackerPlaceholders($db, $redirect_array['landing_page_url'], $mysql['click_id']);	
				header('location: ' . $redirect_site_url);
				die();
			} else if($redirect_array['type'] == 'url') {
				header('location: ' . $redirect_array['redirect_url']);
				die();
			} else if ($redirect_array['type'] == 'monetizer') {
				header('location: http://prosper202.com');
				die();
			}
} else {
		updateLpClickDataForRotator('', $mysql['click_id'], $mysql['rotator_id'], $mysql['rule_id']);
		if ($rotator_row['default_campaign'] != null) {
				$click_sql = "SELECT
					   2c.click_id, 
					   2c.user_id,
					   2c.click_filtered,
					   2c.landing_page_id,
					   2cr.click_cloaking,
					   2cs.click_cloaking_site_url_id,
					   2cs.click_redirect_site_url_id
				FROM 202_clicks AS 2c
				LEFT JOIN 202_clicks_record AS 2cr ON 2cr.click_id = 2c.click_id
				LEFT JOIN 202_clicks_site AS 2cs ON 2cs.click_id = 2c.click_id
				WHERE 2c.click_id='".$mysql['click_id']."'"; 
				$click_row = memcache_mysql_fetch_assoc($db, $click_sql);

				$mysql['aff_campaign_id'] = $db->real_escape_string($rotator_row['aff_campaign_id']);
				$mysql['click_payout'] = $db->real_escape_string($rotator_row['aff_campaign_payout']);

				$update_sql = "
					UPDATE
						202_clicks AS 2c
					SET
						2c.aff_campaign_id='" . $mysql['aff_campaign_id'] . "',
						2c.click_payout='" . $mysql['click_payout'] . "'
					WHERE
						2c.click_id='" . $mysql['click_id'] . "'
				";
				$click_result = $db->query($update_sql) or record_mysql_error($db, $update_sql);

				if (($click_row['click_cloaking'] == 1) or // if tracker has overrided cloaking on
				(($click_row['click_cloaking'] == - 1) and ($rotator_row['aff_campaign_cloaking'] == 1)) or ((! isset($click_row['click_cloaking'])) and ($rotator_row['aff_campaign_cloaking'] == 1))) // if no tracker but but by default campaign has cloaking on
				{
				    $cloaking_on = true;
				    $mysql['click_cloaking'] = 1;
				    // if cloaking is on, add in a click_id_public, because we will be forwarding them to a cloaked /cl/xxxx link
				} else {
				    $mysql['click_cloaking'] = 0;
				}

				$update_sql = "
					UPDATE
						202_clicks_record
					SET
						click_out='" . $mysql['click_out'] . "',
						click_cloaking='" . $mysql['click_cloaking'] . "'
					WHERE
						click_id='" . $mysql['click_id'] . "'
				";
				$click_result = $db->query($update_sql) or record_mysql_error($db, $update_sql);

				$outbound_site_url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
				$click_outbound_site_url_id = INDEXES::get_site_url_id($db, $outbound_site_url);
				$mysql['click_outbound_site_url_id'] = $db->real_escape_string($click_outbound_site_url_id);

				if ($cloaking_on == true) {
				    $cloaking_site_url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
				}

				$redirect_site_url = rotateTrackerUrl($db, $rotator_row);
				$redirect_site_url = replaceTrackerPlaceholders($db, $redirect_site_url, $mysql['click_id']);

				$click_redirect_site_url_id = INDEXES::get_site_url_id($db, $redirect_site_url);
				$mysql['click_redirect_site_url_id'] = $db->real_escape_string($click_redirect_site_url_id);

				$update_sql = "
					UPDATE
						202_clicks_site
					SET
						click_outbound_site_url_id='" . $mysql['click_outbound_site_url_id'] . "',
						click_redirect_site_url_id='" . $mysql['click_redirect_site_url_id'] . "'
					WHERE
						click_id='" . $mysql['click_id'] . "'
				";
				$click_result = $db->query($update_sql) or record_mysql_error($db, $update_sql);

				$de = new DataEngine();
				$data = $de->setDirtyHour($mysql['click_id']);

				if ($cloaking_on == true) { ?>
				<html>
				<head>
				<title><?php echo $rotator_row['aff_campaign_name']; ?></title>
				<meta name="robots" content="noindex">
				<meta http-equiv="refresh"
					content="1; url=<?php echo $redirect_site_url; ?>">
				</head>
				<body>
					<form name="form1" id="form1" method="get"
						action=dirname($_SERVER['PHP_SELF'])."/cl2.php">
						<input type="hidden" name="q"
							value="<?php echo $redirect_site_url; ?>" />
					</form>
					<script type="text/javascript">
							document.form1.submit();
						</script>

					<div style="padding: 30px; text-align: center;">
							You are being automatically redirected to <?php echo $rotator_row['aff_campaign_name']; ?>.<br />
						<br /> Page Stuck? <a href="<?php echo $redirect_site_url; ?>">Click
							Here</a>.
					</div>
				</body>
				</html>
				<?php } else {

				    header('location: ' . $redirect_site_url);
				    die();
				}

		} else if ($rotator_row['default_lp'] != null) {
			$redirect_site_url = replaceTrackerPlaceholders($db, $rotator_row['landing_page_url'], $mysql['click_id']);	
			header('location: ' . $redirect_site_url);
			die();
		} else if($rotator_row['default_url'] != null) {
			header('location: ' . $rotator_row['default_url']);
			die();
		} else if ($rotator_row['auto_monetizer'] != null) {
			header('location: http://prosper202.com');
			die();
		}

}
