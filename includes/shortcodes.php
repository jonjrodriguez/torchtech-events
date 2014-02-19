<?php

//[tt_events_att id="your_event_identifier"]
function tt_events_att_display($atts) {
	wp_enqueue_style('tt_events_custom_css', TT_EVENTS_URL . 'styles/tt_events.custom.css');
	
 	extract( shortcode_atts( array('id' => ''), $atts ) );

	if(!$id) 
		return "<p><strong>Please enter an id.</strong></p>";

	$event = get_post($id);
	if(!$event || $event->post_type != 'tt_events')
		return "<p><strong>Please enter a valid TT Event id</strong></p>";

	$error = tt_events_show_att_list($id);	
	if($error)
		return $error;
}

//[tt_events_reg id="your_event_identifier"]
function tt_events_reg_display($atts) {
	// register and enqueue scripts and css
    wp_register_script( 'custom_js', TT_EVENTS_URL . 'scripts/jquery.custom.js', array( 'jquery','validation', 'jquery-ui-dialog', 'jquery-ui-autocomplete' ), false, TRUE );
    wp_enqueue_script( 'custom_js' );
    wp_enqueue_style("wp-jquery-ui-dialog");
    wp_register_script( 'validation', 'https://ajax.aspnetcdn.com/ajax/jquery.validate/1.10.0/jquery.validate.min.js', array( 'jquery' ),false, TRUE );
    wp_enqueue_script( 'validation' );
    wp_enqueue_style('jquery_ui_css', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/themes/base/jquery-ui.css');
    wp_enqueue_style('tt_events_custom_css', TT_EVENTS_URL . 'styles/tt_events.custom.css');

	extract( shortcode_atts( array('id' => ''), $atts ) );
	
	if(!$id) 
		return "<p><strong>Please enter an id.</strong></p>";

	$event = get_post($id);
	if(!$event || $event->post_type != 'tt_events')
		return "<p><strong>Please enter a valid TT Event id</strong></p>";

	if(!is_user_logged_in()) {
		$args = array(
        	'form_id' => 'reglogin',
	        'label_username' => __( 'NetID' )
		);

		echo "<p><strong>Please login to register using your NetID and password.</strong></p>";
		wp_login_form($args);
	} else {
		$error = tt_events_show_reg_form($id, $event->post_content);
		if($error)
			return $error;
	}
}

?>