<?php
/*
Plugin Name: FAQ Builder
Plugin URI: http://faqbuilder.squarecompass.com/
Description: The FAQ Builder for Wordpress plugin is an easy way to manage a Frequently Asked Questions page on your blog. FAQ Builder for Wordpress allows you to receive questions from your readers, and to manage and display them with answers.
Version: 0.6 Beta
Author: Square Compass
Author URI: http://squarecompass.com
*/

require_once(dirname(__FILE__)."/config.php"); //Load FAQ Builder Config File
/*
* Add Functions to WordPress
*/
//Register Hooks
register_activation_hook(__FILE__,"faq_build_install"); //Instalation Hook
//Add Actions
add_action("wp_head","faq_build_js_header"); //Add Javascript and Ajax to public pages
add_action('admin_menu','faq_build_navigation'); //Add FAQ Builder Tab in the menu
add_action('admin_print_scripts','faq_build_js_admin_header'); //Add Ajax to the admin side
//Add AJAX Calls
add_action('wp_ajax_faq_build_manage_category','faq_build_manage_category');
add_action('wp_ajax_faq_build_manage_question','faq_build_manage_question');
//Add Short Code
add_shortcode("faq_build_page","faq_build_page_shortcode"); //Add ShortCode for "Add Form"
add_shortcode("faq_build_ask","faq_build_ask_shortcode"); //Add ShortCode for "Add Form"
/*
*  Set admin Messages
*/
$faq_build_categories = faq_build_get_categories();
if(empty($faq_build_categories) || !is_array($faq_build_categories) || count($faq_build_categories) < 1)
	add_action("admin_notices","faq_build_warning_nocat");
