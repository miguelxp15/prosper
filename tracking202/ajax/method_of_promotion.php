<?php include_once(str_repeat("../", 2).'202-config/connect.php'); 

AUTH::require_user();
//switch methoud of promotion based on if users is on a page with the refin box or not
if ($db->real_escape_string(trim($_POST[page]))=='refine')
    $method_of_promotion="landingpages";
else
    $method_of_promotion="landingpage";

if ($db->real_escape_string(trim($_POST[validate]))=='1')
    $validatelp=1;
else
    $validatelp=0;
?>

<select class="form-control input-sm" name="method_of_promotion" id="method_of_promotion" onchange="tempLoadMethodOfPromotion(this,<?php echo $validatelp; ?>);">
	<option value="0"> -- </option>
	<option <?php if ($_POST['method_of_promotion'] == 'directlink') { echo 'selected=""'; } ?> value="directlink">Direct Linking</option>
	<option <?php if ($_POST['method_of_promotion'] == 'landingpage') { echo 'selected=""'; } ?> value="<?php echo $method_of_promotion; ?>">Landing Page</option>
</select>