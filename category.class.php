<?php
/*
*  FAQ_Build_Category Class
*
*  This object defines the display and database interaction for categories in FAQ Builder 
*/
class FAQ_Build_Category {
	/*
	*  Object Variables
	*/
	private $category_id;
	private $name;
	private $description;
	/*
	*  Constructor: returns true only if data was loaded
	*/
	public function __construct($seed = NULL) {
		if(is_numeric($seed) || is_string($seed))
			$this->load($seed);
		elseif(is_array($seed))
			$this->fromArray($seed);
		$this->validate();
	}
	/*
	*  Display Functions
	*/
	function display() { return "<b>".$this->outputName()."</b>: ".$this->outputDescription(); }
	function displayEditor($width = "100%") { 
		//Validate Input
		if(!is_string($width) && !is_numeric($width))
			$width = "100%";
		//Build Rows
		$id = faq_build_random_string(); //Get a random string to identify this form with
		$rows = array(
			"Category Name *"=>
				"<input ".
					"type='text' name='category_name' class='faq_build_input_style' max_length='100' value='".$this->outputName()."' ".
					"onFocus='faq_build_clear_autofill(this);' onClick='faq_build_clear_autofill(this);'".
				"/>",
			"Description"=>
				"<textarea ".
					"name='description' class='faq_build_textarea' ".
					"onFocus='faq_build_clear_autofill(this);' onClick='faq_build_clear_autofill(this);'".
				">".$this->outputDescription()."</textarea>"
		);
		//Build Heading & Hidden
		$heading = "";
		$hidden = "<input type='button' name='cancel' value='Cancel' onClick='faq_build_category(\"cancel\",null);'/> ";
		if(!empty($this->category_id)) {
			$heading = "Edit";
			$hidden .= "<input type='hidden' name='category_id' value='".$this->outputID(true)."'/>";
		} else
			$heading = "Add";
		//Return form
		return faq_build_form(
			faq_build_random_string(),"faq_build_manage_category",$heading." Category",
			$rows,"faq_build_save_category","Save",$hidden
		);
	}
	function displayTableHeader() { return "<th width='20%'>Name</th><th width='65%'>Description</th><th width='15%'>Actions</th>"; }
	function displayTableRow() {
		return 
			"<td>".$this->outputName()."</td><td>".$this->outputDescription()."</td>".
			"<td>".
				"<a href='javascript:faq_build_category(\"edit\",\"".$this->outputID()."\");'>edit</a> | ".
				"<a ".
					"onClick='faq_build_delete_category(\"".$this->outputID()."\",this.name,".$this->getQuestionCount().")' ".
					"name='".$this->outputName()."'".
				">delete</a>".
			"</td>"
		;
	}
	/*
	*  Getter Functions
	*/
	public function getID() { return $this->category_id; }
	public function getName() { return $this->name; }
	public function getDescription() { return $this->description; }
	public function getQuestionCount() {
		global $wpdb;
		$res = $wpdb->get_var($wpdb->prepare("SELECT COUNT(question_id) FROM ".FAQBUILDDBQUESTION." WHERE category_id=%d;",$this->getID()));
		if(!empty($res) && is_numeric($res))
			return absint($res);
		return 0;
	}
	//For Validation
	public function getPermittedInput() { return array("category_id"=>"id","name"=>"text","description"=>"text"); }
	public function getRequiredInput() {
		$required = $this->getPermittedInput();
		unset($required["category_id"]);
		unset($required["description"]);
		return $required;
	}
	//For Default Inputs function 
	public function defaultName() { return "Enter the category name..."; }
	public function defaultDescription() { return "Enter a breif description of the category..."; }
	/*
	*  Setter Functions
	*
	*  The idea here is not to touch this values unless we have a clean value to replace it with.
	*/
	public function setID($cid) { if($cid == faq_build_clean_input($cid,"id")) $this->category_id = $cid; }
	public function setName($name) { if($name == faq_build_clean_input($name,"text")) $this->name = $name; }
	public function setDescription($des)  { if($des == faq_build_clean_input($des,"text")) $this->description = $des; }
	/*
	*  Output Functions
	*/
	public function outputID($decode = false) { return faq_build_clean_output($this->category_id,$decode); }
	public function outputName($decode = false) { return faq_build_clean_output($this->name,$decode); }
	public function outputDescription($decode = false) { return faq_build_clean_output($this->description,$decode); }
	/*
	*  Database Management Functions
	*/
	public function save() {
		global $wpdb;
		//Build Query
		$types = array("%s","%s");
		if(empty($this->category_id)) { //New Category
			$wpdb->insert(FAQBUILDDBCATEGORY,$this->toArray(true),$types); //Insert the this faq build category object into the database
			$this->category_id = $wpdb->insert_id;
		} else
			$wpdb->update(FAQBUILDDBCATEGORY,$this->toArray(true),array("category_id"=>$this->category_id),$types,array("%d"));
	}
	public function load($seed) {
		global $wpdb;
		//Retrieve Data
		$info = NULL;
		if(is_numeric($seed))
			$info = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".FAQBUILDDBCATEGORY." WHERE category_id=%d;",absint($seed)),ARRAY_A);
		elseif(is_string($seed))
			$info = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".FAQBUILDDBCATEGORY." WHERE name=%s;",faq_build_clean_input($seed,"text")),ARRAY_A);
		if(empty($info))
			return false;
		//Load Data Values: As many as are good...
		return $this->fromArray($info);
	}
	public function delete() {
		global $wpdb;
		if(empty($this->category_id))
			return false;
		//Delete all questions in this category
		$wpdb->query($wpdb->prepare("DELETE FROM ".FAQBUILDDBQUESTION." WHERE category_id=%d;",absint($this->category_id)));
		//Delete this category
		return $wpdb->query($wpdb->prepare("DELETE FROM ".FAQBUILDDBCATEGORY." WHERE category_id=%d;",absint($this->category_id))) == 1;
	}
	/*
	*  Import/Export to Array Functions
	*/
	public function toArray($exclude_id = false) { 
		$data = array("category_id"=>$this->category_id,"name"=>$this->name,"description"=>$this->description); 
		if($exclude_id)
			unset($data["category_id"]);
		return $data;
	}
	public function fromArray($info) {
		$data = $this->validateArray($info,"array",true); //Load Data Values: As many as are good...
		if(!empty($info["category_id"])) $this->category_id = $data["category_id"];
		if(!empty($info["name"])) $this->name = $data["name"];
		if(!empty($info["description"])) $this->description = $data["description"];
		return count($data) > 0;
	}
	/*
	*  Validation Functions
	*/
	public function validate() { //Ensure that all the values of this object are proper...
		if(!empty($this->category_id) && $this->category_id != faq_build_clean_input($this->category_id,"id")) $this->category_id = NULL;
		if(!empty($this->name) && $this->name != faq_build_clean_input($this->name,"text")) $this->name = "";
		if(!empty($this->description) && $this->description != faq_build_clean_input($this->description,"text")) $this->description = "";
	}
	public function validateArray($values,$return = "errors",$complete = false) { //Validate that the passed array has the proper values
		//Validate Input
		if(!is_array($values) || !is_string($return) || !is_bool($complete))
			return false;
		//Clean Values
		$v = faq_build_clean_input($values,$this->getPermittedInput());
		//Validate required fields
		$missing = array();
		$invalid = array();
		$errors = "";
		//Name: Required - Text
		if(empty($values["name"]) || $values["name"] == $this->defaultName()) {
			$errors .= "Please enter the category name.<br/>";
			$missing[] = "name";
		} elseif(empty($v["name"])) {
			$errors .= "Invalid category name: Please re-enter your category name as text (no scripting, formating, or line breaks).<br/>";
			$invalid[] = "name";
		}
		//Description: Not-Required - Text
		if(empty($values["description"]) || $values["description"] == $this->defaultDescription()) {
			$missing[] = "description";
		} elseif(empty($v["description"])) {
			$errors .= "Invalid description: Please re-enter your description as text (no scripting, formating, or line breaks).<br/>";
			$invalid[] = "description";
		}
		//Clean the remaining Fields if Requested
		if($complete) {
			//Description: Required - ID
			if(empty($v["category_id"])) {
				$errors .= "Missing category id.<br/>";
				$missing[] = "category_id";
			} elseif(empty($v["category_id"])) {
				$errors .= "Invalid category id.<br/>";
				$invalid[] = "category_id";
			}
		}
		//Return Requested type
		switch($return) {
			case "errors": return $errors; break;
			case "array":case "clean":
				foreach($missing as $key) 
					unset($v[$key]);
				foreach($invalid as $key) 
					unset($v[$key]);
				return $v;
			break;
		}
		return (count($missing) + count($invalid)) < 1;
	}
}
/*
*  FAQ Builder Category Query Functions
*
*  The following functions define the search possibilities for retrieving categories from the database 
*/
function faq_build_get_categories() { //retrieve all categories
	global $wpdb;
	$res = $wpdb->get_results("SELECT * FROM ".FAQBUILDDBCATEGORY." ORDER BY name ASC;",ARRAY_A);
	$categories = array();
	if(is_array($res)) {
		foreach($res as $row) {
			$c = new FAQ_Build_Category();
			if($c->fromArray($row))
				$categories[] = $c;
			else
				unset($c);
		}
	}
	return $categories;
}