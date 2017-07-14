<?
session_start();
global $wpdb;
global $faq_build_version;
//Variables
$faq_build_version = "0.5.1 Beta";
//Constants
define("FAQBUILDDIRPATH",dirname(__FILE__)."/");
define("FAQBUILDRELATIVEPATH","/wp-content/plugins/faq-builder/");
define("FAQBUILDCALLBACK",get_option("siteurl").FAQBUILDRELATIVEPATH);
define("FAQBUILDREQUESTS",FAQBUILDCALLBACK."requests.php");
define("FAQBUILDDBQUESTION",$wpdb->prefix."faq_build_questions");
define("FAQBUILDDBCATEGORY",$wpdb->prefix."faq_build_category");
define("FAQBUILDPERPAGE",20);
define("FAQBUILDUNFOCUSEDCOLOR","#777777");
define("FAQBUILDFOCUSEDCOLOR","#000000");
define("FAQBUILDSTYLESHEET","<link rel='stylesheet' href='".FAQBUILDCALLBACK."main.css' type='text/css' media='screen'/>");
define(
	"FAQBUILDPOWERED",
	"<span class='faq_build_powered'>Powered by ".
	"<a href='http://faqbuilder.squarecompass.com/' target='_blank' title='FAQ Buider for WordPress'>FAQ Builder</a></span>"
);
define("FAQBUILDDOCUMENTATION","<a href='http://faqbuilder.squarecompass.com/documentation/' target='_blank'>FAQ Builder Help/Documentation</a>");
//Require Scripts
require_once(FAQBUILDDIRPATH."functions.php"); //Load FAQ Builder Functions Library 
require_once(FAQBUILDDIRPATH."category.class.php"); //Load FAQ Builder Category Class 
require_once(FAQBUILDDIRPATH."question.class.php"); //Load FAQ Builder Question Class 
require_once(FAQBUILDDIRPATH."javascript.php"); //Load FAQ Builder Question Class 
