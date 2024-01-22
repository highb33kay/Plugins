// ajax-search.js
jQuery(document).ready(function ($) {
	$('#search-form').submit(function (event) {
		event.preventDefault(); // Prevents the default form submission

		var searchTerm = $('#search-term').val();
		var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'ajax_search',
				term: searchTerm
			},
			success: function (response) {
				$('#search-results').html(response);
			}
		});
	});
});
