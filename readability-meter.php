<?php

/*
Plugin Name: Readability Meter (AKA 'Geared Towards' Meter)
Plugin URI: http://www.codingrush.com/
Description: This plugin simply adds a graphical display, showing who the target demographic (i.e. audience) is. In other words, display graphically, whether your article is geared towards Novice, Intermediate, Advanced, Experts, or Gurus Users.
Version: 0.1
Author: RushiKumar
Author URI: http://www.codingrush.com
*/

/*  Copyright 2009 RushiKumar Bhatt (email : RushiKumar dot Bhatt at no spam dot gmail dot com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; GNU GPL version 3 of the License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//Define table name to use throughout our Plugin
global $wpdb;
$wpdb->rmeter = $wpdb->prefix.'rm_post_ref';

//Define a term that points to the folder where our three images will be stored
define('RMETER', "wp-content/plugins/readability-meter/img");

/**
 ** Function: Adding "Readability Meter" Meta box
 **/
add_action('admin_menu', 'rmeter_add_meta_box');
function rmeter_add_meta_box() {
	add_meta_box('rmeterdiv', __('Readability Meter', 'rmeter'), 'rmeter_metabox_admin', 'post', 'side');
}

/**
 ** Function: Readability Meter Activate & Install
 **/
//First, we add the action, which tells WordPress to set the activation hook for a plugin
//this particular call requires to parameters: the file location and name and the function name
add_action('init', 'rmeter_init');
function rmeter_init(){
	global $wpdb;

	//let's start by defining our table name
	$table_name = $wpdb->prefix . "rm_post_ref";
	
	//now, we make sure that such a table doesn't already exist.
	//if our desired table name already exist, we will not Create it again!
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		//we will be creating a table that holds two key pieces of information: meter type, and the post it belongs to.
		$create_rmeter_info = "CREATE TABLE " . $table_name . " (
						`post_id` INT NOT NULL ,
						`rmeter_type` TEXT NOT NULL ,
						PRIMARY KEY ( `post_id` )
						);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($create_rmeter_info);			
	}
}

/**
** Function: Meta Box Function
**/
function rmeter_metabox_admin() {
	global $wpdb, $post;
	$currentPID = $post->ID;
	echo '
	<h3 class="dbx-handle"> Selection </h3>
	<h4 class="dbx-handle">Assign Readability Meter to This Post</h4>
	<div class="dbx-content">
			<select id="select_rmeter" name="select_rmeter" onChange="rmeter_preview.src=(\''.rmeter_url().''.RMETER.'/\'+this.options[this.selectedIndex].value);">';
			
			
	//let's proceed only if the post id exist -- this ensures that we don't make unneccessary queries to our database
	if($currentPID != null){
		//echo 'not null';
		$chkIfExist = $wpdb->get_results("SELECT rmeter_type FROM `$wpdb->rmeter` WHERE `post_id` = $currentPID AND `rmeter_type` != ''");
	}
	
	//if we have already associated a readability meter to the post, let's display
	//that particular meter name first (instead of the non-sensical "Select Readability Level..." text)
	if($chkIfExist){
		$rmeter_name = $chkIfExist[0]->rmeter_type;
		
		$rmeter_name_ch = explode('.', $rmeter_name);
		$getName = implode(".", array_slice($rmeter_name_ch, 0, count($rmeter_name_ch) - 1));			
		$getName = ucwords($getName);
			
		//we are going to padd the select box with some blank spaces... just so it doesn't look all crammed up!
		echo '<option value="'.$rmeter_name.'">'.$getName.'   </option>';
	//if have yet to associate a readability meter to the post, we will
	//let the author select know to select one...
	}else{
		echo '<option value="">Select Readability Level ... </option>';
	}
	echo '<option value="novice.gif">Novice </option>
		  <option value="intermidiate.gif">Intermediate   </option>
		  <option value="advance.gif">Advanced </option>
		  <option value="expert.gif">Experts </option>
		  <option value="guru.gif">Gurus </option>';
	echo '
			</select>
		</p>
	</div>
	
	<h3 class="dbx-handle"> Preview </h3>
	<div class="dbx-content">
		<br />
		<center><img id="rmeter_preview" src="'. rmeter_url().''.RMETER.'/'.$rmeter.'" /></center>
		<br />
	</div>';
	
	//Adding functionality: Displaying current selected readability meter for the post at hand (i.e., for the current post)
	if ($chkIfExist) {
		//echo 'found a match...<br />';
		echo '<h3 class="dbx-handle"> Current Selection</h3>
		<div class="dbx-content">
			<br />
			<center><img id="curr_rmeter_preview" src="'. rmeter_url().''.RMETER.'/'.$chkIfExist[0]->rmeter_type.'" /></center>
			<br />
		</div>';
	}
}

