(function($) {
	$(function() {

		var data = {
			action: 'localise_batch'
		};

		var data_f = {
			action: 'localise_featured_batch'
		};

		var data_b = {
			action: 'localise_bad_batch'
		};

		function do_response(response){
			if(response == '<p>no more posts</p>'){
				$('#localise_entries').append('<p>finished</p>');
			} else if (response.indexOf("Aborting process") != -1){
				$('#localise_entries').append(response);
				$('#localise_entries').append('<p>Problems were encountered!</p>');
			} else {
				$('#localise_entries').append(response);
/*				$('#localise_entries').append('<p>Problems were encountered!</p>');
			} else {
				$('#localise_entries').append(response);*/
//				alert('Got this from the server: ' + response);
				$.post(ajaxurl, data, do_response);
			}
		}

		function do_response_featured(response){
			if(response == '<p>no more posts</p>'){
				$('#localise_entries').append('<p>finished</p>');
			} else if (response.indexOf("Aborting process") != -1){
				$('#localise_entries').append(response);
				$('#localise_entries').append('<p>Problems were encountered!</p>');
			} else {
				$('#localise_entries').append(response);
//				alert('Got this from the server: ' + response);
				$.post(ajaxurl, data_f, do_response_featured);
			}
			
		}

		function do_response_bad(response){
			if(response == '<p>no more posts</p>'){
				$('#localise_entries').append('<p>finished</p>');
			} else if (response.indexOf("Aborting process") != -1){
				$('#localise_entries').append(response);
				$('#localise_entries').append('<p>Problems were encountered!</p>');
			} else {
				$('#localise_entries').append(response);
				$('#localise_entries').append('<p>Single batch retrieved, re-run to grab the next</p>');
//				alert('Got this from the server: ' + response);
				//$.post(ajaxurl, data_b, do_response_bad);
			}
			
		}
		// Place your administration-specific JavaScript here
		$('#do_localiser_script').click(function(e){
			$('#localise_results').show();
			$('#localise_entries').append('<p>starting image localisation</p>');
			$.post(ajaxurl, data, do_response);
			
			return false;
		});
		$('#do_localiser_bad_script').click(function(e){
			$('#localise_results').show();
			$('#localise_entries').append('<p>starting image localisation on posts marked as bad</p>');
			$.post(ajaxurl, data_b, do_response_bad);
			
			return false;
		});

		$('#do_localiser_featured_script').click(function(e){
			$('#localise_results').show();
			$('#localise_entries').append('<p>starting featured image setup</p>');
			$.post(ajaxurl, data_f, do_response_featured);
			
			return false;
		});
	});
})(jQuery);