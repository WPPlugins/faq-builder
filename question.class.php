<?php
/*
*  FAQ_Build_Question Class
*
*  This object defines the display and database interaction for questions in FAQ Builder 
*/
class FAQ_Build_Question {
	/*
	*  Define Constants: Many of these may be pulled from the database in the future
	*/
	//Display/Search Status
	const PENDING = 0;
	const INACTIVE = 3;
	const ACTIVE = 2;
	const DISPLAY = 1;
	//Personal Info Display Status
	const OFF = 0;
	const ON = 1;
	//Other
	const PUBLICADJECTIVE = "your";
	const ADMINADJECTIVE = "lister's";
	/*
	*  Object Variables
	*/
	private $question_id;
	private $category_id;
	private $status;
	private $date_submitted;
	private $personal_info;
	private $name;
	private $email;
	private $url;
	private $state;
	private $question;
	private $answer;
	private $tags;
	/*
	*  Constructor: returns true only if data was loaded
	*/
	public function __construct($seed = NULL) {
		if(is_numeric($seed) && $seed > 0)
			$this->load($seed);
		elseif(is_array($seed))
			$this->fromArray($seed);
		$this->validate();
		return $this;
	}
	/*
	*  Display Functions
	*/
	public function display() {
		return 
			"<div class='faq_build_question'>".$this->outputQuestion()."</div>".
			"<div class='faq_build_date'>".$this->displayDateSubmitted()." ".$this->displayPersonalInfo()."</div>".
			"<div class='faq_build_answer'>".$this->outputAnswer()."</div>"
		;
	}
	public function displayEditor($edit_view = false,$width = "100%") {
		//Validate Input
		if(!is_bool($edit_view)) $edit_view = false;
		if(!is_string($width) && !is_numeric($width)) $width = "100%";
		//Build Rows
		$id = faq_build_random_string(); //Get a random string to identify this form with
		$rows = array(
			"Name *"=>
				"<input ".
					"type='text' name='submitters_name' class='faq_build_text' max_length='100' value='".$this->outputName()."' ".
					"onFocus='faq_build_clear_autofill(this);' onClick='faq_build_clear_autofill(this);'".
				"/>",
			"Email *"=>
				"<input ".
					"type='text' name='email' class='faq_build_text' max_length='100' value='".$this->outputEmail()."' ".
					"onFocus='faq_build_clear_autofill(this);' onClick='faq_build_clear_autofill(this);'".
				"/>",
			"Website"=>
				"<input ".
					"type='text' name='url' class='faq_build_text' max_length='255' value='".$this->outputURL()."' ".
					"onFocus='faq_build_clear_autofill(this);' onClick='faq_build_clear_autofill(this);'".
				"/>",
			"State/Province"=>
				"<input ".
					"type='text' name='state' class='faq_build_text' max_length='100' value='".$this->outputState()."' ".
					"onFocus='faq_build_clear_autofill(this);' onClick='faq_build_clear_autofill(this);'".
				"/>",
			"&nbsp;"=>
				"<input ".
					"type='checkbox' name='personal_info' value='".self::ON."'".
					(empty($this->question_id) || $this->personal_info != self::OFF?" checked":"").
				"/> ".
				"Yes, display my name and location as the submitter of this question (including a link to my site).",
			"Category *"=>faq_build_array_to_select("category_id",faq_build_get_categories(),$this->getCategoryID(),"Select Category",0),
			"Question *"=>
				"<textarea ".
					"name='question' class='faq_build_textarea' ".
					"onFocus='faq_build_clear_autofill(this);' onClick='faq_build_clear_autofill(this);".
				"'>".$this->outputQuestion()."</textarea>"
		);
		if($edit_view) {
			$rows["Tags"] = 
				"<input ".
					"type='text' name='tags' class='faq_build_text' max_length='255' value='".$this->outputTags()."' ".
					"onFocus='faq_build_clear_autofill(this);' onClick='faq_build_clear_autofill(this);'".
				"/>"
			;
			$rows["Answer"] = 
				"<textarea name='answer' class='faq_build_textarea' onFocus='faq_build_clear_autofill(this);' onClick='faq_build_clear_autofill(this);'>".
					preg_replace("/<br{1}\s*\/?>{1}/i","\\n",$this->outputAnswer()).
				"</textarea><br/>".
				"<small>".
					"FAQ Builder supports the use of bold (strong), line breaks (br), ".
					"links (a), and italics (em) HTML tags in the Answer field.".
				"</small>"
			;
			$rows["Status"] = faq_build_array_to_select("status",$this->getStatuses(),$this->getStatus(),false);
			$rows["&nbsp;&nbsp;"] = 
				"<input type='checkbox' name='send_email' ".($this->status == self::PENDING?" checked":"")."/> ".
				"Send email with answer".($this->outputName() != NULL?" to ".$this->outputName():"")
			;
		} else
			$rows["<img id='captcha_image_$id' src='".FAQBUILDREQUESTS."?action=captcha_src&id=$id' border='0' align='right'/>"] = 
				"<input ".
					"type='text' name='captcha' class='faq_build_text_small' maxlength='20' ".
					"onFocus='faq_build_clear_autofill(this);' onClick='faq_build_clear_autofill(this);'".
				"/>&nbsp;&nbsp;&nbsp;".
				"<a href='javascript:faq_build_reset_captcha(&quot;$id&quot;);'>I can't read the text, please reset it.</a>"
			;
		//Build Heading & Hidden
		$heading = "";
		$hidden = "";
		if(!empty($this->question_id)) {
			$heading = "Answer/Update Question";
			$hidden = "<input type='hidden' name='question_id' value='".$this->outputID(true)."'/>";
		} elseif(!$edit_view)
			$heading = "Add Question";
		else
			$heading = "Ask a Question";
		if($edit_view) 
			$hidden .= "<input type='button' name='cancel' value='Cancel' onClick='faq_build_question(\"cancel\",null)'/>";
		//Return form
		return faq_build_form($id,"faq_build_manage_question",$heading,$rows,"faq_build_ask_question",($edit_view?"Answer":"Ask"),$hidden);
	}
	public function displayTableHeader() { 
		return 
			"<th width='7%'>Status</th>".
			"<th width='10%'>Category</th>".
			"<th width='30%'>Question</th>".
			"<th width='30%'>Answer</th>".
			"<th width='10%'>Name</th>".
			"<th width='15%'>Actions</th>"
		; 
	}
	public function displayTableRow() {
		$answer_excerpt = wp_kses($this->outputAnswer(),array());
		if(strlen($answer_excerpt) > 50) $answer_excerpt = substr($answer_excerpt,0,50)."...";
		return 
			"<td>".$this->displayStatus()."</td><td>".$this->outputCategoryName()."</td>".
			"<td>".$this->outputQuestionExcerpt()."</td><td>".$this->outputAnswerExcerpt()."</td>".
			"<td>".(empty($this->url)?$this->outputName():"<a href='".$this->outputURL()."' target='_blank'>".$this->outputName()."</a>")."</td>".
			"<td>".
				"<a href='javascript:faq_build_question(\"edit\",\"".$this->outputID()."\");' id=>edit</a> | ".
				"<a ".
					"onClick='faq_build_delete_question(\"".$this->outputID()."\",this.name);' name='".$this->outputName()."'".
				">delete</a>".
			"</td>"
		;
	}
	/*
	*  Display Values Functions
	*/
	public function displayStatus() { 
		$list = $this->getStatuses(); 
		return @$list[absint($this->status)];
		return false; 
	}
	public function displayPersonalInfoStatuses() { 
		$list = $this->getPersonalInfoStatuses(); 
		if(is_numeric($this->personal_info)) 
			return @$list[intval($this->getPersonalInfo())];
		return false; 
	}
	public function displayPersonalInfo() {
		if($this->getPersonalInfo() == self::ON)
			return 
				"by ".
				(empty($this->url)?$this->outputName():"<a href='".$this->url."' target='_blank'>".$this->outputName()."</a>").
				(empty($this->state)?"":", ".$this->outputState())
			;
		return "";
	}
	public function displayDateSubmitted($date_only = true) { 
		$time_stamp = $this->getDateSubmitted(true);
		if($time_stamp == false)
			$time_stamp = time();
		return $date_only?date("M. j, Y",$time_stamp):date("F j, Y",$time_stamp)." at ".date("g:m A",$time_stamp); 
	}
	/*
	*  Getter Functions
	*/
	public function getID() { return $this->question_id; }
	public function getCategoryID() { return $this->category_id; }
	public function getCategoryName() { 
		$category_name = "";
		$category = $this->getCategory(); 
		if(!empty($category))
			$category_name = $category->getName();
		unset($category);
		return $category_name;
	}
	public function getCategory() { return new FAQ_Build_Category($this->category_id); }
	public function getStatus() { return $this->status; }
	public function getDateSubmitted($time_stamp = false) { return $time_stamp?strtotime($this->date_submitted):$this->date_submitted; }
	public function getPersonalInfo() { return $this->personal_info; }
	public function getName() { return $this->name; }
	public function getEmail() { return $this->email; }
	public function getURL() { return $this->url; }
	public function getState() { return $this->state; }
	public function getQuestion() { return $this->question; }
	public function getAnswer() { return $this->answer; }
	public function getTags() { return $this->tags; }
	//For Validation
	public function getPermittedInput() {
		return array(
			"question_id"=>"id","category_id"=>"id","status"=>"abs","date_submitted"=>"date","personal_info"=>"abs",
			"name"=>"text","email"=>"email","url"=>"url","state"=>"text",
			"question"=>"text","answer"=>"basic_html","tags"=>"text","captcha"=>"alpha_numeric"
		); 
	}
	public function getRequiredInput() {
		$required = $this->getPermittedInput();
		unset($required["question_id"]);
		unset($required["category_id"]);
		unset($required["status"]);
		unset($required["date_submitted"]);
		unset($required["personal_info"]);
		unset($required["url"]);
		unset($required["state"]);
		unset($required["answer"]);
		unset($required["tags"]);
		return $required;
	}
	//For Default Inputs function 
	public function defaultName() { return "Enter your name..."; }
	public function defaultEmail() { return "Where you would like the answer sent?"; }
	public function defaultURL($edit_view = false) { return "Enter ".($edit_view?self::ADMINADJECTIVE:self::PUBLICADJECTIVE)." website..."; }
	public function defaultState($edit_view = false) { return "Enter ".($edit_view?self::ADMINADJECTIVE:self::PUBLICADJECTIVE)." state/province..."; }
	public function defaultQuestion() { return "Ask your question (Please no line breaks, formatting, or scripting.)..."; }
	public function defaultAnswer() { return "Answer question..."; }
	public function defaultTags() { return "Enter keywords for searchability..."; }
	public function defaultCaptcha() { return "Type text at left..."; }
	public function defaultSearch() { return "Search the FAQ..."; }
	/*
	*  Get Options Functions
	*/
	public function getStatuses() { return array(self::PENDING=>"Pending",self::INACTIVE=>"Inactive",self::ACTIVE=>"Active",self::DISPLAY=>"Displayed"); }
	public function getPersonalInfoStatuses() { return array(self::OFF=>"Do Not Display",self::ON=>"Display"); }
	/*
	*  Setter Functions
	*/
	public function setID($qid) { if($qid == faq_build_clean_input($qid,"id")) $this->question_id = $qid; }
	public function setCategoryID($cid) { if($cid == faq_build_clean_input($cid,"id")) $this->category_id = $cid; }
	public function setDateSubmitted() { $this->date_submitted = date("Y-m-d H:i:s"); }
	public function setName($name) { if($name == faq_build_clean_input($name,"text")) $this->name = $name; }
	public function setEmail($email) { if($email == faq_build_clean_input($email,"email")) $this->email = $email; }
	public function setURL($url) { if($url == faq_build_clean_input($url,"url")) $this->url = $url; }
	public function setState($state) { if($state == faq_build_clean_input($state,"text")) $this->state = $state; }
	public function setQuestion($question) { if($question == faq_build_clean_input($question,"text")) $this->question = $question; }
	public function setAnswer($answer) { if($answer == faq_build_clean_input($answer,"basic_html")) $this->answer = $answer; }
	public function setTags($tags) { if($tags == faq_build_clean_input($tags,"text")) $this->tags = $tags; }
	//Status Functions
	public function setStatus($status) { if($this->isValidStatus($status)) $this->status = $status; }
	public function setPending() { $this->setStatus(self::PENDING); }
	public function setInactive() { $this->setStatus(self::INACTIVE); }
	public function setActive() { $this->setStatus(self::ACTIVE); }
	public function setDisplay() { $this->setStatus(self::DISPLAY); }
	//Personal Status Functions
	public function setPersonalInfo($p_info) { if($this->isValidPersonalInfoStatus($p_info)) $this->personal_info = $p_info; }
	public function setPersonalInfoDisplayOff() { $this->setPersonalInfo(self::INACTIVE); }
	public function setPersonalInfoDisplayOn() { $this->setPersonalInfo(self::PENDING); }
	/*
	*  Output Functions
	*/
	public function outputID($decode = false) { return faq_build_clean_output($this->question_id,$decode); }
	public function outputCategoryID($decode = false) { return faq_build_clean_output($this->category_id,$decode); }
	public function outputCategoryName($decode = false) { return faq_build_clean_output($this->getCategoryName(),$decode); }
	public function outputStatus($decode = false) { return faq_build_clean_output($this->status,$decode); }
	public function outputDateSubmitted($date_only = false) { return $this->displayDateSubmitted($date_only); }
	public function outputPersonalInfo($decode = false) { return faq_build_clean_output($this->personal_info,$decode); }
	public function outputName($decode = false) { return faq_build_clean_output($this->name,$decode); }
	public function outputEmail($decode = false) { return faq_build_clean_output($this->email,$decode); }
	public function outputURL($decode = false) { return faq_build_clean_output($this->url,$decode); }
	public function outputState($decode = false) { return faq_build_clean_output($this->state,$decode); }
	public function outputQuestion($decode = false) { return faq_build_clean_output($this->question,$decode); }
	public function outputAnswer($decode = false) { return faq_build_strip_multiquotes($this->answer); }
	public function outputQuestionExcerpt() { return faq_build_excerpt($this->question); }
	public function outputAnswerExcerpt() { return faq_build_excerpt($this->answer); }
	public function outputTags($decode = false) { return faq_build_clean_output($this->tags,$decode); }
	/*
	*  Database Management Functions
	*/
	public function save() {
		global $wpdb;
		//Build Query
		$types = array("%d","%d","%s","%d","%s","%s","%s","%s","%s","%s","%s");
		if(empty($this->question_id)) { //New Question
			$this->setDateSubmitted(); //Set time of save
			$wpdb->insert(FAQBUILDDBQUESTION,$this->toArray(true),$types); //Insert the this faq build question object into the database
			$this->question_id = $wpdb->insert_id;
		} else 
			$wpdb->update(FAQBUILDDBQUESTION,$this->toArray(true),array("question_id"=>$this->question_id),$types,array("%d"));
	}
	public function load($qid) {
		global $wpdb;
		//Retrieve Data
		$info = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".FAQBUILDDBQUESTION." WHERE question_id=%d;",absint($qid)),ARRAY_A);
		if(empty($info))
			return false;
		//Load Data Values
		return $this->fromArray($info);
	}
	public function delete() {
		global $wpdb;
		if(empty($this->question_id))
			return false;
		return $wpdb->query($wpdb->prepare("DELETE FROM ".FAQBUILDDBQUESTION." WHERE question_id=%d;",absint($this->question_id))) == 1;
	}
	/*
	*  Import/Export to Array Functions
	*/
	public function toArray($exclude_id = false) {
		$data = array(
			"question_id"=>$this->question_id,"category_id"=>$this->category_id,
			"status"=>$this->status,"date_submitted"=>$this->date_submitted,"personal_info"=>$this->personal_info,
			"name"=>$this->name,"email"=>$this->email,"url"=>$this->url,"state"=>$this->state,
			"question"=>$this->question,"answer"=>$this->answer,"tags"=>$this->tags
		);
		if($exclude_id)
			unset($data["question_id"]);
		return $data;
	}
	public function fromArray($info) {
		//Clean Array
		$data = $this->validateArray($info,"array",true);
		//Update this
		if(!empty($data["question_id"])) $this->question_id = $data["question_id"];
		if(!empty($data["category_id"])) $this->category_id = $data["category_id"];
		if($this->isValidStatus(@$data["status"])) $this->status = @$data["status"];
		if(!empty($data["date_submitted"])) $this->date_submitted = $data["date_submitted"];
		if($this->isValidPersonalInfoStatus(@$data["personal_info"])) $this->personal_info = @$data["personal_info"];
		if(!empty($data["name"])) $this->name = $data["name"];
		if(!empty($data["email"])) $this->email = $data["email"];
		if(!empty($data["url"])) $this->url = $data["url"];
		if(!empty($data["state"])) $this->state = $data["state"];
		if(!empty($data["question"])) $this->question = $data["question"];
		if(!empty($data["answer"])) $this->answer = $data["answer"];
		if(!empty($data["tags"])) $this->tags = $data["tags"];
		return count($data) > 0;
	}
	/*
	*  Validation Functions
	*/
	public function validate() { //Ensure that all the values of this object are proper...
		//Create local copy of this attributes cleaned
		$question_id = faq_build_clean_input($this->question_id,"id");
		$category_id = faq_build_clean_input($this->category_id,"id");
		$status = faq_build_clean_input($this->status,"abs");
		$date_submitted = faq_build_clean_input($this->date_submitted,"date");
		$personal_info = faq_build_clean_input($this->personal_info,"abs");
		$name = faq_build_clean_input($this->name,"text");
		$email = faq_build_clean_input($this->email,"email");
		$url = faq_build_clean_input($this->url,"url");
		$state = faq_build_clean_input($this->state,"text");
		$question = faq_build_clean_input($this->question,"text");
		$answer = faq_build_clean_input($this->answer,"basic_html");
		$tags = faq_build_clean_input($this->tags,"text");
		//Validate
		if(empty($question_id)) $this->question_id = NULL; else $this->question_id = $question_id;
		if(empty($category_id)) $this->category_id = NULL; else $this->category_id = $category_id;
		if(empty($status)) $this->status = self::PENDING; else $this->status = $status;
		if(empty($date_submitted)) $this->date_submitted = NULL; else $this->date_submitted = $date_submitted;
		if(empty($personal_info)) $this->personal_info = self::OFF; else $this->personal_info = $personal_info;
		if(empty($name)) $this->name = ""; else $this->name = $name;
		if(empty($email)) $this->email = ""; else $this->email = $email;
		if(empty($url)) $this->url = ""; else $this->url = $url;
		if(empty($state)) $this->state = ""; else $this->state = $state;
		if(empty($question)) $this->question = ""; else $this->question = $question;
		if(empty($answer)) $this->answer = ""; else $this->answer = $answer;
		if(empty($tags)) $this->tags = ""; else $this->tags = $tags;
	}
	public function validateArray($v,$return = "errors",$complete = false) { //Validate that the passed array has the proper values
		//Validate Input
		if(!is_array($v) || !is_string($return) || !is_bool($complete))
			return false;
		//Clean Values
		$values = faq_build_clean_input($v,$this->getPermittedInput());
		//Validate required fields
		$missing = array();
		$invalid = array();
		$errors = "";
		//Name: Required - Text
		if(empty($v["name"]) || $v["name"] == $this->defaultName()) {
			$errors .= "Please enter your name.<br/>";
			$missing[] = "name";
		} elseif(empty($values["name"])) {
			$errors .= "Invalid name: Please re-enter your name as text (no scripting, formating, or line breaks).<br/>";
			$invalid[] = "name";
		}
		//Email: Required - Email
		if(empty($v["email"]) || $v["email"] == $this->defaultEmail()) {
			$errors .= "Please enter your email.<br/>";
			$missing[] = "email";
		} elseif(empty($values["email"])) {
			$errors .= "Invalid email: Please enter a valid email.<br/>";
			$invalid[] = "email";
		}
		//Website: Not-required - URL
		if(empty($v["url"]) || $v["url"] == $this->defaultURL()) {
			$missing[] = "url";
			unset($values["url"]); //Make sure that values doesn't have the default text
		} elseif(empty($values["url"])) {
			$errors .= "Invalid website: Please enter a valid URL.<br/>";
			$invalid[] = "url";
		}
		//Personal Information Status: Not-required - ABS
		if(empty($v["personal_info"])) {
			$missing[] = "personal_info";
		} elseif(empty($values["personal_info"]) || !$this->isValidPersonalInfoStatus($values["personal_info"])) {
			$errors .= "Invalid personal information status.<br/>";
			$invalid[] = "personal_info";
		}
		//State/Province: Not-required - State
		if(empty($v["state"]) || $v["state"] == $this->defaultState()) {
			$missing[] = "state";
			unset($values["state"]); //Make sure that values doesn't have the default text
		} elseif(empty($values["state"])) {
			$errors .= "Invalid state: Please re-enter your state/province as text (no scripting, formatting, or line breaks).<br/>";
			$invalid[] = "state";
		}
		//Category: Required - ID
		if(empty($v["category_id"])) {
			$errors .= "Please select a category.<br/>";
			$missing[] = "category_id";
		} elseif(empty($values["category_id"])) {
			$errors .= "Invalid category: Please re-select a category.<br/>";
			$invalid[] = "category_id";
		}
		//Question: Required - Text (less than 3000 characters
		if(empty($v["question"]) || $v["question"] == $this->defaultQuestion()) {
			$errors .= "Please enter your question.<br/>";
			$missing[] = "question";
		} elseif(empty($values["question"])) {
			$errors .= "Invalid question: Please re-enter your question as text (no scripting, formatting, or line breaks).<br/>";
			$invalid[] = "question";
		}
		//Tags: Not-required - text
		if(empty($v["tags"])) {
			$missing[] = "tags";
		} elseif(empty($values["tags"])) {
			$errors .= "Invalid tags: Please re-enter your tags as text (no scripting, formatting, or line breaks).<br/>";
			$invalid[] = "tags";
		}
		//Answer: Not-required - text
		if(empty($v["answer"])) {
			$missing[] = "answer";
		} elseif(empty($values["answer"])) {
			$errors .= "Invalid answer: Please re-enter your answer as text (no scripting, formatting, or line breaks).<br/>";
			$invalid[] = "answer";
		}
		//Status: Not-required - ABS
		if(empty($v["status"])) {
			$missing[] = "status";
		} elseif(empty($values["status"]) || !$this->isValidStatus($values["status"])) {
			$errors .= "Invalid status.<br/>";
			$invalid[] = "status";
		}
		//Clean the remaining Fields if Requested
		if($complete) {
			//Question ID: Not-required - ID
			if(empty($v["question_id"])) {
				$missing[] = "question_id";
			} elseif(empty($values["question_id"])) {
				$errors .= "Invalid question id.<br/>";
				$invalid[] = "question_id";
			}
			//Date Submitted: Not-required - Date
			if(empty($v["date_submitted"])) {
				$missing[] = "date_submitted";
			} elseif(empty($values["date_submitted"])) {
				$errors .= "Invalid date submitted.<br/>";
				$invalid[] = "date_submitted";
			}
		}
		//Captcha: Not-required - Alpha_Numeric
		if(empty($v["captcha"]) || $v["captcha"] == $this->defaultCaptcha()) {
			$missing[] = "captcha";
		} elseif(empty($values["captcha"])) {
			$errors .= "Invalid captcha: Please re-enter the text from the image as an alpha-numeric string.<br/>";
			$invalid[] = "captcha";
		}
		//Return Requested type
		switch($return) {
			case "errors": return $errors; break;
			case "array":case "clean":
				foreach($missing as $key) 
					unset($values[$key]);
				foreach($invalid as $key) 
					unset($values[$key]);
				return $values;
			break;
		}
		return (count($missing) + count($invalid)) < 1;
	}
	public function isValidStatus($status) { 
		if(empty($status)) $status = self::PENDING;
		return in_array($status,array_keys($this->getStatuses())); 
	}
	public function isValidPersonalInfoStatus($status) { 
		if(empty($status)) $status = self::OFF;
		return in_array($status,array_keys($this->getPersonalInfoStatuses())); 
	}
}
/*
*  FAQ Builder Question Query Functions
*
*  The following functions define the search possibilities for retrieving questions from the database 
*/
function faq_build_get_questions($status,$search = "",$category_id = 0,$offset = 0,$limit = FAQBUILDPERPAGE) { 
	global $wpdb;
	$questions = array();
	//Validate Input
	if(!is_string($status)) $status = "";
	if(!is_string($search) && !is_numeric($search)) $search = "";
	if(!is_numeric($offset)) $offset = 0;
	if(!is_numeric($limit) && strtolower($limit) != "all" && $limit === false) $limit = FAQBUILDPERPAGE;
	//Build Query
	$query = "SELECT * FROM ".FAQBUILDDBQUESTION." WHERE ";
	//Set Status
	if(strlen($comparator) == 2) $status = substr($status,0,strlen($status)-1);
	switch(strtolower($status)) { 
		case "display": $query .= $wpdb->prepare("status=%d ",FAQ_Build_Question::DISPLAY); break;
		case "active": $query .= $wpdb->prepare("status=%d ",FAQ_Build_Question::ACTIVE); break;
		case "inactive": $query .= $wpdb->prepare("status=%d ",FAQ_Build_Question::INACTIVE); break;
		case "pending": $query .= $wpdb->prepare("status=%d ",FAQ_Build_Question::PENDING); break;
		case "public": $query .= $wpdb->prepare("(status=%d OR status=%d) ",FAQ_Build_Question::DISPLAY,FAQ_Build_Question::ACTIVE); break;
		default: $query .= "1=1 "; break; //all
	}
	//Search OR Order
	if(!empty($search) || !empty($category_id)) {
		if(!empty($search)) {
			$temp = $wpdb->prepare("AND (question LIKE %s OR answer LIKE %s OR tags LIKE %s )",$search,$search,$search);
			$query .= str_replace(array(" '",' "')," '%",str_replace(array("' ",'" '),"%' ",$temp));
		} if(!empty($category_id))
			$query .= $wpdb->prepare(" AND category_id=%d",$category_id);
		$query .= ";";
	} elseif(strtolower($limit) == "all" || $limit === false)
		$query .= "ORDER BY status ASC,question ASC;";
	else
		$query .= $wpdb->prepare("ORDER BY question ASC LIMIT %d OFFSET %d;",$limit,$offset*FAQBUILDPERPAGE);
	$res = $wpdb->get_results($query,ARRAY_A);
	//Populate Objects
	if(is_array($res)) { 
		foreach($res as $row) {
			$q = new FAQ_Build_Question();
			if($q->fromArray($row))
				$questions[] = $q;
			else
				unset($q);
		}
	}
	return $questions;
}