/**
** Function: Get the site's url
**/
function rmeter_url() {
	$url = trailingslashit(get_option('siteurl')) . $def_path; // idem
	return $url;
}

/**
** Function: Save our information whenever we save or publish the post
**/
add_action('save_post', 'add_rmeter_admin_process');
function add_rmeter_admin_process($post_ID) {
	global $wpdb;
	
	$cPID = wp_is_post_revision($post_ID);
	if($cPID != null){
		//echo 'not null<br />';
		$select_rmeter = $_POST['select_rmeter'];
		//echo 'Select_rmeter POST was: '.$select_rmeter.'<br />';
		//echo 'SQL is: '.$sql.'<br />';
		// Ensure No Duplicate Field
		$check = intval($wpdb->get_var("SELECT * FROM `$wpdb->rmeter` WHERE `post_id` = $cPID"));
		//echo 'check is: '.$check.'<br />';
		if($check == 0) {
			//echo 'new<br />';
			$wpdb->query( "INSERT INTO $wpdb->rmeter (post_id, rmeter_type) VALUES ($cPID, '".$select_rmeter."')" );
		} else {
			//echo 'update<br />';
			if($select_rmeter != null){
				//echo 'upd-> notnull<br />';
				$wpdb->query( "UPDATE $wpdb->rmeter SET rmeter_type = '".$select_rmeter."' WHERE post_id = $cPID" );
			}
		}
	}
}


/**
** Function: Delete the readability meter associated with the post when the post itself gets deleted
**/
add_action('delete_post', 'delete_rmeter_process');
function delete_rmeter_process($post_ID) {
	global $wpdb;
	
	$cPID = wp_is_post_revision($post_ID);
	if($cPID != null){
	
		// Only proceed further if the readability meter exist
		$check = intval($wpdb->get_var("SELECT * FROM `$wpdb->rmeter` WHERE `post_id` = $cPID"));
		if($check > 0) {
			$wpdb->query( "DELETE FROM $wpdb->rmeter WHERE post_id = $cPID" );
		}
	}
}

add_action('wp_head', 'rmeter_style');
function rmeter_style() {
	echo '
	<style>
	<!--
		.info {
			background: #efefef url('. rmeter_url().''.RMETER.'/style/info.png) no-repeat 5px 14px;
			padding: 18px 2px 24px 50px;
			width: 85%;
			margin-top: 15px;
			margin-bottom: 0;
			border: 1px solid #66C1FF;
			font-size: 12px;
			line-height: 17px;
			color: #272727;
			font-weight: 500;
		}
		
		.rmeter_class {
			float: right;
			margin-top: -30px;
		}
	-->
	</style>';
}

/**
** Function: Display our readability meter within the post/the content itself
**/
function display_readability_meter($post_content){
	global $wpdb, $post;
	if($post->ID != null){
		// Only proceed further if the readability meter exist for the post
		$chkIfExist = $wpdb->get_results("SELECT rmeter_type FROM `$wpdb->rmeter` WHERE `post_id` = $post->ID");
		if($chkIfExist) {
			$filename = $chkIfExist[0]->rmeter_type;
			
			$FileNameTokens = explode('.', $filename);
			$gearedTowards = implode(".", array_slice($FileNameTokens, 0, count($FileNameTokens) - 1));

			
			$gearedTowards = ucwords($gearedTowards);
			$imageStr = '<div class="info">
							This Article is Geared towards <strong><em>'.$gearedTowards.'</em></strong> users<img id="readability_meter" class="rmeter_class" src="'. rmeter_url().''.RMETER.'/'.$chkIfExist[0]->rmeter_type.'" />
						</div><br />';
			$post_content = $imageStr . $post_content;
		}
	}
	return $post_content;
}
//we need to tell word press to pass the content THROUGH our function... so we can append readability meter to the post
add_filter('the_content', 'display_readability_meter');

?>