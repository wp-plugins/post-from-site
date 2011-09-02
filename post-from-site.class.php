<?php
/**
 * Plugin Name: Post From Site
 * Plugin URI: http://me.redradar.net/category/plugins/post-from-site/
 * Description: Add a new post directly from your website - no need to go to the admin side.
 * Author: Kelly Dwan
 * Version: 2.1.1
 * Date: 07.17.11
 * Author URI: http://me.redradar.net/
 */
 
/*
TODO
style popup & non popup boxes
add filters/actions
put link in admin bar, and post-box slides down. -- call it 3.0. remove settings, most
*/

/* We need the admin functions to use wp_create_category(). */
require_once(ABSPATH . 'wp-admin' . "/includes/admin.php");
require_once('pfs-widget.php');

class PostFromSite {
	/* global variables */
	protected $cat = '';
	protected $linktext = '';
	protected $popup = true;
	
	public function __construct($cat = '', $linktext = '', $popup = true) {
		$this->cat = $cat;
		$this->linktext = $linktext;
		$this->popup = $popup;
	
		register_activation_hook( __FILE__, array($this,'install') );
		
		// add pfs_domain for translation
		load_plugin_textdomain('pfs_domain');
		
		// add pfs_options group & apply validation filter
		register_setting( 'pfs_options', 'pfs_options', array($this, 'validate') );
		
		// add js & css
		add_action( 'get_header', array($this,'includes') );
		
		// add admin page & options
		add_action( 'admin_menu', array($this, 'show_settings') );
		
		// add shortcode support
		add_shortcode( 'post-from-site', array($this, 'shortcode') );
		
		// add admin menu item
		add_action( 'admin_bar_menu', array($this, 'add_admin_link'), 1000 );
	}
	
	public function install(){
		//nothing here yet, as there's really nothing to 'install' that isn't covered by __construct
	}

	/**
	 * Sanitize and validate input. 
	 * @param array $input an array to sanitize
	 * @return array a valid array.
	 */
	public function validate($input) {
	    $ok = Array('publish','pending','draft');
	    $input['pfs_linktext'] = wp_filter_nohtml_kses($input['pfs_linktext']);
	    $input['pfs_excats'] = wp_filter_nohtml_kses($input['pfs_excats']);;
	    $input['pfs_allowimg'] = ($input['pfs_allowimg'] == 1 ? 1 : 0);
	    $input['pfs_imgfaildie'] = ($input['pfs_imgfaildie'] == 1 ? 1 : 0);
	    $input['pfs_allowtag'] = ($input['pfs_allowtag'] == 1 ? 1 : 0);
	    $input['pfs_post_status'] = (in_array($input['pfs_post_status'],$ok) ? $input['pfs_post_status'] : 'pending');
	    $input['pfs_post_type'] = (post_type_exists($input['pfs_post_type']) ? $input['pfs_post_type'] : 'post');
	    $input['pfs_comment_status'] = ($input['pfs_comment_status'] == 'open' ? 'open' : 'closed');
	    $input['pfs_maxfilesize'] = intval($input['pfs_maxfilesize']);
	    $input['enablecaptcha'] = ($input['enablecaptcha'] == 1 ? 1 : 0);
	    $input['pfs_allow_anon'] = ($input['pfs_allow_anon'] == 1 ? 1 : 0);
	    return $input;
	}

	/**
	 * Add javascript and css to header files.
	 */
	public function includes(){
	    wp_enqueue_script( 'jquery-multi-upload', plugins_url("includes/jquery.MultiFile.pack.js",__FILE__), array('jquery','jquery-form') );
	    wp_enqueue_script( 'pfs-script', plugins_url("includes/pfs-script.js",__FILE__) );
	    //wp_enqueue_style( 'pfs-style',  plugins_url("includes/pfs-style.php",__FILE__) );
	    wp_enqueue_style( 'pfs-min-style',  plugins_url("includes/minimal.css",__FILE__) );
	}
	
	/**
	 * Add options to databases with defaults
	 */
	public function show_settings() {
	    add_options_page('Post From Site', 'Post From Site', 'manage_options', 'pfs', array($this, 'settings') );
	    
	    if (!get_option("pfs_options")) {
	        $pfs_options['pfs_linktext'] = 'quick post';
	        $pfs_options['pfs_excats'] = '';
	        $pfs_options['pfs_allowimg'] = 1;
	        $pfs_options['pfs_imgfaildie'] = 1;
	        $pfs_options['pfs_allowtag'] = 1;
	        $pfs_options['pfs_post_status'] = 'publish';
	        $pfs_options['pfs_post_type'] = 'post';
	        $pfs_options['pfs_comment_status'] = 'open';
	        $pfs_options['pfs_maxfilesize'] = 3000000;
	        $pfs_options['enablecaptcha'] = false;
	        $pfs_options['pfs_allow_anon'] = false;
	        add_option ("pfs_options", $pfs_options) ;
	    }
	}

