<?php
/**
 * Humans TXT Options Page
 *
 * This file contains the code for the Humans TXT options page
 * as well as all related admin actions, filters and hooks.
 *
 * @package HumansTXT
 * @subpackage Options
 */

/**
 * URL to Humans TXT plugin folder.
 */
define( 'HUMANSTXT_PLUGIN_URL', plugin_dir_url( HUMANSTXT_PLUGIN_FILE ) );

/**
 * Humans TXT's "WordPress basename".
 */
define( 'HUMANSTXT_PLUGIN_BASENAME', plugin_basename( HUMANSTXT_PLUGIN_FILE ) );

/**
 * URL to Humans TXT options page.
 */
define( 'HUMANSTXT_OPTIONS_URL', admin_url( 'options-general.php?page=humanstxt' ) );

/**
 * URL to Humans TXT revisions page.
 */
define( 'HUMANSTXT_REVISIONS_URL', add_query_arg( array( 'subpage' => 'revisions' ), HUMANSTXT_OPTIONS_URL ) );

/**
 * Register plugin admin actions, filters and hooks.
 */
add_action( 'admin_init', 'humanstxt_admin_init' );
add_action( 'admin_menu', 'humanstxt_admin_menu' );
add_action( 'admin_notices', 'humanstxt_version_warning' );
add_action( 'admin_print_styles', 'humanstxt_admin_print_styles' );
add_action( 'admin_print_scripts', 'humanstxt_admin_print_scripts' );
add_action( 'wp_ajax_humanstxt-preview', 'humanstxt_ajax_preview' );
add_action( 'after_plugin_row_humans-txt/plugin.php', 'humanstxt_plugin_notice', 10, 2 );
add_action( 'after_plugin_row_humans-dot-txt/humans-dot-txt.php', 'humanstxt_plugin_notice', 10, 2 );
add_filter( 'plugin_action_links_' . HUMANSTXT_PLUGIN_BASENAME, 'humanstxt_action_links' );
register_uninstall_hook( __FILE__, 'humanstxt_uninstall' );

// Initialize Timber. @TODO: make a more seamless import.
require_once ABSPATH . '../vendor/autoload.php';
Timber\Timber::init();

/**
 * Load plugin text-domain.
 */
humanstxt_load_textdomain();

/**
 * Enqueue print styles.
 */
function humanstxt_admin_print_styles(): void {
	wp_enqueue_style( 'thickbox' );
	wp_enqueue_style( 'humanstxt-options' );
}

/**
 * Enqueue print scripts.
 */
function humanstxt_admin_print_scripts(): void {
	wp_enqueue_script( 'thickbox' );
	wp_enqueue_script( 'humanstxt-options' );
}

/**
 * Callback function for 'admin_init' action.
 * Registers the CSS and JavaScript file.
 * Calls humanstxt_update_options() if necessary.
 * Calls humanstxt_restore_revision() if necessary.
 * Calls humanstxt_import_file() if necessary.
 */
function humanstxt_admin_init(): void {
	if ( isset( $_GET['page'] ) && 'humanstxt' === $_GET['page'] ) {

		// Register css/js files.
		wp_register_style(
			'humanstxt-options',
			HUMANSTXT_PLUGIN_URL . 'options.css',
			array(),
			HUMANSTXT_VERSION,
		);
		wp_register_script(
			'humanstxt-options',
			HUMANSTXT_PLUGIN_URL . 'options.js',
			array( 'jquery', 'hoverIntent' ),
			HUMANSTXT_VERSION,
			true,
		);

		// Update plugin options?
		if ( isset( $_POST['action'] ) && 'update' === $_POST['action'] ) {
			check_admin_referer( 'humanstxt-options' );
			humanstxt_update_options();
		}

		// Restore a revision?
		if ( isset( $_GET['action'], $_GET['revision'] ) && 'restore' === $_GET['action'] ) {
			$revision = filter_input( INPUT_GET, 'revision', FILTER_VALIDATE_INT );
			$revision = absint( $revision );
			check_admin_referer( 'restore-humanstxt_' . $revision );
			humanstxt_restore_revision( $revision );
		}

		// Import and rename physical humans.txt file?
		if ( isset( $_GET['action'] ) && 'import-file' === $_GET['action'] ) {
			check_admin_referer( 'import-humanstxt-file' );
			humanstxt_import_file();
		}
	}
}

