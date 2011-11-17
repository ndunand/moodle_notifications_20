****************************************************************
* Thu Nov 17 11:16:44 CET 2011
* Goran Josic goran.josic@usi.ch
* eLab - http://www.elearninglab.org
****************************************************************

Thank you for using moodle_notifications plugin. 

About: 
This plugin notifies changes and updates on Moodle courses via
three channels: e-mail, sms and rss.
For the license please check LICENSE.txt file.


Note:
SMS functionality depends on your provider. To enable SMS
channel please extend lib/AbstractSMS.php class. Call the new
class SMS. Check lib/SMS.php.sample if you need a starting
point.

Installation:
Before you start the installation please read the instructions. 
	
	1.	rename the plugin folder to moodle_notifications if you have
		chosen a different name during the repository cloning

	2.	move the folder to the blocks directory of your Moodle
		installation

	3.	Login in your Moodle platform as Administrator and
		click on Notifications inside Site Administration block.

At this point the tables should be created and the plugins should
be available in the Blocks list.

Settings: 
This plugin has three levels of settings. 
	- Global settings are managed by Administrators	 
	- Course settings are managed by Teachers and assistants
	- Personal settings are managed by Students

Global settings have priority on Course settings and the
Course settings have priority on Personal Settings.
The e-mail and sms channels can be enabled and disabled on 
every level. Only the rss channel is managed Globally or on
Course level.

Bugs:
If you find a bug please submit it here:
http://sourceforge.net/tracker/?func=add&group_id=398313&atid=1652206

Please provide the bug description and don't forget Moodle and 
moodle_notifications plugin version.