	/**
	 * Add shortcode support.
	 * @param $atts shortcode attributes, cat, link, and popup
	 * cat is the category to post to, link is the display text of the link,
	 * and popup decides whether it's an inline form (false) or a popup box (true).
	 */
	function shortcode($atts, $content=null, $code="") {
	    $a = shortcode_atts( array(
	        'cat' => '',
	        'link' => 'quick post',
	        'popup' => false
	    ), $atts );
	    $pfs = new PostFromSite($a['cat'],$a['link'],$a['popup']);
	    return $pfs->get_form();
	}

	/**
	 * Add a link to show the form from the admin bar
	 */
	function add_admin_link() {
		global $wp_admin_bar, $wpdb;
		if ( !is_super_admin() || !is_admin_bar_showing() )
			return;
		$this->popup = false;
	    $form = "</a>".$this->get_form();
		/* Add the main siteadmin menu item */
		$wp_admin_bar->add_menu( array( 'id' => 'post_from_site', 'title' => __( 'Write a Post', 'pfs_domain' ), 'href' => FALSE ) );
		$wp_admin_bar->add_menu( array( 'parent' => 'post_from_site', 'title' => $form, 'href' => FALSE ) );
	}

	/**
	 * Creates link and postbox (initially hidden with display:none), calls pfs_submit on form-submission. Echos the form.
	 * @param string $cat Category ID for posting specifically to one category. Default is '', which allows user to choose from allowed categories.
	 * @param string $linktext Link text for post link. Default is set in admin settings, any text here will override that. 
	 * @param bool $popup Whether the box should be a 'modal-style' popup or always display
	 */
	public function form(){
		echo $this->get_form();
	}

	/**
	 * Creates link and postbox (initially hidden with display:none), calls pfs_submit on form-submission. Returns the form.
	 * @param string $cat Category ID for posting specifically to one category. Default is '', which allows user to choose from allowed categories.
	 * @param string $linktext Link text for post link. Default is set in admin settings, any text here will override that. 
	 * @param bool $popup Whether the box should be a 'modal-style' popup or always display
	 */
	public function get_form(){
		$linktext = $this->linktext;
		$cat = $this->cat;
		$popup = $this->popup;
		$pfs_options = get_option('pfs_options');
		if (''==$linktext) $linktext = $pfs_options['pfs_linktext'];
		$idtext = $cat.preg_replace('/[^A-Za-z0-9]/','',$linktext);
		// Javascript displays the box when the link is clicked 
		$out = ($popup) ? "<a href='#' class='pfs-post-link clearfix' id='$idtext-link'>$linktext</a>" : '';
		$out .= "<div id='pfs-post-box-$idtext' ";
		$out .= ($popup) ? "style='display:none' class='pfs-post-box pfs_postbox'" : "class='pfs-post-box pfs_postform'";
		$out .= ">\n";
		$out .= ($popup) ? "<div class='closex'>&times;</div>\n" : '';
		$out .= "<div id='pfs-alert' style='display:none;'></div> \n";
		$out .= "<form class='pfs' id='pfs_form' method='post' action='".plugins_url("pfs-submit.php",__FILE__). "' enctype='multipart/form-data'>\n";
		$out .= "<input type='hidden' name='MAX_FILE_SIZE' value='" .$pfs_options['pfs_maxfilesize']. "' />\n";
		$out .= "<label for='pfs_title'>". __('Title:','pfs_domain'). "</label> <input name='title' id='pfs_title' value='' type='text' />\n";
		if (current_user_can('publish_posts') && $pfs_options['pfs_allow_anon']){
			$out .= "<label for='pfs_name'>".__('Name:','pfs_domain')."</label> <input name='name' id='pfs_title' value='' />";
			$out .= "<label for='pfs_email'>".__('Email:','pfs_domain')."</label> <input name='email' id='pfs_email' value='' />\n";
		}
		$out .= "<label for='postcontent'>". __('Content:','pfs_domain'). "</label><textarea id='postcontent' class='theEditor large-text' name='postcontent' rows='12' cols='50'></textarea>\n";
		
		$out .= $this->get_categories_list($pfs_options['pfs_excats']);
		$out .= $this->get_tags_list($pfs_options['pfs_extags']);
		
		if ($pfs_options['pfs_allowimg']) $out .=  __('Image:','pfs_domain')." <div id='pfs-imgdiv$idtext'><input type='file' class='multi' name='image[]' accept='png|gif|jpg|jpeg'/></div>\n";
		$out .= "<div class='clear'></div>\n";
		if ($pfs_options['enablecaptcha'] || $pfs_options['pfs_allow_anon']){
		    require_once('recaptchalib.php');
		    $publickey = '6LfvqcASAAAAAF9DN4HzPkjIhKeRgI78iJXJL606'; // you got this from the signup page
		    $out .= recaptcha_get_html($publickey);
		}
		$out .= "<input type='submit' id='post' class='submit' name='post' value='".__("Post","pfs_domain")."' />\n";
		$out .= "</form>\n<div class='clear'></div>\n";
		$out .= "<small>Powered by <a href='http://me.redradar.net/category/plugins/post-from-site/'>post-from-site</a> &amp; <a href='http://www.redradar.net/wp'>redradar</a></small>\n";
		$out .= "</div>\n\n";
		return $out;
	}


