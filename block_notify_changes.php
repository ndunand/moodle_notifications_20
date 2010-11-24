<?php 
include_once realpath(dirname( __FILE__ ).DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR."common.php";
include_once LIB_DIR.DIRECTORY_SEPARATOR."User.php";
include_once LIB_DIR.DIRECTORY_SEPARATOR."Course.php";
include_once LIB_DIR.DIRECTORY_SEPARATOR."eMail.php";

class block_notify_changes extends block_base {

//***************************************************	
// Init
//***************************************************	
	function init() {
		$this->title = get_string('pluginname', 'block_notify_changes'); 
		$this->cron = 1;
	}

//***************************************************	s
// Configurations
//***************************************************	
	function specialization(){
		global $COURSE;
		$Course = new Course();
		// if the course has not been registered so far
		// then register the course and set the starting time
		// for notifications
		if( !$Course->is_registered($COURSE->id) ) 
			$Course->register($COURSE->id, time());
	}

	function instance_allow_config() {
		return true; 
	}
	function instance_config_save($data) {
		global $COURSE;
		$Course = new Course();
		$Course->update_course_notification_settings($COURSE->id, $data);
  		return true;
	}

	function personal_settings($course_registration){
		global $CFG;
		global $COURSE;
		global $USER;

		$User = new User();
		$user_preferences = $User->get_preferences($USER->id, $COURSE->id);
		// if admin user or both sms and email notifications
		// are disabled in the course then do not display user preferences
		if( 
			is_null($user_preferences) or 
			($course_registration->notify_by_email == 0 and $course_registration->notify_by_sms == 0 ) 
		) return;

		// prepare mail notification status
		$mail_notification_status = '';
		if( $user_preferences->notify_by_email == 1) $mail_notification_status = 'checked="checked"';

		$sms_notification_status = '';
		if( $user_preferences->notify_by_sms == 1) $sms_notification_status = 'checked="checked"';
		//user preferences interface
		$up_interface ="<script src='$CFG->wwwroot/blocks/notify_changes/js/jquery-1.4.3.js' type='text/javascript'></script>";
		$up_interface.="<script src='$CFG->wwwroot/blocks/notify_changes/js/user_preferences_interface.php' type='text/javascript'></script>";
		$up_interface.='<div id="notify_changes_config_preferences">';
		$up_interface.='<a id="notify_changes_user_preferences_trigger" href="#" onclick="show_user_preferences_panel()">Settings</a>';
		$up_interface.='<div id="notify_changes_user_preferences" style="display:none">';
		$up_interface.='<div>';
		$up_interface.= get_string('user_preference_header', 'block_notify_changes');
		$up_interface.='</div>';
		$up_interface.='<form id="user_preferences">';
		$up_interface.='<input type="hidden" name="user_id" value="'.$USER->id.'" />';
		$up_interface.='<input type="hidden" name="course_id" value="'.$COURSE->id.'" />';
		if ( $course_registration->notify_by_email == 1 ) {
			$up_interface.='<div>';
			$up_interface.="<input type='checkbox' name='notify_by_email' value='1' $mail_notification_status />";
			$up_interface.= get_string('notify_by_email', 'block_notify_changes');
			$up_interface.='</div>';
		}
		if ( $course_registration->notify_by_sms == 1 ) {
			$up_interface.='<div>';
			$up_interface.="<input type='checkbox' name='notify_by_sms' value='1' $sms_notification_status />";
			$up_interface.= get_string('notify_by_sms', 'block_notify_changes');
			$up_interface.='</div>';
		}
		$up_interface.='</form>';
		$up_interface.='';
		$up_interface.='<input type="button" name="save_user_preferences" value="Save" onclick="save_user_preferences()" />';
		$up_interface.='<input type="button" name="cancel" value="Cancel" onclick="hide_user_preferences_panel()" />';
		$up_interface.='</div>';
		return $up_interface;
		/*
		*/
	}

//***************************************************	
// Block content
//***************************************************	
	function get_content() {
		if ($this->content !== NULL) {
			return $this->content;
		}

		global $COURSE;
		global $USER;
		global $CFG;

		$this->content   = new stdClass;
		$Course = new Course();
		$course_registration = $Course->get_registration($COURSE->id);

		if ( $course_registration->notify_by_email == 0 and $course_registration->notify_by_sms == 0 and $course_registration->notify_by_rss == 0  )
			$this->content->text =  get_string('configuration_comment', 'block_notify_changes');
		else {
			$this->content->text = '';

			if ( $course_registration->notify_by_email == 1 ) {
				$this->content->text.= "<img src='$CFG->wwwroot/blocks/notify_changes/images/Mail-icon.png' alt='Notification by mail' />";
			} 
			
			if ( $course_registration->notify_by_sms == 1 ) {
				$this->content->text.= "<img src='$CFG->wwwroot/blocks/notify_changes/images/SMS-icon.png' alt='Notification by sms' />";
			}
			if ( $course_registration->notify_by_rss == 1 ) {
				$this->content->text.= "<img src='$CFG->wwwroot/blocks/notify_changes/images/RSS-icon.png' alt='Notification by rss' />";
			}
		}

		$this->content->text.= $this->personal_settings($course_registration);
		/*
		*/
		$this->content->footer = '';
		$this->cron();
		return $this->content;
	}
 
//***************************************************	
// Cron
//***************************************************	
	function cron(){
		// get the list of courses that are using 
		$Course = new Course();
		$courses = $Course->get_all_courses_using_notify_changes_block();
		if( !is_array($courses) or count($courses) < 1 ) return;
		
		foreach($courses as $course){
			// check if the course has something new or not
			$changelist = $Course->get_recent_activities($course); 
			if( empty($changelist) ) continue; // check the next course. No new items in this one.

			// get list of users enrolled in this course
			$User = new User();
			$enrolled_users = $User->get_all_users_enrolled_in_the_course($course->id);
			$course_registration = $Course->get_registration($course->id);
			foreach($enrolled_users as $user){
				// check if the user has preferences	
				$user_preferences = $User->get_preferences($user->id, $course->id);
				// if the user has not preferences than set the default
				if(is_null($user_preferences)){
					$user_preferences = new Object();	
					$user_preferences->user_id = $user->id;
					$user_preferences->course_id = $course->id;
					$user_preferences->notify_by_email = 1;
					$user_preferences->notify_by_sms = 1;
					$User->initialize_preferences(	$user_preferences->user_id, 
													$user_preferences->course_id, 
													$user_preferences->notify_by_email, 
													$user_preferences->notify_by_sms );
				}

				// if the email notification is enabled in the course
				// and if the user has set the emailing notification in preferences
				// then send a notification by email
				if( $course_registration->notify_by_email == 1 and $user_preferences->notify_by_email == 1 ){
					$eMail = new eMail();
					$eMail->notify_changes($changelist, $user, $course);
				}
			}
		}
		return;
	}
}
?>
