<?php
header('Content-type: text/css');
require('../../../../wp-load.php');

echo "/* CSS generated for the Post-From-Site plugin. Defaults can be edited through settings page in Wordpress. */\n\n";
$stylevars = Array('pfs_titlecolor','pfs_textcolor','pfs_bgcolor');
$pfs_options = get_option('pfs_options');
foreach($stylevars as $var) ${$var}=$pfs_options[$var];
?>
#wpadminbar .quicklinks #wp-admin-bar-post_from_site.menupop ul li *,
#wpadminbar .quicklinks #wp-admin-bar-post_from_site.menupop ul li a,
#wpadminbar .quicklinks #wp-admin-bar-post_from_site.menupop ul li a span,
#wpadminbar .quicklinks #wp-admin-bar-post_from_site.menupop ul li a strong {
	color: #555;
	text-shadow: none;
	white-space: nowrap;
}
#wpadminbar .quicklinks #wp-admin-bar-post_from_site.menupop ul li:hover > a,
#wpadminbar .quicklinks #wp-admin-bar-post_from_site.menupop ul li:hover > a span,
#wpadminbar .quicklinks #wp-admin-bar-post_from_site.menupop ul li:hover > a strong {
	background-color:white;
}

.pfs-post-box{
    background:<?php echo $pfs_bgcolor; ?>;
    border:1px solid #888;
    padding:20px 20px 10px;
    font-family:Verdana,Arial,"Bitstream Vera Sans",sans-serif;
    font-size:12px;
    line-height:18px;
    font-weight:normal;
    <?php echo (''==$pfs_textcolor)?"":"color:$pfs_textcolor;\n"; ?>
    text-align:center;
    -moz-border-radius:5px;
    -webkit-border-radius:5px;
    border-radius:5px;
    position:absolute;
    width:600px;
}
.pfs_postform {
    color:#522A00;
}
.pfs-post-box .closex {
    float:right;
    font-size:16px;
    font-weight:bold;
    text-decoration:none;
    color:#888;
    padding:0;
    margin:0;
    margin-top:-15px;
}
.pfs-post-box h1 {
    font-size:20px;
    margin-bottom:40px;
}
.pfs-post-box textarea {
    background-color:#fbfaf8;
    color:#522A00;
    width:530px;
    height: 200px;
    padding:5px;
    margin:5px 0;
    border:1px solid #82c6dc;
    -moz-border-radius:3px;
    -webkit-border-radius:3px;
    border-radius:3px;
    font-family:Verdana,Arial,"Bitstream Vera Sans",sans-serif;
    font-size:12px;
    line-height:18px;
}
.pfs-post-box .submit {
    float:right;
    padding:4px 10px;
    border:1px solid #2B7F9B;
    -moz-border-radius:3px;
    -webkit-border-radius:3px;
    border-radius:3px;
}
.pfs-post-box input {
    background-color:#fbfaf8;
    color:#522A00;
    padding:4px;
    margin:5px;
    border:1px solid #82c6dc;
    -moz-border-radius:3px;
    -webkit-border-radius:3px;
    border-radius:3px;
}
.pfs-post-box input.upload { 
    border:none;
}
#pfs_meta select {
    width:100%;
    border:1px solid #CCC;
    -moz-border-radius:3px;
    -webkit-border-radius:3px;
    border-radius:3px;    
}
.pfs-post-box h4 {
    display:inline;
    font-size:130%;
    <?php echo (''==$pfs_titlecolor)?"":"color:$pfs_titlecolor;"; ?>
}
.pfs-post-box .MultiFile-wrap {
    border:1px solid #ccc;
    padding:5px;
    margin-bottom:10px;
}
#pfs_catchecks, #pfs_tagchecks {
    float:left;
    width:255px;
    text-align:center;
    padding:0 0 10px;
    margin-left:35px;
}
#pfs_catchecks div, #pfs_tagchecks div { text-align:right; }
#pfs_meta h4 {
    margin-bottom:0;
    margin-left:-35px;
    display:block;
    text-align:left;
}
#pfs_meta input, #pfs_meta label { }
.pfs-post-box h3 {
    padding-bottom:40px;
}
#pfs-alert {
    border:1px solid #DDD;
    padding:10px;
    margin:3px;
    background-color:#FAF6CE;
    font-weight:normal;
}
#pfs-alert p {
    margin-bottom:0;
}

.pfs_postform textarea { width:637px; }
.pfs_postform #pfs_catchecks { width:100%; }
.pfs_postform label { display:block; float:left; width:60px; font-size:18px; margin:4px; }
.pfs_postform input { font-size:14px; }
.pfs_postform small { font-size:10px; }
.pfs_postform .submit { padding:3px 30px; width:110px; margin:10px 0 0; float:none; background-color:#E1D29A; }

#recaptcha_response_field { left:0 !important; }

#alert,.error{
    border:1px solid red;
    padding:10px;
    margin:3px;
    background-color:#eda9a9;
    font-weight:normal;
}
.clear {clear:both;}
<?php echo $pfs_options['pfs_customcss']; ?>