	/**
	 * What to display in the admin menu
	 */
	public function settings() { ?>
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
				document.getElementById('pfs_mfs').style.border="1px solid #880000";
				document.getElementById('filesize_alert').innerHTML='<?php _e('Default: 30MB','pfs_domain'); echo "<br />"; _e('Not a valid filesize','pfs_domain'); ?>';
			} else {
				var size = 0;
				if (m[2] == null) size = m[1];
				else if (m[2] == 'B') size = m[1];
				else if (m[2] == 'KB') size = m[1]*1024;
				else if (m[2] == 'MB') size = m[1]*1024*1024;
				else if (m[2] == 'GB') size = m[1]*1024*1024*1024;
				else if (m[2] == 'TB') size = m[1]*1024*1024*1024*1024;
				document.getElementById('pfs_mfs').style.border="1px solid #DFDFDF";
				document.getElementById('filesize_alert').innerHTML='<?php _e('Default: 30MB','pfs_domain');?>';
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
			font-size:13px;
		}
		.pfs td input, .pfs td select { width:156px; }
		</style>
		<div class="wrap pfs">
			<h2><?php _e('Post From Site Settings','pfs_domain'); ?></h2>
	
			<form method="post" action="options.php" id="options">
				<?php settings_fields('pfs_options'); ?>
				<?php $pfs_options = get_option('pfs_options'); ?>
	
				<table class="form-table">
					<tr><th colspan='2'><?php _e('User Permissions','pfs_domain');?></th></tr>
					<tr><td><?php _e("What categories can't users post to (ie, which to exclude)?",'pfs_domain');?><br /><small><?php _e('use cat IDs, comma seperated values, please.','pfs_domain');?></small></td><td><input type='text' name='pfs_options[pfs_excats]' value='<?php echo $pfs_options['pfs_excats'];?>' /></td><td class='notes'><?php _e('Default: none','pfs_domain');?></td></tr>
					<tr><td><?php _e('Allow post tags (includes ability to create new tags)?','pfs_domain');?></td><td><select name='pfs_options[pfs_allowtag]'><option value='1' <?php echo ($pfs_options['pfs_allowtag'])?'selected':'';?>><?php _e('Yes','pfs_domain');?></option><option value='0' <?php echo ($pfs_options['pfs_allowtag'])?'':'selected';?>><?php _e('No','pfs_domain');?></option></select></td><td class='notes'></td></tr>
					<tr><td><?php _e('Allow users to upload an image?','pfs_domain');?></td><td><select name='pfs_options[pfs_allowimg]'><option value='1' <?php echo ($pfs_options['pfs_allowimg'])?'selected':'';?>><?php _e('Yes','pfs_domain');?></option><option value='0' <?php echo ($pfs_options['pfs_allowimg'])?'':'selected';?>><?php _e('No','pfs_domain');?></option></select></td><td class='notes'><?php _e("Note: Images automatically uploaded to 'uploads' directory of wp-content -- just like uploading through the write-post/write-page pages.",'pfs_domain');?></td></tr>
					<tr><td><?php _e('Maximum file size for uploaded images?','pfs_domain');?></td><td><input type='text' id='pfs_mfs' onblur='javascript:filesize_bytes()' value='<?php echo display_filesize($pfs_options['pfs_maxfilesize']);?>' /></td><td class='notes' id="filesize_alert"><?php _e('Default: 3MB','pfs_domain');?></td></tr>
					<input type="hidden" id='pfs_mfsHidden' name='pfs_options[pfs_maxfilesize]' value='' />
					<tr><td><?php _e('If image upload fails, should the post still be posted (without the image)?','pfs_domain');?></td><td><select name='pfs_options[pfs_imgfaildie]'><option value='1' <?php echo ($pfs_options['pfs_imgfaildie'])?'selected':'';?>><?php _e('Yes','pfs_domain');?></option><option value='0' <?php echo ($pfs_options['pfs_imgfaildie'])?'':'selected';?>><?php _e('No','pfs_domain');?></option></select></td><td class='notes'><?php _e("Note: If you select no, your post will not be created.",'pfs_domain'); ?></td></tr>
					<tr><td><?php _e("Post status? (set to draft or pending if you don't want these posts seen before approval)",'pfs_domain');?></td><td><select name='pfs_options[pfs_post_status]'>
						<option value='draft' <?php echo ('draft'==$pfs_options['pfs_post_status'])?'selected':'';?>><?php _e('Draft','pfs_domain');?></option>
						<option value='pending'<?php echo ('pending'==$pfs_options['pfs_post_status'])?'selected':'';?>><?php _e('Pending','pfs_domain');?></option>
						<option value='publish' <?php echo ('publish'==$pfs_options['pfs_post_status'])?'selected':'';?>><?php _e('Publish','pfs_domain');?></option>
					</select></td><td class='notes'><?php _e('Default: Publish','pfs_domain');?></td></tr>
					<tr><td><?php _e('Comment status? (closed means no one can comment on these posts)','pfs_domain');?></td><td><select name='pfs_options[pfs_comment_status]'>
						<option value='closed' <?php echo ('closed'==$pfs_options['pfs_comment_status'])?'selected':'';?>><?php _e('Closed','pfs_domain');?></option>
						<option value='open' <?php echo ('open'==$pfs_options['pfs_comment_status'])?'selected':'';?>><?php _e('Open','pfs_domain');?></option>
					</select></td><td class='notes'><?php _e('Default: Open','pfs_domain');?></td></tr>
	                <tr><td><?php _e("Post type?",'pfs_domain');?></td><td><select name='pfs_options[pfs_post_type]'>
	                    <?php $post_types=get_post_types('','names'); 
	                    foreach ($post_types as $post_type ) {
	                        if ($post_type == $pfs_options['pfs_post_type']) {
	                            echo '<option value="'.$post_type.'" selected>'.$post_type.'</option>';
	                        } else {
	                            echo '<option value="'.$post_type.'">'.$post_type.'</option>';
	                        }
	                    } ?>
	                </select></td><td class='notes'><?php _e('Set this to control what kind of post type your form will process as (post, page, any custom post types, etc).','pfs_domain'); ?><br /><?php _e('Default: post','pfs_domain'); ?></td></tr>
				</table>
				
