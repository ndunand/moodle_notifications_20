<?php

defined('MOODLE_INTERNAL') || die;
global $CFG;

if ($ADMIN->fulltree) {
	$settings->add(new admin_setting_heading('block_notify_changes_settings', '', get_string('global_configuration_comment', 'block_notify_changes')));
	$settings->add(new admin_setting_configcheckbox('block_notify_changes_email_channel', get_string('email', 'block_notify_changes'), '', 1));
	$settings->add(new admin_setting_configcheckbox('block_notify_changes_sms_channel', get_string('sms', 'block_notify_changes'), '', 1));
	$settings->add(new admin_setting_configcheckbox('block_notify_changes_rss_channel', get_string('rss', 'block_notify_changes'), '', 1));

	$options = array();
	for($i=1; $i<25; ++$i)
		$options[$i] = $i;

	$default = 12;
	if( isset($CFG->block_notify_changes_frequency) ) 
		$default = $CFG->block_notify_changes_frequency;

    $settings->add(new admin_setting_configselect('block_notify_changes_frequency', 
													get_string('notification_frequency', 'block_notify_changes'), 
													get_string('notification_frequency_comment', 'block_notify_changes'), $default , $options));
}