/**
 * Callback function if plugin is uninstalled.
 * Deletes all plugin options from the database.
 */
function humanstxt_uninstall(): void {
	delete_option( 'humanstxt_options' );
	delete_option( 'humanstxt_content' );
	delete_option( 'humanstxt_revisions' );
}

/**
 * Callback function for 'admin_notices' action.
 * Prints warning message if the current WP version is too old.
 */
function humanstxt_version_warning(): void {
	if ( ! humanstxt_is_wp( HUMANSTXT_VERSION_REQUIRED ) ) {
		$update_link = ' <a href="' . admin_url( 'update-core.php' ) . '">' . __( 'Please update your WordPress installation.', 'humanstxt' ) . '</a>';

		echo '<div id="humanstxt-warning" class="updated fade"><p><strong>'
			. esc_attr(
				sprintf(
						/* translators: the plugin version requires WordPress version ... */
					__( 'Humans TXT %1$s requires WordPress %2$s or higher.', 'humanstxt' ),
					HUMANSTXT_VERSION,
					HUMANSTXT_VERSION_REQUIRED,
				),
			)
			. '</strong>'
			. ( current_user_can( 'update_core' ) ? ( wp_kses( $update_link, array( 'a' => array( 'href' => array() ) ) ) ) : '' )
			. '</p></div>';
	}
}

/**
 * Return TRUE if given $version is higher or equals the running
 * WordPress version. This function considers pre-release versions,
 * such as 3.0.0-dev, as high as their final release counterparts (like 4.0.0).
 *
 * @param string $version Version to compare with the current WP version.
 * @return bool TRUE if current WP version is higher or equals the given $version.
 */
function humanstxt_is_wp( string $version ): bool {
	return version_compare( preg_replace( '~[^0-9.]~', '', get_bloginfo( 'version' ) ) ?? '', $version, '>=' );
}

/**
 * Callback function for 'admin_menu' action.
 * Registers the options page if the current user has access.
 */
function humanstxt_admin_menu(): void {
	/**
	 * Array of roles.
	 *
	 * @var array<string>
	 */
	$roles = is_array( humanstxt_option( 'roles' ) ) ? humanstxt_option( 'roles' ) : array();
	// Admins can always edit.
	array_unshift( $roles, 'administrator' );

	// Loop through all roles that can edit the humans.txt and
	// add options page if the current user has one of the required roles.
	foreach ( $roles as $role ) {
		/**
		 * The user role.
		 *
		 * @var string
		 */
		$role = strval( $role );
		if ( current_user_can( $role ) ) {
			$plugin_page = add_options_page( __( 'Humans TXT', 'humanstxt' ), __( 'Humans TXT', 'humanstxt' ), $role, 'humanstxt', 'humanstxt_options' );
			break;
		}
	}

	// Add contextual help menu.
	if ( isset( $plugin_page ) ) {
		add_action( 'load-' . $plugin_page, 'humanstxt_contextual_help' );
	}
}

/**
 * Callback function for 'plugin_action_links_{$plugin_file}' filter.
 * Adds a link to the plugin options page.
 *
 * @param array<string> $actions WordPress plugin actions.
 * @return array<string> $actions Actions with added link to plugin options page.
 */
function humanstxt_action_links( array $actions ): array {
	return array_merge(
		array( 'settings' => sprintf( '<a href="%s">%s</a>', HUMANSTXT_OPTIONS_URL, __( 'Settings' ) ) ),
		$actions
	);
}

/**
 * Callback function for 'load-{$page_hook}' action.
 * Registers the contextual help menu.
 */
