<?php
/*
	Plugin Name: Activity List
	Plugin URI: https://github.com/NoahY/q2a-admin-plus
	Plugin Description: Adds activity list to user profile, and links to all questions and answers of a user
	Plugin Version: 1.0
	Plugin Date: 2011-10-26
	Plugin Author: NoahY
	Plugin Author URI: http://www.question2answer.org/qa/user/NoahY
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.4

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.gnu.org/licenses/gpl.html
	
*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
}

qa_register_plugin_layer('qa-user-activity-layer.php', 'User Activity Layer');
qa_register_plugin_module('event', 'qa-user-activity-check.php','user_activity_check','History Check');
qa_register_plugin_module('module', 'qa-user-activity-admin.php','user_activity_admin','History Admin');
