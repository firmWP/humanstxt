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
add_action( 'after_plugin_row_humans-txt/plugin.php', 'humanstxt_plugin_notice', 10, 3 );
add_action( 'after_plugin_row_humans-dot-txt/humans-dot-txt.php', 'humanstxt_plugin_notice', 10, 3 );
add_filter( 'plugin_action_links_' . HUMANSTXT_PLUGIN_BASENAME, 'humanstxt_action_links' );
register_uninstall_hook( __FILE__, 'humanstxt_uninstall' );

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
		wp_register_style( 'humanstxt-options', HUMANSTXT_PLUGIN_URL . 'options.css', array(), HUMANSTXT_VERSION );
		wp_register_script( 'humanstxt-options', HUMANSTXT_PLUGIN_URL . 'options.js', array( 'jquery', 'hoverIntent' ), HUMANSTXT_VERSION );

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
			. sprintf( __( 'Humans TXT %1$s requires WordPress %2$s or higher.', 'humanstxt' ), HUMANSTXT_VERSION, HUMANSTXT_VERSION_REQUIRED )
			. '</strong>' . ( current_user_can( 'update_core' ) ? $update_link : '' )
			. '</p></div>';
	}
}

/**
 * Return TRUE if given $version is higher or equals the running
 * WordPress version. This function considers pre-release versions,
 * such as 3.0.0-dev, as high as their final release counterparts (like 4.0.0).
 */
function humanstxt_is_wp( string $version ): bool {
	return version_compare( preg_replace( '~[^0-9.]~', '', get_bloginfo( 'version' ) ) ?? '', $version, '>=' );
}

/**
 * Callback function for 'admin_menu' action.
 * Registers the options page if the current user has access.
 */
function humanstxt_admin_menu(): void {
	/** @var array<string> */
	$roles = is_array( humanstxt_option( 'roles' ) ) ? humanstxt_option( 'roles' ) : array();
	array_unshift( $roles, 'administrator' ); // admins can always edit

	// loop through all roles that can edit the humans.txt and
	// add options page if the current user has one of the required roles
	foreach ( $roles as $role ) {
		/** @var string */
		$role = strval( $role );
		if ( current_user_can( $role ) ) {
			$plugin_page = add_options_page( __( 'Humans TXT', 'humanstxt' ), __( 'Humans TXT', 'humanstxt' ), $role, 'humanstxt', 'humanstxt_options' );
			break;
		}
	}

	// add contextual help menu
	if ( isset( $plugin_page ) ) {
		add_action( 'load-' . $plugin_page, 'humanstxt_contextual_help' );
	}
}

/**
 * Callback function for 'plugin_action_links_{$plugin_file}' filter.
 * Adds a link to the plugin options page.
 *
 * @param array<string> $actions
 * @return array<string> $actions Hijacked actions.
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
 * @param string $content New content of the humans.txt file
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
	/** @var array<string> $humanstxt_options */
	global $humanstxt_options;

	// Only update the admin-only options if current user can manage options.
	if ( current_user_can( 'manage_options' ) ) {
		$humanstxt_options['enabled']    = isset( $_POST['humanstxt_enable'] );
		$humanstxt_options['author_tag'] = isset( $_POST['humanstxt_author_tag'] );

		$humanstxt_options['roles'] = array();
		if ( isset( $_POST['humanstxt_roles'] ) && is_array( $_POST['humanstxt_roles'] ) ) {
			$humanstxt_options['roles'] = array_keys( $_POST['humanstxt_roles'] );
		}

		update_option( 'humanstxt_options', $humanstxt_options );
	}

	if ( isset( $_POST['humanstxt_content'] ) ) {
		$content = (string) filter_input( INPUT_POST, 'humanstxt_content', FILTER_UNSAFE_RAW );
		humanstxt_update_content( humanstxt_content_normalize( stripslashes( $content ) ) );
	}

	wp_redirect( add_query_arg( array( 'settings-updated' => '1' ), HUMANSTXT_OPTIONS_URL ) );
	exit;
}

/**
 * Restores the given $revision of the humans.txt, if revisions
 * aren't disabled. Redirects to the plugin options page afterwards.
 *
 * @param int $revision Revision's number (key)
 */