function humanstxt_contextual_help(): void {
	$humanstxt  = sprintf(
		'<p><strong>%s</strong> &mdash; %s</p>',
		__( 'What is the humans.txt?', 'humanstxt' ),
		__( "It's an initiative for knowing the people behind a website. It's a TXT file in the site root that contains information about the humans who have contributed to the website.", 'humanstxt' )
	);
	$humanstxt .= sprintf(
		'<p><strong>%s</strong> &mdash; %s</p>',
		__( 'Who should I mention?', 'humanstxt' ),
		__( 'Whoever you want to, provided they wish you to do so. You can mention the developer, the designer, the copywriter, the webmaster, the editor, ... anyone who contributed to the website.', 'humanstxt' )
	);
	$humanstxt .= sprintf(
		'<p><strong>%s</strong> &mdash; %s</p>',
		__( 'How should I format it?', 'humanstxt' ),
		__( 'However you want, just make sure humans can easily read it. For some inspiration check the humans.txt of <a href="http://humanstxt.org/humans.txt" rel="external">humanstxt.org</a> or <a href="http://html5boilerplate.com/humans.txt" rel="external">html5boilerplate.com</a>.', 'humanstxt' )
	);

	$variables = __( 'Variables can be used to show dynamic content in your humans.txt file. You can show your visitors for example the amount of published posts, a list of activated plugins, the name of the current theme or the installed WordPress version. Hover your cursor over a variable to see a preview of it.', 'humanstxt' );

	$more = '<p><strong>' . __( 'For more information:' ) . '</strong></p>
		<p><a href="http://humanstxt.org/" rel="external">' . __( 'Humans TXT Website', 'humanstxt' ) . '</a></p>
		<p><a href="http://wordpress.org/extend/plugins/humanstxt/" rel="external">' . __( 'Plugin Homepage', 'humanstxt' ) . '</a></p>
		<p><a href="http://wordpress.org/tags/humanstxt" rel="external">' . __( 'Plugin Support Forum', 'humanstxt' ) . '</a></p>
	';

	$screen = get_current_screen();

	if ( ! is_null( $screen ) ) {
		$variables = '<p>' . $variables . '</p>';
		$screen->add_help_tab(
			array(
				'id'      => 'help-humanstxt-file',
				'title'   => __( 'Humans TXT File', 'humanstxt' ),
				'content' => $humanstxt,
			)
		);
		$screen->add_help_tab(
			array(
				'id'      => 'help-humanstxt-vars',
				'title'   => __( 'Variables', 'humanstxt' ),
				'content' => $variables,
			)
		);
		$screen->set_help_sidebar( $more );
	}
}

/**
 * Updates the current content of the humans.txt file and
 * adds it as a new revision, if the current content doesn't
 * equal the given $content.
 *
 * @param string $content New content of the humans.txt file.
 */
function humanstxt_update_content( string $content ): void {
	if ( humanstxt_content() !== $content ) {
		humanstxt_add_revision( $content );
		update_option( 'humanstxt_content', $content );
	}
}

/**
 * Updates the plugin options and redirects to plugin options page.
 *
 * @global $humanstxt_options
 */
function humanstxt_update_options(): void {
	/**
	 * Plugin options.
	 *
	 * @var array<string>
	 */
	global $humanstxt_options;

	// Verify the nonce.
	// check_admin_referer( 'humanstxt-options' );

	// Only update the admin-only options if current user can manage options.
	if ( current_user_can( 'manage_options' ) ) {
		$humanstxt_options['enabled']    = isset( $_POST['humanstxt_enable'] );
		$humanstxt_options['author_tag'] = isset( $_POST['humanstxt_author_tag'] );

		$humanstxt_options['roles'] = array();
		if ( isset( $_POST['humanstxt_roles'] ) && is_array( $_POST['humanstxt_roles'] ) ) {
			$sanitized_roles            = array_map( 'sanitize_text_field', wp_unslash( $_POST['humanstxt_roles'] ) );
			$humanstxt_options['roles'] = array_keys( $sanitized_roles );
		}

		update_option( 'humanstxt_options', $humanstxt_options );
	}

	if ( isset( $_POST['humanstxt_content'] ) ) {
		$content = (string) filter_input( INPUT_POST, 'humanstxt_content', FILTER_UNSAFE_RAW );
		humanstxt_update_content( humanstxt_content_normalize( stripslashes( $content ) ) );
	}

	wp_safe_redirect(
		add_query_arg(
			array( 'settings-updated' => '1' ),
			HUMANSTXT_OPTIONS_URL
		)
	);
	exit;
}

