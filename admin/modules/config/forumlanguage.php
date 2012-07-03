<?php
if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}

$page->add_breadcrumb_item("Foren Sprache", "index.php?module=config-forumlanguage");
 
$languages = $lang->get_languages();
 
if($mybb->input['action']=="save") {
	foreach($languages as $key => $language) {
		$mybb->input[$key] = implode(",", $mybb->input[$key]); 
 		$insert = array(
			"language" => $db->escape_string($key),
			"fid" => $db->escape_string($mybb->input[$key])
		);
		$getLang=$db->simple_select("forumlanguage", "*", "language='$key'"); 
		if($db->num_rows($getLang)==0)
			$db->insert_query("forumlanguage", $insert);
		else
			$db->update_query("forumlanguage", $insert, "language='$key'");
	} 		
	flash_message("Gespeichert", 'success');
	admin_redirect("index.php?module=config-forumlanguage");
}
 
$page->output_header("Foren Sprache");

$form = new Form("index.php?module=config-forumlanguage&amp;action=save", "post");

$table = new Table;

$table->construct_header("Foren Sprache");
$table->construct_header("Foren");

foreach($languages as $key => $language) {
	$getLang=$db->simple_select("forumlanguage", "fid", "language='$key'"); 
	$forums=explode(",", $db->fetch_field($getLang, "fid"));
	$table->construct_cell($language, array('width' => '50%'));
	$forum = $form->generate_forum_select($key."[]", $forums, array("multiple" => "true"));
	$table->construct_cell($forum, array('width' => '50%'));
	$table->construct_row();
}
$table->output("Foren Sprache");

$buttons[] = $form->generate_submit_button("Speichern");
$buttons[] = $form->generate_reset_button($lang->reset);
$form->output_submit_wrapper($buttons);
$form->end();

$page->output_footer(); 
?>
