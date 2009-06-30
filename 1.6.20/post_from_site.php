<?php
/*
 * Plugin Name: Post From Site
 * Plugin URI: http://www.redradar.net/wp/?p=95
 * Description: Add a new post directly from your website - no need to go to the admin side.
 * Author: Kelly Dwan
 * Version: 1.6.20
 * Date: 6.20.09
 * Author URI: http://www.redradar.net/wp
 */
/* * *
 * TODO:
 *		Tag support
 * 		additional media?
 * SUGGESTIONS:
 *		BuddyPress
 *		non-registered users -> captcha and/or automatic pending status
 * 		horizontal category layout rather than vertical
 * SEPERATE PLUGINS:
 * 		simple pulldown login box
 * 		forked pfs that just does images
 */
add_action('wp_head','pfs_includes');
function pfs_includes(){
$path = split('wp-content',__FILE__,2); ?>
<!-- CSS/js added by post-from-site plugin -->
<script language='javascript' src='<?php echo get_bloginfo('url').'/wp-content'.dirname($path[1]); ?>/pfs_display.js'></script> 
<link rel="stylesheet" type="text/css" media="screen" href='<?php echo get_bloginfo('url').'/wp-content'.dirname($path[1]); ?>/pfs_style.php' />
<?php } ?>
<?php 
/* * *
 * Creates link and postbox (initially hidden with display:none), calls pfs_submit on form-submission.
 * @param string $cat Category ID for posting specifically to one category. Default is '', which allows user to choose from allowed categories.
 * @param string $linktext Link text for post link. Default is set in admin settings, any text here will override that. 
 */
function post_from_site($cat = '', $linktext = ''){
	if (''==$linktext) $linktext = get_option('pfs_linktext');
	// Javascript displays the box when the link is clicked 
	echo "<a id='postlink' onclick='pfsopen()'>$linktext</a><span id='pfs_proc'></span>"; ?>
	<div id="pfs_postbox" style="display:none">
		<div id="closex"><a onclick="javascript:pfsclose()">x</a></div>
		<form class="pfs" id="pfs_form" method="post" action="<?php echo ''; ?>" enctype='multipart/form-data'>
		<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo get_option('pfs_maxfilesize');?>" />
		<center><h4>Title:</h4> <input name="title" id="pfs_title" value="" size="50" /></center>
		<textarea id="postcontent" name="postcontent" rows="20" cols="50"></textarea><br />
		<?php if (True == get_option('pfs_allowimg')) echo "Image: <input type='file' name='image' id='pfs_image' size='50'>"; ?>
		<div id="pfs_catchecks">
		<?php 
		if (''==$cat){
			echo "<h4>Categories:</h4><br />";
			$excats = get_option('pfs_excats');
			$categories = wp_dropdown_categories("exclude=$excats&echo=0&hide_empty=0");
			preg_match_all('/\s*<option class="(\S*)" value="(\S*)">(.*)<\/option>\s*/', $categories, $matches, PREG_SET_ORDER);
			foreach ($matches as $match)
				echo '<input type="checkbox" name="cats[]" value="'.$match[2].'" class="'.$match[1].'" id="'.$match[1].$match[2].'" /><label for="'.$match[1].$match[2].'">'.$match[3].'</label><br />';
		} else {
			echo "<h4>Posting to ".get_cat_name($cat)."</h4><br />";
			echo "<input type='hidden' name='cats[]' value='$cat' />";
		}
		?>
		</div>
		<input type="hidden" name="page" value="<?php get_option(''); ?>" />
		<input type="submit" id="submit" name="submit" value="Post" />
		</form>
		<div class="clear"></div>
		<small>Powered by <a href="http://www.redradar.net/wp/?p=95">post-from-site</a> &amp; <a href="http://www.redradar.net/wp">rrn</a></small>
	</div>
<?php
	if (isset($_POST['submit'])){
		pfs_submit($_POST);
	} 
}
?>
<?php 
/* * *
 * Processed form data into a proper post array, uses wp_insert_post() to add post. Also uses variables defined in the admin settings.
 * @param array $pfs_data POSTed array of data from the form
 */
