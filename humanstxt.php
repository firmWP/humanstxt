<?php
/*
Plugin Name: Humans TXT
Plugin URI: http://wordpress.org/plugins/humanstxt/
Description: Credit the people behind your website in your <strong>humans.txt</strong> file. Easy to edit, directly within WordPress.
Text Domain: humanstxt
Domain Path: /languages
Version: 1.3.1
Author: Till Krüss
Author URI: http://till.kruss.me/
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Humans TXT plugin version.
 *
 * @since 1.0.1
 */
define('HUMANSTXT_VERSION', '1.3.1');

/**
 * Required WordPress version for this plugin.
 *
 * @since 1.1.0
 */
define('HUMANSTXT_VERSION_REQUIRED', '6.9');

/**
 * Absolute path to the main Humans TXT plugin file.
 */
define('HUMANSTXT_PLUGIN_FILE', __FILE__);

/**
 * Absolute path to the Humans TXT plugin directory.
 */
define('HUMANSTXT_PLUGIN_PATH', dirname(HUMANSTXT_PLUGIN_FILE));

/**
 * Default amount of stored revisions.
 * Use the 'humanstxt_max_revisions' filter to change it.
 *
 * @since 1.1.0
 */
define('HUMANSTXT_MAX_REVISIONS', 50);

/**
 * Default Humans TXT plugin settings.
 *
 * @global array $humanstxt_defaults Default plugin settings.
 */
$humanstxt_defaults = array(
	'enabled'   => false,
	'authortag' => false,
	'roles'     => array(),
);

/**
 * Register plugin actions, filters and shortcode.
 */
add_action('init', 'humanstxt_init');
add_action('template_redirect', 'humanstxt_template_redirect', 8);
add_action('do_humans', 'humanstxt_do_humans');
add_filter('humans_txt', 'humanstxt_replace_variables');
add_filter('humanstxt_content', 'humanstxt_content_normalize');
add_shortcode('humanstxt', '_humanstxt_shortcode');

/**
 * Load plugin code for WordPress backend, if needed.
 */
if (is_admin()) {
	require_once HUMANSTXT_PLUGIN_PATH . '/options.php';
}

/**
 * Echos the content of the virtual humans.txt file.
 *
 * @since 1.0.4
 */
function humanstxt(): void
{
	print get_humanstxt();
}

/**
 * Returns the content of the virtual humans.txt file,
 * after applying the 'humans_txt' filter to it.
 *
 * @since 1.0.4
 *
 * @return string Content of the virtual humans.txt file
 */
function get_humanstxt(): string
{
	$txt = apply_filters('humans_txt', humanstxt_content());
	$txt = filter_var($txt, FILTER_UNSAFE_RAW);
	$txt = false !== $txt ? $txt : '';
	return $txt;
}

/**
 * Echos a XHTML-conform author link tag.
 */
function humanstxt_authortag(): void
{
	print get_humanstxt_authortag();
}

/**
 * Returns a XHTML-conform author link tag, pointed to
 * the humans.txt URL.
 *
 * @since 1.0.4
 *
 * @return string XHTML-conform author link tag
 */
function get_humanstxt_authortag(): string
{
	$htmlTag = sprintf('<link rel="author" type="text/plain" href="%s" />', home_url('humans.txt'));
	$htmlTag .= PHP_EOL;
	$authortag = filter_var($htmlTag, FILTER_UNSAFE_RAW);
	return $authortag;
}

/**
 * Determines if it is a request for the humans.txt file.
 *
 * @return bool
 */
function is_humans(): bool
{
	return (bool) get_query_var('humans');
}

/**
 * Determines if there is a physical humans.txt file in WP's root folder.
 *
 * @return bool
 */
function humanstxt_exists(): bool
{
	return file_exists(ABSPATH . 'humans.txt');
}

/**
 * Callback function for 'init' action.
 * Registers humans.txt rewrite rules and calls flush_rewrite_rules()
 * if necessary (performance friendly of course).
 *
 * @global $wp_rewrite
 */
