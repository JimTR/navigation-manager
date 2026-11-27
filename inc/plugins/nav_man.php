<?php
/*
 * nav_man.php
 * 
 * Copyright 2026 Jim Richardson <jim@phporyx.co.uk>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 * 
 * 
 */
if(!defined("IN_MYBB")){die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");}
$calling_hook = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_FILENAME)."_end";
$plugins->add_hook($calling_hook, 'nav_man_mangle_nav');
$GLOBALS['nav_codename'] = basename(__FILE__, '.php');
$GLOBALS['calling_hook'] = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_FILENAME)."_end";

function nav_man_info() {
/*
 * 
 * name: nav-man_info
 * sends plugin info back to mybb
 * 
 * 
 */
	global $nav_codename;

	return array(
		"name"			=> 'Navigation Manager',
		"description"	=> "Control the navigation system",
		"website"		=> "https://phporyx.co.uk/plugins",
		"author"			=> 'JimR',
		"authorsite"	=> "https://community.mybb.com/user-37677.html",
		"version"		=> '2.00a',
		"guid" 			=> '',
		"codename"	=> "navman",
		"compatibility" => '18*'
	);
	
}

function nav_man_install(){
	global $mybb, $db, $nav_codename;
	$group_data = array(
		'name'		=> "{$nav_codename}",
		'title'		=> 'Navigation Manager 2',
		'description'	=> 'Settings for the Navigation Manager  plugin.',
		'disporder'	=> 1,
		'isdefault'	=> 0
	);
	$query = $db->simple_select(
		'settinggroups',
		'gid',
		"name='$nav_codename'"
	);

	$gid = (int) $db->fetch_field($query, 'gid');
	if (!$gid) {
		$gid = (int) $db->insert_query('settinggroups', $group_data);
	} 
	else {
		$db->update_query('settinggroups', $group_data, "gid='{$gid}'");
	}
}

function nav_man_is_installed(){
	global $mybb, $db, $nav_codename;
	// check for setting 
	$name="{$nav_codename}_home_title";
	
	$query = $db->simple_select(
		'settings',
		'sid',
		"name='$name'"
	);
	$sid = (int) $db->fetch_field($query, 'sid');
	if ($sid) {
		return true;
		
	}
	return false;
}

function nav_man_activate(){
	// activation code
	global $mybb, $db, $nav_codename;
	
	$group_data = array(
		'name'		=> "{$nav_codename}",
		'title'		=> 'Navigation Manager 2',
		'description'	=> 'Settings for the Navigation Manager  plugin.',
		'disporder'	=> 1,
		'isdefault'	=> 0
	);
	$query = $db->simple_select(
		'settinggroups',
		'gid',
		"name='$nav_codename'"
	);

	$gid = (int) $db->fetch_field($query, 'gid');
	if (!$gid) {
		$gid = (int) $db->insert_query('settinggroups', $group_data);
	} 
	else {
		$db->update_query('settinggroups', $group_data, "gid='{$gid}'");
	}
	$settings = array(
		array(
			'name'=> "{$nav_codename}_home_title",
			"title" => "Home breadcrumb title",
			"description" => "The text displayed for the website Home breadcrumb.",
			"optionscode" => "text",
			"value" => "Home",
			'gid'	=> (int) $gid
		),
		array(
			'name'=> "{$nav_codename}_home_page",
			"title" => "Home page URL",
			"description" => "The text displayed for the forum section breadcrumb when navigating pages that are not listed as direct links under Home.",
			"optionscode" => "text",
			"value" => "",
			'gid'	=> (int) $gid
		),
		array(
			'name'=> "{$nav_codename}_forum_title",
			"title" => "Forum breadcrumb title",
			"description" => "This setting will display on the navigation as the link text for index.php if you are using a different script as your home page",
			"optionscode" => "text",
			"value" =>'Forums',
			'gid'	=> (int) $gid
		),
		array(
			'name'=> "{$nav_codename}_home_hide_breadcrumb",
			"title" => "Hide Home Breadcrumb",
			"description" => "Hide the Home breadcrumb when viewing the configured home page.",
			"optionscode" => "yesno",
			"value" => 0,
			'gid'	=> (int) $gid
		),
		array(
			'name'=> "{$nav_codename}_files",
			"title" => "Direct links under Home",
			"description" => "Add scripts that will <b>NOT</b> have the forum link added to the navigation. seperate each script with a comma.<br>This has no bearing if you use index.php as your home page ",
			"optionscode" => "text",
			"value" => "",
			'gid'	=> (int) $gid
		),   
	);
	foreach ($settings as $setting){
	
		$query = $db->simple_select(
			'settings',
			'sid',
			"name='{$setting['name']}'"
		);

		$sid = (int) $db->fetch_field($query, 'sid');

		if ($sid) {
			// Preserve the existing setting value.
			unset($setting['value']);
			$db->update_query('settings', $setting, "sid='{$sid}'");
		} 
		else {
			$db->insert_query('settings', $setting);
		}
	}
}
function nav_man_deactivate(){
	// deactive code
	global $mybb, $db, $nav_codename;
	$db->delete_query('settinggroups', "name='{$nav_codename}'");
	rebuild_settings();
	
}

