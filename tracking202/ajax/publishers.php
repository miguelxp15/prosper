<?php include_once(str_repeat("../", 2).'202-config/connect.php'); 

AUTH::require_user(); ?>

<select class="form-control input-sm" name="publisher_id" id="publisher_id" onchange="load_publisher_id(this.value, 0);">
    
    <option value="1" <?php if ($_POST['user_id'] == '1') echo 'selected=""'; ?>>[Admin Account - Default]</option>
	<?php  $mysql['user_id'] = $db->real_escape_string($_SESSION['user_id']);
		$user_sql = "SELECT * FROM `202_users` WHERE `user_active`='1' AND `user_deleted`='0'  AND `user_id`!='1'";
        $user_result = $db->query($user_sql) or record_mysql_error($user_sql);

        while ($user_row = $user_result->fetch_array(MYSQLI_ASSOC)) {
            
			$html['user_name'] = htmlentities($user_row['user_fname'], ENT_QUOTES, 'UTF-8').' '.htmlentities($user_row['user_lname'], ENT_QUOTES, 'UTF-8');
            $html['user_id'] = htmlentities($user_row['user_id'], ENT_QUOTES, 'UTF-8');
            
            if ($_POST['user_id'] == $user_row['user_id']) {
                $selected = 'selected=""';   
            } else {
                $selected = '';  
            }
            
            printf('<option %s value="%s">%s</option>', $selected, $html['user_id'],$html['user_name']);

        } ?>
</select>