function humanstxt_init(): void
{
	/** @var WP_Rewrite $wp_rewrite */
	global $wp_rewrite;

	$rewrite_rules = is_array(get_option('rewrite_rules')) ? get_option('rewrite_rules') : array();

	if (humanstxt_option('enabled')) {
		add_filter('query_vars', 'humanstxt_query_vars');
		add_rewrite_rule('humans\.txt$', $wp_rewrite->index . '?humans=1', 'top');

		// register author link tag action if enabled
		if (humanstxt_option('authortag')) {
			add_action('wp_head', 'humanstxt_authortag', 1);
		}

		// flush rewrite rules if ours is missing
		if (! array_key_exists('humans\.txt$', $rewrite_rules)) {
			flush_rewrite_rules(false);
		}
	} else {

		// flush rewrite rules if ours shouldn't be there
		if (array_key_exists('humans\.txt$', $rewrite_rules)) {
			flush_rewrite_rules(false);
		}
	}
}

/**
 *
 *
 * @param array<string> $qv
 * @return array<string>
 */
function humanstxt_query_vars(array $qv): array
{
	$qv[] = 'humans';

	return $qv;
}

/**
 * Callback function for 'template_redirect' action.
 * Calls 'do_humans' action if is_humans() is positive.
 */
function humanstxt_template_redirect(): void
{
	if (is_humans()) {
		do_action('do_humans');
		exit;
	}
}

/**
 * Callback function for 'do_humans' action.
 * Calls 'do_humanstxt' action and echos get_humanstxt().
 */
function humanstxt_do_humans(): void
{
	header('Content-Type: text/plain; charset=utf-8');
	do_action('do_humanstxt');

	print get_humanstxt();
}

/**
 * @param array<string> $matches
 * @return string
 */
function _humanstxt_antispambot_function(array $matches): string
{
	return antispambot($matches[0]);
}

/**
 * Loads the plugin text-domain, if not already loaded.
 */
function humanstxt_load_textdomain(): void
{
	if (! is_textdomain_loaded('humanstxt')) {
		load_plugin_textdomain('humanstxt', false, 'humanstxt/languages');
	}
}

/**
 * Loads plugin options from database and sets missing
 * options to their default values.
 *
 * @since 1.0.5
 *
 * @global $humanstxt_options
 * @global $humanstxt_defaults
 */
function humanstxt_load_options(): void
{
	global $humanstxt_options;
	/** @var array<int,string> $humanstxt_defaults */
	global $humanstxt_defaults;

	// already loaded?
	if (is_null($humanstxt_options)) {
		$humanstxt_options = get_option('humanstxt_options') !== false ? get_option('humanstxt_options') : array();
		$humanstxt_options = is_array($humanstxt_options) ? $humanstxt_options : array();
		// populate with defaults options if missing...
		foreach ($humanstxt_defaults as $option => $value) {
			if (array_key_exists($option, $humanstxt_options) === false) {
				$humanstxt_options[$option] = $value;
			}
		}
	}
}

/**
 * Returns options value of given $option.
 * Returns NULL if $option doesn't exist.
 *
 * @global $humanstxt_options
 *
 * @param string $option Name of the option.
 * @return mixed|null Plugin option value
 */
function humanstxt_option(string $option): mixed
{
	/** @var array<string> $humanstxt_options */
	global $humanstxt_options;

	humanstxt_load_options();
	return isset($humanstxt_options[$option]) ? $humanstxt_options[$option] : null;
}

/**
 * Returns stored humans.txt file content.
 * Stores and returns default humans.txt content, if not stored yet.
 *
 * @return string $content
 */
function humanstxt_content(): string
{
	$content = get_option('humanstxt_content');

	// add option if missing
	if ($content === false) {
		$content = humanstxt_default_content();
		add_option('humanstxt_content', $content, '', false);
	}

	$content = apply_filters('humanstxt_content', $content);
	$content = filter_var($content, FILTER_UNSAFE_RAW);
	$content = false !== $content ? $content : '';
	return $content;
}

