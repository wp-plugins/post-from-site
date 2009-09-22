<?php
require('../../../wp-blog-header.php');
header('Content-type: text/css');

echo "/* CSS generated for the Post-From-Site plugin. Defaults can be edited through settings page in Wordpress. */\n\n";
/*move options into proper variables*/
$stylevars = Array('pfs_titlecolor','pfs_textcolor','pfs_bgcolor','pfs_bgimg');
foreach($stylevars as $var)	${$var}=get_option($var);
?>

.pfs_postbox{
	position:absolute !important; 
	top: 200px;
	left: 350px;
	z-index: 100000;
	width:600px;
	margin:auto;
	background:<?php echo $pfs_bgcolor; ?> url('<?php echo $pfs_bgimg; ?>') no-repeat top left;
	padding:10px;
	border:1px solid black;
	padding-top:55px;
	text-align:center;
	font-weight:normal;
	<?php echo (''==$pfs_textcolor)?"":"color:$pfs_textcolor;\n"; ?>
	font-family:'Trebuchet MS',sans-serif;
	font-size:9pt;
}
.pfs_postbox #closex {
	float:right;
	margin:0;
	margin-top:-50px;
	padding:0;
}
.pfs_postbox #closex a {
	padding:2px 5px;
	text-decoration:none;
	font-size:14px;
	color:#EEE;
	background-color:#BBB;
}
.pfs_postbox #closex a:hover {
	background-color:#AAA;
}
.pfs_postbox textarea {
	width:550px;
	margin:5px 25px;
}
.pfs_postbox #pfs_span {
	padding:7px 0;
	float:left;
}
.pfs_postbox #submit {
	float:right;
	padding:7px 25px;
}
.pfs_postbox h4 {
	display:inline;
	font-size:130%;
	<?php echo (''==$pfs_titlecolor)?"":"color:$pfs_titlecolor;"; ?>
}
#pfs_catchecks, #pfs_tagchecks {
	float:left;
	width:40%;
	text-align:center;
	padding:10px;
	padding-top:0;
	margin-left:35px;
}
#pfs_meta h4 {
	margin-bottom:0;
	margin-left:-35px;
	display:block;
	text-align:left;
}
#pfs_meta input, #pfs_meta label {
	margin:0;
	margin-top:5px;
}
#alert{
	border:1px solid red;
	padding:10px;
	margin:3px;
	background-color:#eda9a9;
	color:bold;
	font-weight:normal;
}
.clear {clear:both;}
<?php echo get_option('pfs_customcss'); ?>
