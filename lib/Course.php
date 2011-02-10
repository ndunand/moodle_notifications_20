<?php 
//***************************************************	
// Course registration management
//***************************************************	
class Course {

	function register($course_id, $starting_time){
		global $DB;
		global $CFG;
		$course=new Object();
		$course->course_id = $course_id;
		$course->last_notification_time = $starting_time;
		$course->notify_by_email = 1;
		$course->notify_by_sms = 1;
		$course->notify_by_rss = 1;
		$course->notification_frequency = $CFG->block_notify_changes_frequency*3600;
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
		//var_dump($settings);
		if(isset($settings->notification_frequency))
			$course->notification_frequency = $settings->notification_frequency % 25 * 3600;
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

	function uses_notify_changes_block($course_id){
		global $DB, $CFG;
		$id = $DB->get_records_sql("select instanceid from {$CFG->prefix}context where id in (select parentcontextid from {$CFG->prefix}block_instances where blockname = 'notify_changes') and instanceid = $course_id");
		if(empty($id))
			return false;
		else
			return true;
	}


	function get_all_courses_using_notify_changes_block(){
		global $DB, $CFG;
		// join block_instances, context and course and extract all courses
		// that are using notify_changes block
		return $DB->get_records_sql(" select * from {$CFG->prefix}course where id in 
											( select instanceid from {$CFG->prefix}context where id in 
												( select parentcontextid from {$CFG->prefix}block_instances where blockname = 'notify_changes' ) );");
	}
	
	function get_updated_and_deleted_modules($course_id){
		global $DB;
		$last_notification_time = $this->get_last_notification_time($course_id);
		$this->update_last_notification_time($course_id, time());
		return $DB->get_records_select('log', "course=$course_id and action in ('update', 'delete mod') and time > $last_notification_time", null,'cmid,action');
	}



	function update_log($course){
		global $DB;
		$modinfo =& get_fast_modinfo($course);
		foreach($modinfo->cms as $cms => $module){
			// filter labels
			if($module->modname == 'label' or $this->is_module_logged($course->id, $module->id, $module->modname)) continue;
			$new_record = new Object();
			$new_record->course_id = $course->id;
			$new_record->module_id = $module->id;
			$new_record->name = $module->name;
			$new_record->type = $module->modname;
			$new_record->action = 'added';
			$new_record->status = 'pending';
			// if the resource is not visible than
			// mark it as pending and then notify once it is made visible
			$DB->insert_record('block_notify_changes_log', $new_record);
		}
		// update records
		$course_updates = $this->get_updated_and_deleted_modules($course->id);

		// if no course updates available then return 
		if(empty($course_updates)) return;

		foreach($course_updates as $course_update){
			$log_row = $this->get_log_entry($course_update->cmid);
			if($course_update->action == 'update'){
				$log_row->action = 'updated';
				// set new name if name has been changed
				$log_row->name = $modinfo->cms[$log_row->module_id]->name;
				$log_row->status = 'pending';
			} else if($course_update->action == 'delete mod') {
				$log_row->action = 'deleted';
				$log_row->status = 'pending';
			}
			$DB->update_record('block_notify_changes_log', $log_row);
		}
		
	}

	function initialize_log($course){
		global $DB;
		$modinfo =& get_fast_modinfo($course);
		// drop all previous records
		$DB->delete_records('block_notify_changes_log', array('course_id'=>$course->id) );
		// add new records
		foreach($modinfo->cms as $cms => $module){
			// filter labels
			if( $module->modname == 'label') continue;
			$new_record = new Object();
			$new_record->course_id = $course->id;
			$new_record->module_id = $module->id;
			$new_record->name = $module->name;
			$new_record->type = $module->modname;
			$new_record->action = 'added';
			$new_record->status = 'notified';
			// if the resource is not visible than
			// mark it as pending and then notify once it is made visible
			if($module->visible == '0') $new_record->status = 'pending';
			$DB->insert_record('block_notify_changes_log', $new_record);
		}
	}

	function is_module_logged($course_id, $module_id, $type){
		global $DB;
		$log = $DB->get_records_select('block_notify_changes_log', "course_id = $course_id AND module_id = $module_id AND type = '$type'", null,'id');
		if(empty($log))
			return false;
		else
			return true;
	}

	function log_exists($course_id){
		global $DB;
		$log = $DB->get_records_select('block_notify_changes_log', "course_id = $course_id", null,'id');
		if(empty($log))
			return false;
		else
			return true;
	}

	function get_log_entry($module_id){
		global $DB, $CFG;
		return current( $DB->get_records_select('block_notify_changes_log', "module_id = $module_id") );
	}

	function get_recent_activities($course_id){
		global $DB, $CFG;
		//block_notify_changes_log table plus visible field from course_modules
		$subtable = "( select {$CFG->prefix}block_notify_changes_log.*, {$CFG->prefix}course_modules.visible 
						from {$CFG->prefix}block_notify_changes_log left join {$CFG->prefix}course_modules 
							on ({$CFG->prefix}block_notify_changes_log.module_id = {$CFG->prefix}course_modules.id) ) logs_with_visibility";
		// select all modules that are visible and whose status is pending
		$recent_activities = $DB->get_records_sql("select * from $subtable where course_id = $course_id and status='pending' and (visible = 1 or visible is null)");
		//print_r($recent_activities);
		// clear all pending notifications
		if(!empty($recent_activities))
			$DB->execute("update {$CFG->prefix}block_notify_changes_log set status = 'notified' 
								where 
									course_id = $course_id and status='pending' 
									and id in ( select id from $subtable where course_id = $course_id and (visible = 1 or visible is null) )");
		return $recent_activities;
	}

	function get_course_info($course_id){
		global $CFG, $DB;
		return current( $DB->get_records_sql("select fullname, summary from {$CFG->prefix}course where id = $course_id") );
	}
	
	// purge entries of courses that have been deleted
	function collect_garbage(){
		global $CFG, $DB;
		$course_list = "(select id from {$CFG->prefix}course)";
		$DB->execute("delete from {$CFG->prefix}block_notify_changes_courses where course_id not in $course_list");	
		$DB->execute("delete from {$CFG->prefix}block_notify_changes_log where course_id not in $course_list");	
	}

}
?>
