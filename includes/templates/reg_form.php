<?php

//Display registration form
function tt_events_show_reg_form($id, $content) {
	global $current_user;
	global $wpdb;

	$reg_limit = get_post_meta($id, 'tt_event_reg_limit', true);
	$reg_start = get_post_meta($id, 'tt_event_reg_start', true);
	$reg_end = get_post_meta($id, 'tt_event_reg_end', true);

	$sql = $wpdb->prepare("SELECT COUNT(*) FROM $wpdb->tt_events_attendees WHERE event_id = %d and cancelled = %d",$id, 0);
	$att_count = $wpdb->get_var($sql);

	$sql = $wpdb->prepare("SELECT * FROM $wpdb->tt_events_attendees WHERE event_id = %d AND user_id = %d AND cancelled = %d",$id, $current_user->ID, 0);
	$registered = $wpdb->get_row($sql);
	
	if($reg_start && strtotime('now')<strtotime($reg_start))
		return "<p class='warning'>Registration has not yet opened.</p>";

	if($reg_end && strtotime('now')>strtotime($reg_end))
		return "<p class='warning'>Registration for the event is full.  Registration is closed.</p>";

	if($registered) {
		$orgs = wp_get_object_terms($registered->id,'tt_event_reg_orgs');
		$interests = wp_get_object_terms($registered->id,'tt_event_reg_interests');
		$interestList = '';
		foreach ($interests as $interest) {
			$interestList .= $interest->name . ', ';
		}

		if($registered->waitlist) 
			echo "<p class='success'>You are already on the waitlist for this event. You can edit your registration by updating the form below.</p><br />";
		else
			echo "<p class='success'>You are already registered for this event.  You can edit your registration by updating the form below.</p><br />";

		$nonce = wp_create_nonce('cancel-registration-nonce');
		$referer = get_permalink();

		echo "<p>If you are unable to attend, please cancel your registration by clicking the button below.";
		if($reg_limit && $att_count >= $reg_limit) echo "  <strong>If you cancel and choose to re-register, you will be added to the waitlist until there is available space.</strong>";
		echo "</p><p><input type='button' id='cancel_reg' value='Cancel Registration' data-action='cancelRegistration' data-nonce='$nonce' data-referer='$referer' data-event-id=$id data-user-id=$current_user->ID /></p>";
	} elseif($reg_limit && $att_count >= $reg_limit) {
		echo "<p class='warning'>Registration for the event is full. You may complete the registration form and will be added to the waitlist.</p>";
	}

?>
	<h2>Register <span>Now!</span></h2>
	<p><?php echo $content ?></p>
	<form id='registration_form' action="" method="POST">
		<?php wp_nonce_field( 'submit_registration_form', 'registration_form_nonce' );  ?>
	<?php if($registered) : ?>
		<input type='hidden' name='reg_id' value='<?php echo $registered->id ?>' />
		<input type="hidden" name="action" value="updateRegistration" />
	<?php else : ?>
		<input type="hidden" name="action" value="addRegistration" />
	<?php endif ?>	
		<input type='hidden' name='event_id' value='<?php echo $id ?>' />
		<input type='hidden' name='user_id' value='<?php echo $current_user->ID ?>' />
		<p>
			<label for='name'>Name</label>
			<input type='text' name='name' disabled='disabled' value='<?php echo $current_user->display_name ?>' id='name' />
			<span class='desc'>If the above information is not correct, please update it in your profile.</span>
		</p>
		<p>
			<label for='email'>Email</label>
			<input type='text' name='email' disabled='disabled' value='<?php echo $current_user->user_email ?>' id='email' />
			<span class='desc'>If the above information is not correct, please update it in your profile.</span>
		</p>
		<p>
			<label for='title'>Title<span class='required'>*</span></label>
			<input class="required" type='text' name='title' id='title' value="<?php if($registered) echo $registered->title ?>" />
		</p>
		<p>
			<label for='org'>Organization<span class='required'>*</span></label>
			<input class="required" type='text' name='org' id='org'  value="<?php if($registered) echo $orgs[0]->name ?>" />
			<span class='desc'>Start typing to get matching organizations or press down to see all suggestions.</span>
		</p>
		<p>
			<label for='url'>URL</label>
			<input class="url" type='text' name='url' id='url' value="<?php if($registered) echo $registered->url ?>"/>
			<span class='desc'>Example: <i>http://linkedin.com/in/myname</i></span>
		</p>
		<p>
			<label for='interests'>Interests<span class='required'>*</span></label>
			<input class="required" type='text' name='interests' id='interests' value="<?php if($registered) echo $interestList ?>" />
			<span class='desc'>Enter a few of your IT-related interests. Start typing and matching options will appear. You can add your own, as well.</span>
		</p>
		<p>
			<input type='submit' name='submit' value='Submit' id='submit' />
		</p>
	</form>
<?php
}
?>