/**
 * Normalizes the line endings of the given $string.
 *
 * @since 1.1.0
 *
 * @param string $string String to be normalized
 * @return string Normalized string
 */
function humanstxt_content_normalize(string $string): string
{
	$string = str_replace("\r\n", "\n", $string);
	$string = str_replace("\r", "\n", $string);
	return $string;
}

/**
 * Returns an array with all stored revisions, containing each
 * revions's content, author-id and it's time of creation.
 * Returns FALSE if revisions are disabled.
 *
 * @since 1.1.0
 *
 * @return list<array{date: int, user: int, content: string}> Revisions of the humans.txt file
 */
function humanstxt_revisions(): array|false
{

	$revision_amount = apply_filters('humanstxt_max_revisions', HUMANSTXT_MAX_REVISIONS);
	$revision_amount = filter_var($revision_amount, FILTER_VALIDATE_INT);
	$revision_amount = false !== $revision_amount ? $revision_amount : HUMANSTXT_MAX_REVISIONS;

	// are revisions disabled?
	if ($revision_amount < 1) {
		return false;
	}

	$revisions = get_option('humanstxt_revisions');

	if (is_array($revisions)) {
		$result = array();
		foreach ($revisions as $key => $revision) {
			if (false !== is_array($revision)) {
				$date = filter_var($revision['date'], FILTER_VALIDATE_INT);
				$date = false !== $date ? $date : current_time('timestamp');
				$user = filter_var($revision['user'], FILTER_VALIDATE_INT);
				$user = false !== $user ? $user : 0;
				$content = filter_var($revision['content'], FILTER_DEFAULT);
				$content = false !== $content ? $content : '';
				$result[] = array(
					'date' => $date,
					'user' => $user,
					'content' => $content,
				);
			}
		}
		return $result;
	}

	$revisions = array(
		array(
			'date'    => current_time('timestamp'),
			'user'    => 0,
			'content' => humanstxt_content(),
		),
	);
	add_option('humanstxt_revisions', $revisions, '', false);

	return $revisions;
}

/**
 * Stores a new revision with the given $content.
 * Ensures that only the last 50 revisons are stored.
 * Limit can be changed with the 'humanstxt_max_revisions' filter.
 *
 * @since 1.1.0
 *
 * @param string $content Revisions content
 */
function humanstxt_add_revision(string $content): void
{
	$current_user = wp_get_current_user();
	$revisions    = humanstxt_revisions();

	if (! is_array($revisions)) {
		$revisions = array();
	}

	$revisions[] = array(
		'date'    => current_time('timestamp'),
		'user'    => $current_user->ID,
		'content' => $content,
	);

	// limit amount of revisions
	$keys       = array_slice(array_keys($revisions), -abs(HUMANSTXT_MAX_REVISIONS), count($revisions));
	$_revisions = array();
	foreach ($keys as $key) {
		$_revisions[$key] = $revisions[$key];
	}

	update_option('humanstxt_revisions', $_revisions);
}

/**
 * Replaces all valid content-variables in given string and returns it.
 *
 * @param string $string String in which content-variables should be replaced.
 * @return string Given string with replaced content-variables.
 */
function humanstxt_replace_variables(string $string): string
{
	$variables = humanstxt_valid_variables();

	foreach ($variables as $variable) {

		$tag = $variable[1];
		$localized_tag = $variable[2];
		$callback = $variable[3];

		// 1 = english; 2 = translated varname
		$varnames = array('$' . $tag . '$', '$' . $localized_tag . '$');

		// does one of the variables occur in the string?
		if (stripos($string, $tag) !== false || stripos($string, $localized_tag) !== false) {
			if (! is_callable($callback)) {
				continue;
			}
			$result = call_user_func($callback);
			$result = filter_var($result, FILTER_UNSAFE_RAW);
			$result = false !== $result ? $result : '';
			// replace all occurrences of the variables with callback result
			$string = str_ireplace($varnames, $result, $string);
		}
	}

	return $string;
}

