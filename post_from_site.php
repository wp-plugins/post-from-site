<?php
/*
 * Plugin Name: Post From Site
 * Plugin URI: http://redradar.net/wp/2009/06/unveiling-post-from-site/
 * Description: Add a new post directly from your website - no need to go to the admin side.
 * Author: Kelly Dwan
 * Version: 1.9.0
 * Date: 9.21.09
 * Author URI: http://www.redradar.net/wp
 */
/* We need the admin functions to use wp_create_category(). */
require_once(dirname(__FILE__).'/../../../wp-admin/includes/admin.php');
add_action('init','pfs_includes');
function pfs_includes(){
	$path = split('wp-content',__FILE__,2); 
	wp_enqueue_script( 'pfs_display', get_bloginfo('url').'/wp-content'.dirname($path[1]).'/pfs_display.js');
	wp_enqueue_style( 'pfs_style', get_bloginfo('url').'/wp-content'.dirname($path[1]).'/pfs_style.php');
?>
<?php } ?>
<?php 
/* * *
 * Creates link and postbox (initially hidden with display:none), calls pfs_submit on form-submission.
 * @param string $cat Category ID for posting specifically to one category. Default is '', which allows user to choose from allowed categories.
 * @param string $linktext Link text for post link. Default is set in admin settings, any text here will override that. 
 */
