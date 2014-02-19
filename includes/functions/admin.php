<?php

if (!class_exists("TorchTechEventsAdmin")) {
    class TorchTechEventsAdmin {

        function edit_tt_events_columns($columns) {
            $columns = array(
                'cb' => '<input type="checkbox" />',
                'title' => __( 'Event' ),
                'reg_limit' => __( 'Attendee Limit' ),
                'reg_start' => __( 'Registration Start' ),
                'reg_end' => __( 'Registration End' ),
                'event_loc' => __( 'Event Location' ),
                'event_start' => __( 'Event Start' ),
                'event_end' => __( 'Event End' ),
                'date' => __( 'Date' )
            );

            return $columns;
        }

        function manage_tt_events_columns($column, $post_id) {
            global $post;

            switch( $column ) {
                case 'reg_limit' :
                    /* Get the post meta. */
                    $reg_limit = get_post_meta( $post_id, 'tt_event_reg_limit', true );

                    if ( empty( $reg_limit ) )
                        echo __( '' );
                    else
                        echo __($reg_limit );
                    break;

                case 'reg_start' :
                    /* Get the post meta. */
                    $reg_start = get_post_meta( $post_id, 'tt_event_reg_start', true );

                    if ( empty( $reg_start ) )
                        echo __( '' );
                    else
                        echo __($reg_start );
                    break;

                case 'reg_end' :
                    /* Get the post meta. */
                    $reg_end = get_post_meta( $post_id, 'tt_event_reg_end', true );

                    if ( empty( $reg_end ) )
                        echo __( '' );
                    else
                        echo __($reg_end );
                    break;

                case 'event_loc' :
                    /* Get the post meta. */
                    $event_loc = get_post_meta( $post_id, 'tt_event_location', true );

                    if ( empty( $event_loc ) )
                        echo __( '' );
                    else
                        echo __($event_loc );
                    break;

                case 'event_start' :
                    /* Get the post meta. */
                    $event_start = get_post_meta( $post_id, 'tt_event_start', true );

                    if ( empty( $event_start ) )
                        echo __( '' );
                    else
                        echo __(date('m/d/Y h:i a',$event_start) );
                    break;

                case 'event_end' :
                    /* Get the post meta. */
                    $event_end = get_post_meta( $post_id, 'tt_event_end', true );

                    if ( empty( $event_end ) )
                        echo __( '' );
                    else
                        echo __(date('m/d/Y h:i a',$event_end ));
                    break;

                /* Just break out of the switch statement for everything else. */
                default :
                    break;
            }
        }

        function fix_tt_event_columns( $columns ) {
            $columns['posts'] = 'Count';
            return $columns;
        }

        function tt_check_for_export() {
            if ('tt_events' == get_post_type( $_GET['post']) && isset($_REQUEST['export'])) {
                $this->exportAttendeeList();
            }
        }

        function exportAttendeeList() {
            $event = get_post($_GET['post']);

            global $wpdb;

            $sql = $wpdb->prepare("SELECT users.display_name, users.user_email, events.title, events.url, events.cancelled, events.waitlist, events.reg_date,  terms.name as org
                FROM $wpdb->tt_events_attendees events 
                JOIN $wpdb->users users ON events.user_id = users.id
                JOIN $wpdb->term_relationships rels ON events.id = rels.object_id
                JOIN $wpdb->term_taxonomy tax ON rels.term_taxonomy_id = tax.term_taxonomy_id
                AND tax.taxonomy = 'tt_event_reg_orgs'
                JOIN $wpdb->terms terms ON tax.term_id = terms.term_id
                WHERE events.event_id = %d", 
                $event->ID
            );
            $attendees = $wpdb->get_results($sql);

            $rows = array();
                    
            foreach ($attendees as $attendee) {
                $rows[] = array(
                    'Name'                => $attendee->display_name,
                    'Email'               => $attendee->user_email,
                    'Title'               => $attendee->title,
                    'Organization'        => $attendee->org,
                    'URL'                 => $attendee->url,
                    'Cancelled'           => $attendee->cancelled,
                    'Waitlist'            => $attendee->waitlist,
                    'Registration Date'   => $attendee->reg_date
                );
            }
            
            //Process headers and data array, output into .csv file, display download file dialog
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename=' . $event->post_title . '_attendees_' . date('Y-m-d_gia') . '.csv');
            $fp = fopen('php://output', 'x+');
            fputcsv($fp, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
            exit;
        }

    }

} //End Class TorchTechEvents



?>