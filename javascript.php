<?php
/*
*  Public Functions
*/
function faq_build_javascript_public() {
	?>
		<script>
			function faq_build_ask_question(form) { //Add Form Ajax Call
				//Deactivate submit button and display processing message
				form.submit.disabled = true;
				//Clear inputs with Auto Text
				faq_build_clear_autofill(form);
				//Build SACK Call
				var mysack = new sack("<?php echo FAQBUILDREQUESTS; ?>");
				mysack.execute = 1;
				mysack.method = 'POST';
				mysack.setVar("action","ask");
				mysack.setVar("category_id",form.category_id.value);
				mysack.setVar("name",form.submitters_name.value);
				mysack.setVar("email",form.email.value);
				mysack.setVar("url",form.url.value);
				mysack.setVar("state",form.state.value);
				mysack.setVar("personal_info",(form.personal_info.checked?1:0));
				mysack.setVar("question",form.question.value);
				mysack.setVar("captcha",form.captcha.value);
				mysack.setVar("form",form.id);
				mysack.onError = function() { alert('An error occured while submitting. Please reload the page and try again.') };
				mysack.runAJAX();//excecute
				return true;
			}
			function faq_build_search_question(form) { //Search Ajax Call
				//Deactivate submit button and display processing message
				form.search.disabled = true;
				//Display Searching Message
				var message = document.getElementById("message_"+form.id);
				message.className = "faq_build_message";
				message.innerHTML = "Searching...";
				//Clear inputs with Auto Text
				faq_build_clear_autofill(form);
				//Build SACK Call
				var mysack = new sack("<?php echo FAQBUILDREQUESTS; ?>");
				mysack.execute = 1;
				mysack.method = 'POST';
				mysack.setVar("action","search");
				mysack.setVar("category_id",form.category_id.value);
				mysack.setVar("search_term",form.search_term.value);
				mysack.setVar("form",form.id);
				mysack.onError = function() { alert('An error occured while submitting. Please reload the page and try again.') };
				mysack.runAJAX();//excecute
			}
			function faq_build_change_page(id,offset) { //Search Ajax Call
				//Build SACK Call
				var mysack = new sack("<?php echo FAQBUILDREQUESTS; ?>");
				mysack.execute = 1;
				mysack.method = 'POST';
				mysack.setVar("action","search");
				mysack.setVar("offset",offset);
				mysack.setVar("form",id);
				mysack.onError = function() { alert('An error occured while submitting. Please reload the page and try again.') };
				mysack.runAJAX();//excecute
			}
			var faq_build_reset_captcha_count = 0;
			function faq_build_reset_captcha(form_id) { //Captcha Reset
				faq_build_reset_captcha_count++; //Incrament this so that the src is reloaded
				var img = document.getElementById("captcha_image_"+form_id);
				img.src = "<?php echo FAQBUILDREQUESTS; ?>?action=captcha_src&count="+faq_build_reset_captcha_count+"&id="+form_id;
			}
		</script>
	<?php
}
/*
*  Admin Side Functions
*/
function faq_build_javascript_admin() {
	?>
		<script>
			function faq_build_question(acao,id) { //Add Form Ajax Call
				//Build SACK Call
				var mysack = new sack("<?php bloginfo("wpurl"); ?>/wp-admin/admin-ajax.php");
				mysack.execute = 1;
				mysack.method = 'POST';
				mysack.setVar("action","faq_build_manage_question");
				mysack.setVar("acao",acao);
				mysack.setVar("question_id",id);
				mysack.onError = function() { alert('An error occured while submitting. Please reload the page and try again.') };
				mysack.runAJAX();//excecute
			}
			function faq_build_ask_question(form) { //Add Form Ajax Call
				//Build SACK Call
				var mysack = new sack("<?php bloginfo("wpurl"); ?>/wp-admin/admin-ajax.php");
				mysack.execute = 1;
				mysack.method = 'POST';
				mysack.setVar("action","faq_build_manage_question");
				if(form.question_id != null)
					mysack.setVar("question_id",form.question_id.value);
				mysack.setVar("category_id",form.category_id.value);
				mysack.setVar("name",form.submitters_name.value);
				mysack.setVar("email",form.email.value);
				mysack.setVar("url",form.url.value);
				mysack.setVar("state",form.state.value);
				mysack.setVar("personal_info",(form.personal_info.checked?1:0));
				mysack.setVar("question",form.question.value);
				mysack.setVar("tags",form.tags.value);
				mysack.setVar("answer",form.answer.value);
				mysack.setVar("status",form.status.value);
				if(form.send_email != null) 
					mysack.setVar("send_email",(form.send_email.checked?1:0));
				mysack.setVar("form",form.id);
				mysack.setVar("acao","save");
				mysack.onError = function() { alert('An error occured while submitting. Please reload the page and try again.') };
				mysack.runAJAX();//excecute
			}
			function faq_build_delete_question(id,name) { //Add Form Ajax Call
				//Confirm Delete
				if(!confirm("Are you sure you want to delete the question from "+name+"?"))
					return;
				//Build SACK Call
				var mysack = new sack("<?php bloginfo("wpurl"); ?>/wp-admin/admin-ajax.php");
				mysack.execute = 1;
				mysack.method = 'POST';
				mysack.setVar("action","faq_build_manage_question");
				mysack.setVar("question_id",id);
				mysack.setVar("acao","delete");
				mysack.onError = function() { alert('An error occured while submitting. Please reload the page and try again.') };
				mysack.runAJAX();//excecute
			}
			function faq_build_category(acao,id) { //Add Form Ajax Call
				//Build SACK Call
				var mysack = new sack("<?php bloginfo("wpurl"); ?>/wp-admin/admin-ajax.php");
				mysack.execute = 1;
				mysack.method = 'POST';
				mysack.setVar("action","faq_build_manage_category");
				mysack.setVar("acao",acao);
				mysack.setVar("category_id",id);
				mysack.onError = function() { alert('An error occured while submitting. Please reload the page and try again.') };
				mysack.runAJAX();//excecute
			}
			function faq_build_save_category(form) { //Add Form Ajax Call
				//Build SACK Call
				var mysack = new sack("<?php bloginfo("wpurl"); ?>/wp-admin/admin-ajax.php");
				mysack.execute = 1;
				mysack.method = 'POST';
				mysack.setVar("action","faq_build_manage_category");
				if(form.category_id != null)
					mysack.setVar("category_id",form.category_id.value);
				mysack.setVar("acao","save");
				mysack.setVar("name",form.category_name.value);
				mysack.setVar("description",form.description.value);
				mysack.setVar("form",form.id);
				mysack.onError = function() { alert('An error occured while submitting. Please reload the page and try again.') };
				mysack.runAJAX();//excecute
			}
			function faq_build_delete_category(id,name,count) { //Add Form Ajax Call
				//Confirm Delete
				if(!confirm(
					"Are you sure you want to delete the '"+name+"' category? "+
					"All questions in this category will be deleted (there are currently "+count+" questions in this category).")
				)
					return;
				//Build SACK Call
				var mysack = new sack("<?php bloginfo("wpurl"); ?>/wp-admin/admin-ajax.php");
				mysack.execute = 1;
				mysack.method = 'POST';
				mysack.setVar("action","faq_build_manage_category");
				mysack.setVar("category_id",id);
				mysack.setVar("acao","delete");
				mysack.onError = function() { alert('An error occured while submitting. Please reload the page and try again.') };
				mysack.runAJAX();//excecute
			}
		</script>
	<?php
}
/*
*  Autofill Functions
*/
function faq_build_javascript_autofill() {
	$q = new FAQ_Build_Question();
	?>
		<script>
			/*
			*  Autofill Management Functions
			*/
			function faq_build_clear_autofill(input) {
				var clear = false;
				switch(input.name) {
					//Question Fields
					case "submitters_name": clear = (input.value == "<?php echo $q->defaultName(); ?>"); break;
					case "email": clear = (input.value == "<?php echo $q->defaultEmail(); ?>"); break;
					case "url": clear = (input.value == "<?php echo $q->defaultURL(false); ?>"); break;
					case "state": clear = (input.value == "<?php echo $q->defaultState(false); ?>"); break;
					case "question": clear = (input.value == "<?php echo $q->defaultQuestion(); ?>"); break;
					//Other Fields
					case "captcha": clear = (input.value == "<?php echo $q->defaultCaptcha(); ?>"); break;
					case "search_term": clear = (input.value == "<?php echo $q->defaultSearch(); ?>"); break;
				}
				if(clear) {
					input.value = ""; 
					input.style.color = "<?php echo FAQBUILDFOCUSEDCOLOR; ?>";
				}
			}
			function faq_build_clear_form_autofill(form) {
				//Clear text fields
				switch(form.name) {
					case "faq_build_manage_question":
						faq_build_clear_autofill(form.submitters_name);
						faq_build_clear_autofill(form.email);
						faq_build_clear_autofill(form.url);
						faq_build_clear_autofill(form.state);
						faq_build_clear_autofill(form.question);
						faq_build_clear_autofill(form.captcha);
					break;
					case "faq_build_search":
						faq_build_clear_autofill(form.search_term);
					break;
				}
			}
			function faq_build_populate_autofill(form) {
				//If specified only populate to that for, otherwise search for all FAQ Forms on the page
				var all_forms;
				if(form == null)
					var all_forms = document.getElementsByTagName('form');
				else {
					all_forms = new Array();
					all_forms[0] = form;
				}
				//Populate forms
				for(var i=0;i<all_forms.length;i++) {
					var form = all_forms[i];
					switch(form.name) {
						case "faq_build_manage_question": 
							if(form.submitters_name.value == "") { 
								form.submitters_name.value = "<?php echo $q->defaultName(); ?>"; 
								form.submitters_name.style.color = "<?php echo FAQBUILDUNFOCUSEDCOLOR; ?>";
							}
							if(form.email.value == "") { 
								form.email.value = "<?php echo $q->defaultEmail(); ?>";
								form.email.style.color = "<?php echo FAQBUILDUNFOCUSEDCOLOR; ?>";
							}
							if(form.url.value == "") { 
								form.url.value = "<?php echo $q->defaultURL(); ?>";
								form.url.style.color = "<?php echo FAQBUILDUNFOCUSEDCOLOR; ?>";
							}
							if(form.state.value == "") { 
								form.state.value = "<?php echo $q->defaultState(); ?>";
								form.state.style.color = "<?php echo FAQBUILDUNFOCUSEDCOLOR; ?>";
							}
							if(form.question.value == "") { 
								form.question.value = "<?php echo $q->defaultQuestion(); ?>";
								form.question.style.color = "<?php echo FAQBUILDUNFOCUSEDCOLOR; ?>";
							}
							if(form.captcha.value == "") { 
								form.captcha.value = "<?php echo $q->defaultCaptcha(); ?>";
								form.captcha.style.color = "<?php echo FAQBUILDUNFOCUSEDCOLOR; ?>";
							}
							form.submit.disabled = false;
						break;
						case "faq_build_search": 
							if(form.search_term.value == "") { 
								form.search_term.value = "<?php echo $q->defaultSearch(); ?>";
								form.search_term.style.color = "<?php echo FAQBUILDUNFOCUSEDCOLOR; ?>";
							}
						break;
					}
				}
			}
			function faq_build_reset_form(form) {
				switch(form.name) {
					case "faq_build_manage_question": 
						form.submitters_name.value = "";
						form.email.value = "";
						form.url.value = "";
						form.state.value = "";
						form.category_id.value = "";
						form.question.value = "";
						form.captcha.value = "";
					break;
					case "faq_build_search": 
						form.search_term.value = "";
					break;
				}
				faq_build_reset_captcha(form.id);
				faq_build_populate_autofill(form);
			}
			/*
			* ON LOAD
			*/
			Event.observe(window,'load',function faq_build_on_load() { faq_build_populate_autofill(); });
		</script>
	<?php
}
/*
*  HELPER FUNCTIONS
*/
function faq_build_javascript_helpers() {
	?>
		<script></script>
	<?php
}
