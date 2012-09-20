(function($) {
	$(function() {
		var buttons = [
			{
				id:			'do_localiser_script',
				startup:	"starting image localisation",
				singlerun:	false,
				ajax_data:	{
					action:	"localise_batch"
				}
			},
			{
				id:			'do_localiser_featured_script',
				startup:	"starting featured image setup",
				singlerun:	false,
				ajax_data:	{
					action:	"localise_featured_batch"
				}
			},
			{
				id:			'do_localiser_bad_script',
				startup:	"starting image localisation on posts marked as bad",
				singlerun:	true,
				ajax_data:	{
					action:	"localise_bad_batch"
				}
			},
			{
				id:			'do_localiser_featured_meta_script',
				startup:	"Localising featured images from Post meta",
				singlerun:	false,
				ajax_data:	{
					action:	"localise_featured_meta_batch"
				}
			}
		];

		function create_response_func(data){
			return function(response){
				if(do_response_single(response) === true){
					var f = create_response_func(data);
					$.post(ajaxurl, data, f);
				}
			};
		}

		function do_response(response){
			if(do_response_single(response) === true){
				$.post(ajaxurl, data, do_response);
			}
		}

		function do_response_single(response){
			$('#localise_entries').append('<p>Processing Response</p>');
			if(response == '<p>no more posts</p>'){
				$('#localise_entries').append('<p>finished</p>');
				return false;
			} else if (response.indexOf("Aborting process") != -1){
				$('#localise_entries').append(response);
				$('#localise_entries').append('<p>Problems were encountered!</p>');
				return false;
			} else {
				$('#localise_entries').append(response);
				return true;
			}
		}

		function setup_ajax_button(button){
			$('#'+button['id']).click(function(e){
				$('#localise_results').show();
				$('#localise_entries').append('<p>'+button.startup+'</p>');
				var func = null;
				if(button.singlerun === true){
					func = do_response_single;
				} else {
					func = create_response_func(button['ajax_data']);
				}
				button.ajax_data.from = $( '#from_date' ).val();
				$.post(ajaxurl, button.ajax_data, func);
				return false;
			});
		}
		for (var i = buttons.length - 1; i >= 0; i--) {
			var button = buttons[i];
			setup_ajax_button(button);
		}
//		do_localiser_featured_meta_script
	});
})(jQuery);
