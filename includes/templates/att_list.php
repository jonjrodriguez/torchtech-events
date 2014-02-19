<?php

//Display attendee list
function tt_events_show_att_list($id) {
	global $current_user;
	global $wpdb;

	if ($_GET['org'])
		$group['org'] = get_term_by('slug',$_GET['org'],'tt_event_reg_orgs');
	if ($_GET['interest'])
		$group['interest'] = get_term_by('slug',$_GET['interest'],'tt_event_reg_interests');

	//Get users registered for event (not on waitlist or cancelled)
	$data = array();
	$query = "SELECT $wpdb->tt_events_attendees.* FROM $wpdb->tt_events_attendees ";
	if($group) {

		echo "<h3>Categories:</h3>";

		$i = 1;
		foreach($group as $key => $tag) {
			$alias = $wpdb->term_relationships .'_'.$i;
			$query .= "JOIN $wpdb->term_relationships $alias
					ON $wpdb->tt_events_attendees.id = $alias.object_id
					AND $alias.term_taxonomy_id = %d ";

			$data[] = $tag->term_taxonomy_id;
			$i++;

			echo $tag->name;
			if($key =='org')
				echo " <a href='". remove_query_arg('org') . "'>clear</a> ";
			else
				echo " <a href='". remove_query_arg('interest') . "'>clear</a> ";
			echo "<br />";
		}

		$query .= "JOIN $wpdb->term_taxonomy 
				ON $alias.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id
				AND ($wpdb->term_taxonomy.taxonomy = 'tt_event_reg_orgs' OR $wpdb->term_taxonomy.taxonomy = 'tt_event_reg_interests') ";
	} 

	$query .= "WHERE event_id = %d AND waitlist = %d AND cancelled = %d";

	array_push($data,$id,0,0);
	$sql = $wpdb->prepare($query,$data);

	$attendees = $wpdb->get_results($sql);
	$i = 0;

	if(!$attendees)
		return "<p>No attendees found.</p>";
?>
 	<table>
		<thead>
			<tr>
				<th>Name</th>
				<th>Title</th>
				<th>Organization</th>
				<th>Interests</th>
			</tr>
		</thead>
		<tbody>
<?php foreach($attendees as $attendee) : ?>
<?php
	$user = get_userdata($attendee->user_id);

	if($user) :

		$orgs = wp_get_object_terms($attendee->id,'tt_event_reg_orgs');
		$interests = wp_get_object_terms($attendee->id,'tt_event_reg_interests');
		$cp_interests = $interests; //For appending commas

		$class = ($i % 2 == 0) ? 'even' : 'odd';
		$i++;
?>
			<tr class="<?php echo $class ?>">
			<?php if($attendee->url) : ?>
				<td><a href="<?php echo $attendee->url ?>" target='_blank'><?php echo $user->display_name ?></a></td>
			<?php else : ?>
				<td><?php echo $user->display_name ?></td>
			<?php endif ?>
				<td><?php echo $attendee->title ?></td>
				<td>
				<?php if($orgs) : ?>
					<a href="<?php echo add_query_arg( 'org', $orgs[0]->slug ) ?>"><?php echo $orgs[0]->name ?></a>
				<?php endif ?>
				</td>
				<td>
				<?php foreach($interests as $interest) : ?>
					<a href="<?php echo add_query_arg( 'interest', $interest->slug ) ?>"><?php echo $interest->name ?></a><?php if(next($cp_interests)) echo ',' ?>
				<?php endforeach ?>
				</td>
			</tr>
<?php endif ?>
<?php endforeach ?>
		</tbody>
	</table>
<?php
}
?>