function humanstxt_restore_revision( int $revision ): void {
	$revisions = humanstxt_revisions();

	if ( ! isset( $revisions[ $revision ] ) ) {
		return;
	}

	humanstxt_update_content( strval( $revisions[ $revision ]['content'] ) );

	wp_redirect( add_query_arg( array( 'revision-restored' => '1' ), HUMANSTXT_OPTIONS_URL ) );
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
	/** @var WP_Filesystem_Direct $wp_filesystem */
	global $wp_filesystem;

	$import = true;
	$file   = ABSPATH . 'humans.txt';

	if ( ! current_user_can( 'update_core' ) ) {
		wp_die( __( 'Access denied.', 'humanstxt' ) );
	}

	// don't bother requesting filesystem credentials
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
		/** @var string $contents */
		$contents = $wp_filesystem->get_contents( $file );
		if ( '' === $contents ) {
			$import = false;
		}

		if ( preg_match( '~\S~', $contents ) === false || preg_match( '~\S~', $contents ) === 0 ) { // only white-space?
			$import = false;
		}

		if ( $import ) {

			// import content
			humanstxt_update_content( humanstxt_content_normalize( $contents ) );

			// backup file, delete original
			if ( $wp_filesystem->is_writable( $file ) ) {
				$wp_filesystem->move( $file, $file . '-' . time() . '.bak', true );
			}

			if ( humanstxt_exists() ) {
				wp_redirect( add_query_arg( array( 'rename-failed' => '1' ), HUMANSTXT_OPTIONS_URL ) );
			} else {
				wp_redirect( add_query_arg( array( 'file-imported' => '1' ), HUMANSTXT_OPTIONS_URL ) );
			}

			exit;
		}
	}

	wp_redirect( add_query_arg( array( 'import-failed' => '1' ), HUMANSTXT_OPTIONS_URL ) );
	exit;
}

/**
 * Callback function for 'after_plugin_row_{$plugin_file}' action.
 * Prints a warning message which suggests to deactivate other
 * humans.txt plugins to avoid conflicts.
 *
 * @param string        $plugin_file WordPress plugin path.
 * @param array<string> $plugin_data Plugin information.
 * @param string        $status Plugin context: mustuse, dropins, etc.
 */
