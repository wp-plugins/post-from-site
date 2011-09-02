<?php 
/* * *
 * Processed form data into a proper post array, uses wp_insert_post() to add post. 
 * 
 * @param array $pfs_data POSTed array of data from the form
 */
require('../../../wp-load.php');

/**
 * Create post from form data, including uploading images
 * @param array $post
 * @param array $files
 * @return string success or error message.
 */
function pfs_submit($post,$files){
	$pfs_options = get_option('pfs_options');
	$pfs_data = $post;
	$pfs_files = $files;
    $pfs_options = get_option('pfs_options');
	//echo "<pre style=\"border:1px solid #ccc;margin-top:5px;\">".print_r($pfs_data, true)."</pre>\n";
	//echo "<pre style=\"border:1px solid #ccc;margin-top:5px;\">".print_r($pfs_files, true)."</pre>\n";
	
    $title = $pfs_data['title'];
    $postcontent = $pfs_data['postcontent'];
    $name = wp_kses($pfs_data['name'],array());
    $email = wp_kses($pfs_data['email'],array());
    $cats = array(5);
    $newcats = '';
    $tags = '';
    $newtags = '';

	$imgAllowed = 0;
	$result = Array(
		'image'=>"",
		'error'=>"",
		'success'=>"",
		'post'=>""
	);
	$success = False;
    require_once('recaptchalib.php');
    $privatekey = "6LfvqcASAAAAAALRhWqQkH2IQ8IqnbJY637X1-1p";
    $resp = recaptcha_check_answer ($privatekey,
                                $_SERVER["REMOTE_ADDR"],
                                $_POST["recaptcha_challenge_field"],
                                $_POST["recaptcha_response_field"]);
    
    if (!$resp->is_valid) {
        // What happens when the CAPTCHA was entered incorrectly
        $result['error'] = "Incorrect reCAPTCHA: " . $resp->error;
    } else {
        if (array_key_exists('image',$pfs_files)) { 
            /* play with the image */
            switch (True) {
            case (1 < count($pfs_files['image']['name'])):
                // multiple file upload
                $result['image'] = "multiple";
                $file = $pfs_files['image'];
                for ( $i = 0; $i < count($file['tmp_name']); $i++ ){
                    if( ''!=$file['tmp_name'][$i] ){
                        $imgAllowed = (getimagesize($file['tmp_name'][$i])) ? True : (''==$file['name'][$i]);
                        if ($imgAllowed){
                            upload_image(array('name'=>$pfs_files["image"]["name"][$i], 'tmp_name'=>$pfs_files["image"]["tmp_name"][$i]));
                        } else {
                            $result['error'] = "Incorrect filetype. Only images (.gif, .png, .jpg, .jpeg) are allowed.";
                        }
                    }
                }
                break;
            case ((1 == count($pfs_files['image']['name'])) && ('' != $pfs_files['image']['name'][0]) ):
                // single file upload
                $file = $pfs_files['image'];
                $result['image'] = 'single';
                $imgAllowed = (getimagesize($file['tmp_name'][0])) ? True : (''==$file['name'][0]);
                if ($imgAllowed){
                    $upload[1] = wp_upload_bits($file["name"][0], null, file_get_contents($file["tmp_name"][0]));
                    //echo "<pre style=\"border:1px solid #ccc;margin-top:5px;\">".print_r($upload, true)."</pre>\n";
                    if (False === $upload[1]['error']){
                        $success[1] = True;
                    } else {
                        $result['error'] = "There was an error uploading the image: {$upload[1]['error']}";
                        return $result;
                    }
                } else {
                    $result['error'] = "Incorrect filetype. Only images (.gif, .png, .jpg, .jpeg) are allowed.";
                }
                break;
            default: 
                $result['image'] = 'none';
            }
        }
        if ( ('' != $result['error']) && ($pfs_options['pfs_imgfaildie']) )return $result;
        //echo "<pre style=\"border:1px solid #ccc;margin-top:5px;\">".print_r($upload, true)."</pre>\n";
        //echo "<pre style=\"border:1px solid #ccc;margin-top:5px;\">".print_r($success, true)."</pre>\n";
        /* manipulate $pfs_data into proper post array */
        if ($title != '' && $postcontent != '' && $name != '' && $email != '') {
            $content = $postcontent."<p>Suggested by <a href='mailto:$email'>$name</a></p>";
            global $user_ID;
            get_currentuserinfo();
            if (is_array($success)){
                foreach(array_keys($success) as $i){
                    //$i++;
                    $imgtag = "[!--image$i--]";
                    if (False === strpos($content,$imgtag)) $content .= "<br />$imgtag";
                    $content = str_replace($imgtag, "<img src='{$upload[$i]['url']}' class='pfs-image' />", $content);
                }
            } else {
                /* success is always an array if there was an image upload. If it's not an array, there was no image.
                $imgtag = "[!--image1--]";
                if (False === strpos($content,$imgtag)) $content .= "<br />$imgtag";
                $content = str_replace($imgtag, "<img src='{$upload[1]['url']}' class='pfs-image' />", $content);
                */
            }
            //if any [!--image#--] tags remain, they are invalid and should just be deleted.
            $content = preg_replace('/\[\!--image\d*--\]/','',$content);
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
            $postarr['post_content'] = apply_filters('comment_text', $content);
            $postarr['comment_status'] = $pfs_options['pfs_comment_status'];
            $postarr['post_status'] = $pfs_options['pfs_post_status'];
            $postarr['post_author'] = 5; //$user_ID;
            $postarr['post_category'] = $categories;
            $postarr['tags_input'] = implode(',',$tags);
            $postarr['post_type'] = $pfs_options['pfs_post_type'];
            //echo "<pre style=\"border:1px solid #ccc;margin-top:5px;\">".print_r($postarr, true)."</pre>\n";
            $post_id = wp_insert_post($postarr);
            
            if (0 == $post_id) {
                $result['error'] = __("Unable to insert post- unknown error.",'pfs_domain');
            } else {
                $result['success'] = __("Post added, please wait to return to the previous page.",'pfs_domain');
                $result['post'] = $post_id;
            }
        } else {
             $result['error'] = __("You've left a field empty. All fields are required",'pfs_domain');
        }
    }
	return $result;
}

/**
 * Upload images
 */
function upload_image($image){
    $file = wp_upload_bits( $image["name"], null, file_get_contents($image["tmp_name"]));
    if (false === $file['error']) {
        $wp_filetype = wp_check_filetype(basename($file['file']), null );
        $attachment = array(
         'post_mime_type' => $wp_filetype['type'],
         'post_title' => preg_replace('/\.[^.]+$/', '', basename($file['file'])),
         'post_content' => '',
         'post_status' => 'inherit'
        );
        $attach_id = wp_insert_attachment( $attachment, $file['file'] );
        // you must first include the image.php file
        // for the function wp_generate_attachment_metadata() to work
        require_once(ABSPATH . "wp-admin" . '/includes/image.php');
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file['file'] );
        wp_update_attachment_metadata( $attach_id,  $attach_data );
        return $attach_id;
    } else {
        //TODO: er, error handling?
        return "error";
    }
}

if (!empty($_POST)){
	$pfs = pfs_submit($_POST,$_FILES);
	echo json_encode($pfs);
	//echo "<pre style=\"border:1px solid #ccc;margin-top:5px;\">".print_r($pfs, true)."</pre>\n";
} else {
	/* TODO: translate following */
	echo "You should not be seeing this page, something went wrong. <a href='".get_bloginfo('url')."'>Go home</a>?";
}

//get_footer();
?>
