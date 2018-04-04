<?php
include_once(str_repeat("../", 2).'202-config/connect.php');

AUTH::require_user();

header('location: '.get_absolute_url().'tracking202/update/subids.php');