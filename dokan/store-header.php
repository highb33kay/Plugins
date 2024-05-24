<?php

// Fetch and display the vendor's video
$store_video = isset($store_info['store_video']) ? $store_info['store_video'] : '';
if ($store_video) {
	echo '<div class="dokan-vendor-video">';
	if (strpos($store_video, 'youtube.com') !== false || strpos($store_video, 'vimeo.com') !== false) {
		// Embed YouTube or Vimeo video
		echo '<iframe width="560" height="315" src="' . esc_url($store_video) . '" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
	} else {
		// Embed self-hosted video
		echo '<video width="560" height="315" controls>';
		echo '<source src="' . esc_url($store_video) . '" type="video/mp4">';
		echo '</video>';
	}
	echo '</div>';
}