function pfs_submit($pfs_data){
	foreach($pfs_data as $key=>$value) ${$key} = $value;
	echo "<script language=javascript>document.getElementById('pfs_proc').innerHTML='processing...'</script>";
	if (is_user_logged_in()) { 
		/* play with the image */
		if(''!=$_FILES['image']['tmp_name']){
			(getimagesize($_FILES['image']['tmp_name'])) ? $imgAllowed = 1 : $imgAllowed = (''==$_FILES['image']['name']);
		}
		if ($imgAllowed && (''!=$_FILES['image']['tmp_name'])){
			$upload = wp_upload_bits($_FILES["image"]["name"], null, file_get_contents($_FILES["image"]["tmp_name"]));
			if (False === $upload['error']){
				$success = True;
			} else {
				echo "<div id=\"alert\">There was an error uploading the image: {$upload['error']}</div><br />";
				return;
			}
		} 
		if ($imgAllowed){
			if ($pfs_data['title'] != '' && $pfs_data['postcontent'] != '') {
				/* manipulate $pfs_data into proper post array */
				global $user_ID;
				get_currentuserinfo();
				$title = $pfs_data['title'];
				$content = $pfs_data['postcontent'];
				($success)?$content .= "<br /><img src='{$upload['url']}' class='postimg' />":'';
				$categories = $pfs_data['cats'];
				$postarr = array();
				$postarr['post_title'] = $title;
				$postarr['post_content'] = $content;
				$postarr['comment_status'] = get_option('pfs_comment_status');
				$postarr['post_status'] = get_option('pfs_post_status');
				$postarr['post_author'] = $user_ID;
				$postarr['post_category'] = $categories;
				$post_id = wp_insert_post($postarr);
				if (0 == $post_id) echo "<div id=\"alert\">Unable to insert post- unknown error.</div>";
			} else {
				echo "<div id=\"alert\">You've left either the title or content empty.</div>";
			}
		} else {
			echo "<div id=\"alert\">Only images (.gif, .png, .jpg, .jpeg) are allowed.</div>";
		}
	} else {
		echo "<div id=\"alert\">You need to be logged in to post. <a href='http://www.redradar.net/wp/wp-login.php?redirect_to=$page'>Log in</a></div><br />";
	}
	echo "<script language=javascript>document.getElementById('pfs_proc').innerHTML='';</script>";
	return;
} 
?>
<?php add_action('admin_menu','show_pfs_settings');
/* Add options to databases with defaults */
function show_pfs_settings() {
	add_options_page('Post From Site', 'Post From Site', 8, 'pfs', 'pfs_settings');
	add_option('pfs_linktext', 'quick post');
	add_option('pfs_excats', 0);
	add_option('pfs_allowimg', 0);
	add_option('pfs_post_status', 'publish');
	add_option('pfs_comment_status', 'open');
	add_option('pfs_imgdir', dirname(__FILE__));
	add_option('pfs_maxfilsize', 30000);
	add_option('pfs_bgcolor', '#EDF0CF');
	add_option('pfs_bgimg', 'pfs_title.png');
	add_option('pfs_titlecolor', '');
	add_option('pfs_textcolor', 'black');
	add_option('pfs_customcss', '');
}?>
<?php
/* * *
 * What to display in the admin menu
 */
