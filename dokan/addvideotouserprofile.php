<?php

// Add extra field in seller settings
add_filter('dokan_settings_form_bottom', 'extra_fieldss', 10, 2);

function extra_fieldss($current_user, $profile_info)
{
	$store_video = isset($profile_info['store_video']) ? $profile_info['store_video'] : '';
?>
	<div class="dokan-form-group">
		<label class="dokan-w3 dokan-control-label" for="setting_store_video"><?php esc_html_e('Store Video URL', 'dokan-lite'); ?></label>
		<div class="dokan-w5 dokan-text-left">
			<input id="setting_store_video" value="<?php echo esc_attr($store_video); ?>" name="setting_store_video" placeholder="<?php esc_attr_e('Enter or Upload Video URL', 'dokan-lite'); ?>" class="dokan-form-control input-md" type="text">
		</div>
	</div>
	<div class="dokan-form-group">
		<label class="dokan-w3 dokan-control-label" for="upload_store_video"><?php esc_html_e('Upload Video', 'dokan-lite'); ?></label>
		<div class="dokan-w5 dokan-text-left">
			<button id="upload_video_button" class="dokan-btn dokan-btn-theme"><?php esc_html_e('Upload Video', 'dokan-lite'); ?></button>
			<input type="hidden" id="upload_store_video" name="upload_store_video" value="<?php echo esc_attr($store_video); ?>" />
			<div id="video_preview" style="margin-top: 10px;">
				<?php if ($store_video) : ?>
					<?php if (strpos($store_video, 'youtube.com') !== false || strpos($store_video, 'youtu.be') !== false) : ?>
						<iframe width="320" height="240" src="<?php echo esc_url($store_video); ?>" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
					<?php else : ?>
						<video width="320" height="240" controls>
							<source src="<?php echo esc_url($store_video); ?>" type="video/mp4">
						</video>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<script>
		jQuery(document).ready(function($) {
			$('#upload_video_button').click(function(e) {
				e.preventDefault();
				var mediaUploader;
				if (mediaUploader) {
					mediaUploader.open();
					return;
				}
				mediaUploader = wp.media.frames.file_frame = wp.media({
					title: '<?php esc_html_e("Choose Video", "dokan-lite"); ?>',
					button: {
						text: '<?php esc_html_e("Choose Video", "dokan-lite"); ?>'
					},
					multiple: false
				});
				mediaUploader.on('select', function() {
					var attachment = mediaUploader.state().get('selection').first().toJSON();
					$('#setting_store_video').val(attachment.url); // Set the URL in the text field
					$('#upload_store_video').val(attachment.url); // Set the URL in the hidden field
					$('#video_preview').html('<video width="320" height="240" controls><source src="' + attachment.url + '" type="video/mp4"></video>');
				});
				mediaUploader.open();
			});

			// Sync video URL input field with media URL if changed manually
			$('#setting_store_video').on('input', function() {
				$('#upload_store_video').val($(this).val()); // Sync hidden field with text field
			});
		});
	</script>
<?php
}


// Save the field value
add_action('dokan_store_profile_saved', 'save_extra_fieldss', 15);
function save_extra_fieldss($store_id)
{
	$dokan_settings = dokan_get_store_info($store_id);
	if (isset($_POST['setting_store_video'])) {
		$dokan_settings['store_video'] = esc_url_raw($_POST['setting_store_video']);
	}
	update_user_meta($store_id, 'dokan_profile_settings', $dokan_settings);
}


// Add field value in backend admin user profile area
add_action('dokan_seller_meta_fields', 'more_fields', 10);

function more_fields($user)
{
	$store_settings = dokan_get_store_info($user->ID);
	$store_video = isset($store_settings['store_video']) ? $store_settings['store_video'] : '';
?>
	<tr>
		<th><?php esc_html_e('Store Video', 'dokan-lite'); ?></th>
		<td>
			<input type="text" name="dokan_store_video" class="regular-text" value="<?php echo esc_url($store_video); ?>">
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e('Video Preview', 'dokan-lite'); ?></th>
		<td>
			<?php if ($store_video) : ?>
				<video width="320" height="240" controls>
					<source src="<?php echo esc_url($store_video); ?>" type="video/mp4">
				</video>
			<?php endif; ?>
		</td>
	</tr>
<?php
}


// Display video on vendor profile page
add_action('dokan_store_profile_frame_after', 'display_vendor_video');

function display_vendor_video($store_user)
{
	$store_settings = dokan_get_store_info($store_user->get_id());
	$store_video = isset($store_settings['store_video']) ? $store_settings['store_video'] : '';

	if ($store_video) {
		echo '<div class="dokan-vendor-video">';
		echo '<div class="vendor-video-url">';
		if (strpos($store_video, 'youtube.com') !== false || strpos($store_video, 'vimeo.com') !== false) {
			echo '<iframe width="560" height="315" src="' . esc_url($store_video) . '" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
		} else {
			echo '<video width="320" height="240" controls>';
			echo '<source src="' . esc_url($store_video) . '" type="video/mp4">';
			echo '</video>';
		}
		echo '</div>';
		echo '</div>';
	}
}
