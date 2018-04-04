<?php
include_once(str_repeat("../", 1).'202-config/connect.php');

if ($_SESSION['toolbar'] == 'true')
	$redir_url = get_absolute_url().'202-Mobile/';
else
	$redir_url = get_absolute_url();
session_destroy();
setcookie(
	'remember_me',
	'',
	time() - 3600,
	'/',
	$_SERVER['HTTP_HOST'],
	false,
	true
);
header('location: '.$redir_url);