				<p class="submit">
					<input type="submit" name="Submit" value="<?php _e('Save Changes','pfs_domain') ?>" />
				</p>
			</form>
			
			<h2><?php _e('Installation','pfs_domain');?></h2>
			<p><?php _e('Add the following code wherever you want the link to appear in your theme.','pfs_domain');?></p>
			<p><code>&lt;?php if (function_exists('post_from_site')) {post_from_site();} ?&gt;</code></p>
			<p><?php _e('To generate individual links to specific category posts:','pfs_domain');?> <small><?php _e("(like, 'click here to post in the general category')",'pfs_domain');?></small></p> 
			<p><?php _e('Category:','pfs_domain');?> <select id="cat" class="postform"><?php 
				$categories = wp_dropdown_categories("echo=0&hide_empty=0");
				preg_match_all('/\s*<option class="(\S*)" value="(\S*)">(.*)<\/option>\s*/', $categories, $matches, PREG_SET_ORDER);
				echo "<option class='{$matches[0][1]}' value=''></option>";
				foreach ($matches as $match) echo $match[0]; 
			?></select> &nbsp; <?php _e('Link Text:','pfs_domain'); ?> <input type="text" id="pfs_indlinktxt" /> &nbsp; <input type="submit" value="<?php _e('generate code','pfs_domain');?>" onclick="javascript:genCode();"/></p>
			<p><code id="gendCode"></code></p>
		</div>
	<?php 
	}

}
$pfs = new PostFromSite();

/**  === === HELPER FUNCTIONS === ===  **/

/**
 * Convert number in bytes into human readable format (KB, MB etc)
 * @param int $filesize number in bytes to be converted
 * @return string bytes in human readable form
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

/**
 * Convert string filesize in KB (or MB etc) into integer bytes
 * @param string $filesize size to be converted
 * @return int filesize in bytes
 */
function filesize_bytes($filesize){
    $prefix = array('B'=>0,'KB'=>1,'MB'=>2,'GB'=>3,'TB'=>4);
    preg_match('/([0-9]*{\.[0-9]*}?)([KMGT]?B)/', strtoupper($filesize), $match);
    if ('' != $match[0]) {
        $size = $match[1];
        for ($i = 0; $i < $prefix[$match[2]]; $i++) $size *= 1000;
    }
    return $size;
}