function post_from_site($cat = '', $linktext = ''){
	if (''==$linktext) $linktext = get_option('pfs_linktext');
	$idtext = $cat.preg_replace('/[^A-Za-z0-9]/','',$linktext);
	$linktext = htmlspecialchars(htmlspecialchars_decode(strip_tags($linktext)));
	// Javascript displays the box when the link is clicked 
	echo "<a id='postlink' onclick='pfsopen(\"$idtext\")'>$linktext</a>"; ?>
	<div class="pfs_postbox" id="pfs_postbox<?php echo "$idtext"; ?>" style="display:none">
		<div id="closex"><a onclick="javascript:pfsclose('<?php echo "$idtext"; ?>')">x</a></div>
		<form class="pfs" id="pfs_form" method="post" action="<?php echo ''; ?>" enctype='multipart/form-data'>
		<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo get_option('pfs_maxfilesize');?>" />
		<center><h4>Title:</h4> <input name="title" id="pfs_title" value="" size="50" /></center>
		<textarea id="postcontent" name="postcontent" rows="20" cols="50"></textarea><br />
		<?php if (get_option('pfs_allowimg')) echo "Image: <input type='file' name='image' id='pfs_image' size='50'>"; ?>
		<br />
		<div id="pfs_meta">
		<div id="pfs_catchecks">
		<?php 
		if (''==$cat){
			echo "<h4>Categories:</h4>";
			$excats = get_option('pfs_excats');
			$categories = wp_dropdown_categories("exclude=$excats&echo=0&hide_empty=0");
			preg_match_all('/\s*<option class="(\S*)" value="(\S*)">(.*)<\/option>\s*/', $categories, $matches, PREG_SET_ORDER);
			echo "<select name='cats[]' size='2' multiple='multiple'>";
			foreach ($matches as $match){
				echo "<option value='{$match[2]}'>{$match[3]}</option>";
			}
			echo "</select><br />\n";
			if (get_option('pfs_allowcat')) echo "<small>create new:</small><input type='text' name='newcats' value='' size='15' />";
		} else {
			echo "<h4>Posting to ".get_cat_name($cat)." category</h4>";
			echo "<input type='hidden' name='cats[]' value='$cat' />";
		}
		echo "</div>";
		echo "<div id='pfs_tagchecks'>";
		if (get_option('pfs_allowtag')){
			echo "<h4>Tags:</h4>";
			$tags = get_tags('get=all');
			if (''!=$tags) {
				$i = 0;
				echo "<select name='tags[]' size='2' multiple='multiple'>";
				foreach ($tags as $tag) {
					echo "<option value='{$tag->name}'>{$tag->name}</option>";
				}
				echo "</select><br />\n";
			}
			echo "<small>create new:</small><input type='text' name='newtags' value='' size='15' />";
		}
		echo "</div></div>";
		?>
		<div class="clear"></div>
		<input type="hidden" name="page" value="<?php get_option(''); ?>" />
		<input type="submit" id="submit" name="submit" value="Post" />
		</form>
		<div class="clear"></div>
		<small>Powered by <a href="http://www.redradar.net/wp/2009/06/unveiling-post-from-site/">post-from-site</a> &amp; <a href="http://www.redradar.net/wp">redradar</a></small>
	</div>
<?php
	if (isset($_POST['submit'])){		
		pfs_submit($_POST);
		unset($_POST['submit']);
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
	$imgAllowed = 0;
	$success = False;
	if (is_user_logged_in()) { 
		/* play with the image */
		if(''!=$_FILES['image']['tmp_name']){
			(getimagesize($_FILES['image']['tmp_name'])) ? $imgAllowed = 1 : $imgAllowed = (''==$_FILES['image']['name']);
			if ($imgAllowed){
				$upload = wp_upload_bits($_FILES["image"]["name"], null, file_get_contents($_FILES["image"]["tmp_name"]));
				if (False === $upload['error']){
					$success = True;
				} else {
					echo "<div id=\"alert\">There was an error uploading the image: {$upload['error']}</div><br />";
					return;
				}
			} else {
				echo "<div id=\"alert\">Only images (.gif, .png, .jpg, .jpeg) are allowed.</div>";
			}
		}
		/* manipulate $pfs_data into proper post array */
		if ($title != '' && $postcontent != '') {
			$content = $postcontent;
			global $user_ID;
			get_currentuserinfo();
			$imgtag = '[!--image--]';
			if ($success){
				if (False === strpos($content,$imgtag)) $content .= "<br />$imgtag";
				$content = str_replace($imgtag, "<img src='{$upload['url']}' class='postimg' />", $content);
			}
			$categories = $cats;
			$newcats = explode(',',$newcats);
			foreach ($newcats as $cat) $categories[] = wp_insert_category(array('cat_name' => trim($cat), 'category_parent' => 0));
			$newtags = explode(',',$newtags);
			foreach ($newtags as $tag) {
				wp_create_tag(trim($tag));
				$tags[] = trim($tag);
			}
			$postarr = array();
			$postarr['post_title'] = $title;
			$postarr['post_content'] = $content;
			$postarr['comment_status'] = get_option('pfs_comment_status');
			$postarr['post_status'] = get_option('pfs_post_status');
			$postarr['post_author'] = $user_ID;
			$postarr['post_category'] = $categories;
			$postarr['tags_input'] = implode(',',$tags);
			$postarr['post_type'] = 'post';
			$post_id = wp_insert_post($postarr);
			if (0 == $post_id) echo "<div id=\"alert\">Unable to insert post- unknown error.</div>";
		} else {
			echo "<div id=\"alert\">You've left either the title or content empty.</div>";
		}
	} else {
		echo "<div id=\"alert\">You need to be logged in to post. <a href='".get_bloginfo('wpurl')."/wp-login.php?redirect_to=".get_bloginfo('url')."'>Log in</a></div><br />";
	}
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
	add_option('pfs_allowcat', 1);
	add_option('pfs_allowtag', 1);
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
		//alert("Not a properly formatted filesize");
		document.getElementById('pfs_mfs').style.border="1px solid #880000";
		document.getElementById('filesize_alert').innerHTML='Default: 30MB<br />Not a valid filesize';
	} else {
		var size = 0;
		if (m[2] == 'B') size = m[1];
		else if (m[2] == 'KB') size = m[1]*1024;
		else if (m[2] == 'MB') size = m[1]*1024*1024;
		else if (m[2] == 'GB') size = m[1]*1024*1024*1024;
		else if (m[2] == 'TB') size = m[1]*1024*1024*1024*1024;
		document.getElementById('pfs_mfs').style.border="1px solid #DFDFDF";
		document.getElementById('filesize_alert').innerHTML='Default: 30MB';
		document.getElementById('pfs_mfsHidden').value = size;
	}
}
function genCode(){
	if (document.getElementById("cat").value == ''){cat = "''";} else {cat=document.getElementById("cat").value;}
	linktext = document.getElementById('pfs_indlinktxt').value.replace(/'/g, "\\'");
	linktext = linktext.replace(/"/g, "&amp;quot;");
	document.getElementById('gendCode').innerHTML = "&lt;?php if (function_exists('post_from_site')) {post_from_site("+cat+",'"+linktext+"');} ?&gt;";
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
				<tr><td>What text do you want do display as the link text?</td><td><input type='text' id='pfs_linktext' name='pfs_linktext' value='<?php echo get_option('pfs_linktext');?>' /></td></tr>
				<tr><th colspan='2'>User Permissions</th></tr>
				<tr><td>What categories can't quickpost users post to (ie, which to exclude)? <br /><small>use cat IDs, comma seperated values, please.</small></td><td><input type='text' name='pfs_excats' value='<?php echo get_option('pfs_excats');?>' /></td><td class='notes'>Default: none</td></tr>
				<tr><td>Allow creation of new categories?</td><td><select name='pfs_allowcat'><option value='1' <?php echo (get_option('pfs_allowcat'))?'selected':'';?>>Yes</option><option value='0' <?php echo (get_option('pfs_allowcat'))?'':'selected';?>>No</option></select></td><td class='notes'></td></tr>
				<tr><td>Allow post tags (includes ability to create new tags)?</td><td><select name='pfs_allowtag'><option value='1' <?php echo (get_option('pfs_allowtag'))?'selected':'';?>>Yes</option><option value='0' <?php echo (get_option('pfs_allowtag'))?'':'selected';?>>No</option></select></td><td class='notes'></td></tr>
				<tr><td>Allow users to upload an image?</td><td><select name='pfs_allowimg'><option value='1' <?php echo (get_option('pfs_allowimg'))?'selected':'';?>>Yes</option><option value='0' <?php echo (get_option('pfs_allowimg'))?'':'selected';?>>No</option></select></td><td class='notes'>Note: Images automatically uploaded to 'uploads' directory of wp-content -- just like uploading through the write-post/write-page pages.<br />To put this in the post, use the tag [!--image--]. If you don't include this, but do upload an image, it will automatically be appended to the end of the post.</td></tr>
				<tr><td>Maximum file size for uploaded images?</td><td><input type='text' id='pfs_mfs' onblur='javascript:filesize_bytes()' value='<?php echo display_filesize(get_option('pfs_maxfilesize'));?>' /></td><td class='notes' id="filesize_alert">Default: 30MB</td></tr>
				<input type="hidden" id='pfs_mfsHidden' name='pfs_maxfilesize' value='' />
				<tr><td>Post status? (set to draft or pending if you don't want these posts seen before approval)</td><td><select name='pfs_post_status'>
					<option value='draft' <?php echo ('draft'==get_option('pfs_post_status'))?'selected':'';?>>Draft</option>
					<option value='pending'<?php echo ('pending'==get_option('pfs_post_status'))?'selected':'';?>>Pending</option>
					<option value='publish' <?php echo ('publish'==get_option('pfs_post_status'))?'selected':'';?>>Publish</option>
				</select></td><td class='notes'>Default: Publish</td></tr>
				<tr><td>Comment status? (closed means no one can comment on these posts)</td><td><select name='pfs_comment_status'>
					<option value='closed' <?php echo ('closed'==get_option('pfs_comment_status'))?'selected':'';?>>Closed</option>
					<option value='open' <?php echo ('open'==get_option('pfs_comment_status'))?'selected':'';?>>Open</option>
				</select></td><td class='notes'>Default: Open</td></tr>
				
				<tr><th colspan='2'>Post-box Style</th></tr>
				<tr><td>Container background color?</td><td><input type='text' name='pfs_bgcolor' value='<?php echo get_option('pfs_bgcolor');?>' /></td><td class='notes'>Default: #EDF0CF</td></tr>
				<tr><td>Top-left corner image location? (path/to/filename.jpg)<br /><small>relative to plugin folder</small></td><td><input type='text' name='pfs_bgimg' value='<?php echo get_option('pfs_bgimg');?>' /></td><td class='notes'>Default: pfs_title.png</td></tr>
				<tr><td>Title text color?</td><td><input type='text' name='pfs_titlecolor' value='<?php echo get_option('pfs_titlecolor');?>' /></td><td class='notes'>Default: none (inherited)</td></tr>
				<tr><td>Regular text color?</td><td><input type='text' name='pfs_textcolor' value='<?php echo get_option('pfs_textcolor');?>' /></td><td class='notes'>Default: black</td></tr>
				<tr><td>Add your own CSS:</td><td colspan='2'><textarea name='pfs_customcss' rows='5' cols='50'><?php echo get_option('pfs_customcss');?></textarea></td></tr>
			</table>
			
			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="page_options" value="pfs_linktext,pfs_excats,pfs_allowcat,pfs_allowtag,pfs_allowimg,pfs_post_status,pfs_comment_status,pfs_imagedir,pfs_maxfilesize,pfs_titlecolor,pfs_textcolor,pfs_bgcolor,pfs_bgimg,pfs_customcss" />

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
