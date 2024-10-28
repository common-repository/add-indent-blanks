<?php
/*
Plugin Name: Add Indent Blanks
Plugin URI: http://blog.programet.org/
Description: This plugin give writers an option to automatically add spaces before paragraphs.
Version: 1.0
Author: LastLeaf
Author URI: http://blog.programet.org/lastleaf
License: GPLv3
*/

define('AIB_INDENT_TIMES', 4-1);

load_plugin_textdomain('aib', false, dirname(plugin_basename( __FILE__ )) . '/languages/');

// meta box
add_action('add_meta_boxes', 'aib_add_meta_box', 10, 2);
function aib_add_meta_box($post_type, $post) {
    if(!aib_has_indent($post))
    	add_meta_box('aib-meta-box', __('Add Spaces', 'aib'), 'aib_meta_box_show_content', 'post', 'side', 'high');
}
function aib_meta_box_show_content($post) {
	wp_nonce_field( plugin_basename( __FILE__ ), 'aib_nonce' );
	echo '<div><p>';
	echo '<input id="aib-add-check" type="checkbox" ';
	echo 'value="aib-add-check" name="aib-add-check"><label for="aib-add-check">';
	echo __('&nbsp;Auto add indent before paragraphs', 'aib');
	echo '</label></p>';
	echo '<p>';
	echo __('If you have added indent manually, do not check it. You can preview changes after you save the post.', 'aib');
	echo '</p></div>';
}

// manage settings
function aib_is_empty_line($content)
{
	// check empty line
	if(preg_match('/((\<p\>|\<br\>|\<br\/\>|\<br \/\>|^)[\s]*)+(\&nbsp\;|[\s]+)*$/', $content))return true;
	// wipe the beginning and check special content
	$content = preg_replace('/^(\<p\>|\<br\>|\<br\/\>|\<br \/\>|\s)*/', ' ', $content);
	if(preg_match('/^(\&nbsp\;|\s)*\</', $content))return true;
	
	return false;
}
function aib_is_valid($content)
{
	// combine multi-spaces and check
	$matches=array();
	if(!preg_match('/\&nbsp\;(\&nbsp\;|\s)+/m', $content, $matches))return false;
	$newcontent = preg_replace_callback('/(\s)+/m', 'aib_is_valid_replace', $matches[0]);
	if(!preg_match('/\&nbsp\;(\&nbsp\;|\s){' . AIB_INDENT_TIMES . '}/m', $newcontent))return false;
	
	return true;
}
function aib_is_valid_replace($content)
{
	return ' ';
}
function aib_has_indent($post)
{
	if(!$post->post_content)return false;
	preg_match_all('/((\<p\>|\<br\>|\<br\/\>|\<br \/\>|^)[\s]*)+.*$/m', $post->post_content, $matches);
	foreach($matches[0] as $match)
	{
		if(!aib_is_valid($match))
		{
			if(!aib_is_empty_line($match))return false;
		}
	}
	return true;
}
add_filter('content_save_pre', 'aib_indent', 5, 1);
function aib_indent($content)
{
	if(array_key_exists('aib-add-check', $_POST))
	{
		if( !wp_verify_nonce( $_POST['aib_nonce'], plugin_basename( __FILE__ ) ) )
      		return $content;
      	$content = preg_replace_callback('/((\<p\>|\<br\>|\<br\/\>|\<br \/\>|^)[\s]*)+.*$/m', 'aib_indent_replace', $content);
	}
	return $content;
}
function aib_indent_replace($matches)
{
	if(aib_is_empty_line($matches[0]))return $matches[0];
	//add_post_meta($_POST['post_ID'], 'aib-test1', rawurlencode($matches[0]));
	return preg_replace_callback('/^(\s|\<p\>|\<br\>|\<br\/\>|\<br \/\>)*(\&nbsp\;|\s)*/', 'aib_indent_replace_replace', $matches[0]);
}
function aib_indent_replace_replace($matches)
{
	$match = $matches[0];
	//add_post_meta($_POST['post_ID'], 'aib-test', rawurlencode($match));
	while(!aib_is_valid($match))
		$match = $match . '&nbsp;';
	return $match;
}

?>