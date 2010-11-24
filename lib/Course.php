<?php 
//***************************************************	
// Course registration management
//***************************************************	
class Course {

	function register($course_id, $starting_time){
		global $DB;
		$course=new Object();
		$course->course_id = $course_id;
		$course->last_notification_time = $starting_time;
		$course->notify_by_email = 1;
		$course->notify_by_sms = 1;
		$course->notify_by_rss = 1;
		return $DB->insert_record('block_notify_changes_courses', $course);
	}

	function update_last_notification_time($course_id, $last_notification_time){
		global $DB;
		$course=new Object();
		$course->id = $this->get_registration_id($course_id);
		$course->course_id = $course_id;
		$course->last_notification_time = $last_notification_time;
		return $DB->update_record('block_notify_changes_courses', $course);
	}

	function update_course_notification_settings($course_id, $settings){
		global $DB;
		$course=new Object();
		$course->id = $this->get_registration_id($course_id);
		$course->course_id = $course_id;
		$course->notify_by_email = 0;
		if(isset($settings->notify_by_email) and $settings->notify_by_email == 1) $course->notify_by_email = 1;
		$course->notify_by_sms = 0;
		if(isset($settings->notify_by_sms) and $settings->notify_by_sms == 1) $course->notify_by_sms = 1;
		$course->notify_by_rss = 0;
		if(isset($settings->notify_by_rss) and $settings->notify_by_rss == 1) $course->notify_by_rss = 1;
		return $DB->update_record('block_notify_changes_courses', $course);
	}

	function is_registered($course_id){
		$course_registration = $this->get_registration_id($course_id); 
		if( !is_null($course_registration) )
			return true;
		else
			return false;
	}

	function get_registration_id($course_id){
		if( is_null($course_registration = $this->get_registration($course_id) ) )
			return null;
		else
			return $course_registration->id;
	}
	
	function get_registration($course_id){
		global $DB;
		$course_registration = $DB->get_records_select('block_notify_changes_courses', "course_id=$course_id"); 
		if( isset($course_registration) && is_array($course_registration) )
			return current($course_registration);
		else
			return null;
	}
	
	function get_last_notification_time($course_id){
		global $DB;
		$course_registration = $DB->get_records_select('block_notify_changes_courses', "course_id=$course_id"); 
		if( isset($course_registration) && is_array($course_registration) )
			return current($course_registration)->last_notification_time;
		else
			return null;
	}

	function get_recent_activities($course) {
		global $DB;
		// module information for the current course
		$modinfo =& get_fast_modinfo($course);
		$last_notification_time = $this->get_last_notification_time($course->id);
		$changelist = array();
		$logs = $DB->get_records_select( 'log', "time > $last_notification_time AND course = $course->id AND module = 'course' AND
										(action = 'add mod' OR action = 'update mod' OR action = 'delete mod')", null, "id ASC");
		if ($logs) {
			$actions  = array('add mod', 'update mod', 'delete mod');
			$newgones = array(); // added and later deleted items
			foreach ($logs as $key => $log) {
				// skip if the log is not about actions
				if (!in_array($log->action, $actions)) {
					continue;
				}

				// remove the space from the info field
				$info = split(' ', $log->info); 
				// ignore labels
				if ($info[0] == 'label') {	  
					continue;
				}
				// if in the info field modname o instance id is missing skip
				if (count($info) != 2) {
					debugging("Incorrect log entry info: id = ".$log->id, DEBUG_DEVELOPER);
					continue;
				}

				$modname	= $info[0];
				$instanceid = $info[1];

				if ($log->action == 'delete mod') {
					// unfortunately we do not know if the mod was visible
					if (!array_key_exists($log->info, $newgones)) {
						$strdeleted = get_string('deletedactivity', 'moodle', get_string('modulename', $modname));
						$changelist[$log->info] = array ('operation' => 'delete', 'text' => $strdeleted);
					}
				} else {
					//var_dump($modinfo);
					if (!isset($modinfo->instances[$modname][$instanceid])) {
						if ($log->action == 'add mod') {
							// do not display added and later deleted activities
							$newgones[$log->info] = true;
						 }
						 continue;
					 }
					 $cm = $modinfo->instances[$modname][$instanceid];
					 if (!$cm->uservisible) {
						 continue;
					 }
					 if ($log->action == 'add mod') {
						 $stradded = get_string('added', 'moodle', get_string('modulename', $modname));
						 $changelist[$log->info] = array(	'operation' => $stradded, 
						 									'modname' => $cm->modname, 
															'id' => $cm->id, 
															'resource_name' => format_string($cm->name) );

					 } else if ($log->action == 'update mod' and empty($changelist[$log->info])) {
						 $strupdated = get_string('updated', 'moodle', get_string('modulename', $modname));
						 $changelist[$log->info] = array(	'operation' => $strupdated, 
						 									'modname' => $cm->modname, 
															'id' => $cm->id, 
															'resource_name' => format_string($cm->name) );
					 }
				}
			}
		}

		// update last notification time
		$this->update_last_notification_time($course->id, time());
		
		if (!empty($changelist))
			return $changelist;
		else 
			return null;
	}

	function get_all_courses_using_notify_changes_block(){
		global $DB;
		// join block_instances, context and course and extract all courses
		// that are using notify_changes block
		return $DB->get_records_sql(" select * from course where id in 
											( select instanceid from context where id in 
												( select parentcontextid from block_instances where blockname = 'notify_changes' ) );");
	}
}
?>
