<?php 
include_once realpath(dirname( __FILE__ ).DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR."common.php";
print_header(get_string('help'));
print_simple_box_start();

// print title
echo '<h1>'.get_string('help_title', 'block_notify_changes').'</h1>';
echo '<p>'.get_string('set_mobile_number_instructions', 'block_notify_changes').'</p>';
print_simple_box_end();
close_window_button();
print_footer('none');
?>
