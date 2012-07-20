<?php

/**
 * Standard class for all ICIT WordPress products
 *
 * - Register the plugin to add settings/boiler plate and API page
 * - Use add_meta_box() or settings API to extend the page, page id is the file path
 *
 *
 * v0.2
 *
 */

if ( ! class_exists( 'icit_plugins' ) ) {

	add_action( 'init', array( 'icit_plugins', 'instance' ), 8 );

	class icit_plugins {

		public $plugins = array();
		public $plugin = '';

		// initilisation structure
		protected static $instance = null;

		public static function instance() {
			null === self :: $instance AND self :: $instance = new self;
			return self :: $instance;
		}

		/**
		 * Setup
		 *
		 * @return void
		 */
		function __construct() {

			// create plugin page
			add_action( 'admin_menu', array( $this, 'plugin_pages' ), 99999999 );

			// icit plugin page CSS
			add_action( 'admin_print_styles', array( $this, 'css' ) );

			// handle save after pages are prepped and settings registered
			add_action( 'admin_init', array( $this, 'save' ), 99999999 );

		}

		/**
		 * Registers the plugin so we can automatically add a page for it
		 *
		 * @return void
		 */
		function register( $id = false, $plugin_file = false, $args = array() ) {

			if ( ! $id || ! $plugin_file )
				return;

			/** WordPress Plugin Administration API */
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			$plugin_data = get_plugin_data( $plugin_file );

			$args = wp_parse_args( $args, array(
				'ID' 			=> $id,
				'page_title' 	=> $plugin_data[ 'Name' ],
				'menu_title' 	=> $plugin_data[ 'Name' ],
				'menu_slug' 	=> $id,
				'capability' 	=> 'manage_options',
				'parent_slug' 	=> 'options-general.php',
				'extra_content' => '',
				'file' 			=> $plugin_file
			) );

			// add plugin to list
			$this->plugins[ $id ] = $args;

		}


		/**
		 * Add options page for each registered plugin and set current page
		 *
		 * @return Type    Description
		 */
		function plugin_pages() {

			foreach( $this->plugins as $id => $plugin ) {

				// choose the current page if any
				if ( isset( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] == $plugin[ 'menu_slug' ] )
					$this->plugin = $id;

				// create page
				add_submenu_page( $plugin[ 'parent_slug' ], $plugin[ 'page_title' ], $plugin[ 'menu_title' ], $plugin[ 'capability' ], $plugin[ 'menu_slug' ], array( $this, 'build_page' ) );

			}

		}

		function build_page() {

			$id = $this->plugin;

			if ( empty( $id ) )
				return;

			$plugin = $this->plugins[ $id ];
			$plugin_data = get_plugin_data( $plugin[ 'file' ] );

			echo '
		<div class="wrap icit-plugin">';

			// title
			if ( isset( $plugin[ 'icon' ] ) )
				echo $plugin[ 'icon' ];
			echo '<h2>' . esc_html( $plugin[ 'page_title' ] ) . '</h2>';

			// wrap form around everything
			echo '
			<form name="icit" action="' . admin_url( 'options.php' ) . '" method="post" enctype="multipart/form-data">';

			settings_fields( $id );

			// error/update messages
			settings_errors( 'general' ); 	// standard 'updated' message
			settings_errors( $id ); 		// custom errors

			// version & info metabox
			echo '
				<div class="right-column">
					<div class="column-inner">
						<div class="postbox icit-branding">
							<h3>' . $plugin_data[ 'Name' ] . '</h3>
							<div class="version">v' . $plugin_data[ 'Version' ] . '</div>
							<p class="description">' . $plugin_data[ 'Description' ] . '</p>
							<div class="plugin-url"><a href="' . $plugin_data[ 'PluginURI' ] . '">' . __( 'Visit plugin page' ) . '</a></div>
							<div class="credit">by <a href="' . $plugin_data[ 'AuthorURI' ] . '">interconnect/it</a></div>
						</div>';

			// process sidebar metaboxes
			do_meta_boxes( $id, 'side', $plugin );

			echo '
					</div>
				</div>
				<div class="left-column">
					<div class="column-inner">';

			// custom callback content
			if ( is_callable( $plugin[ 'extra_content' ] ) )
				call_user_func_array( $plugin[ 'extra_content' ], array($id, $plugin) );

			// normal context metaboxes
			do_meta_boxes( $id, 'normal', $plugin );

			// API key field
			$this->api();

			// settings API hooks
			ob_start();
			do_settings_fields( $id, 'default' );
			$settings_fields = trim( ob_get_clean() );

			ob_start();
			do_settings_sections( $id );
			$settings_sections = trim( ob_get_clean() );

			if ( ! empty( $settings_fields ) || ! empty( $settings_sections ) ) {

				ob_start();
				if ( ! empty( $settings_fields ) )
					echo '<table class="form-table">' . $settings_fields . '</table>';
				if ( ! empty( $settings_sections ) )
					echo $settings_sections;
				submit_button();

				echo ob_get_clean();

			}

			echo '
					</div>
				</div>
			</form>
		</div>';

		}

		function get() {
			return $this->plugins;
		}

		function save() {
			do_action( "icit_plugin_save", $this->plugin );
		}

		function css() {
			?>
			<style>
				.icit-plugin {  }
				.icit-plugin .right-column { float: right; width: 280px; }
				.icit-plugin .left-column { float: left; width: 100%; margin-right: -300px; }
				.icit-plugin .left-column .column-inner { margin-right: 300px; }
				.icit-plugin .icit-branding { background: #fff; }
				.icit-plugin .icit-branding h3,
				.icit-plugin .icit-branding h3:hover { margin: 0; padding: 10px 10px 0; cursor: text; background: none; color: #464646; border: 0; border-top: 20px solid #c00; font-size: 18px; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; }
				.icit-plugin .icit-branding p { margin: 20px 10px; line-height: 16px; }
				.icit-plugin .icit-branding p cite { display: none; }
				.icit-plugin .icit-branding div { margin: 10px; }
				.icit-plugin .icit-branding .version { margin: 5px 10px 10px; font-size: 16px; color: #787878; }
			</style>
			<?php
		}

		/**
		 * Checks if API key is required and returns the field for the settings page if it is.
		 *
		 * @return string    HTML for API key field
		 */
		function api() {

		}

	}

	if ( ! function_exists( 'icit_register_plugin' ) ) {

		/**
		 * Public method to register ICIT plugins
		 *
		 * @param string 	$id 			A unique ID used to refer to the page via the settings API and metabox API
		 * @param string 	$plugin_file 	The full path of the main plugin file
		 * @param array 	$args        	Optional settings for the boiler plate page
		 *
		 *	'page_title' 	=> Plugin page title, defaults to plugin name
		 *	'menu_title' 	=> Plugin page title in menu, defaults to plugin name
		 *	'menu_slug' 	=> Unique slug for query string parameter, default generated from plugin name
		 *	'capability' 	=> Capability type required to see page, defaults to 'manage_options'
		 *	'parent_slug' 	=> Parent page file name or slug, defaults to 'options-general.php'
		 *	'extra_content' => Callable function to output anything you want
		 *
		 * @return icit_plugins::register()
		 */
		function icit_register_plugin( $id, $plugin_file, $args = array() ) {
			$icit_plugins_class = icit_plugins::instance();
			return $icit_plugins_class->register( $id, $plugin_file, $args );
		}

	}

}

?>
