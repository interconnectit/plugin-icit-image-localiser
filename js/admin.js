(function($) {
	$(function() {

		var data = {
			action: 'localise_batch'
		};

		function do_response(response){
			if(response == '<p>no more posts</p>'){
				$('#localise_entries').append('<p>finished</p>');
			} else if (response.indexOf("Aborting process") != -1){
				$('#localise_entries').append(response);
				$('#localise_entries').append('<p>Problems were encountered!</p>');
			} else {
				$('#localise_entries').append(response);
//				alert('Got this from the server: ' + response);
				$.post(ajaxurl, data, do_response);
			}
			
		}
		// Place your administration-specific JavaScript here
		$('#do_localiser_script').click(function(e){
			$('#localise_results').show();
			$('#localise_entries').append('<p>starting</p>');
			$.post(ajaxurl, data, do_response);
			
			return false;
		});
	});
})(jQuery);