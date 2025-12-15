<?php

/**
 * Callback functions.
 *
 * @package Humanstxt
 */

if (! function_exists('humanstxt_callback_ip')) :
	/**
	 * Returns the IP address of the server under which
	 * the current script is executing.
	 *
	 * @since 1.2
	 *
	 * @return string Value of $_SERVER['SERVER_ADDR'], or NULL
	 */
	function humanstxt_callback_ip(): string
	{
		$hostname = gethostname();
		return gethostbyname(strval($hostname));
	}
endif;

if (! function_exists('humanstxt_callback_os')) :
	/**
	 * Returns the server's operating system name.
	 *
	 * @since 1.2
	 *
	 * @return string Value of php_uname('s')
	 */
	function humanstxt_callback_os(): string
	{
		// PHP_OS.
		return php_uname('s');
	}
endif;

if (! function_exists('humanstxt_callback_server')) :
	/**
	 * Returns the server identification string,
	 * given in the headers when responding to requests.
	 * E.g.: Apache/2.2.17 (Unix) mod_ssl/2.2.17 DAV/2 PHP/5.3.6
	 *
	 * @since 1.2
	 *
	 * @return string Value of $_SERVER['SERVER_SOFTWARE']
	 */
	function humanstxt_callback_server(): ?string
	{
		return isset($_SERVER['SERVER_SOFTWARE']) ? filter_var($_SERVER['SERVER_SOFTWARE'], FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE) : null;
	}
endif;

if (! function_exists('humanstxt_callback_phpversion')) :
	/**
	 * Returns the server's PHP version.
	 *
	 * @return string Value of phpversion()
	 */
	function humanstxt_callback_phpversion()
	{
		return phpversion();
	}
endif;

if (! function_exists('humanstxt_callback_zendversion')) :
	/**
	 * Returns the PHP's Zend engine version.
	 *
	 * @since 1.2
	 *
	 * @return string Value of zend_version()
	 */
	function humanstxt_callback_zendversion()
	{
		return zend_version();
	}
endif;

if (! function_exists('humanstxt_callback_mysqlversion')) :
	/**
	 * Returns the server's MySQL version.
	 *
	 * @since 1.2
	 *
	 * @return string MySQL version.
	 */
	function humanstxt_callback_mysqlversion(): ?string
	{
		global $wpdb;
		return ($wpdb instanceof wpdb) ? $wpdb->db_version() : 'unknown';
	}
endif;

if (! function_exists('humanstxt_callback_timezone')) :
	/**
	 * Returns the server's timezone, as user-friendly as possible.
	 * Something like: "US/Central (-05:00)", "Asia/Bangkok (+07:00)"
	 * or "+01:00".
	 *
	 * @since 1.2
	 *
	 * @return string Server timezone.
	 */
	function humanstxt_callback_timezone()
	{
		$offset   = intval(wp_date('Z'));
		$offset   = sprintf('%s%02d:%02d', ($offset < 0 ? '-' : '+'), abs($offset / 3600), abs($offset % 3600) / 60);
		$timezone = function_exists('date_default_timezone_get') ? date_default_timezone_get() : null;
		return (is_null($timezone) || '' === $timezone || 'UTC' === $timezone) ? $offset : $timezone . ' (' . $offset . ')';
	}
endif;

if (! function_exists('humanstxt_callback_wpversion')) :
	/**
	 * Returns the WordPress version.
	 *
	 * @return string WordPress version.
	 */
	function humanstxt_callback_wpversion()
	{
		return get_bloginfo('version');
	}
endif;

if (! function_exists('humanstxt_callback_wpblogname')) :
	/**
	 * Returns the site/blog title.
	 *
	 * @since 1.0.5
	 *
	 * @return string Site/blog name.
	 */
	function humanstxt_callback_wpblogname()
	{
		return get_bloginfo('name');
	}
endif;

if (! function_exists('humanstxt_callback_wptagline')) :
	/**
	 * Returns the site/blog description (tagline).
	 *
	 * @since 1.0.5
	 *
	 * @return string Site/blog description.
	 */
	function humanstxt_callback_wptagline()
	{
		return get_bloginfo('description');
	}
endif;

if (! function_exists('humanstxt_callback_wpcharset')) :
	/**
	 * Returns the encoding used for pages and feeds.
	 *
	 * @since 1.0.5
	 *
	 * @return string Site/blog encoding.
	 */
	function humanstxt_callback_wpcharset()
	{
		return get_bloginfo('charset');
	}
endif;

