<?php
//echo preg_quote('{$lang->show_redirect}</label></span></td></tr>');
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}
if(!$pluginlist)
    $pluginlist = $cache->read("plugins");

$plugins->add_hook("global_start", "forumlanguage_forum");
$plugins->add_hook("usercp_options_end", "forumlanguage_ucp");
$plugins->add_hook("datahandler_user_update", "forumlanguage_ucp_handler");

if(is_array($pluginlist['active']) && in_array("myplugins", $pluginlist['active'])) {
	$plugins->add_hook("myplugins_actions", "forumlanguage_myplugins_actions");
	$plugins->add_hook("myplugins_permission", "forumlanguage_admin_config_permissions");
} else {
	$plugins->add_hook("admin_config_menu", "forumlanguage_admin_config_menu");
	$plugins->add_hook("admin_config_action_handler", "forumlanguage_admin_config_action_handler");
	$plugins->add_hook("admin_config_permissions", "forumlanguage_admin_config_permissions");
}

function forumlanguage_info()
{
	return array(
		"name"			=> "Forum Language",
		"description"	=> "Make Forums just availabale for some languages",
		"website"		=> "http://jonesboard.de/",
		"author"		=> "Jones",
		"authorsite"	=> "http://jonesboard.de/",
		"version"		=> "1.0",
		"guid" 			=> "",
		"compatibility" => "16*",
		"myplugins_id"	=> "forum-language"
	);
}

function forumlanguage_install()
{
	global $db;
	$col = $db->build_create_table_collation();
	$db->query("CREATE TABLE `".TABLE_PREFIX."forumlanguage` (
				`language` varchar(100),
				`fid` varchar(20))
	ENGINE=MyISAM {$col}");
	$settinggroup = array(
        "name" => "Forum Language",
        "title" => "Forum Language",
        "description" => "Settings for the \"Forum Language\" Plugin.",
        "disporder" => "1",
        "isdefault" => "0",
        );
    $db->insert_query("settinggroups", $settinggroup);
    $gid = $db->insert_id();

    $setting = array(
        "name" => "forumlanguage_ucp",
        "title" => "Welche Gruppen können Spracheinstellungen überbrücken",
        "description" => "",
        "optionscode" => "text",
        "value" => "0",
        "disporder" => "1",
        "gid" => (int)$gid,
        );
    $db->insert_query("settings", $setting);
	rebuild_settings();

	$template="
</tr>
<tr>
<td valign=\"top\" width=\"1\"><input type=\"checkbox\" class=\"checkbox\" name=\"showotherforums\" id=\"showotherforums\" value=\"1\" {\$showotherforumscheck} /></td>
<td><span class=\"smalltext\"><label for=\"showotherforums\">Zeige Foren in anderen Sprachen?</label></span></td>";
	lang_createTemplate("usercp_options_forumlanguage", $template);

	$db->add_column('users', 'showotherforums', "int(1) NOT NULL default '0'");
}

function forumlanguage_is_installed()
{
	global $db;
	return $db->table_exists("forumlanguage");
}

function forumlanguage_uninstall()
{
	global $db;
	$db->drop_table("forumlanguage");
	$query = $db->simple_select("settinggroups", "gid", "name='Forum Language'");
    $g = $db->fetch_array($query);
	$db->delete_query("settinggroups", "gid='".$g['gid']."'");
	$db->delete_query("settings", "gid='".$g['gid']."'");
	rebuild_settings();
    $db->delete_query("templates", "title='usercp_options_forumlanguage'");
	$db->drop_column('users', 'showotherforums');
}

function forumlanguage_activate()
{
	require MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets("usercp_options", "#".preg_quote('{$lang->show_redirect}</label></span></td>')."#i", '{$lang->show_redirect}</label></span></td>{$forumlang}');
}

function forumlanguage_deactivate()
{
	require MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets("usercp_options", "#".preg_quote('{$forumlang}')."#i", "", 0);
}

function forumlanguage_ucp()
{
	global $mybb, $forumlang, $templates, $user;
	$allowed = explode(",", $mybb->settings['forumlanguage_ucp']);
	if(!lang_user_in_group($mybb->user, $allowed))
	    return;

	if($user['showotherforums'] == 1)
	{
		$showotherforumscheck = "checked=\"checked\"";
	}
	else
	{
		$showotherforumscheck = "";
	}
	eval("\$forumlang = \"".$templates->get("usercp_options_forumlanguage")."\";");
}

function forumlanguage_ucp_handler($user)
{
	global $mybb;
	$user->user_update_data['showotherforums']=$mybb->input['showotherforums'];
	return $user;
}

function forumlanguage_forum($forum)
{
	global $db, $mybb, $forum_cache, $cache, $unviewableforums, $lang;
	$allowed = explode(",", $mybb->settings['forumlanguage_ucp']);
	if(lang_user_in_group($mybb->user, $allowed) && $mybb->user['showotherforums']=="1")
	    return;
	$language=$lang->language ;
	$getLang=$db->simple_select("forumlanguage", "*", "language='$language'");
   	if($db->num_rows($getLang)==0)
		return;
	$allowed=explode(",", $db->fetch_field($getLang, "fid"));
	$current_forum_cache = $cache->read('forums');
	foreach($current_forum_cache as $fid=>$value) {
		if(in_array($fid, $allowed)) {
			$new_forum_cache[$fid] = $current_forum_cache[$fid];
		}
	}
	$forum_cache = $new_forum_cache;
}

function forumlanguage_myplugins_actions($actions)
{
	global $page, $lang, $info;

	$actions['forumlanguage'] = array(
		"active" => "forumlanguage",
		"file" => "../config/forumlanguage.php"
	);

	$sub_menu = array();
	$sub_menu['10'] = array("id" => "forumlanguage", "title" => "Foren Sprache", "link" => "index.php?module=myplugins-forumlanguage");

	$sidebar = new SidebarItem("Foren Sprache");
	$sidebar->add_menu_items($sub_menu, $actions[$info]['active']);

	$page->sidebar .= $sidebar->get_markup();

	return $actions;
}

function forumlanguage_admin_config_menu($sub_menu)
{
	$sub_menu[] = array("id" => "forumlanguage", "title" => "Foren Sprachen", "link" => "index.php?module=config-forumlanguage");

	return $sub_menu;
}

function forumlanguage_admin_config_action_handler($actions)
{
	$actions['forumlanguage'] = array(
		"active" => "forumlanguage",
		"file" => "forumlanguage.php"
	);

	return $actions;
}

function forumlanguage_admin_config_permissions($admin_permissions)
{	$admin_permissions['forumlanguage'] = "Kann Foren Sprache verwalten?";

	return $admin_permissions;
}

function lang_user_in_group($user, $allowedgroups)
{
	if(sizeof($allowedgroups)==1 && $allowedgroups[0]==0)
	    return true;
	$groups = array();
	$agroups = explode(',', $user['additionalgroups']);
	array_push($groups, $user['usergroup']);
	for($i=0; $i<sizeof($agroups); ++$i) {
		array_push($groups, $agroups[$i]);
	}
	$in = false;
	foreach ($groups as $group) {
		if(in_array($group, $allowedgroups)) {
		   $in = true;
		}
	}
	return $in;
}

function lang_createTemplate($name, $template)
{
	global $db;
	$templatearray = array(
	        "title" => $name,
	        "template" => $template,
	        "sid" => "-2",
	        );
    $db->insert_query("templates", $templatearray);
}
?>