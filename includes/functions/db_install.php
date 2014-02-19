<?php

	
function tt_events_register_attendees_table() {
	global $wpdb;
	$wpdb->tt_events_attendees = "{$wpdb->prefix}tt_events_attendees";
}

function tt_events_create_tables() {
	global $wpdb;
	global $charset_collate;

	// Call this manually as we may have missed the init hook
	tt_events_register_attendees_table();

	$sql = "CREATE TABLE {$wpdb->tt_events_attendees} (
		id bigint(20) NOT NULL auto_increment,
		user_id bigint(20) NOT NULL,
		event_id bigint(20) NOT NULL,
		title varchar(255) NOT NULL,
		url varchar(255),
		cancelled tinyint(1) default 0,		
		waitlist tinyint(1) default 0,
		reg_date timestamp default CURRENT_TIMESTAMP(),
		PRIMARY KEY  (id),
		KEY user_id (user_id),
		KEY event_id (event_id)
 	) $charset_collate; ";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

?>