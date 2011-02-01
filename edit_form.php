<?php
class block_notify_changes_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
		global $CFG;
		global $COURSE;
		$Course = new Course();
		$course_notification_setting = $Course->get_registration($COURSE->id);
        // Fields for editing HTML block title and contents.
		$mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

		$attributes = array();
		$attributes['disabled'] = 'disabled';

		if($CFG->block_notify_changes_email_channel == 1)
        	$mform->addElement('checkbox', 'notify_by_email', get_string('notify_by_email', 'block_notify_changes'));
		else
        	$mform->addElement('advcheckbox', 'notify_by_email', get_string('notify_by_email', 'block_notify_changes'), null, $attributes);
		if ( isset($course_notification_setting->notify_by_email) and $course_notification_setting->notify_by_email == 1 ) 
        	$mform->setDefault('notify_by_email', 1);
		if($CFG->block_notify_changes_sms_channel == 1)
        	$mform->addElement('checkbox', 'notify_by_sms', get_string('notify_by_sms', 'block_notify_changes'));
		else
        	$mform->addElement('advcheckbox', 'notify_by_sms', get_string('notify_by_sms', 'block_notify_changes'), null, $attributes);
		if ( isset($course_notification_setting->notify_by_sms) and $course_notification_setting->notify_by_sms == 1 ) 
        	$mform->setDefault('notify_by_sms', 1);

		if($CFG->block_notify_changes_rss_channel == 1)
        	$mform->addElement('checkbox', 'notify_by_rss', get_string('notify_by_rss', 'block_notify_changes'));
		else
        	$mform->addElement('advcheckbox', 'notify_by_rss', get_string('notify_by_rss', 'block_notify_changes'), null, $attributes);
		if ( isset($course_notification_setting->notify_by_rss) and $course_notification_setting->notify_by_rss == 1 ) 
        	$mform->setDefault('notify_by_rss', 1);
		if( 
			$CFG->block_notify_changes_email_channel == 1 or 
			$CFG->block_notify_changes_sms_channel == 1
		) {
	 		$options = array();
			for($i=1; $i<25; ++$i) $options[$i] = $i;
        	$mform->addElement('select', 'notification_frequency', get_string('notification_frequency', 'block_notify_changes'), $options);
        	$mform->setDefault('notification_frequency', $this->block->cron/3600);
		}
    }

    function set_data($defaults) {
		$block_config = new Object();
		$block_config->notify_by_email = file_get_submitted_draft_itemid('notify_by_email');
		$block_config->notify_by_sms = file_get_submitted_draft_itemid('notify_by_sms');
		$block_config->notify_by_rss = file_get_submitted_draft_itemid('notify_by_rss');
		$block_config->notification_frequency = file_get_submitted_draft_itemid('notification_frequency');
        unset($this->block->config->text);
		parent::set_data($defaults);
        $this->block->config = $block_config;
	}
/*
*/
}

?>
