<?
require('../../../wp-blog-header.php'); //Load WordPress
require(dirname(__FILE__).'/config.php'); //Load FAQ Builder Config File
//Output Captcha if Requested
if($_REQUEST["action"] == "captcha_src") {
	$id = faq_build_clean_input($_REQUEST["id"],"alpha_numeric");
	die(faq_build_captcha($id));
}
//Clean Post Input
$v = faq_build_clean_input($_POST,array("action"=>"string"));
//Process Data
$response = "";	
switch(@$v["action"]) {
	case "ask": $response = faq_build_manage_question(true); break;
	case "search": 
		$v = faq_build_clean_input($_POST,array("category_id"=>"id","search_term"=>"text","offset"=>"numeric","form"=>"alpha_numeric"));
		//Clean Default Text
		$q = new FAQ_Build_Question();
		if(@$v["search_term"] == $q->defaultSearch())
			$v["search_term"] = "";
		unset($q);
		//Get Message and Page Divs 
		$response .= 
			"var form = document.getElementById('".@$v["form"]."');".
			"var manager = document.getElementById('faq_build_page_".@$v["form"]."');".
			"var message = document.getElementById('message_".@$v["form"]."');".
			"manager.innerHTML = '".str_replace("'","\'",faq_build_display_faq(@$v["form"],@$v["search_term"],@$v["category_id"],@$v["offset"]))."';".
			"message.className = '';".
			"message.innerHTML = '';".
			"form.search.disabled = false;"
		;
	break;
}
die($response);