/**
 * Restores the given $revision of the humans.txt, if revisions
 * aren't disabled. Redirects to the plugin options page afterwards.
 *
 * @param int $revision Revision's number (key).
 */
function humanstxt_restore_revision( int $revision ): void {
	$revisions = humanstxt_revisions();

	if ( ! isset( $revisions[ $revision ] ) ) {
		return;
	}

	humanstxt_update_content( strval( $revisions[ $revision ]['content'] ) );

	wp_safe_redirect( add_query_arg( array( 'revision-restored' => '1' ), HUMANSTXT_OPTIONS_URL ) );
	exit;
}

/**
 * This function tries to import the contents of the physical
 * humans.txt file and if successful rename it to humans.txt-{time}.bak,
 * so this plugin can work properly. Redirects to the plugin
 * options page afterwards.
 *
 * @global $wp_filesystem
 */
function humanstxt_import_file(): void {
	/**
	 * The WP Filesystem object.
	 *
	 * @var WP_Filesystem_Direct $wp_filesystem
	 */
	global $wp_filesystem;

	$import = true;
	$file   = ABSPATH . 'humans.txt';

	if ( ! current_user_can( 'update_core' ) ) {
		wp_die(
			/* translators: User is denied access. */
			esc_attr_e( 'Access denied.', 'humanstxt' )
		);
	}

	// Don't bother requesting filesystem credentials.
	if ( get_filesystem_method() === 'direct' ) {
		if ( ! (bool) WP_Filesystem() ) {
			$import = false;
		}

		if ( ! humanstxt_exists() ) {
			$import = false;
		}

		if ( ! is_readable( $file ) ) {
			$import = false;
		}
		/**
		 * Contents of the humans.txt file.
		 *
		 * @var string $contents
		 */
		$contents = $wp_filesystem->get_contents( $file );
		if ( '' === $contents ) {
			$import = false;
		}

		if ( preg_match( '~\S~', $contents ) === false || preg_match( '~\S~', $contents ) === 0 ) { // only white-space?
			$import = false;
		}

		if ( $import ) {

			// Import content.
			humanstxt_update_content( humanstxt_content_normalize( $contents ) );

			// Backup file, delete original.
			if ( $wp_filesystem->is_writable( $file ) ) {
				$wp_filesystem->move( $file, $file . '-' . time() . '.bak', true );
			}

			if ( humanstxt_exists() ) {
				wp_safe_redirect( add_query_arg( array( 'rename-failed' => '1' ), HUMANSTXT_OPTIONS_URL ) );
			} else {
				wp_safe_redirect( add_query_arg( array( 'file-imported' => '1' ), HUMANSTXT_OPTIONS_URL ) );
			}

			exit;
		}
	}

	wp_safe_redirect( add_query_arg( array( 'import-failed' => '1' ), HUMANSTXT_OPTIONS_URL ) );
	exit;
}

/**
 * Callback function for 'after_plugin_row_{$plugin_file}' action.
 * Prints a warning message which suggests to deactivate other
 * humans.txt plugins to avoid conflicts.
 *
 * @param string        $plugin_file WordPress plugin path.
 * @param array<string> $plugin_data Plugin information.
 */
function humanstxt_plugin_notice( string $plugin_file, array $plugin_data ): void {
	if ( is_plugin_active( $plugin_file ) ) {
		echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update colspanchange"><div class="update-message">'
			. sprintf(
				/* translators: the placeholder is for plugin name. */
				esc_attr_x( 'Humans TXT includes the functionality of %1$s. Please deactivate %1$s to avoid plugin conflicts.', 'humanstxt' ),
				'<em>' . esc_attr( $plugin_data['Name'] ) . '</em>'
			)
			. '</div></td></tr>';
	}
}

/**
 * Returns an array with plugin rating and total votes from WordPress.org.
 *
 * @return array<string,int>|false Plugin rating and total votes.
 */
