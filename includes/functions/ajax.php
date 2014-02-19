<?php

if (!class_exists("TorchTechEventsAjax")) {
    class TorchTechEventsAjax {
        function TorchTechEventsAjax() { //constructor
            
        }

        function tt_events_ajaxurl() {
            //Set ajax url
            echo ('<script type="text/javascript">');
            echo ('var ajaxurl = "' . admin_url('admin-ajax.php', 'https'). '";');
            echo ('</script>');
        }

        function addRegistration() {
            //Check if form submittal is valid
            if ( empty($_POST) || !wp_verify_nonce($_POST['registration_form_nonce'],'submit_registration_form') ) {
               echo '<p class="warning">An error has occurred.  Please refresh the page and try again.  If you continue to have issues, please email <a href="mailto:torchtech-info@nyu.edu">TorchTech</a>.</p>';
               exit;
            }

            //Check that all required fields are present
            if($_POST['user_id'] == '' || $_POST['event_id'] == '' || $_POST['title'] == '' || $_POST['org'] == '' || $_POST['interests'] == '') {
                echo '<p class="warning">An error has occurred.  Please refresh the page and try again.  If you continue to have issues, please email <a href="mailto:torchtech-info@nyu.edu">TorchTech</a>.</p>';
                exit;
            }

            //Get Form variables
            global $wpdb;
            
            $user_id = $_POST['user_id'];
            $event_id = $_POST['event_id'];
            $title = $_POST['title'];
            $org = $_POST['org'];
            $url = $_POST['url'];
            $interests = $_POST['interests'];
            $referer = site_url($_POST['_wp_http_referer']);

            //Check if event and user are valid
            $event = get_post($event_id);
            if(!$event || $event->post_type != 'tt_events') {
                echo '<p class="warning">An error has occurred.  Please refresh the page and try again.  If you continue to have issues, please email <a href="mailto:torchtech-info@nyu.edu">TorchTech</a>.</p>';
                exit;
            }
            $user = get_userdata($user_id);
            if(!$user) {
                echo '<p class="warning">An error has occurred.  Please refresh the page and try again.  If you continue to have issues, please email <a href="mailto:torchtech-info@nyu.edu">TorchTech</a>.</p>';
                exit;
            }

            //Check if user is already registered and hasn't cancelled
            $sql = $wpdb->prepare("SELECT * FROM $wpdb->tt_events_attendees WHERE event_id = %d AND user_id = %d AND cancelled = %d", $event_id, $user_id, 0);
            $attendees = $wpdb->get_row($sql);

            if($attendees) {
                echo '<p class="warning">An error has occurred.  Please refresh the page and try again.  If you continue to have issues, please email <a href="mailto:torchtech-info@nyu.edu">TorchTech</a>.</p>';
                exit;
            }

            //Check if registration limit has been reached.  Add user to waitlist
            $sql = $wpdb->prepare("SELECT  COUNT(*) FROM $wpdb->tt_events_attendees WHERE event_id = %d AND cancelled = %d", $event_id, 0);
            $reg_count = $wpdb->get_var($sql);
            $reg_limit = get_post_meta($event_id, 'tt_event_reg_limit', true);

            if($reg_count >= $reg_limit) {
                $waitlist = 1;
            }

            $data = array(
                'user_id' => $user_id,
                'event_id' => $event_id,
                'title' => $title,
                'url' => $url
            );

            if($waitlist) 
                $data['waitlist'] = $waitlist;

            //All checks passed. Save data.
            if($wpdb->insert($wpdb->tt_events_attendees,$data,array('%d','%d','%s','%s','%s')) === FALSE) {
                echo '<p class="warning">An error has occurred.  Please refresh the page and try again.  If you continue to have issues, please email <a href="mailto:torchtech-info@nyu.edu">TorchTech</a>.</p>';
                exit;
            } 

            $attendee = $wpdb->insert_id;

            //Add organization of attendee
            $org_id = term_exists( $org, 'tt_event_reg_orgs' );
            if($term_id)
                wp_set_post_terms($attendee,$org_id,'tt_event_reg_orgs');
            else 
                wp_set_object_terms($attendee,$org,'tt_event_reg_orgs');

            //Add interests of attendee
            wp_set_post_terms($attendee,$interests,'tt_event_reg_interests');

            if(!$this->confirmationEmail($user_id, $event_id, $waitlist, $referer)) {
                echo '<p class="warning">There was an error sending you a confirmation email, but your registration has been recorded.  If you have any questions regarding your registration, please email <a href="mailto:torchtech-info@nyu.edu">TorchTech</a>.</p><br />';
            }

            $reg_end_meta = get_post_meta($event_id, 'tt_event_reg_end', true);
            $reg_end = ($reg_end_meta) ? $reg_end_meta : 'the event';

            if($waitlist) {
                echo "<p class='success'>Congratulations! You have been added to the waitlist for the $event->post_title. You will be notified via email when there is space available.</p>";
            } else {
                echo "<p class='success'>Congratulations! You have been registered for the $event->post_title.  Feel free to head over to the <a href='../attendees'>attendees page</a> to see who else has registered.</p>";
            }

            echo "<br /><p>If for some reason you are unable to attend, we would ask that you kindly cancel your registration so that waitlisted registrants can be accommodated. To cancel your registration or edit your registration information, refresh this page or come back to it at any time before $reg_end.</p>";
            exit;
        }

        function updateRegistration() {
            //Check if form submittal is valid
            if ( empty($_POST) || !wp_verify_nonce($_POST['registration_form_nonce'],'submit_registration_form') ) {
               echo '<p class="warning">An error has occurred.  Please refresh the page and try again.  If you continue to have issues, please email <a href="mailto:torchtech-info@nyu.edu">TorchTech</a>.</p>';
               exit;
            }

            //Check that all required fields are present
            if($_POST['reg_id'] == '' || $_POST['user_id'] == '' || $_POST['event_id'] == '' || $_POST['title'] == '' || $_POST['org'] == '' || $_POST['interests'] == '') {
                echo '<p class="warning">An error has occurred.  Please refresh the page and try again.  If you continue to have issues, please email <a href="mailto:torchtech-info@nyu.edu">TorchTech</a>.</p>';
                exit;
            }

            //Get Form variables
            global $wpdb;
            
            $reg_id = $_POST['reg_id'];
            $user_id = $_POST['user_id'];
            $event_id = $_POST['event_id'];
            $title = $_POST['title'];
            $org = $_POST['org'];
            $url = $_POST['url'];
            $interests = $_POST['interests'];
            $referer = site_url($_POST['_wp_http_referer']);

            //Check if event and user are valid
            $event = get_post($event_id);
            if(!$event || $event->post_type != 'tt_events') {
                echo '<p class="warning">An error has occurred.  Please refresh the page and try again.  If you continue to have issues, please email <a href="mailto:torchtech-info@nyu.edu">TorchTech</a>.</p>';
                exit;
            }
            $user = get_userdata($user_id);
            if(!$user) {
                echo '<p class="warning">An error has occurred.  Please refresh the page and try again.  If you continue to have issues, please email <a href="mailto:torchtech-info@nyu.edu">TorchTech</a>.</p>';
                exit;
            }

            //All checks passed. Update data.
            $update = $wpdb->update($wpdb->tt_events_attendees,
                array(
                    'title' => $title,
                    'url' => $url
                ),
                array('id' => $reg_id),
                array('%s','%s'),
                array('%d')
            );

            if($update === FALSE) {
                echo '<p class="warning">An error has occurred.  Please refresh the page and try again.  If you continue to have issues, please email <a href="mailto:torchtech-info@nyu.edu">TorchTech</a>.</p>';
                exit;
            }

            //Add organization of attendee
            $org_id = term_exists( $org, 'tt_event_reg_orgs' );
            if($term_id)
                wp_set_post_terms($reg_id,$org_id,'tt_event_reg_orgs');
            else 
                wp_set_object_terms($reg_id,$org,'tt_event_reg_orgs');

            //Add interests of attendee
            wp_set_post_terms($reg_id,$interests,'tt_event_reg_interests');

            echo '<p class="success">Congratulations! You have successfully updated your registration information.</p>';
            exit;
        }

        function cancelRegistration() {
            //Check if form submittal is valid
            if ( empty($_POST) || !wp_verify_nonce($_POST['nonce'],'cancel-registration-nonce') || $_POST['userId'] == '' || $_POST['eventId'] == '') {
               echo '<p class="warning">An error has occurred.  Please refresh the page and try again.  If you continue to have issues, please email <a href="mailto:torchtech-info@nyu.edu">TorchTech</a>.</p>';
               exit;
            }

            //Get Form variables
            global $wpdb;
 
            $user_id = $_POST['userId'];
            $event_id = $_POST['eventId'];
            $referer = $_POST['referer'];

            //Check if event and user are valid
            $event = get_post($event_id);
            if(!$event || $event->post_type != 'tt_events') {
                echo '<p class="warning">An error has occurred.  Please refresh the page and try again.  If you continue to have issues, please email <a href="mailto:torchtech-info@nyu.edu">TorchTech</a>.</p>';
                exit;
            }
            $user = get_userdata($user_id);
            if(!$user) {
                echo '<p class="warning">An error has occurred.  Please refresh the page and try again.  If you continue to have issues, please email <a href="mailto:torchtech-info@nyu.edu">TorchTech</a>.</p>';
                exit;
            }

            //Get user and flag as cancelled
            $sql = $wpdb->prepare("SELECT * FROM $wpdb->tt_events_attendees WHERE event_id = %d AND user_id = %d", $event_id, $user_id);
            $attendee = $wpdb->get_row($sql);

            $update = $wpdb->update(
                $wpdb->tt_events_attendees,
                array('cancelled' => 1),
                array('user_id' => $user_id, 'event_id' => $event_id),
                '%d',
                array('%d','%d')
            );

            if(!$update) {
                echo '<p class="warning">An error has occurred.  Please refresh the page and try again.  If you continue to have issues, please email <a href="mailto:torchtech-info@nyu.edu">TorchTech</a>.</p>';
                exit;
            }

            //Email user cancellation confirmation
            if(!$this->cancellationEmail($user_id, $event_id, $referer)) {
                 echo '<p class="warning">There was an error sending you an email to confirm your cancellation. If you have any questions regarding your registration, please email <a href="mailto:torchtech-info@nyu.edu">TorchTech</a>.</p><br />';
            }

            //Get first user on waitlist (if there are any and they have not cancelled).  Automatically register them for the event.
            if(!$attendee->waitlist) {
                $waitlist = $this->getNextWaitlist($event_id);
                if($waitlist) {
                    //Take user off of waitlist and email
                    $addReg = $wpdb->update(
                        $wpdb->tt_events_attendees,
                        array('waitlist' => 0),
                        array('id' => $waitlist->id),
                        '%d','%d'
                    );

                    if($addReg)
                        $this->waitlistEmail($waitlist->id, $event_id, $referer);
                }
            }

            $reg_end_meta = get_post_meta($event_id, 'tt_event_reg_end', true);
            $reg_end = ($reg_end_meta) ? $reg_end_meta : 'the event';

            echo "<p>You have successfully cancelled your registration.  Although we are sorry that you cannot attend, we appreciate that you have made space so others can participate. <strong>If you wish to re-register, please do so before $reg_end.</strong></p>";
            exit;
        }

        function getNextWaitlist($event_id) {
            global $wpdb;

            //Get Waitlist
            $sql = $wpdb->prepare("SELECT  * FROM $wpdb->tt_events_attendees WHERE event_id = %d and waitlist = %d and cancelled = %d ORDER BY reg_date ASC", $event_id,1,0);
            $waitlist = $wpdb->get_results($sql);

            if(!$waitlist)
                return false;

            //Loop through waitlist to find first valid user
            foreach($waitlist as $userReg) {
                $user = get_userdata($userReg->user_id);
                if(!$user) {
                    //Not a valid user.  Cancel their registration
                    $wpdb->update($wpdb->tt_events_attendees,
                        array('cancelled' => 1), 
                        array('id' => $userReg->id),
                        '%d','%d');
                } else {
                    return $userReg;
                }
            }
        }

        function add_autosuggest_tags() {
            global $wpdb;
            if ( isset( $_GET['tax'] ) ) {
                $taxonomy = sanitize_key( $_GET['tax'] );
                $tax = get_taxonomy( $taxonomy );
                if ( ! $tax )
                    wp_die( 'Error' );
            } else {
                wp_die( 'Error' );
            }

            $s = stripslashes( $_GET['term'] );

            $comma = _x( ',', 'tag delimiter' );
            if ( ',' !== $comma )
                $s = str_replace( $comma, ',', $s );
            if ( false !== strpos( $s, ',' ) ) {
                $s = explode( ',', $s );
                $s = $s[count( $s ) - 1];
            }
            $s = trim( $s );

            $results = $wpdb->get_col( $wpdb->prepare( "SELECT t.name FROM $wpdb->term_taxonomy AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id WHERE tt.taxonomy = %s AND t.name LIKE (%s)", $taxonomy, '%' . like_escape( $s ) . '%' ) );

            echo json_encode($results);
            wp_die();
        }

        function confirmationEmail($user_id, $event_id, $waitlist, $referer) {
            $user = get_userdata($user_id);
            $event = get_post($event_id);
            $event_date = date('m/d/Y', get_post_meta($event_id, 'tt_event_start', true));
            $event_start = date('h:i a', get_post_meta($event_id, 'tt_event_start', true));
            $event_end = date('h:i a', get_post_meta($event_id, 'tt_event_end', true));
            $event_location = get_post_meta($event_id, 'tt_event_location', true);

            $to = $user->user_email;
            $subject = "Registration Confirmation for the $event->post_title";

            $message = "<p>Dear $user->display_name,</p>";
            
            if($waitlist)
                $message .= "<p>Congratulations! You have been added to the waitlist for the $event->post_title.  You will be notified when there is space available.";
            else                
                $message .= "<p>Congratulations! You have been registered for the $event->post_title.</p>";

            $message .= "<p>The event will take place on $event_date from $event_start to $event_end</p>";
            $message .= "<p>The location is $event_location.</p>";
            $message .= "<p>If for some reason you are unable to attend, we would ask that you kindly cancel your registration so that waitlisted registrants can be accommodated.  To cancel your registration or edit your registration information, please go to <a href='$referer'>nyu.edu/torchtech</a>.</p>";
            $message .= "<p>Regards,<br />TorchTech</p>";

            $headers[] = 'From: TorchTech <torchtech-info@nyu.edu>';
            $headers[] = 'content-type: text/html';

            return wp_mail( $to, $subject, $message, $headers );
        }

        function cancellationEmail($user_id, $event_id, $referer) {
            $user = get_userdata($user_id);
            $event = get_post($event_id);
            $event_date = date('m/d/Y', get_post_meta($event_id, 'tt_event_start', true));
            $reg_end_meta = get_post_meta($event_id, 'tt_event_reg_end', true);
            $reg_end = ($reg_end_meta) ? $reg_end_meta : 'the event';

            $to = $user->user_email;
            $subject = "Cancellation Confirmation for the $event->post_title";

            $message = "<p>Dear $user->display_name,</p>";
            $message .= "<p>This email is to confirm your registration cancellation for the $event->post_title.</p>";
            $message .= "<p>We are sorry that you are unable to attend this event, but appreciate that you have allowed waitlisted registrants to be accommodated.</p>";
            $message .= "<p>If your schedule changes and are able to attend, please visit <a href='$referer'>nyu.edu/torchtech</a> and re-register before $reg_end.</p>";
            $message .= "<p>Regards,<br />TorchTech</p>";

            $headers[] = 'From: TorchTech <torchtech-info@nyu.edu>';
            $headers[] = 'content-type: text/html';

            return wp_mail( $to, $subject, $message, $headers );
        }

        function waitlistEmail($user_id, $event_id, $referer) {
            $user = get_userdata($user_id);
            $event = get_post($event_id);
            $event_date = date('m/d/Y', get_post_meta($event_id, 'tt_event_start', true));
            $event_start = date('h:i a', get_post_meta($event_id, 'tt_event_start', true));
            $event_end = date('h:i a', get_post_meta($event_id, 'tt_event_end', true));
            $event_location = get_post_meta($event_id, 'tt_event_location', true);

            $to = $user->user_email;
            $subject = "Waitlist Accommodation for the $event->post_title";

            $message = "<p>Dear $user->display_name,</p>";
            $message .= "<p>Congratulations! There is available space and you have been removed from the waitlist and added to the attendee list for the $event->post_title.";
            $message .= "<p>If for some reason you are now unable to attend, we would ask that you kindly cancel your registration so that other waitlisted registrants can be accommodated.  To cancel your registration or edit your registration information, please go to <a href='$referer'>nyu.edu/torchtech</a>.</p>";
            $message .= "<p>As a reminder, the event will take place on $event_date from $event_start to $event_end and the location is $event_location.</p>";
            $message .= "<p>Regards,<br />TorchTech</p>";

            $headers[] = 'From: TorchTech <torchtech-info@nyu.edu>';
            $headers[] = 'content-type: text/html';

            return wp_mail( $to, $subject, $message, $headers );
        }
    }

} //End Class TorchTechEvents



?>