/**
 * Returns an array of default content-variables after
 * applying the 'humanstxt_variables' filter to it.
 *
 * Each array value represents a content-variable:
 * array(string $group, string $varname, string $translated-varname,
 *   callback $function [, string $description]);
 *
 * @return array<int,array<string>> $variables Default content-variables.
 */
function humanstxt_variables(): array
{
	humanstxt_load_textdomain();
	require_once HUMANSTXT_PLUGIN_PATH . '/callbacks.php';

	$variables   = array();
	$variables[] = array('wordpress', 'wp-title', /* translators: variable name for the site/blog name (title) */ __('wp-title', 'humanstxt'), 'humanstxt_callback_wpblogname', __('Name (title) of site/blog', 'humanstxt'));
	$variables[] = array('wordpress', 'wp-tagline', /* translators: variable name for the site/blog tagline (description) */ __('wp-tagline', 'humanstxt'), 'humanstxt_callback_wptagline', __('Tagline (description) of site/blog', 'humanstxt'));
	$variables[] = array('wordpress', 'wp-posts', /* translators: variable name for the number of published posts */ __('wp-posts', 'humanstxt'), 'humanstxt_callback_wpposts', __('Number of published posts', 'humanstxt'));
	$variables[] = array('wordpress', 'wp-pages', /* translators: variable name for the number of published pages */ __('wp-pages', 'humanstxt'), 'humanstxt_callback_wppages', __('Number of published pages', 'humanstxt'));
	$variables[] = array('wordpress', 'wp-authors', /* translators: variable name for the author list */ __('wp-authors', 'humanstxt'), 'humanstxt_callback_wpauthors', __('Active authors and their contact details', 'humanstxt'));
	$variables[] = array('wordpress', 'wp-lastupdate', /* translators: variable name for the "last modified" timestamp */ __('wp-lastupdate', 'humanstxt'), 'humanstxt_callback_lastupdate', __('Date of last modified post/page', 'humanstxt'));
	$variables[] = array('wordpress', 'wp-language', /* translators: variable name for WordPress languages(s) */ __('wp-language', 'humanstxt'), 'humanstxt_callback_wplanguage', __('WordPress language(s)', 'humanstxt'));
	$variables[] = array('wordpress', 'wp-timezone', /* translators: variable name for WordPress timezone */ __('wp-timezone', 'humanstxt'), 'humanstxt_callback_wptimezone', __('WordPress timezone', 'humanstxt'));
	$variables[] = array('wordpress', 'wp-version', /* translators: variable name for the installed WordPress version */ __('wp-version', 'humanstxt'), 'humanstxt_callback_wpversion', __('Installed WordPress version', 'humanstxt'));
	$variables[] = array('wordpress', 'wp-charset', /* translators: variable name for the encoding (charset) used by WordPress */ __('wp-charset', 'humanstxt'), 'humanstxt_callback_wpcharset', __('Encoding used for pages and feeds', 'humanstxt'));

	$variables[] = array('server', 'server-timezone', /* translators: variable name for server timezone */ __('server-timezone', 'humanstxt'), 'humanstxt_callback_timezone', __('Server timezone', 'humanstxt'));
	$variables[] = array('server', 'server-ip', /* translators: variable name for server ip address */ __('server-ip', 'humanstxt'), 'humanstxt_callback_ip', __('Server IP address', 'humanstxt'));
	$variables[] = array('server', 'server-os', /* translators: variable name for operating system name */ __('server-os', 'humanstxt'), 'humanstxt_callback_os', __('Operating system name', 'humanstxt'));
	$variables[] = array('server', 'server-identity', /* translators: variable name server identification string  */ __('server-identity', 'humanstxt'), 'humanstxt_callback_server', __('Server identification', 'humanstxt'));
	$variables[] = array('server', 'php-version', /* translators: variable name for php parser version */ __('php-version', 'humanstxt'), 'humanstxt_callback_phpversion', __('PHP parser version', 'humanstxt'));
	$variables[] = array('server', 'zend-version', /* translators: variable name for zend engine version */ __('zend-version', 'humanstxt'), 'humanstxt_callback_zendversion', __('Zend Engine version', 'humanstxt'));
	$variables[] = array('server', 'mysql-version', /* translators: variable name for MySQL server version */ __('mysql-version', 'humanstxt'), 'humanstxt_callback_mysqlversion', __('MySQL server version', 'humanstxt'));

	$variables[] = array('addons', 'wp-plugins', /* translators: variable name for activated WordPress plugins */ __('wp-plugins', 'humanstxt'), 'humanstxt_callback_wpplugins', __('Activated WordPress plugins', 'humanstxt'));
	$variables[] = array('addons', 'wp-theme', /* translators: variable name for the summary of the active WordPress theme */ __('wp-theme', 'humanstxt'), 'humanstxt_callback_wptheme', __('Summary of the active WordPress theme', 'humanstxt'));
	$variables[] = array('addons', 'wp-theme-name', /* translators: variable name for the name of the active WordPress theme */ __('wp-theme-name', 'humanstxt'), 'humanstxt_callback_wptheme_name', __('Name of the active theme', 'humanstxt'));
	$variables[] = array('addons', 'wp-theme-version', /* translators: variable name for the version of the active WordPress theme */ __('wp-theme-version', 'humanstxt'), 'humanstxt_callback_wptheme_version', __('Version of the active theme', 'humanstxt'));
	$variables[] = array('addons', 'wp-theme-author', /* translators: variable name for the author name of the active WordPress theme */ __('wp-theme-author', 'humanstxt'), 'humanstxt_callback_wptheme_author', __('Author name of the active theme', 'humanstxt'));
	$variables[] = array('addons', 'wp-theme-author-link', /* translators: variable name for the author link of the active WordPress theme */ __('wp-theme-author-link', 'humanstxt'), 'humanstxt_callback_wptheme_author_link', __('Author link of the active theme', 'humanstxt'));

	// $variables = apply_filters('humanstxt_variables', $variables);
	// $variables = filter_var_array( $variables, FILTER_REQUIRE_ARRAY );
	// $variables = is_array( $variables ) ? $variables : array(array());
	return $variables;
}

