<?php
/*
*  Public Side: Ask, Display, Search Functions
*/
function faq_build_page($width = "100%") { //Build FAQ Page with Search Box
	//Validate Input
	if(!is_string($search_term) && !is_numeric($search_term)) $search_term = ""; 
	if(!is_string($width) && !is_numeric($width)) $width = "100%";
	//Display Search Form
	 $id = faq_build_random_string();
	 $form = 
	 	"<div style='width:$width'>".
			"<form id='$id' name='faq_build_search' onSubmit='faq_build_search_question(this);return false;'>".
				"<b>Search the FAQ:</b> ".
				"<input ".
					"type='text' name='search_term' class='faq_build_text_small' ".
					"faq_build_clear_autofill(this);' onClick='faq_build_clear_autofill(this);'".
				"/> ".
				faq_build_array_to_select("category_id",faq_build_get_categories(),0,"--All Categories",0,"faq_build_select_small").
				"<input type='submit' name='search' value='Search'/>".
			"</form><br/>".
			"<div id='message_$id'></div>".
			"<div id='faq_build_page_$id'>".faq_build_display_faq($id)."</div>".
		"</div>"
	;
	return $form;
}
function faq_build_display_faq($id,$search = "",$category_id = 0,$offset = 0) {
	global $wpdb;
	//Validate Input (Assumes input is clean... So CLEAN IT FIRST!)
	if(!is_string($id) && !is_numeric($id)) return "";
	if(!is_string($search) && !is_numeric($search)) $search = "";
	if(!is_numeric($category_id)) $category_id = 0;
	if(!is_numeric($offset)) $offset = 0;
	//Build Page Header
	$pagination = "";
	$header = "";
	$status = "display";
	if(empty($search) && empty($category_id)) { //Build Pagination
		//Get the number of questions
		$count = $wpdb->get_var("SELECT COUNT(question_id) FROM ".FAQBUILDDBQUESTION." WHERE status=".FAQ_Build_Question::DISPLAY.";");
		//Build Pagination
		if($count > FAQBUILDPERPAGE) {
			$pagination .= "<div class='faq_build_pagination'><small>Page:</small>";
			$index = 0;
			$c = $count;
			do{
				$pagination .= 
					"&nbsp;<a onClick='faq_build_change_page(\"$id\",$index);'".($index == $offset?" style='font-weight:bold;'":"").">".
						($index+1).
					"</a>&nbsp;"
				;
				$index++;
				$c -= FAQBUILDPERPAGE;
			} while($c > 0);
			$pagination .= "</div>";
		}
	} else { //Build Search Page Header
		$status = "public";
		$header .= "<center style='font-weight:bold;'>Search Results";
		if(!empty($search)) $header .= " for \"$search\"";
		if(!empty($category_id)) {
			$c = new FAQ_Build_Category($category_id);
			if($c->getID() == $category_id) 
				$header .= " in the \"".$c->outputName()."\" category";
		}
		$header .= ".<br/><small><a onClick='faq_build_change_page(\"$id\",0);'>&laquo; Back to FAQ Page</a></small></center> ";
	}
	$questions = faq_build_get_questions($status,$search,$category_id,$offset,($status == "public"?false:FAQBUILDPERPAGE));
	$page = $pagination.$header;
	foreach($questions as $q) {
		$page .= $q->display()."<br/>";
	} if(count($questions) < 1) $page .= "No Answers Found.";
	$page .= $pagination;
	return $page;
}
function faq_build_ask($id = NULL,$width = "100%") {
	//Validate Input
	if((!empty($id) && !is_numeric($id))) return "";
	if(!is_string($width) && !is_numeric($width)) $width = "100%";
	//Get FAQ Question And Return Form
	$q = new FAQ_Build_Question($id);
	return $q->displayEditor(!empty($id),$width);
}
/*
*  Question Managing Functions
*/
function faq_build_question_manager() { return faq_build_manager("question",faq_build_get_questions("all","",0,0,"all")); }
function faq_build_manage_question($public = true) {
	$v = faq_build_clean_input($_POST,array("question_id"=>"id","acao"=>"alpha","form"=>"alpha_numeric","captcha"=>"alpha_numeric"));
	if($public) $v["acao"] = "save";
	//Build Question
	$q = new FAQ_Build_Question(@$v["question_id"]);
	$response = "";
	if($public) {
		$response .= 
			"var manager = document.getElementById('".$v["form"]."');".
			"var message = document.getElementById('message_".$v["form"]."');"
		;
	} else {
		$response .= 
			"var manager = document.getElementById('faq_build_question_manager');".
			"var message = document.getElementById('faq_build_question_message');"
		;
	}
	switch(@$v["acao"]) {
		case "save":case "update": 
			$errors = $q->validateArray($_POST);
			$passed_captcha = false; //Keep track if the captcha image passed (don't reset it later if it did)
			if($public && (!empty($v["form"]) && $_SESSION["faq_build_captcha_".$v["form"]] != $v["captcha"])) //Validate Captcha
				$errors .= "Entered text did not match the image, please re-enter the text from the image.<br/>";
			elseif($public) $passed_captcha = true;
			if($public) unset($v["question_id"]);
			if(empty($errors)) { 
				$q->fromArray($_POST,true); //Save Changes
				//Clear Empty Values
				if(empty($_POST["status"])) $q->setPending();
				if(empty($_POST["personal_info"])) $q->setPersonalInfo("");
				if(empty($_POST["url"])) $q->setURL("");
				if(empty($_POST["state"])) $q->setState("");
				if(empty($_POST["answer"]) || $public) $q->setAnswer(""); //Only the admin can add HTML
				$q->save(); //Save Question
				//Send Email Response
				if($public) { //Notify the admin that a new question has been submitted.
					wp_mail(
						get_option("admin_email"), 
						stripslashes($q->outputName(true))." has asked a question", 
						stripslashes($q->outputName(true))." has asked a question on ".get_option("blogname").":\n\n".
							stripslashes($q->outputQuestion(true))."\n\n".
							"Login at ".get_option("blogname")."/wp-login.php and click on the FAQ Builder tab to answer the question."
					);
				} elseif($_POST["send_email"] == 1) { //Notify submiter of answer.
					wp_mail(
						$q->getEmail(), 
						"The answer to your question", 
						nl2br(
							stripslashes($q->outputName(true)).",\n".
							"In response to your question on ".get_option("blogname").":\n\n".
							"<b>Your Question</b>:\n".stripslashes($q->outputQuestion(true))."\n\n".
							"<b>Answer</b>:\n".stripslashes($q->outputAnswer(true))
						),
						'Content-type: text/html; charset=iso-8859-1'."\r\n".
						'From: '.get_option("blogname").' <no-reply@'.get_option("siteurl").'>' . "\r\n"
					);
				}
				//Return Happy Message
				if($public) {
					$response .= 
						"faq_build_reset_form(manager);".
						"message.className = 'faq_build_message';message.innerHTML = 'Thank you, your question has been submitted.';"
					;
				} else {
					$response .= 
						"manager.innerHTML = '".str_replace("'","\'",faq_build_question_manager())."';manager.submit.disabled = false;".
						"message.className = 'faq_build_message';message.innerHTML = 'Question Updated';"
					;
				}
			} else {
				$response .= "message.className = 'faq_build_error';message.innerHTML = '$errors';manager.submit.disabled = false;";
				if($public && !$passed_captcha) //reset captcha
					$response .= "faq_build_reset_captcha('".$v["form"]."');";
			}
		break; 
		case "add":case "edit": 
			$response .= "manager.innerHTML = '".str_replace("'","\'",$q->displayEditor(!$public))."';message.className = '';message.innerHTML = '';";
		break;
		case "delete": 
			$q->delete();
			$response .= 
				"manager.innerHTML = '".str_replace("'","\'",faq_build_question_manager())."';".
				"message.className = 'faq_build_message';message.innerHTML = 'Question Deleted';"
			;
		break;
		default:
			$response .= "manager.innerHTML = '".str_replace("'","\'",faq_build_question_manager())."';message.className = '';message.innerHTML = '';";
		break;
	}
	if($public) return $response;
	die($response);
}
/*
*  Category Managing Functions
*/
function faq_build_category_manager() { return faq_build_manager("category",faq_build_get_categories()); }
function faq_build_manage_category() { 
	//Get General Accepted Values
	$v = faq_build_clean_input($_POST,array("category_id"=>"id","acao"=>"alpha"));
	//Build Question
	$c = new FAQ_Build_Category(@$v["category_id"]);
	$response = "var message = document.getElementById('faq_build_category_message');var manager = document.getElementById('faq_build_category_manager');";
	switch(@$v["acao"]) {
		case "save":case "update": 
			$v = faq_build_clean_input($_POST,array("category_id"=>"id","acao"=>"alpha","name"=>"text","description"=>"text","form"=>"alpha_numeric"));
			$errors = $c->validateArray($v);
			if(empty($errors)) {
				//Save Question
				$c->fromArray($v);
				if(empty($v["description"]))
					$c->setDescription("");
				$c->save();
				//Return Happy Message
				$response .= 
					"manager.innerHTML = '".str_replace("'","\'",faq_build_category_manager())."';".
					"message.className = 'faq_build_message';message.innerHTML = 'Category Updated';"
				;
			} else 
				$response .= "message.className = 'faq_build_error';message.innerHTML = '$errors';";
		break; 
		case "add":case "edit": 
			$response .= "manager.innerHTML = '".str_replace("'","\'",$c->displayEditor())."';message.className = '';message.innerHTML = '';";
		break;
		case "delete": 
			$c->delete();
			$response .= 
				"manager.innerHTML = '".str_replace("'","\'",faq_build_category_manager())."';".
				"message.className = 'faq_build_message';message.innerHTML = 'Category Deleted';"
			;
		break;
		default:
			$response .= "manager.innerHTML = '".str_replace("'","\'",faq_build_category_manager())."';message.className = '';message.innerHTML = '';";
		break;
	}
	die($response);
}
/*
* Input/Output Cleaning Functions
*/ 
function faq_build_clean_input($input,$keys = NULL) {
	$v = array();
	//Convert strings and numbers to an array (kick out empty)
	if(empty($input))
		return NULL;
	elseif(is_string($input) || is_numeric($input)) {
		$v = array("faq_build_value"=>$input);
		if(!empty($keys) && is_string($keys))
			$keys = array("faq_build_value"=>$keys);
		elseif(empty($keys) || !is_array($keys) || count($keys) != 1)
			$keys = array("faq_build_value"=>(is_string($input)?"string":"numeric"));
	} elseif(is_array($v))
		$v = $input;
	//Clean v
	$v = stripslashes_deep($v);
	if(is_array($keys) && count($v) > 0 && count($keys) > 0) {
		$res = array();
		$allowable_types = array(
			"abs","alpha","alpha_numeric","basic_html","d","date","decimal","email","id","n","num","numeric","phone","s","str","string","text","url","website"
		);
		$line_breaks = array("\r\n","\n\r","\n","\r");
		foreach($v as $key=>$value) {
			if(
				!empty($keys[$key]) && (is_string($key) || is_numeric($key)) && 
				in_array($keys[$key],$allowable_types) && //Validate that the key is valid and wanted
				(is_string($value) || is_numeric($value)) //Validate that the value is a string or a number
			) {
				//Ensure that the key is a safe value
				if(is_string($key))
					while(strpos($key,"\\") !== false)
						$key = stripslashes($key);
				$key = wp_kses(faq_build_strip_multiquotes(stripcslashes(str_replace($line_breaks," ",trim($key)))),array());
				if(!preg_match("/^[\w\s\-_]+$/",$key))
					continue;
				//Ensure that the value is a safe value.
				if(is_string($value))
					while(strpos($value,"\\") !== false)
						$value = stripslashes($value);
				//Get Permitted HTML
				$permitted_html = array();
				$line_break_replacement = " ";
				if(strtolower($keys[$key]) == "basic_html") {
					$permitted_html = array('a' => array('href' => array(),'target' => array(),'title' => array()),'br' => array(),'em' => array(),'strong' => array());
					$line_break_replacement = "<br />";
				}
				//Kill some evil scripts
				$val = 
					wp_kses(
						faq_build_strip_multiquotes(stripcslashes(str_replace($line_breaks,$line_break_replacement,trim($value)))),
						$permitted_html
					)
				;
				switch(strtolower($keys[$key])) { //Verify that the value is acceptable
					case "abs": //A positive or zero integer
						if(is_numeric($val) && intval($val) == $val && $val >= 0)
							$res[$key] = absint($val); //Save the value to the new array
					break;
					case "alpha": //String containing only letters
						if(preg_match("/^[a-zA-Z]+$/",$val))
							$res[$key] = $val; //Save the value to the new array
					break;
					case "alpha_numeric": //String containing only letters and numbers (no decimals)
						if(preg_match("/^[a-zA-Z0-9]+$/",$val))
							$res[$key] = $val; //Save the value to the new array
					break;
					case "date": //a String of the form ##-##-## ##:##:##
						if(preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}\s[0-9]{2}:[0-9]{2}:[0-9]{2}$/',$val))
							$res[$key] = $val; //Save the value to the new array
					break;
					case "decimal":case "d": //Any real number
						if(is_numeric($val))
							$res[$key] = $val; //Save the value to the new array
					break;
					case "id": //A positive, non-zero integer
						if(is_numeric($val) && intval($val) == $val && $val > 0)
							$res[$key] = absint($val); //Save the value to the new array
					break;
					case "email": //An email
						if(preg_match('/^[A-z]+[A-z0-9\._\-]*[@][A-z0-9_\-]+([.][A-z0-9_\-]+)*[.][A-z]{2,4}$/',$val))
							$res[$key] = $val; //Save the value to the new array
					break;
					case "phone": //A phone number
						if(preg_match('/^[\d\w\s\(\)\+\.-]{7,25}$/',$val))
							$res[$key] = $val; //Save the value to the new array
					break;
					case "numeric":case "n":case "num": //Integer number
						if(is_numeric($val) && round($val) == $val)
							$res[$key] = $val; //Save the value to the new array
					break;
					//A string containing leters, numbers, basic symbols, whitespace (no line breaks), and html safe characters.
					case "string":case "s":case "str":
						$val = htmlentities($val,ENT_NOQUOTES);
						if(preg_match("/^[\w\s_-]+$/",$val)) // /^[a-zA-Z0-9\s\/\.\!\?\"'&,:;_-]+$/ 
							$res[$key] = $val; //Save the value to the new array
					break;
					case "text":case "basic_html":
						if(is_string($val))
							$res[$key] = $val;
					break;
					case "website":case "url": //A URL
						if(substr($val,0,7) != "http://" && substr($val,0,8) != "https://") 
							$val = "http://$val";
						if(preg_match('/^http{1}s?:{1}\/\/{1}\w+[\w\-\.]*\.\w{2,4}[\w\?\/\-\.&_=]*$/',$val))
							$res[$key] = $val; //Save the value to the new array
					break;
				} 
			}
		}
		//If input was not an array, then return a variable, not an array.
		if(is_string($input) || is_numeric($input))
			return @$res["faq_build_value"];
		return $res;
	}
	return array();
}
function faq_build_clean_output($values,$decode = false) {
	$v = array();
	$res = array();
	//If values is a string or number, put it in an array (kick out empty)
	if(empty($values))
		return NULL;
	elseif(is_string($values) || is_numeric($values))
		$v = array("faq_build_value"=>$values);
	elseif(is_array($values))
		$v = $values;
	//Clean array to be outputed to HTML
	if(is_array($v) && count($v) > 0) {
		$v = stripslashes_deep($v);
		foreach($v as $key=>$value)
			if((is_string($key) || is_numeric($key)) && (is_string($value) || is_numeric($value))) {
				$res[$key] = wp_specialchars(faq_build_strip_multiquotes($value),ENT_QUOTES);
				if($decode)
					$res[$key] = str_replace("&#039;","\'",html_entity_decode($res[$key]));
			} elseif((is_string($key) || is_numeric($key)) && is_array($value))
				$res[$key] = faq_build_clean_output($value);
	}
	//If input was not an array, then return a variable, not an array.
	if(is_string($values) || is_numeric($values))
		return @$res["faq_build_value"];
	return $res;
}
function faq_build_strip_multiquotes($string) {
	if(is_string($string)) { //Only strings will have quotes
		$res = stripslashes_deep($string);
		$new_string = "";
		do {
			//Remove Multiple Double Quotes
			while($res != str_replace(array('""',"&quot;&quot;"),'"',$res))
				$res = str_replace(array('""',"&quot;&quot;"),'"',$res);
			//Remove Multiple Single Quotes
			while($res != str_replace(array("''","&#039;&#039;"),"'",$res))
				$res = str_replace(array("''","&#039;&#039;"),"'",$res);
			$new_string = str_replace(array("''","&#039;&#039;"),'"',str_replace(array('""',"&quot;&quot;"),"'",$res));
		} while($res != $new_string);
		return $res;
	}
	return $string;
}
function faq_build_is_faq_object($thing) { 
	return is_object($thing) && (get_class($thing) == "FAQ_Build_Question" || get_class($thing) == "FAQ_Build_Category");
}
/*
*  Display Functions
*/
function faq_build_form($id,$name,$heading,$rows,$submit,$submit_value,$hidden = "",$width = "100%") {
	//Validate Input
	if(
		(!is_string($id) && !is_numeric($id)) || !is_string($name) || !is_string($heading) || !is_array($rows) || !is_string($submit) || 
		!is_string($submit_value) || !is_string($hidden) || (!is_string($width) && !is_numeric($width))
	) 
		return false;
	//Build Form
	$form = 
		"<form id='$id' name='$name' method='POST' width='$width' class='faq_build_default' onSubmit='$submit(this);return false;'>".
			"<table width='100%' border='0' cellspacing='0' cellpadding='0'>".
			  "<thead><tr><td class='faq_build_title' colspan='2'>$heading</td></tr></thead>".
			  "<tbody>".
			  	"<tr><td colspan='2'><div id='message_$id'></div></td></tr>"
	;
	foreach($rows as $title=>$input)
		$form .= "<tr><td class='faq_build_form_text'>$title</td><td class='faq_build_form_input'>$input</td></tr>";
	$form .= 
				"<tr>".
					"<td class='faq_build_form_text'>&nbsp;</td>".
					"<td class='faq_build_form_input'>".
						"<input type='submit' name='submit' value='$submit_value'/>".$hidden." ".FAQBUILDPOWERED."<br/>".
						"<span class='faq_build_required'>* required</span><br/>".
					"</td>".
				"</tr>".
			  "</tbody>".
			"</table>".
		"</form>"
	;
	return $form;
}
function faq_build_manager($type,$objects,$width = "100%") {
	//Validate Input
	if(!is_string($type) || !is_array($objects) || (!is_string($width) && !is_numeric($width))) 
		return false;
	//Get Type
	$new = NULL;
	$lable = "";
	$add_new_function = "";
	$add_new_lable = "";
	switch(strtolower($type)) {
		case "category":case "faq_build_category": 
			$new = new FAQ_Build_Category();
			$lable = "Categories";
			$add_new_function = "faq_build_category(\"add\",null);";
			$add_new_lable = "Category";
		break;
		default:
			$new = new FAQ_Build_Question();
			$lable = "Questions";
			$add_new_function = "faq_build_question(\"add\",null);";
			$add_new_lable = "Question";
		break;
	}
	//Build Manager
	$form = 
		"<h3>Manage $lable</h3><p><a style='cursor:pointer;' onClick='$add_new_function'>Add New $add_new_lable</a></p>".
		"<table width='100%' border='0' cellspacing='0' cellpadding='0' class='widefat faq_build_default'>".
		  "<thead><tr>".$new->displayTableHeader()."</tr></thead>".
			  "<tbody>"
	;
	foreach($objects as $o)
		if(faq_build_is_faq_object($o))
			$form .= "<tr>".$o->displayTableRow()."</tr>";
	$form .= "</tbody></table></form>"; 
	return $form; 
}
function faq_build_array_to_select($id,$data,$current_value = 0,$default = "None",$default_value = 0,$class = "faq_build_select") {
	//Validate Input
	if(
		!is_string($id) || 
		!is_array($data) || 
		(!empty($current_value) && !is_numeric($current_value) && !is_string($current_value))  || 
		(!is_string($default) && !is_bool($default)) || 
		(!is_numeric($default_value) && !is_string($default_value)) ||
		!is_string($class)
	)
		return "";
	//Build Select Box
	$select = "<select id='$id' name='$id' class='$class'>";
	if($default !== false)
		$select .= "<option value='$default_value'>$default</option>";
	foreach($data as $value=>$tag) {
		if(is_numeric($value) && (is_string($tag) || is_numeric($tag)))
			$select .= "<option value='$value' ".($current_value == $value?"selected":"").">$tag</option>";
		elseif(faq_build_is_faq_object($tag))
			$select .= "<option value='".$tag->outputID()."' ".($current_value == $tag->getID()?"selected":"").">".$tag->outputName()."</option>";
	}
	$select .= "</select>";
	return $select;
}
function faq_build_excerpt($string) {
	if(!is_string($string))
		return "";
	$excerpt = wp_kses(faq_build_strip_multiquotes($string),array());
	if(strlen($excerpt) > 40) $excerpt = substr($excerpt,0,40)."...";
	return $excerpt;
}
/*
*  Captcha Functions
*/
function faq_build_captcha($reference = NULL) {
	$id = faq_build_clean_input($reference,"alpha_numeric");
	//Get Random String$string = '';
	$code = faq_build_random_string(5,"ABCDGHJKMNPRUVWXY346789");
	$_SESSION["faq_build_captcha_$id"] = $code;
	//Create Image
	$width = 60; 
	$height = 20; 
	$image = imagecreate($width, $height); 
	imagefill($image,0,0,imagecolorallocate($image,255,255,255)); //Set Backround
	//Draw 2 rectangles
	imagefilledrectangle($image,rand(10,50),rand(3,17),rand(5,55),rand(5,20),imagecolorallocate($image,rand(175,255),rand(175,255),rand(175,255)));
	imagefilledrectangle($image,rand(10,50),rand(3,17),rand(5,55),rand(5,20),imagecolorallocate($image,rand(175,255),rand(175,255),rand(175,255)));
	//Draw an elipse
	imageellipse($image,rand(10,50),rand(3,17),rand(5,55),rand(5,20),imagecolorallocate($image,rand(175,255),rand(175,255),rand(175,255)));
	//Draw 2 lines to make it a little bit harder for any bots to break 
	imageline($image,0,3+rand(0,5),$width,3+rand(0,5),imagecolorallocate($image,rand(0,255),rand(0,255),rand(0,255)));
	imageline($image,0,11+rand(0,5),$width,11+rand(0,5),imagecolorallocate($image,rand(0,255),rand(0,255),rand(0,255)));
	//Add randomly generated string to the image
	$start = 3;
	for($i=0;$i<5;$i++) {
		$start += rand(0,3);
		imagestring($image,5,$start,rand(2,5),substr($code,$i,1),imagecolorallocate($image,rand(0,125),rand(0,125),rand(0,125)));
		$start += 9;
	}
	imagerectangle($image,0,0,59,19,imagecolorallocate($image,0,0,0)); //Put Border around image
	header("Content-Type: image/jpeg"); //Add JPG Header
	imagejpeg($image); //Output the newly created image in jpeg format 
	imagedestroy($image); //Free up resources
} 
/*
*  Misc Functions
*/
function faq_build_random_string($length = 10,$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789") {
	if(!is_numeric($length)) $length = 10; //Validate Input
	if(!is_string($chars)) $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789"; //Validate Input
	//Get Random String
	$code = "";
	$char_len = strlen($chars)-1;
    for($i=0;$i<$length;$i++) {
        $pos = mt_rand(0,strlen($chars)-1);
        $code .= $chars{$pos};
    }
	return $code;
}