function humanstxt_plugin_notice( string $plugin_file, array $plugin_data, string $status ): void {
	if ( is_plugin_active( $plugin_file ) ) {
		echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update colspanchange"><div class="update-message">' . sprintf( __( 'Humans TXT includes the functionality of %1$s. Please deactivate %1$s to avoid plugin conflicts.', 'humanstxt' ), '<em>' . $plugin_data['Name'] . '</em>' ) . '</div></td></tr>';
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
	if ( isset( $_GET['content'] ) && '' !== $_GET['content'] ) {
		$content = apply_filters( 'humans_txt', $_GET['content'] );
		$content = filter_var( $content, FILTER_UNSAFE_RAW );
		$content = false === $content ? '' : $content;
		$content = esc_html( $content );
		printf( '<pre>%s</pre>', $content );
	} else {
		echo __( 'An error has occurred. Please reload the page and try again.' );
	}

	exit;
}

/**
 * Callback function registered with add_options_page().
 * Prints the requested page (options or revisions).
 */
function humanstxt_options(): void {

	// show revisions page and are they activated?
	if ( isset( $_GET['subpage'] ) && 'revisions' === $_GET['subpage'] && humanstxt_revisions() !== false ) {
		humanstxt_revisions_page();
	} else {
		humanstxt_options_page();
	}
}

/**
 * Prints the plugin options page.
 */
function humanstxt_options_page(): void {
	?>
	<div id="humanstxt" class="wrap">

		<h1><?php _e( 'Humans TXT', 'humanstxt' ); ?></h1>

		<?php $faq_link = sprintf( '<a href="%s">%s</a>', 'http://wordpress.org/extend/plugins/humanstxt/faq/', __( 'Please read the FAQ...', 'humanstxt' ) ); ?>

		<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
			<div class="updated">
				<p><strong><?php _e( 'Settings saved.' ); ?></strong></p>
			</div>
		<?php elseif ( isset( $_GET['revision-restored'] ) ) : ?>
			<div class="updated">
				<p><strong><?php _e( 'Revision restored.', 'humanstxt' ); ?></strong></p>
			</div>
		<?php elseif ( isset( $_GET['file-imported'] ) ) : ?>
			<div class="updated">
				<p><strong><?php _e( 'Import successful. A backup of the original file has been created.', 'humanstxt' ); ?></strong></p>
			</div>
		<?php elseif ( isset( $_GET['rename-failed'] ) ) : ?>
			<div class="error">
				<p><strong><?php _e( 'Error: The content has been imported, but the original file could not be renamed.', 'humanstxt' ); ?></strong> <?php echo $faq_link; ?></p>
			</div>
		<?php elseif ( isset( $_GET['import-failed'] ) ) : ?>
			<div class="error">
				<p><strong><?php _e( 'Error: Import failed.', 'humanstxt' ); ?></strong> <?php echo $faq_link; ?></p>
			</div>
		<?php endif; ?>

		<?php if ( humanstxt_exists() && ! isset( $_GET['rename-failed'], $_GET['import-failed'] ) ) : ?>
			<div class="error">
				<p>
					<strong><?php _e( 'Error: The site root already contains a physical humans.txt file.', 'humanstxt' ); ?></strong>
					<?php echo $faq_link; ?>
					<?php
					if ( current_user_can( 'edit_files' ) ) {
						printf( /* translators: Please read the FAQ... or try to ... */__( 'or try to <a href="%s">import and rename</a> the physical humans.txt file.', 'humanstxt' ), wp_nonce_url( add_query_arg( array( 'action' => 'import-file' ), HUMANSTXT_OPTIONS_URL ), 'import-humanstxt-file' ) );
					}
					?>
				</p>
			</div>
		<?php elseif ( get_option( 'permalink_structure' ) === '' && current_user_can( 'manage_options' ) ) : ?>
			<div class="error">
				<p><strong><?php printf( __( 'Error: Please <a href="%s">update your permalink structure</a> to something other than the default.', 'humanstxt' ), admin_url( 'options-permalink.php' ) ); ?></strong> <?php echo $faq_link; ?></p>
			</div>
		<?php endif; ?>

		<form method="post" action="<?php echo HUMANSTXT_OPTIONS_URL; ?>">

			<?php settings_fields( 'humanstxt' ); ?>

			<?php if ( current_user_can( 'manage_options' ) ) : ?>

				<h3><?php _e( 'Settings' ); ?></h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( 'Humans TXT File', 'humanstxt' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php _e( 'Humans TXT File', 'humanstxt' ); ?></span></legend>
								<label for="humanstxt_enable">
									<input name="humanstxt_enable" type="checkbox" id="humanstxt_enable" value="1" <?php checked( humanstxt_option( 'enabled' ) ); ?> />
									<?php _e( 'Activate humans.txt file', 'humanstxt' ); ?>
								</label>
								<br />
								<label for="humanstxt_author_tag" title="<?php esc_attr_e( 'Adds an <link rel="author"> tag to the site\'s <head> tag pointing to the humans.txt file.', 'humanstxt' ); ?>">
									<input name="humanstxt_author_tag" type="checkbox" id="humanstxt_author_tag" value="1" <?php checked( humanstxt_option( 'author_tag' ) ); ?> />
									<?php _e( 'Add an author link tag to the site', 'humanstxt' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Editing Permissions', 'humanstxt' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php _e( 'Editing Permissions', 'humanstxt' ); ?></span></legend>
								<p><?php _e( 'Roles that can edit the content of the humans.txt file', 'humanstxt' ); ?>:</p>
								<?php
								$humanstxt_roles = is_array( humanstxt_option( 'roles' ) ) ? humanstxt_option( 'roles' ) : array();
								$wordpress_roles = get_editable_roles();
								unset( $wordpress_roles['subscriber'] );
								?>
								<?php foreach ( $wordpress_roles as $role => $details ) : ?>
									<?php $checked = ( 'administrator' === $role || in_array( $role, $humanstxt_roles, true ) ) ? 'checked="checked" ' : ''; ?>
									<?php $disabled = ( 'administrator' === $role ) ? 'disabled="disabled" ' : ''; ?>
									<label for="humanstxt_role_<?php echo $role; ?>">
										<input name="humanstxt_roles[<?php echo $role; ?>]" type="checkbox" id="humanstxt_role_<?php echo $role; ?>" value="1" <?php echo $checked; ?><?php echo $disabled; ?> />
										<?php
										$name = filter_var( $details['name'], FILTER_UNSAFE_RAW );
										$name = false === $name ? '' : $name;
										echo translate_user_role( $name );
										?>
									</label>
									<br />
								<?php endforeach; ?>
							</fieldset>
						</td>
					</tr>
				</table>

				<p class="submit clear">
					<input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
					<?php if ( humanstxt_option( 'enabled' ) !== null ) : ?>
						<a href="<?php echo home_url( 'humans.txt' ); ?>" rel="external" class="button"><?php _e( 'View Humans TXT', 'humanstxt' ); ?></a>
					<?php endif; ?>
				</p>

			<?php endif; ?>

			<h3><?php _e( 'Humans TXT File', 'humanstxt' ); ?></h3>

			<div id="humanstxt-editor-wrap">
				<table class="form-table">
					<tr valign="top">
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php _e( 'Humans TXT File', 'humanstxt' ); ?></span></legend>
								<span class="description"><label for="humanstxt_content"><?php _e( 'If you need a little help with your humans.txt, try the "Help" button at the top right of this page.', 'humanstxt' ); ?></label></span>
								<textarea name="humanstxt_content" rows="25" cols="80" id="humanstxt_content" class="large-text code"><?php echo esc_textarea( humanstxt_content() ); ?></textarea>
							</fieldset>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Save' ); ?>" />
					<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=humanstxt-preview' ) ); ?>" class="button button-preview hide-if-no-js" title="<?php _e( 'Preview' ); ?>"><?php _e( 'Preview' ); ?></a>
					<?php $revisions = humanstxt_revisions(); ?>
					<?php if ( is_array( $revisions ) && count( $revisions ) > 1 ) : ?>
						<a href="<?php echo esc_url( HUMANSTXT_REVISIONS_URL ); ?>" class="button"><?php _e( 'View Revisions', 'humanstxt' ); ?></a>
					<?php endif; ?>
				</p>
			</div>

			<?php
			$group_names     = array(
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
			?>
			<?php if ( 0 !== count( $variable_groups ) ) : ?>
				<div id="humanstxt-vars">
					<h4><?php _e( 'Variables', 'humanstxt' ); ?></h4>
					<ul>
						<?php foreach ( $variable_groups as $group => $variables ) : ?>
							<li>
								<h5><?php echo $group_names[ $group ]; ?></h5>
								<ul class="hidden">
									<?php foreach ( $variables as $variable ) : ?>
										<?php
										/** @var string $preview */
										$preview = ( ! isset( $variable[5] ) || '' === $variable[5] ) && is_callable( $variable[3] ) ? call_user_func( $variable[3] ) : /* translators: Preview: Not available... */ __( 'Not available...', 'humanstxt' );
										?>
										<li title="<?php echo esc_attr( sprintf( /* translators: %s: output preview of variable */__( 'Preview: %s', 'humanstxt' ), $preview ) ); ?>">
											<code>$<?php echo $variable[2]; ?>$</code>
											<?php if ( isset( $variable[4] ) && '' !== $variable[4] ) : ?>
												&mdash; <?php echo $variable[4]; ?>
											<?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ul>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</form>
	</div>
	<?php
}

/**
 * Prints the plugin options revisions page.
 */
function humanstxt_revisions_page(): void {
	?>
	<div id="humanstxt-revisions" class="wrap">

		<h1><?php _e( 'Humans TXT', 'humanstxt' ); ?>: <?php _e( 'Revisions' ); ?></h1>

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
			<pre id="revision-preview" class="postbox"><?php echo esc_html( strval( $revisions[ $show_revision ]['content'] ) ); ?></pre>
			<p class="submit"><a href="
			<?php
			echo wp_nonce_url(
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
		" class="button-primary"><?php _e( 'Restore Revision', 'humanstxt' ); ?></a></p>

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
									printf( __( 'Older: %s' ), date_i18n( _x( 'j F, Y @ G:i:s', 'revision date format' ), intval( $revisions[ $left ]['date'] ) ) );
								?>
							</span>
							<span class="alignright">
								<?php
									printf( __( 'Newer: %s' ), date_i18n( _x( 'j F, Y @ G:i:s', 'revision date format' ), intval( $revisions[ $right ]['date'] ) ) );
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

				<br class="clear" />

			<?php endif; ?>

		<?php endif; ?>

		<h3><?php esc_attr_e( 'Revisions' ); ?></h3>

		<form action="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>" method="get">

			<div class="tablenav">
				<div class="alignleft">
					<input type="submit" class="button-secondary" value="<?php esc_attr_e( 'Compare Revisions' ); ?>" />
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
						$left  = ( isset( $_GET['left'] ) && isset( $revisions[ $_GET['left'] ] ) ) ? filter_input( INPUT_GET, 'left', FILTER_VALIDATE_INT ) : ( ( false === $show_revision ) ? $live_revision - 1 : $show_revision );
						$right = ( isset( $_GET['right'] ) && isset( $revisions[ $_GET['right'] ] ) ) ? filter_input( INPUT_GET, 'right', FILTER_VALIDATE_INT ) : $live_revision;
						?>
						<tr<?php echo ( $key === $show_revision ) ? ' class="displayed-revision"' : ''; ?>>
							<th scope="row"><input type="radio" name="left" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key === $left ); ?> /></th>
							<th scope="row"><input type="radio" name="right" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key === $right ); ?> /></th>
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