function humanstxt_rating() {
	$api = get_transient( 'humanstxt_plugin_information' );

	// update cache?
	if ( false === $api ) {
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		$api = plugins_api( 'plugin_information', array( 'slug' => 'humanstxt' ) );

		if ( ! is_wp_error( $api ) ) {
			set_transient( 'humanstxt_plugin_information', $api, 60 * 10 );
		}
	}

	if ( is_object( $api ) && ! is_wp_error( $api ) && property_exists( $api, 'rating' ) && property_exists( $api, 'num_ratings' ) ) {
		if ( is_numeric( $api->rating ) && is_numeric( $api->num_ratings ) ) {
			return array(
				'rating' => (int) $api->rating,
				'votes'  => (int) $api->num_ratings,
			);
		}
	}

	return false;
}

/**
 * Callback function of 'wp_ajax_humanstxt-preview' action.
 * Shows a preview of the humans.txt file.
 */
function humanstxt_ajax_preview(): void {
	if ( ! check_ajax_referer( 'humanstxt-options' ) ) {
		esc_attr_e( 'An error has occurred. Please reload the page and try again.' );
		exit;
	}
	if ( isset( $_GET['content'] ) && '' !== $_GET['content'] ) {
		$content = apply_filters( 'humans_txt', sanitize_text_field( strval( wp_unslash( $_GET['content'] ) ) ) );
		$content = filter_var( $content, FILTER_UNSAFE_RAW );
		$content = false === $content ? '' : $content;
		printf( '<pre>%s</pre>', esc_html( $content ) );
	} else {
		esc_attr_e( 'An error has occurred. Please reload the page and try again.' );
	}

	exit;
}

/**
 * Callback function registered with add_options_page().
 * Prints the requested page (options or revisions).
 */
function humanstxt_options(): void {

	// Verify the nonce.
	// check_admin_referer( 'humanstxt-options' );

	// Show revisions page.
	if (
		isset( $_GET['subpage'] )
		&& 'revisions' === $_GET['subpage']
		&& humanstxt_revisions() !== false
	) {
		humanstxt_revisions_page();
	} else {
		humanstxt_options_page();
	}
}

/**
 * Prints the plugin options page.
 */
