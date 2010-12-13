<?php

//***************************************************	
// SMS notification abstract class
//***************************************************	
abstract class AbstractSMS{
	// overhead are number of chars of overhead in the sms message
	abstract function message($changelist, $course);
	abstract function notify_changes($changelist, $user, $course);
}

?>
