<?php
/*
Plugin Name: ICIT Image Localiser
Plugin URI: http://interconnectit.com
Description: Grabs remote images and downloads them to local FS
Version: 1.0 beta
Author: Tom J Nowell
Author URI: http://interconnectit.com
Author Email: tom@interconnectit.com
License:

  Copyright 2012 interconnect/it (tom@interconnectit.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once('icit-plugin/icit-plugin.php');
require_once('images.php');
// TODO: rename this class to a proper name for your plugin
class ICIT_ImageLocaliser {

	/*--------------------------------------------*
	 * Constructor
	 *--------------------------------------------*/

	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 */
	function __construct() {

		$this->donemeta = 'icit_localised_statet2';

		icit_register_plugin( 'ICIT_ImageLocaliser', __FILE__, $args = array( 'extra_content' => array(&$this, 'main_page'),'parent_slug'=>'tools.php') );

		load_plugin_textdomain( 'image-localiser-locale', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

		// Register admin styles and scripts
		add_action( 'admin_print_styles', array( &$this, 'register_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'register_admin_scripts' ) );

		// Register site styles and scripts
		add_action( 'wp_print_styles', array( &$this, 'register_plugin_styles' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'register_plugin_scripts' ) );

		register_activation_hook( __FILE__, array( &$this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );

		add_action('wp_ajax_localise_batch', array(&$this,'localise_batch_callback'));
		add_action('wp_ajax_localise_bad_batch', array(&$this,'localise_bad_batch_callback'));

		add_action('wp_ajax_localise_featured_batch', array(&$this,'localise_featured_batch_callback'));
		add_action('wp_ajax_localise_featured_meta_batch', array(&$this,'localise_featured_meta_batch_callback'));

	} // end constructor

	function localise_ajax_single_post($p){

		$m = $this->donemeta;
		if($_POST['action'] =='localise_featured_batch'){
			$m.= '_f';
		}
		global $icit_feed_images;
		//error_log(print_r($p,true));
		$ret = $icit_feed_images->sideload_remote_images($p);
		if(($ret == false)|| is_wp_error($ret) ){

			$error = ' returned false';
			if(is_wp_error($ret)){
				$error = implode(', ', $ret->get_error_messages());
			}
			echo '<p class="failed_localisation">Failed to get some images for <a href="'.get_permalink($p->ID).'">'.$p->post_title.' ( '.$p->ID.' )</a>, message given was: '.$error.'</p>';
			if(!update_post_meta($p->ID,$m,1)){
				add_post_meta($p->ID, $m, 1, true);
			}
		} else {
			$post_data = array();
			$post_data['ID'] = $p->ID;
			$post_data['post_content'] = $ret;
			$success = wp_update_post($post_data);
			if($success != 0){
				if(!update_post_meta($p->ID,$m,4)){
					add_post_meta($p->ID, $m, 4, true);
				}
				echo '<p>Localised <a href="'.get_permalink($p->ID).'">'.$p->post_title.'</a></p>';
			} else {
				echo '<p>Acquired remote images for <a href="'.get_permalink($p->ID).'">'.$p->post_title.'</a> but updating the post content failed</p>';
				if(!update_post_meta($p->ID,$m,3)){
					add_post_meta($p->ID, $m, 3, true);
				}
			}
		}
	}

	function localise_ajax_single_post_meta($p,$meta){
		$fimg = get_post_meta($p->ID, 'icit_featured_image',true);
		$i = new ICIT_Image(home_url().'/'.$fimg,$p->ID);
		if($i->is_valid()){
			add_post_meta($p->ID, '_thumbnail_id', $i->ID);
			echo '<p>Grabbed Featured Image from post meta on <a href="'.get_permalink($p->ID).'">'.$p->post_title.'</a></p>';
		} else {
			$error = ' returned false';
			if(is_wp_error($i->error)){
				$error = implode(', ', $i->error->get_error_messages());
			}
			echo '<p>An error occurred processing <a href="'.get_permalink($p->ID).'">'.$p->post_title.'</a>, '.$error.'</p>';
		}
		return;
	}


	function localise_batch_callback() {

		global $post, $icit_feed_images,$wpdb;

		$m = $this->donemeta;

		$sql = "select * from $wpdb->posts q where q.post_type = 'post' AND q.ID NOT in
		 (SELECT p.ID FROM $wpdb->posts p join $wpdb->postmeta a on (a.post_id = p.ID) where a.meta_key = '".$m."' AND a.meta_value > 0 )
		  order by q.post_date DESC LIMIT 5";
		$myposts= $wpdb->get_results($sql);
		$excludes = array();
		if(!empty($myposts)){
			foreach($myposts as $p){
				$this->localise_ajax_single_post($p);
			}
			echo '<p>Batch complete</p>';
		} else {
			echo '<p>no more posts</p>';
		}
		die();

	}

	function localise_bad_batch_callback() {

		global $post, $icit_feed_images,$wpdb;

		$m = $this->donemeta;

		$args = array('post_type' => 'any', 'meta_key' => $this->donemeta,'meta_value' => 1,'posts_per_page'=>5 );
		$q = new WP_Query($args);
		if($q->have_posts()){
			while($q->have_posts()){
				$q->the_post();
				$this->localise_ajax_single_post($post);
			}
			echo '<p>Batch complete</p>';
		} else {
			echo '<p>no more posts</p>';
		}
		die(); // this is required to return a proper result

	}

	public function localise_featured_batch_callback(){
		global $post, $icit_feed_images,$wpdb;

		$m = $this->donemeta.'_f';

		$sql = "select * from $wpdb->posts q where q.post_type = 'post' AND q.ID NOT in
		 (SELECT p.ID FROM $wpdb->posts p join $wpdb->postmeta a on (a.post_id = p.ID) where a.meta_key = '".$m."' AND a.meta_value > 0 )
		  order by q.post_date DESC LIMIT 5";
		$myposts= $wpdb->get_results($sql);
		$excludes = array();
		if(!empty($myposts)){
			foreach($myposts as $p){
				$this->localise_ajax_single_post($p);
			}
			echo '<p>Batch complete</p>';
		} else {
			echo '<p>no more posts</p>';
		}
		die();
	}

	public function localise_featured_meta_batch_callback(){
		global $post, $icit_feed_images,$wpdb;

		$sql1 = "SELECT p.ID FROM $wpdb->posts p join $wpdb->postmeta a on (a.post_id = p.ID) where a.meta_key = 'icit_featured_image' AND a.meta_value <> ''";
		$sql2 = "SELECT p.ID FROM $wpdb->posts p join $wpdb->postmeta a on (a.post_id = p.ID) where a.meta_key = '_thumbnail_id'";

		$results= $wpdb->get_results($sql1);
		$IDS1 = array();
		foreach($results as $r){
			$IDS1[] = $r->ID;
		}

		$results= $wpdb->get_results($sql2);
		$IDS2 = array();
		foreach($results as $r){
			$IDS2[] = $r->ID;
		}
		unset($results);
		$IDS1 = implode(',', (array)$IDS1);
		$IDS2 = implode(',', (array)$IDS2);

		$sql = "SELECT * from $wpdb->posts q where q.post_type = 'post' AND Q.ID in ($IDS1) AND Q.ID NOT in ($IDS2) order by q.post_date DESC LIMIT 5";
		timer_start();
		$myposts= $wpdb->get_results($sql);
		error_log('db query takes: '.timer_stop());
		$excludes = array();
		if(!empty($myposts)){
			foreach($myposts as $p){
				$this->localise_ajax_single_post_meta($p,'icit_featured_image');
			}
			echo '<p>Batch complete</p>';
		} else {
			echo '<p>no more posts</p>';
		}
		die();
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @params	$network_wide	True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
	 */
	function activate( $network_wide ) {
		// TODO define activation functionality here
	} // end activate

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @params	$network_wide	True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
	 */
	function deactivate( $network_wide ) {
		// TODO define deactivation functionality here
	} // end deactivate

	/**
	 * Registers and enqueues admin-specific styles.
	 */
	public function register_admin_styles() {
		wp_register_style( 'image-localiser-admin-styles', plugins_url( 'icit-image-localiser/css/admin.css' ) );
		wp_enqueue_style( 'image-localiser-admin-styles' );
	} // end register_admin_styles

	/**
	 * Registers and enqueues admin-specific JavaScript.
	 */
	public function register_admin_scripts() {
		wp_register_script( 'image-localiser-admin-script', plugins_url( 'icit-image-localiser/js/admin.js' ) );
		wp_enqueue_script( 'image-localiser-admin-script' );
	} // end register_admin_scripts

	/**
	 * Registers and enqueues plugin-specific styles.
	 */
	public function register_plugin_styles() {
		wp_register_style( 'image-localiser-plugin-styles', plugins_url( 'icit-image-localiser/css/display.css' ) );
		wp_enqueue_style( 'image-localiser-plugin-styles' );
	} // end register_plugin_styles

	/**
	 * Registers and enqueues plugin-specific scripts.
	 */
	public function register_plugin_scripts() {
		wp_register_script( 'image-localiser-plugin-script', plugins_url( 'icit-image-localiser/js/display.js' ) );
		wp_enqueue_script( 'image-localiser-plugin-script' );
	} // end register_plugin_scripts


	public function main_page($plugin,$p){
		include('views/admin.php');
	}

	/*--------------------------------------------*
	 * Core Functions
	 *---------------------------------------------*/

	/**
 	 * Note:  Actions are points in the execution of a page or process
	 *        lifecycle that WordPress fires.
	 *
	 *		  WordPress Actions: http://codex.wordpress.org/Plugin_API#Actions
	 *		  Action Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 */
	function action_method_name() {
    	// TODO define your action method here
	} // end action_method_name

	/**
	 * Note:  Filters are points of execution in which WordPress modifies data
	 *        before saving it or sending it to the browser.
	 *
	 *		  WordPress Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *		  Filter Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 */
	function filter_method_name() {
	    // TODO define your filter method here
	} // end filter_method_name

} // end class

// TODO: update the instantiation call of your plugin to the name given at the class definition
new ICIT_ImageLocaliser();
