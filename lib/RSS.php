<?php 
include_once realpath(dirname( __FILE__ ).DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR."common.php";
include_once LIB_DIR."Course.php";
include_once LIB_DIR."User.php";

class RSS {
	function __construct($course_id){
		global $CFG, $DB;	
		
		$Course = new Course();
		// if the course is not registered or
		// the course is registered but the block is not active
		//if( !$Course->is_registered($course_id) or !$Course->uses_notify_changes_block($course_id) ) {
		if( !$Course->is_registered($course_id) or !$Course->uses_notify_changes_block($course_id) ) {
			echo "RSS on this course is not enabled.";
			return;
		}
		$User = new User();
		$teacher = $User->get_professor($course_id);

		$course_info = $Course->get_course_info($course_id);
		//var_dump($course_info); exit;
		$course_registration = $Course->get_registration($course_id);

		//print_r("here");
		if ( $course_registration->notify_by_rss != 1 ) return;
		// here
		$now = date("D, d M Y H:i:s T");
		$output = "<?xml version=\"1.0\"?>
					<rss version=\"2.0\">
					<channel>
					<title>$course_info->fullname</title>
					<link>$CFG->wwwroot/course/view.php?id=$course_id</link>
					<description>$course_info->summary</description>
					<language>en-us</language>
					<pubDate>$now</pubDate>
					<lastBuildDate>$now</lastBuildDate>
					<docs>$CFG->wwwroot/course/view.php?id=$course_id</docs>
					<managingEditor>$teacher->email</managingEditor>
					<webMaster>helpdesk@elearninglab.org</webMaster>";

		$moodle_logs = $DB->get_records_sql("select 
											{$CFG->prefix}log.id, 
											{$CFG->prefix}block_notify_changes_log.module_id, 
											{$CFG->prefix}block_notify_changes_log.type, 
											time, 
											name, 
											{$CFG->prefix}log.action 
											from 
											{$CFG->prefix}log join {$CFG->prefix}block_notify_changes_log on 
												({$CFG->prefix}block_notify_changes_log.module_id = {$CFG->prefix}log.cmid) 
													where {$CFG->prefix}log.action in ('add','update','delete mod') 
														and {$CFG->prefix}block_notify_changes_log.status = 'notified' 
															and course = $course_id order by time desc limit 20");

		//print_r($moodle_logs); exit;

		foreach($moodle_logs as $log){
			$output .= "<item>";
			$output .= '<title>'.get_string($log->type, 'block_notify_changes').'</title>';
			if($log->action == 'delete mod')
				$output .= "<link></link>";
			else
				$output .= "<link>$CFG->wwwroot/mod/$log->type/view.php?id=$log->module_id</link>";

			$output .= "<description>";
			switch($log->action){
				case 'add':	
					$output .= get_string('added', 'block_notify_changes').' ';
					break;
				case 'update':	
					$output .= get_string('updated', 'block_notify_changes').' ';
					break;
				case 'delete mod':	
					$output .= get_string('deleted', 'block_notify_changes').' ';
					break;
			}
			$output .= get_string($log->type, 'block_notify_changes').': ';
			$output .= $log->name;
			$output .= "</description>";
			$output .= "</item>";
		}
		$output .= "</channel></rss>";
		header("Content-Type: application/rss+xml");
		echo $output;
	}
}

$course_id = intval( $_GET['id'] );
// if course id not valid exit

if( empty($course_id) ) {
	print_r("Invalid id.");
	exit;
}

$rss = new RSS($course_id);
/*
*/
?>
