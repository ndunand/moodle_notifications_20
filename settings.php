<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
	$settings->add(new admin_setting_heading('enrol_meta_settings', '', get_string('global_configuration_comment', 'block_notify_changes')));
    $settings->add(new admin_setting_configcheckbox('block_notify_changes_email_channel', get_string('email', 'block_notify_changes'), '', 1));
    $settings->add(new admin_setting_configcheckbox('block_notify_changes_sms_channel', get_string('sms', 'block_notify_changes'), '', 1));
    $settings->add(new admin_setting_configcheckbox('block_notify_changes_rss_channel', get_string('rss', 'block_notify_changes'), '', 1));
}

