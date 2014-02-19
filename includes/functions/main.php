<?php

if (!class_exists("TorchTechEvents")) {
    class TorchTechEvents {
        function TorchTechEvents() { //constructor
            
        }

        function tt_register_events() {
            $labels = array(
                'name' => _x( 'Events', 'post type general name' ),
                'singular_name' => _x( 'Event', 'post type singular name' ),
                'add_new' => _x( 'Add New', 'Event' ),
                'add_new_item' => __( 'Add New Event' ),
                'edit_item' => __( 'Edit Event' ),
                'new_item' => __( 'New Event' ),
                'view_item' => __( 'View Event' ),
                'search_items' => __( 'Search Events' ),
                'not_found' =>  __( 'No Events found' ),
                'not_found_in_trash' => __( 'No Events found in Trash' ),
                'parent_item_colon' => ''
            );
            $args = array(
                'labels' => $labels,
                'singular_label' => __('Event', 'torchtech-events'),
                'public' => true,
                'rewrite' => false,
                'supports' => array('title', 'editor'),
		'capabilities' => array(
	        	'edit_post' => 'manage_options',
            		'edit_posts' => 'manage_options',
            		'edit_others_posts' => 'manage_options',
            		'publish_posts' => 'manage_options',
            		'read_post' => 'manage_options',
            		'read_private_posts' => 'manage_options',
            		'delete_post' => 'manage_options'
        	)
            );
            register_post_type('tt_events', $args);
        }


        function tt_events_interest_taxonomy(){
            $labels = array(
                'name' => _x( 'Interests', 'post type general name' ),
                'singular_name' => _x( 'Interest', 'post type singular name' ),
                'add_new_item' => __( 'Add New Interest' ),
                'edit_item' => __('Edit Interest'),
                'search_items' => __( 'Search Interests' ),
                'popular_items' => __('Popular Interests'),
                'separate_items_with_commas' =>  __( 'Separate interests with commas' ),
                'choose_from_most_used' => __('Choose from the most used interests'),
                'parent_item_colon' => ''
            );
            $args = array(
                'labels' => $labels,
                'update_count_callback' => array($this, 'update_tt_event_tax_count')
            );

            register_taxonomy('tt_event_reg_interests', 'tt_events', $args);
        }

        function tt_events_org_taxonomy(){
            $labels = array(
                'name' => _x( 'Organizations', 'post type general name' ),
                'singular_name' => _x( 'Organization', 'post type singular name' ),
                'add_new_item' => __( 'Add New Organization' ),
                'edit_item' => __('Edit Interest'),
                'search_items' => __( 'Search Organizations' ),
                'popular_items' => __('Popular Organizations'),
                'separate_items_with_commas' =>  __( 'Separate organizations with commas' ),
                'choose_from_most_used' => __('Choose from the most used organizations'),
                'parent_item_colon' => ''
            );
            $args = array(
                'labels' => $labels,
                'hierarchical' => true,
                'update_count_callback' => array($this, 'update_tt_event_tax_count')
            );

            register_taxonomy('tt_event_reg_orgs', 'tt_events', $args);
        }

        function update_tt_event_tax_count( $terms, $taxonomy ) {
            global $wpdb;

            foreach ( (array) $terms as $term ) {

                $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term ) );

                do_action( 'edit_term_taxonomy', $term, $taxonomy );
                $wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
                do_action( 'edited_term_taxonomy', $term, $taxonomy );
            }
        }

        /* Initialize the metabox class */
        function wpb_initialize_cmb_meta_boxes() {
            if ( ! class_exists( 'cmb_Meta_Box' ) )
                 require_once(TT_EVENTS_PATH . '/CMB/init.php');
        }

        function tt_events_meta_boxes( array $meta_boxes ) {

            $meta_boxes[] = array(
                'id'         => 'tt_events_details',
                'title'      => 'Event Details',
                'pages'      => array( 'tt_events', ), // Post type
                'context'    => 'normal',
                'priority'   => 'high',
                'show_names' => true, // Show field names on the left
                'fields'     => array(
                    array(
                        'name' => 'Event Start Date',
                        'desc' => 'Start date of the event.',
                        'id'   => 'tt_event_start',
                        'type' => 'text_datetime_timestamp',
                    ),
                    array(
                        'name' => 'Event End Date',
                        'desc' => 'End date of the event.',
                        'id'   => 'tt_event_end',
                        'type' => 'text_datetime_timestamp',
                    ),
                    array(
                        'name' => 'Event Location',
                        'desc' => 'Location of the event.',
                        'id'   => 'tt_event_location',
                        'type' => 'text_medium',
                    ),
                ),
            );

            $meta_boxes[] = array(
                'id'         => 'tt_events_reg',
                'title'      => 'Event Registration Settings',
                'pages'      => array( 'tt_events', ), // Post type
                'context'    => 'normal',
                'priority'   => 'high',
                'show_names' => true, // Show field names on the left
                'fields'     => array(
                    array(
                        'name' => 'Attendee Limit',
                        'desc' => 'Maximum number of attendees allowed to attend.',
                        'id'   => 'tt_event_reg_limit',
                        'type' => 'text_small',
                    ),
                    array(
                        'name' => 'Event Registration Start',
                        'desc' => 'Date event registration starts.',
                        'id'   => 'tt_event_reg_start',
                        'type' => 'text_date',
                    ),
                    array(
                        'name' => 'Event Registration End',
                        'desc' => 'Date event registration ends.',
                        'id'   => 'tt_event_reg_end',
                        'type' => 'text_date',
                    ),
                ),
            );

            // Add other metaboxes as needed
            return $meta_boxes;
        }

        function add_shortcodes_meta_box() {
            add_meta_box( 'tt_event_shortcode', 'Event Shortcodes', array($this, 'tt_events_shortcodes_cb'), 'tt_events', 'normal', 'high' );  
        }

        function tt_events_shortcodes_cb() {
            $id = $_GET['post'];
            $url = get_bloginfo('wpurl') . add_query_arg( 'export', 'attendees' );

            echo '<p><a href="#" class="button" onclick="prompt(&#39;Registration Shortcode:&#39;, \'[tt_events_reg id=&#34;\' + jQuery(\'#post_ID\').val() + \'&#34;]\'); return false;">' . __('Get Registration Shortcode') . '</a></p>';
            echo '<p><a href="#" class="button" onclick="prompt(&#39;Attendee List Shortcode:&#39;, \'[tt_events_att id=&#34;\' + jQuery(\'#post_ID\').val() + \'&#34;]\'); return false;">' . __('Get Attendee List Shortcode') . '</a></p>';
            echo '<p><a href="'. $url . '" class="button">' . __('Export Attendee List') . '</a></p>';
        }
    }

} //End Class TorchTechEvents



?>