/**
 * Returns an array all valid content-variables.
 *
 * @return array<int,array<string>> $variables Valid content-variables.
 */
function humanstxt_valid_variables(): array
{
	$variables = humanstxt_variables();

	foreach ($variables as $key => $variable) {
		// delete if variable hasn't enough params
		if (count($variable) < 5) {
			unset($variables[$key]);
			continue;
		}
		// delete if variable callback is not a function
		if (! function_exists($variable[3])) {
			unset($variables[$key]);
			continue;
		}
	}

	return $variables;
}

/**
 * Returns default content of humans.txt file. Quite ugly function,
 * but fast, since we have a translated string *without* having to
 * load the plugin text-domain on every request.
 *
 * @return string Default humans.txt file content.
 */
function humanstxt_default_content(): string
{
	humanstxt_load_textdomain();

	/* translators: only translate the text inside angle brackets < > to keep the humans.txt international. if the variable names are translated, you may translate them here too. */
	return humanstxt_content_normalize(
		__(
			'/* the humans responsible & colophon */
/* humanstxt.org */

/* TEAM */
	<your title>: <your name>
	Site: <website url>
	X: <@username>
	Location: <city, country>

		[...]

/* THANKS */
	<name>: <link, email, @x_handle, ...>

		[...]

/* SITE */
	Last update: $wp-lastupdate$
	Standards: <HTML5, CSS3, ...>
	CMS: WordPress $wp-version$ (running PHP $php-version$)
	Language: <English, Klingon, ...>
	Components: <jQuery, Typekit, Modernizr, ...>
	IDE: <Coda, Zend Studio, Photoshop, Terminal, ...>
',
			'humanstxt'
		)
	);
}
