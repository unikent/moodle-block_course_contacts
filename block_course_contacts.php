<?php
class block_course_contacts extends block_base {
    public function init() {
		global $USER;
        $this->title = get_string('course_contacts', 'block_course_contacts');
    }
	
	//a custom function for shortening names
	public function shorten_name($lname){
		if (strpos($lname, '-')){
			$names = explode('-', $lname);
			$lname = '';
			foreach($names as $name){
				if (strlen($name)>6){
					$name = substr($name, 0, 1);
				}
				$lname .= $name."-";
			}
			$lname = substr($lname, 0, -1);
		}	
		if (strpos($lname, ' ')){
			$names = explode(' ', $lname);
			$lname = $names[0];
		}		
		return $lname;
	}	
	
	public function get_content(){
		global $CFG, $DB, $OUTPUT, $USER;
		
		//if the user hasnt configured the plugin, set these as defaults
		if (empty($this->config)){
			$this->config->role_3 = 1;
			$this->config->email = 1;
			$this->config->message = 1;
			$this->config->phone = 0;
		}
		
		$courseid = $this->page->course->id;
		
		if ($this->content !== null){
			return $this->content;
		}
		
		$this->content = new stdClass;		

		//find the roles available on this course
		$roles = array_reverse(get_default_enrol_roles(get_context_instance(CONTEXT_COURSE, $courseid),NULL), TRUE);
		$content = "";
		
		
		//step through each role and check that the config is set to display
		foreach($roles as $key=>$role){
			$att = "role_".$key;
			if(!empty($this->config->$att)){
				if($this->config->$att == 1){
					$sql = "SELECT DISTINCT `".$CFG->prefix ."user`.* 
			
					FROM `".$CFG->prefix ."user`
					LEFT JOIN `".$CFG->prefix ."role_assignments`
					ON `".$CFG->prefix ."role_assignments`.`userid` = `".$CFG->prefix ."user`.`id`
					LEFT JOIN `".$CFG->prefix ."context`
					ON `".$CFG->prefix ."context`.`id`=`".$CFG->prefix ."role_assignments`.`contextid`
							
					WHERE `".$CFG->prefix ."context`.`instanceid` = ?
					AND `".$CFG->prefix ."context`.`contextlevel` = 50
					AND `".$CFG->prefix ."role_assignments`.`roleid` = ?
							
					ORDER BY LOWER(`".$CFG->prefix ."user`.`lastname`)
					";
					$contacts = $DB->get_records_sql($sql, array($courseid,$key));
					
					//adds an s to the end of the heading
					if(count($contacts) > 1){
						$plural = "s";
					}else{
						$plural = "";
					}
					//because the role search finds the custom name and the proper name in brackets
					if(!empty($contacts)){
						if($shortened = strstr($role, '(', TRUE)){
							$content .= "<h2>".trim($shortened).$plural."</h2>";
						}else{
							$content .= "<h2>".$role.$plural."</h2>";							
						}
					}
					//now display each contact
					foreach ($contacts as $contact){
						//unless they are us
						if ($USER->id != $contact->id){
							$content .= "<div class='ccard'>";
							$content .= $OUTPUT->user_picture($contact, array('size'=>50));
							$content .= "<div class='info'>";
							if($contact->lastaccess > (time()-300)){
								//online :)!
								$content .= "<div class='name online'>".$this->shorten_name($contact->firstname)." ".$this->shorten_name($contact->lastname)."</div>";				
								$content .= "<img class='status' src='".$OUTPUT->pix_url('online', 'block_course_contacts')."' />";				
							}else{
								//offline :(!	
								$content .= "<div class='name online'>".$this->shorten_name($contact->firstname)." ".$this->shorten_name($contact->lastname)."</div>";			
								$content .= "<img class='status' src='".$OUTPUT->pix_url('offline', 'block_course_contacts')."' />";
							}
							$content .= "<hr />";
							$content .= "<div class='comms'>";
							
							//should we display email?
							if ($this->config->email == 1){
								$content .= "<a href='mailto:".$contact->email."'><img src='".$OUTPUT->pix_url('mail', 'block_course_contacts')."' alt='".get_string('email', 'block_course_contacts')." ".$contact->firstname."' title='".get_string('email', 'block_course_contacts')." ".$contact->firstname."' /></a>";
							}
							
							//what about messages?
							if ($this->config->message == 1){
								$content .= "<a href='".$CFG->wwwroot."/message/index.php?id=".$contact->id."'><img src='".$OUTPUT->pix_url('message', 'block_course_contacts')."'  alt='".get_string('message', 'block_course_contacts')." ".$contact->firstname."' title='".get_string('message', 'block_course_contacts')." ".$contact->firstname."'/></a>";
							}
							
							//and phone numbers?
							if ($this->config->phone == 1 && $contact->phone1 != ""){
								$content .= "<a href='tel:".$contact->phone1."'><img src='".$OUTPUT->pix_url('phone', 'block_course_contacts')."'  alt='".get_string('phone', 'block_course_contacts')." ".$contact->firstname."' title='".get_string('phone', 'block_course_contacts')." ".$contact->firstname."'/></a>";
							}
							
							
							$content .= "</div>";					
							$content .= "</div>";					
							$content .= "</div>";		
						}
					}
				}
			}
		}
		$this->content->text = $content;
		return $this->content;
	}
	
	public function instance_allow_config() {
		return true;
	}
	
}
?>