function pfs_settings() { ?>
<script language="Javascript">
function filesize_bytes() {
	document.getElementById('pfs_mfs').value = document.getElementById('pfs_mfs').value.toUpperCase();
	var re = /^([0-9.]*)([KMGT]?B)?$/;
	var KB = 1;
	var MB = 2;
	var GB = 3;
	var TB = 4;
	var m = re.exec(document.getElementById('pfs_mfs').value);
	if (m == null) {
		alert("Not a properly formatted filesize");
	} else {
		var size = 0;
		if (m[2] == 'B') size = m[1];
		else if (m[2] == 'KB') size = m[1]*1024;
		else if (m[2] == 'MB') size = m[1]*1024*1024;
		else if (m[2] == 'GB') size = m[1]*1024*1024*1024;
		else if (m[2] == 'TB') size = m[1]*1024*1024*1024*1024;
		document.getElementById('pfs_mfsHidden').value = size;
	}
}
function genCode(){
	if (document.getElementById("cat").value == ''){cat = "''";} else {cat=document.getElementById("cat").value;}
	document.getElementById('gendCode').innerHTML = "&lt;?php if (function_exists('post_from_site')) {post_from_site("+cat+",'"+document.getElementById('pfs_indlinktxt').value+"');} ?&gt;";
}
</script>
<style type='text/css'>
.pfs th {
	font-family: Georgia,"Times New Roman","Bitstream Charter",Times,serif;
	font-size:12pt;
	font-style:italic;
	font-weight:bold;
}
.pfs td {
	font-size:10pt;
}
</style>
	<div class="wrap pfs">
		<h2>Post From Site Settings</h2>

		<form method="post" action="options.php" id="options">
			<?php wp_nonce_field('update-options'); ?>

			<table class="form-table">
				<tr><td>What text do you want do display as the link text?</td><td><input type='text' name='pfs_linktext' value='<?php echo get_option('pfs_linktext');?>' /></td></tr>
				<tr><th colspan='2'>User Permissions</th></tr>
				<tr><td>What categories can't quickpost users post to (ie, which to exclude)? <small>comma seperated values, please.</small></td><td><input type='text' name='pfs_excats' value='<?php echo get_option('pfs_excats');?>' /></td><td>Default: none</td></tr>
				<tr><td>Allow users to upload an image (will be attached to end of post)?</td><td><select name='pfs_allowimg'><option value='1' <?php echo (get_option('pfs_allowimg'))?'selected':'';?>>Yes</option><option value='0' <?php echo (get_option('pfs_allowimg'))?'':'selected';?>>No</option></select></td><td>Note: Images automatically uploaded to 'uploads' directory of wp-content -- just like uploading through the write-post/write-page pages.</td></tr>
				<tr><td>Maximum file size for uploaded images?</td><td><input type='text' id='pfs_mfs' onblur='javascript:filesize_bytes()' value='<?php echo display_filesize(get_option('pfs_maxfilesize'));?>' /></td><td>Default: 30MB</td></tr>
				<input type="hidden" id='pfs_mfsHidden' name='pfs_maxfilesize' value='' />
				<tr><td>Post status? (set to draft or pending if you don't want these posts seen before approval)</td><td><select name='pfs_post_status'>
					<option value='draft' <?php echo ('draft'==get_option('pfs_post_status'))?'selected':'';?>>Draft</option>
					<option value='pending'<?php echo ('pending'==get_option('pfs_post_status'))?'selected':'';?>>Pending</option>
					<option value='publish' <?php echo ('publish'==get_option('pfs_post_status'))?'selected':'';?>>Publish</option>
				</select></td><td>Default: Publish</td></tr>
				<tr><td>Comment status? (closed means no one can comment on these posts)</td><td><select name='pfs_comment_status'>
					<option value='closed' <?php echo ('closed'==get_option('pfs_comment_status'))?'selected':'';?>>Closed</option>
					<option value='open' <?php echo ('open'==get_option('pfs_comment_status'))?'selected':'';?>>Open</option>
				</select></td><td>Default: Open</td></tr>
				
				<tr><th colspan='2'>Post-box Style</th></tr>
				<tr><td>Container background color?</td><td><input type='text' name='pfs_bgcolor' value='<?php echo get_option('pfs_bgcolor');?>' /></td><td>Default: #EDF0CF</td></tr>
				<tr><td>Top-left corner image location? (path/to/filename.jpg)</td><td><input type='text' name='pfs_bgimg' value='<?php echo get_option('pfs_bgimg');?>' /></td><td>Default: pfs_title.png</td></tr>
				<tr><td>Title text color?</td><td><input type='text' name='pfs_titlecolor' value='<?php echo get_option('pfs_titlecolor');?>' /></td><td>Default: none (inherited)</td></tr>
				<tr><td>Regular text color?</td><td><input type='text' name='pfs_textcolor' value='<?php echo get_option('pfs_textcolor');?>' /></td><td>Default: black</td></tr>
				<tr><td>Add your own CSS:</td><td colspan='2'><textarea name='pfs_customcss' rows='5' cols='50'><?php echo get_option('pfs_customcss');?></textarea></td></tr>
			</table>
			
			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="page_options" value="pfs_linktext,pfs_excats,pfs_allowimg,pfs_post_status,pfs_comment_status,pfs_imagedir,pfs_maxfilesize,pfs_titlecolor,pfs_textcolor,pfs_bgcolor,pfs_bgimg,pfs_customcss" />

			<p class="submit">
				<input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>
		
		<h2>Installation</h2>
		<p>Add the following code wherever you want the link to appear in your theme.</p>
		<p><code>&lt;?php if (function_exists('post_from_site')) {post_from_site();} ?&gt;</code></p>
		<p>To generate individual links to specific category posts: <small>(like, 'click here to post in the general category')</small></p> 
		<p>Category: <select id="cat" class="postform"><?php 
			$categories = wp_dropdown_categories("echo=0&hide_empty=0");
			preg_match_all('/\s*<option class="(\S*)" value="(\S*)">(.*)<\/option>\s*/', $categories, $matches, PREG_SET_ORDER);
			echo "<option class='{$matches[0][1]}' value=''></option>";
			foreach ($matches as $match) echo $match[0]; 
		?></select> &nbsp; Link Text: <input type="text" id="pfs_indlinktxt" /> &nbsp; <input type="submit" value="generate code" onclick="javascript:genCode();"/></p>
		<p><code id="gendCode"></code></p>
	</div>
<?php } ?>
<?php
/* * *
 * A few useful housekeeping-type functions
 */
function display_filesize($filesize){
	if(is_numeric($filesize)) {
		$decr = 1024; $step = 0;
		$prefix = array('B','KB','MB','GB','TB','PB');
		while(($filesize / $decr) > 0.9){
			$filesize = $filesize / $decr;
			$step++;
		}
		return round($filesize,2).$prefix[$step];
	} else {
		return 'NaN';
	}
} 
function filesize_bytes($filesize){
	$prefix = array('B'=>0,'KB'=>1,'MB'=>2,'GB'=>3,'TB'=>4);
	preg_match('/([0-9]*{\.[0-9]*}?)([KMGT]?B)/', strtoupper($filesize), $match);
	if ('' != $match[0]) {
		$size = $match[1];
		for ($i = 0; $i < $prefix[$match[2]]; $i++) $size *= 1000;
	}
	return $size;
}
?>
