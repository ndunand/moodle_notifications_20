<?php 
//***************************************************	
// Mail notification
//***************************************************	
class eMail {

	function notify_changes($changelist, $user, $course) {
		$html_message = $this->html_mail($changelist, $course);
		$text_message = $this->text_mail($changelist, $course);
		$subject = get_string('mailsubject', 'block_notify_changes');
		$subject.= ": ".format_string($course->fullname, true);
		return email_to_user($user, '', $subject, $text_message, $html_message);
	}


	function html_mail($changelist, $course) {
		global $CFG;
		$mailbody = '<head>';
		foreach ($CFG->stylesheets as $stylesheet) {
			$mailbody .= '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'" />'."\n";
		}
		$mailbody .= '</head>';
		$mailbody .= "<body id=\"email\">";
		$mailbody .= '<div class="header">';
		$mailbody .= get_string('mailsubject', 'block_notify_changes').' ';
		$mailbody .= '&laquo; <a target="_blank" href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.$course->fullname.'</a> &raquo; ';
		$mailbody .= '</div>';
		$mailbody .= '<div class="content">';
		$mailbody .= '<ul>';
		foreach ($changelist as $change) {
			$mailbody .='<li>';
			if( $change['operation'] != 'delete'){
				$mailbody .= $change['operation'].': ';
				$mailbody .='<a href="'.$CFG->wwwroot.'/mod/'.$change['modname'].'/view.php?id='.$change['id'].'">'.$change['resource_name'].'</a>';
			} else 
				$mailbody .= $change['text'];
			
			$mailbody .= '</li>';
		}
		$mailbody .= '</ul>';
		$mailbody .= '</div>';
		$mailbody .= '</body>';

		return $mailbody;
	}
	 
	function text_mail($changelist, $course) {
		global $CFG;
		$mailbody = get_string('mailsubject', 'block_notify_changes').': '.$course->fullname.' ';
		$mailbody .= $CFG->wwwroot.'/course/view.php?id='.$course->id."\r\n\r\n";
		foreach ($changelist as $change) {
			if( $change['operation'] != 'delete'){
				$mailbody .= "\t".$change['operation'].': ';
				$mailbody .= $change['resource_name']."\r\n";
				$mailbody .= "\t".$CFG->wwwroot.'/mod/'.$change['modname'].'/view.php?id='.$change['id']."\r\n\r\n";
			} else 
				$mailbody .= "\t".$change['text'];
		}
		/*
		print_r("\n");
		print_r("\n");
		print_r("\n");
		print_r($mailbody);
		*/
		return $mailbody;
	}
}
?>