function nav_man_uninstall(){
	// uninstall
	global $mybb, $db, $lang,$page, $nav_codename;
	if ($mybb->request_method != 'post') {
		$page->output_confirm_action(
			"index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=$nav_codename",
			"Uninstall $nav_codename ?<br>Are you sure you want to completely uninstall $nav_codename ? <br>This will permanently remove all plugin settings.",''
		);
	
	}
	if (isset($mybb->input['no'])) {
		admin_redirect('index.php?module=config-plugins');
	}
	if (!verify_post_check($mybb->get_input('my_post_key'))) {
		flash_message("Security validation failed. Please try again.", 'error');
		admin_redirect('index.php?module=config-plugins');
		return;
	}
	$setting_names = array(
		"{$nav_codename}_home_title",
		"{$nav_codename}_home_page",
		"{$nav_codename}_forum_title",
		"{$nav_codename}_home_hide_breadcrumb",
		"{$nav_codename}_files"
	);
	foreach ($setting_names as $setting){
		$setting = $db->escape_string($setting);
		$db->delete_query('settings', "name = '{$setting}'");
	}
	$db->delete_query('settinggroups', "name = '$nav_codename'");
	rebuild_settings();
	// TODO delete Files
}

function nav_man_mangle_nav() {
	global $mybb, $navbits, $nav_codename;

	$home_url = trim($mybb->settings["{$nav_codename}_home_page"]);

	if ($home_url === '') {
		$home_url = $mybb->settings['bburl'].'/index.php';
	}

	$home_hide_breadcrumb = (int) $mybb->settings["{$nav_codename}_home_hide_breadcrumb"];
	$home_title = $mybb->settings["{$nav_codename}_home_title"];
	$forum_title = $mybb->settings["{$nav_codename}_forum_title"];

	$home_parts = parse_url($home_url);
	$home_path = $home_parts['path'] ?? '';
	$home_query = $home_parts['query'] ?? '';
	$home_script = basename($home_path);

	$home_host = $home_parts['host'] ?? '';
	$forum_host = parse_url($mybb->settings['bburl'], PHP_URL_HOST);

	$is_local_home = empty($home_host) || strcasecmp($home_host, $forum_host) === 0;
	$is_home = $is_local_home && $home_script === THIS_SCRIPT;

	$tmp_navbits = $navbits;

	$home_scripts = array_map(
		'trim',
		explode(',', $mybb->settings["{$nav_codename}_files"])
	);

	array_splice($navbits, 0);

	if (
		$is_home &&
		$home_query === ($_SERVER['QUERY_STRING'] ?? '') &&
		$home_hide_breadcrumb
	) {
		return;
	}
	if ($is_home) {
		$navbits[0]['name'] = $home_title;
		$navbits[0]['url'] = $home_url;
	return;
}

	if (in_array(THIS_SCRIPT, $home_scripts, true)) {
		unset($tmp_navbits[0]);
	}
	else {
		$tmp_navbits[0]['name'] = $forum_title;
	}

	$navbits[0]['name'] = $home_title;
	$navbits[0]['url'] = $home_url;

	foreach ($tmp_navbits as $navbit) {
		$navbits[] = $navbit;
	}
}
/*
function nav_man_debug_output($contents) {
	global $navbits,$calling_hook;
	$debug = '<pre>'.htmlspecialchars(print_r($navbits, true)).'</pre>';
	$debug .= "Calling Hook $calling_hook";
	return $contents.$debug;
}
*/