//Warning functions
function faq_build_warning_nocat() {
	echo 
		"<div id='faq_build_warning' class='updated fade'>".
			"You must ".
			"<a href='".get_option("siteurl")."/wp-admin/admin.php?page=faq-categories'>add at least one category to FAQ Builder</a> ".
			"before you or your users will be able to add questions.".
		"</div>"
	;
}
/*
*  Installation Script
*/
function faq_build_install() {
	global $wpdb;
	global $faq_build_version;
	$sql = "";
	$cur_version = get_option("faq_build_version");
	require_once(ABSPATH.'wp-admin/includes/upgrade.php');
	//The Listings table is where all the user imputed data is stored
	if($wpdb->get_var("show tables like '".FAQBUILDDBQUESTION."';") != FAQBUILDDBQUESTION) {
		$wpdb->query( 
			"CREATE TABLE ".FAQBUILDDBQUESTION." (".
				"question_id int(11) NOT NULL AUTO_INCREMENT,".
				"category_id int(11) NOT NULL DEFAULT '1',".
				"status tinyint(1) DEFAULT '0' NOT NULL,".
				"date_submitted datetime NULL DEFAULT NULL,".
				"personal_info tinyint(1) DEFAULT '0' NOT NULL,".
				"name varchar(100) NULL DEFAULT NULL,".
				"email varchar(100) NULL DEFAULT NULL,".
				"url varchar(255) NULL DEFAULT NULL,".
				"state varchar(100) NULL DEFAULT NULL,".
				"question text NULL DEFAULT NULL,".
				"answer text NULL DEFAULT NULL,".
				"tags varchar(255) NULL DEFAULT NULL,".
				"PRIMARY KEY (question_id)".
			");"
		);
	}
	//The Categories table stores the categories
	if($wpdb->get_var("show tables like '".FAQBUILDDBCATEGORY."';") != FAQBUILDDBCATEGORY) {
		$wpdb->query( 
			"CREATE TABLE ".FAQBUILDDBCATEGORY." (".
				"category_id int(11) NOT NULL AUTO_INCREMENT,".
				"name varchar(100) NOT NULL,".
				"description text NULL DEFAULT NULL,".
				"PRIMARY KEY (category_id)".
			");"
		);
		//Add a General Category
		$category = new FAQ_BUILD_CATEGORY();
		$category->setName("General");
		$category->save();
	}
	//Update Version
	if(!add_option("faq_build_version",$faq_build_version));
		update_option("faq_build_version",$faq_build_version);
}
/*
*  Set Javascript/Ajax calls
*/
//Set Ajax Calls on Public Side
function faq_build_js_header() {
	wp_print_scripts(array("prototype","sack")); //Include Prototype and Ajax SACK library  
	faq_build_javascript_public();
	faq_build_javascript_autofill();
}
//Set Ajax Calls on Admin Side
function faq_build_js_admin_header() { 
	wp_print_scripts(array("prototype","sack")); //Include Prototype and Ajax SACK library 
	faq_build_javascript_admin();
	faq_build_javascript_autofill();
}
/*
*  Navigation
*/
function faq_build_navigation() { 
	add_menu_page(
		"FAQ Builder Manager",
		"FAQ Builder",
		8,
		__FILE__,
		"faq_build_show_question_manager",
		"http://faqbuilder.squarecompass.com/wp-content/themes/thematic/images/faq_logo_mini.png"
	); 
    add_submenu_page(__FILE__,"FAQ Builder Categories","Categories",8,"faq-categories","faq_build_show_category_manager");
}
/*
*  Shortcode
*/
//FAQ Page with Search Form
function faq_build_page_shortcode($atts) {
	extract(shortcode_atts(array('width'=>'100%'),$atts));
	return FAQBUILDSTYLESHEET."<div class='faq_build_default'>".faq_build_page($width)."</div>";
}
//Ask Form
function faq_build_ask_shortcode($atts) { 
	extract(shortcode_atts(array('width'=>'100%'),$atts));
	return FAQBUILDSTYLESHEET."<div class='faq_build_default'>".faq_build_ask(NULL,$width)."</div>";
}
/*
*  Admin Pages
*/
//Questions/Home
function faq_build_show_question_manager() { 
	echo 
		FAQBUILDSTYLESHEET.
		"<div class='wrap wpcf7'>".
			"<div id='icon-tools' class='icon32'><br></div>".
			"<h2>FAQ Builder</h2>".
			"<table class='widefat'>".
			  "<tr>".
				"<td>".
					"<b style='font-size:1.3em;'>How to Display FAQ Builder</b><br/>".
					"To display the <b>FAQ Builder Page</b> (with the search form), place the following code into the content of a page or post: ".
					"[faq_build_page]<br/>".
					"To display the <b>FAQ Builder Ask Form</b>, place the following code into the content of a page or post: ".
					"[faq_build_ask]<br/><br/>".
					"For more information, documentation, and/or help see ".FAQBUILDDOCUMENTATION.".".
				"</td>".
			  "</tr>".
			"</table>".
			"<hr/>".
			"<div id='faq_build_question_message'></div>".
			"<div id='faq_build_question_manager'>".faq_build_question_manager()."</div>".
			"<hr/>".
			"<table class='widefat'>".
			  "<tr>".
				"<td>".
					"<b style='font-size:1.3em;padding-bottom:5px;display:block;'>Status Legend:</b>".
					"<ul>".
						"<li><b>Pending:</b> New question not yet approved.</li>".
						"<li><b>Displayed:</b> Approved and showing on your FAQ page.</li>".
						"<li><b>Active:</b> Approved but NOT showing on your FAQ page, but searchable.</li>".
						"<li><b>Inactive:</b> Approved but not available to your users.</li>".
					"</ul>".
				"</td>".
			  "</tr>".
			"</table>".
		"<div>".
		FAQBUILDPOWERED
	;
}
//Categories
function faq_build_show_category_manager() {
    echo 
        FAQBUILDSTYLESHEET.
		"<div class='wrap wpcf7'>".
			"<div id='icon-tools' class='icon32'><br></div>".
			"<h2>FAQ Builder</h2>".
			"<table class='widefat'><tr><td>For help and documentation on how to use FAQ Builder see ".FAQBUILDDOCUMENTATION.".</td></tr></table>".
			"<hr/>".
			"<div id='faq_build_category_message'></div>".
			"<div id='faq_build_category_manager'>".faq_build_category_manager()."</div>".
		"</div>".
		FAQBUILDPOWERED
	;
}
