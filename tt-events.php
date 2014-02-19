<?php
/*
Plugin Name: TorchTech Events
Plugin URI: http://torchtech.law.nyu.edu
Description: Adds event and registration functionality.
Version: 1.0
Author: Jonathan Rodriguez
*/

//Define constants
define('TT_EVENTS_URL', plugin_dir_url( __FILE__ ));
define('TT_EVENTS_PATH', plugin_dir_path( __FILE__ ));

require_once(TT_EVENTS_PATH . "/includes/functions/main.php");
require_once(TT_EVENTS_PATH . "/includes/functions/ajax.php");
require_once(TT_EVENTS_PATH . "/includes/functions/admin.php");
require_once(TT_EVENTS_PATH . "/includes/functions/db_install.php");
require_once(TT_EVENTS_PATH . "/includes/shortcodes.php");
require_once(TT_EVENTS_PATH . "/includes/templates/reg_form.php");
require_once(TT_EVENTS_PATH . "/includes/templates/att_list.php");

if (class_exists("TorchTechEvents")) {
	$tt_events = new TorchTechEvents();
}

if (class_exists("TorchTechEventsAdmin")) {
	$tt_events_admin = new TorchTechEventsAdmin();
}

if (class_exists("TorchTechEventsAjax")) {
	$tt_events_ajax = new TorchTechEventsAjax();
}

//Actions and Filters	
if (isset($tt_events)) {
	//Actions

	/* Create attendees table */
	register_activation_hook( __FILE__, 'tt_events_create_tables' );
	add_action( 'init', 'tt_events_register_attendees_table', 1 );

	/* Create custom post type 'event' and custom meta boxes */
	add_action('init', array($tt_events, 'tt_register_events'));
	add_action( 'init', array($tt_events, 'wpb_initialize_cmb_meta_boxes' ));
	add_action('add_meta_boxes', array($tt_events, 'add_shortcodes_meta_box'));

	/* Add Event Taxonomies */
	add_action( 'init', array($tt_events, 'tt_events_interest_taxonomy'));
	add_action( 'init', array($tt_events, 'tt_events_org_taxonomy'));

	//Filters
	add_filter( 'cmb_meta_boxes', array($tt_events, 'tt_events_meta_boxes' ));

	//Shortcodes
	add_shortcode( 'tt_events_att', 'tt_events_att_display' );
	add_shortcode( 'tt_events_reg', 'tt_events_reg_display' );
}

//Initialize the admin panel
if (isset($tt_events_admin)) {
	
	add_action( 'manage_tt_events_posts_custom_column', array($tt_events_admin, 'manage_tt_events_columns'), 10, 2 );

	//Export Check
	add_action('admin_init', array($tt_events_admin, 'tt_check_for_export'));

	/* Fix taxonomy columns. */
	add_filter( 'manage_edit-tt_event_reg_interests_columns', array($tt_events_admin, 'fix_tt_event_columns' ));
	add_filter( 'manage_edit-tt_event_reg_orgs_columns', array($tt_events_admin, 'fix_tt_event_columns' ));


	/* Update columns shown on main Events listing */
	add_filter( 'manage_edit-tt_events_columns', array($tt_events_admin, 'edit_tt_events_columns' ));
}

if (isset($tt_events_ajax)) {
	//Set Ajax URL
	add_action('wp_head',array($tt_events_ajax, 'tt_events_ajaxurl'));
	
	/* Ajax Registration Form submit */
	add_action('wp_ajax_addRegistration', array($tt_events_ajax, 'addRegistration'));

	/* Ajax Update Registration Form submit */
	add_action('wp_ajax_updateRegistration', array($tt_events_ajax, 'updateRegistration'));

	/* Ajax Cancel Registration Form submit */
	add_action('wp_ajax_cancelRegistration', array($tt_events_ajax, 'cancelRegistration'));

	/* Add autocomplete on tags for non-admins */
	add_action('wp_ajax_auto-tax-complete', array($tt_events_ajax, 'add_autosuggest_tags'));
	add_action('wp_ajax_nopriv_auto-tax-complete', array($tt_events_ajax, 'add_autosuggest_tags'));
}

?>