function humanstxt_options_page(): void {
	$settings_updated    = isset( $_GET['settings-updated'] );
	$revision_restored   = isset( $_GET['revision-restored'] );
	$file_imported       = isset( $_GET['file-imported'] );
	$rename_failed       = isset( $_GET['rename-failed'] );
	$import_failed       = isset( $_GET['import-failed'] );
	$humanstxt_exists    = humanstxt_exists();
	$user_can_edit_files = current_user_can( 'edit_files' );

	$import_file_url           = wp_nonce_url(
		add_query_arg(
			array( 'action' => 'import-file' ),
			HUMANSTXT_OPTIONS_URL
		),
		'import-humanstxt-file'
	);
	$empty_permalink_structure = '' === get_option( 'permalink_structure' );
	$user_can_manage_options   = current_user_can( 'manage_options' );
	$prmalink_structure_url    = admin_url( 'options-permalink.php' );

	$humanstxt_options_settings = settings_fields( 'humanstxt' );

	$humanstxt_enabled    = humanstxt_option( 'enabled' );
	$humanstxt_author_tag = humanstxt_option( 'author_tag' );

	$humanstxt_roles = is_array( humanstxt_option( 'roles' ) ) ? humanstxt_option( 'roles' ) : array();
	$wordpress_roles = get_editable_roles();
	unset( $wordpress_roles['subscriber'] );

	$roles = array();

	foreach ( $wordpress_roles as $role => $details ) {
		$checked  = ( 'administrator' === $role || in_array( $role, $humanstxt_roles, true ) ) ? 'checked="checked" ' : '';
		$disabled = ( 'administrator' === $role ) ? 'disabled="disabled" ' : '';
		$roles[]  = array(
			'id'       => 'humanstxt_role_' . $role,
			'name'     => 'humanstxt_roles[' . $role . ']',
			'role'     => $role,
			'checked'  => $checked,
			'disabled' => $disabled,
			'friendly' => translate_user_role( $details['name'] ),
		);
	}

	$humanstxt_content     = esc_textarea( humanstxt_content() );
	$humanstxt_preview_url = wp_nonce_url( admin_url( 'admin-ajax.php?action=humanstxt-preview' ), 'humanstxt-options' );

	$revisions      = humanstxt_revisions();
	$have_revisions = ( is_array( $revisions ) && count( $revisions ) > 1 );
	$revisions_url  = wp_nonce_url( HUMANSTXT_REVISIONS_URL, 'humanstxt-options' );

	$group_names         = array(
		'wordpress' => __( 'WordPress' ),
		'server'    => __( 'Server', 'humanstxt' ),
		'addons'    => __( 'Themes & Plugins', 'humanstxt' ),
		'misc'      => __( 'Miscellaneous', 'humanstxt' ),
	);
		$valid_variables = humanstxt_valid_variables();
		$variable_groups = array();
	foreach ( $valid_variables as $variable ) {
		if ( isset( $group_names[ $variable[0] ] ) ) {
			$variable_groups[ $variable[0] ][] = $variable;
		}
	}
	$have_variables = ( 0 !== count( $variable_groups ) );

	Timber::render(
		'twig/options.twig',
		array(
			'plugin_url'                 => HUMANSTXT_PLUGIN_URL,
			'options_url'                => HUMANSTXT_OPTIONS_URL,
			'content'                    => humanstxt_content(),
			'option_enabled'             => humanstxt_option( 'enabled' ),
			'option_author_tag'          => humanstxt_option( 'author_tag' ),
			'option_roles'               => is_array( humanstxt_option( 'roles' ) ) ? humanstxt_option( 'roles' ) : array(),
			'editable_roles'             => get_editable_roles(),
			'settings_updated'           => $settings_updated,
			'revision_restored'          => $revision_restored,
			'file_imported'              => $file_imported,
			'rename_failed'              => $rename_failed,
			'import_failed'              => $import_failed,
			'humanstxt_exists'           => $humanstxt_exists,
			'user_can_edit_files'        => $user_can_edit_files,
			'user_can_manage_options'    => $user_can_manage_options,
			'import_file_url'            => $import_file_url,
			'empty_permalink_structure'  => $empty_permalink_structure,
			'permalink_structure_url'    => $prmalink_structure_url,
			'humanstxt_options_url'      => HUMANSTXT_OPTIONS_URL,
			'humanstxt_options_settings' => $humanstxt_options_settings,
			'humanstxt_enabled'          => $humanstxt_enabled,
			'humanstxt_author_tag'       => $humanstxt_author_tag,
			'roles'                      => $roles,
			'humanstxt_url'              => home_url( 'humans.txt' ),
			'humanstxt_content'          => $humanstxt_content,
			'humanstxt_preview_url'      => $humanstxt_preview_url,
			'have_revisions'             => $have_revisions,
			'revisions_url'              => $revisions_url,
			'have_variables'             => $have_variables,
			'variable_groups'            => $variable_groups,
			'group_names'                => $group_names,
		)
	);
}

/**
 * Prints the plugin options revisions page.
 */