if (! function_exists('humanstxt_callback_wptimezone')) :
	/**
	 * Returns the timezone WordPress uses, as user-friendly as possible.
	 * Something like: "US/Central (-05:00)", "Asia/Singapore (+08:00)"
	 * or "+02:00".
	 *
	 * @since 1.2
	 *
	 * @return string WordPress timezone.
	 */
	function humanstxt_callback_wptimezone()
	{
		$offset   = get_option('gmt_offset', 0);
		$timezone = get_option('timezone_string', '');
		if (is_numeric($offset)) {
			$offset = floatval($offset);
			$offset = sprintf('%s%02d:%02d', ($offset < 0 ? '-' : '+'), abs($offset), abs(($offset * 3600) % 3600) / 60);
		} else {
			$offset = '';
		}
		if (! is_string($timezone)) {
			$timezone = '';
		}
		return ('' === $timezone) ? $offset : sprintf('%s (%s)', $timezone, $offset);
	}
endif;

if (! function_exists('humanstxt_callback_wpposts')) :
	/**
	 * Returns count of posts that are published. Can be
	 * modified using the 'humanstxt_postcount' filter.
	 *
	 * @since 1.0.4
	 *
	 * @return ?string Number of published posts
	 */
	function humanstxt_callback_wpposts(): ?string
	{
		$postcounts = wp_count_posts();
		$postcounts = apply_filters('humanstxt_postcount', $postcounts->publish);
		return filter_var($postcounts, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
	}
endif;

if (! function_exists('humanstxt_callback_wppages')) :
	/**
	 * Returns count of pages that are published. Can be
	 * modified using the 'humanstxt_pagecount' filter.
	 *
	 * @since 1.0.4
	 *
	 * @return ?string Number of published pages
	 */
	function humanstxt_callback_wppages(): ?string
	{
		$pagecounts = wp_count_posts('page');
		$pagecounts = apply_filters('humanstxt_pagecount', $pagecounts->publish);
		return filter_var($pagecounts, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
	}
endif;

if (! function_exists('humanstxt_callback_wplanguage')) :
	/**
	 * Returns user-friendly language of WordPress.
	 *
	 * @return ?string Name(s) of language(s).
	 */
	function humanstxt_callback_wplanguage(): ?string
	{
		require_once ABSPATH . 'wp-admin/includes/ms.php';

		$separator = apply_filters('humanstxt_separator', ', ');
		$separator = apply_filters('humanstxt_languages_separator', $separator);

		$active_languages = format_code_lang(get_bloginfo('language'));
		$active_languages = apply_filters('humanstxt_languages', $active_languages);
		return filter_var($active_languages, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
	}
endif;

if (! function_exists('humanstxt_callback_lastupdate')) :
	/**
	 * Returns YYYY/MM/DD timestamp of the latest modified post/page which is published.
	 * The date format can be modified with the 'humanstxt_lastupdate_format' filter.
	 * The final funtion result can be modified with the 'humanstxt_lastupdate' filter.
	 *
	 * @global $wpdb
	 * @return ?string $last_edit Timestamp of last modified post/page.
	 */
	function humanstxt_callback_lastupdate(): ?string
	{
		$last_edit = get_lastpostdate('blog');
		$format = filter_var(apply_filters('humanstxt_lastupdate_format', 'Y/m/d'), FILTER_UNSAFE_RAW);
		$format = false !== $format ? $format : '';
		$last_edit = wp_date($format, intval(strtotime($last_edit)));
		$last_edit = apply_filters('humanstxt_lastupdate', $last_edit);
		return filter_var($last_edit, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
	}
endif;

if (! function_exists('humanstxt_callback_wpauthors')) :
	/**
	 * Returns all authors with a least 1 post.
	 *
	 * @since 1.1.0
	 *
	 * @global $wpdb
	 *
	 * @return string A list of active authors or empty string.
	 */
	function humanstxt_callback_wpauthors(): string
	{
		$authors    = '';
		$author_ids = array();
		$users      = get_users(
			array(
				'fields'   => array('ID', 'display_name', 'user_email', 'user_url'),
				'role__in' => array('author'),
			)
		);

		$author_ids = array();
		foreach ($users as $user) {
			/** @var WP_User $user */
			$author_ids[] = $user->ID;
		}

		$authors_posts	= count_many_users_posts($author_ids);
		$format					= "\t" . '%1$s: %2$s' . "\n";

		foreach ($users as $user) {
			/** @var WP_User $user */
			if (0 < $authors_posts[$user->ID]) {
				$contact  = empty($user->user_url) ? $user->user_email : $user->user_url;
				$authors .= sprintf($format, $user->display_name, $contact);
			}
		}
		return filter_var($authors, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
	}
endif;

if (! function_exists('humanstxt_callback_wpplugins')) :
	/**
	 * Returns a comma separated list of all active WordPress plugins.
	 * Uses the 'humanstxt_separator' filter which is ', ' (comma + space) by
	 * which is rewritable with the 'humanstxt_plugins_separator' filter.
	 * Final function result can be modified with the 'humanstxt_plugins' filter.
	 *
	 * @return string $active_plugins List of active WP plugins.
	 */
	function humanstxt_callback_wpplugins(): string
	{
		$plugins = '';
		$active_plugins = get_option('active_plugins', array());
		if (is_array($active_plugins) && count($active_plugins) !== 0) {
			foreach ($active_plugins as $key => $plugin_name) {
				if (is_scalar($plugin_name)) {
					$plugin_name = strval($plugin_name);
				} else {
					continue;
				}
				$plugin_data            = get_plugin_data(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_name, false);
				$active_plugins[$key] = $plugin_data['Name'];
			}
			$separator      = apply_filters('humanstxt_separator', ', ');
			$active_plugins = apply_filters('humanstxt_plugins', $active_plugins);
			$active_plugins = is_array($active_plugins) ? $active_plugins : array();

			return implode($separator, $active_plugins);
		}
		return $plugins;
	}
endif;

if (! function_exists('humanstxt_callback_wptheme')) :
	/**
	 * Returns a summary of the active WordPress theme:
	 * "Theme-Name (Version) by Author (Author-Link)"
	 * Function result can be modified with the 'humanstxt_wptheme' filter.
	 *
	 * @return string|null The theme's author name.
	 */
	function humanstxt_callback_wptheme(): ?string
	{
		$theme  = wp_get_theme();
		$output = null;
		if ($theme->errors() === false) {
			$theme_name = $theme->display('Name', false);
			$theme_name = is_string($theme_name) ? $theme_name : '';
			$name       = htmlspecialchars_decode(wp_strip_all_tags($theme_name));

			$theme_version = $theme->display('Version', false);
			$theme_version = is_string($theme_version) ? $theme_version : '';
			$version       = htmlspecialchars_decode(wp_strip_all_tags($theme_version));

			$theme_author = $theme->display('Author', false);
			$theme_author = is_string($theme_author) ? $theme_author : '';
			$author       = htmlspecialchars_decode($theme_author);

			$theme_link = $theme->display('AuthorURI', false);
			$theme_link = is_string($theme_link) ? $theme_link : '';
			$link       = htmlspecialchars_decode(wp_strip_all_tags($theme_link));

			$output = $name;
			if ('' !== $version) {
				$output .= ' (' . $version . ')';
			}
			if ('' !== $author) {
				$output .= ' by ' . $author;
			}
			if ('' !== $link) {
				$output .= ' (' . $link . ')';
			}
		}
		$output = apply_filters('humanstxt_wptheme', $output);
		return filter_var($output, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
	}
endif;

if (! function_exists('humanstxt_callback_wptheme_name')) :
	/**
	 * Returns the theme name or NULL if n/a.
	 *
	 * @return string|null The theme name.
	 */
	function humanstxt_callback_wptheme_name()
	{
		$theme      = wp_get_theme();
		$theme_name = $theme->display('Name', false);
		$theme_name = is_string($theme_name) ? $theme_name : '';
		$name       = htmlspecialchars_decode(wp_strip_all_tags($theme_name));
		return '' === $name ? null : $name;
	}
endif;

if (! function_exists('humanstxt_callback_wptheme_version')) :
	/**
	 * Returns the theme's version or NULL if n/a.
	 *
	 * @return string|null The theme's version name.
	 */
	function humanstxt_callback_wptheme_version()
	{
		$theme         = wp_get_theme();
		$theme_version = $theme->display('Version', false);
		$theme_version = is_string($theme_version) ? $theme_version : '';
		$version       = htmlspecialchars_decode(wp_strip_all_tags($theme_version));
		return '' === $version ? null : $version;
	}
endif;

if (! function_exists('humanstxt_callback_wptheme_author')) :
	/**
	 * Returns the theme's author name or NULL if n/a.
	 *
	 * @return string|null The theme's author name.
	 */
	function humanstxt_callback_wptheme_author()
	{
		$theme        = wp_get_theme();
		$theme_author = $theme->display('Author', false);
		$theme_author = is_string($theme_author) ? $theme_author : '';
		$author       = htmlspecialchars_decode(wp_strip_all_tags($theme_author));
		return '' === $author ? null : $author;
	}
endif;

if (! function_exists('humanstxt_callback_wptheme_author_link')) :
	/**
	 * Returns the theme's author link or NULL if n/a.
	 *
	 * @return string|null The theme's author URI.
	 */
	function humanstxt_callback_wptheme_author_link()
	{
		$theme      = wp_get_theme();
		$theme_link = $theme->display('AuthorURI', false);
		$theme_link = is_string($theme_link) ? $theme_link : '';
		$link       = htmlspecialchars_decode(wp_strip_all_tags($theme_link));
		return '' === $link ? null : $link;
	}
endif;
