<?php
function removeAllAccessFromCourses($course_ids)
{
	// Check if the course IDs array is valid
	if (empty($course_ids) || !is_array($course_ids)) {
		echo "Invalid course IDs or input is not an array.\n";
		return;
	}

	// Iterate over each course ID
	foreach ($course_ids as $course_id) {
		// Check if the individual course ID is valid
		if (!is_numeric($course_id)) {
			echo "Invalid course ID: $course_id.\n";
			continue; // Skip invalid IDs and continue with the next
		}

		// Retrieve all user IDs enrolled in the course using LearnDash function
		$enrolled_user_ids = learndash_get_course_users_access_from_meta($course_id);

		// Check if there are any enrolled users
		if (empty($enrolled_user_ids)) {
			echo "No users are currently enrolled in course ID $course_id or no access found.\n";
			continue;
		}

		// Loop through each user and remove their access
		foreach ($enrolled_user_ids as $user_id) {
			// Remove access using LearnDash functions
			learndash_delete_course_progress($course_id, $user_id);
			ld_update_course_access($user_id, $course_id, true);
			echo "Access removed for user ID $user_id in course ID $course_id.\n";
		}
	}
}

// Example of how to call the modified function with multiple course IDs
removeAllAccessFromCourses([10538, 10566, 10554, 10495, 10439, 10416]);