function humanstxt_revisions_page(): void {
	?>
	<div id="humanstxt-revisions" class="wrap">

		<h1>
			<?php
				_e( 'Humans TXT', 'humanstxt' );
				echo ':';
			?>
			<?php _e( 'Revisions' ); ?>
		</h1>

		<?php
			$show_revision = 0;
			$live_revision = 0;
			$revisions     = is_array( humanstxt_revisions() ) ? humanstxt_revisions() : array();
			$revision      = 0;
			krsort( $revisions );
		if ( count( $revisions ) !== 0 ) :
			$live_revision = max( array_keys( $revisions ) );
			if ( isset( $_GET['revision'] ) && is_scalar( $_GET['revision'] ) ) {
				$revision = strval( $_GET['revision'] );
			}
			$show_revision = ! empty( $revision ) && isset( $revisions[ $revision ] ) ? filter_input( INPUT_GET, 'revision', FILTER_VALIDATE_INT ) : false;
			endif;
		?>
		<?php
			$action = isset( $_GET['action'] ) && is_scalar( $_GET['action'] ) ? strval( $_GET['action'] ) : '';
			$left   = isset( $_GET['left'] ) && is_scalar( $_GET['left'] ) ? strval( $_GET['left'] ) : '';
			$right  = isset( $_GET['right'] ) && is_scalar( $_GET['right'] ) ? strval( $_GET['right'] ) : '';
		?>
		<?php if ( false !== $show_revision && ! is_null( $show_revision ) && $show_revision >= 0 ) : ?>

			<h3>
			<?php
					printf( /* translators: %s: revision date */
						__( 'Revision created on %s', 'humanstxt' ),
						date_i18n(
							_x( 'j F, Y @ G:i:s', 'revision date format' ),
							intval( $revisions[ $show_revision ]['date'] ),
						)
					);
			?>
					</h3>
			<pre id="revision-preview" class="postbox">
				<?php echo esc_html( strval( $revisions[ $show_revision ]['content'] ) ); ?>
			</pre>
			<p class="submit">
				<?php
					$submit_url = wp_nonce_url(
						add_query_arg(
							array(
								'revision' => $show_revision,
								'action'   => 'restore',
							),
							HUMANSTXT_OPTIONS_URL
						),
						'restore-humanstxt_' . $show_revision
					);
				?>
				<a
					href="<?php echo esc_url( $submit_url ); ?>"
					class="button-primary"
				>
					<?php _e( 'Restore Revision', 'humanstxt' ); ?>
				</a>
			</p>

			<?php
				elseif (
					! empty( $action ) && ! empty( $left ) && ! empty( $right )
					&& 'compare' === $action
					&& isset( $revisions[ $left ], $revisions[ $right ] )
				) :
					?>
					<?php if ( $left === $right ) : ?>
				<div class="error">
					<p>
						<?php
							esc_attr_e( 'You cannot compare a revision to itself.', 'humanstxt' );
						?>
					</p>
				</div>
			<?php elseif ( wp_text_diff( strval( $revisions[ $left ]['content'] ), strval( $revisions[ $right ]['content'] ) ) === '' ) : ?>
				<div class="error">
					<p>
						<?php esc_attr_e( 'These revisions are identical.' ); ?>
					</p>
				</div>
			<?php else : ?>

				<table class="form-table ie-fixed">
					<tr>
						<th class="th-full">
							<span class="alignleft">
								<?php
									printf(
										/* translators: %s is the date (in long format). */
										esc_attr_x( 'Older: %s', 'humanstxt' ),
										esc_attr(
											date_i18n(
												_x( 'j F, Y @ G:i:s', 'revision date format' ),
												intval( $revisions[ $left ]['date'] )
											),
										),
									);
								?>
							</span>
							<span class="alignright">
								<?php
									printf(
										/* translators: %s: revision date */
										esc_attr_x( 'Newer: %s', 'humanstxt' ),
										esc_attr(
											date_i18n(
												_x( 'j F, Y @ G:i:s', 'revision date format' ),
												intval( $revisions[ $right ]['date'] )
											),
										),
									);
								?>
							</span>
						</th>
					</tr>
					<tr>
						<td>
							<div class="pre">
								<?php
									// Diff between two text revisions. No escaping needed as wp_text_diff() produces text.
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									echo wp_text_diff( strval( $revisions[ $left ]['content'] ), strval( $revisions[ $right ]['content'] ) );
								?>
							</div>
						</td>
					</tr>
				</table>

				<br class="clear">

			<?php endif; ?>

		<?php endif; ?>

		<h3><?php esc_attr_e( 'Revisions' ); ?></h3>

		<form
			action="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>"
			method="get"
		>

			<div class="tablenav">
				<div class="alignleft">
					<input
						type="submit"
						class="button-secondary"
						value="<?php esc_attr_e( 'Compare Revisions' ); ?>"
					>
					<input type="hidden" name="page" value="humanstxt" />
					<input type="hidden" name="subpage" value="revisions" />
					<input type="hidden" name="action" value="compare" />
				</div>
			</div>

			<br class="clear" />

			<table class="widefat" cellspacing="0" id="humanstxt-revisions">
				<col />
				<col />
				<col style="width: 33%" />
				<col style="width: 33%" />
				<col style="width: 33%" />
				<thead>
					<tr>
						<th scope="col"><?php esc_attr_x( 'Old', 'revisions column name' ); ?></th>
						<th scope="col"><?php esc_attr_x( 'New', 'revisions column name' ); ?></th>
						<th scope="col"><?php esc_attr_x( 'Date Created', 'revisions column name' ); ?></th>
						<th scope="col"><?php esc_attr_e( 'Author' ); ?></th>
						<th scope="col" class="action-links"><?php esc_attr_e( 'Actions' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $revisions as $key => $revision ) : ?>
						<?php
						$left_revision  = isset( $_GET['left'] ) ? filter_input( INPUT_GET, 'left', FILTER_VALIDATE_INT ) : $key;
						$right_revision = isset( $_GET['right'] ) ? filter_input( INPUT_GET, 'right', FILTER_VALIDATE_INT ) : $key;
						$left           =
						isset( $revisions[ $left_revision ] )
						? $revisions[ $left_revision ]
						: (
							( $show_revision )
							?? ( $live_revision - 1 )
						);
						$right          =
						isset( $revisions[ $right_revision ] )
							? $revisions[ $right_revision ]
							: $live_revision;
						?>
						<tr<?php echo ( $key === $show_revision ) ? ' class="displayed-revision"' : ''; ?>>
							<th scope="row">
								<input
									type="radio"
									name="left"
									value="<?php echo esc_attr( (string) $key ); ?>"
									<?php checked( $key === $left ); ?>
								>
							</th>
							<th scope="row">
								<input
									type="radio"
									name="right"
									value="<?php echo esc_attr( (string) $key ); ?>"
									<?php checked( $key === $right ); ?>
								>
							</th>
							<td>
								<?php $date = '<a href="' . esc_url( add_query_arg( array( 'revision' => $key ), HUMANSTXT_REVISIONS_URL ) ) . '">' . date_i18n( _x( 'j F, Y @ G:i', 'revision date format' ), intval( $revision['date'] ) ) . '</a>'; ?>
								<?php
								if ( $key === $live_revision ) {
									/* translators: The current revision label text. */
									$revision_text = sprintf( __( '%1$s [Current Revision]' ), $date );
								} else {
									$revision_text = sprintf( '%s', $date );
								}
								echo wp_kses(
									$revision_text,
									array(
										'a' => array(
											'href' => array(),
										),
									)
								);
								?>
							</td>
							<td>
								<?php if ( $revision['user'] > 0 ) : ?>
									<?php esc_attr( get_the_author_meta( 'display_name', intval( $revision['user'] ) ) ); ?>
								<?php endif; ?>
							</td>
							<td class="action-links">
								<?php if ( $key !== $live_revision ) : ?>
									<a href="
									<?php
									echo esc_url(
										wp_nonce_url(
											add_query_arg(
												array(
													'revision' => $key,
													'action'   => 'restore',
												),
												HUMANSTXT_OPTIONS_URL
											),
											'restore-humanstxt_' . $key
										)
									);
									?>
								"><?php esc_attr_e( 'Restore' ); ?></a>
								<?php endif; ?>
							</td>
							</tr>
						<?php endforeach; ?>
				</tbody>
			</table>

		</form>

		<p>
			<?php
			$max_revisions = apply_filters( 'humanstxt_max_revisions', HUMANSTXT_MAX_REVISIONS );
			$max_revisions = filter_var( $max_revisions, FILTER_VALIDATE_INT, FILTER_REQUIRE_SCALAR );

			$translated_string =
				/* translators: %s: number of stored revisions */
				__( 'WordPress is storing the last %s revisions of your <em>humans.txt</em> file.', 'humanstxt' );
			$formatted_string = sprintf( $translated_string, intval( $max_revisions ) );

			echo wp_kses(
				$formatted_string,
				array(
					'em' => array(),
				)
			);
			?>
		</p>

	</div>
	<?php
}
