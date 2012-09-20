<!-- This file is used to markup the administration form of the plugin.. -->
<p>Run the localiser script to grab remote images and store them locally</p>

	<?php /*<input type="hidden" name="page" value="ICIT_ImageLocaliser" />
	<input type="hidden" name="start_localise_script" value="1" />*/ ?>

	<p><input style="width:400px;" type="text" id="from_date" name="from_date" placeholder="A mysql timestamp,  only process after this date" value="" /></p>
	<input id="do_localiser_script" type="submit" value="Localise Remote Images">
	<input id="do_localiser_bad_script" type="submit" value="Relocalise posts marked as failed">
	<input id="do_localiser_featured_script" type="submit" value="Setup featured images">
	<input id="do_localiser_featured_meta_script" type="submit" value="Setup featured images with Post meta">

<div id="localise_results">
	<h2>Results</h2>

	<div id="localise_entries">

	</div>

</div>
