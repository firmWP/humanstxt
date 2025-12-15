=== Humans TXT ===
Original Author: Till Krüss
Original Author URI: http://till.kruss.me/
Maintainers: Daniel Șerbănescu
Tags: Humans TXT, HumansTXT, humans.txt, human, humans, author, authors, contributor, contributors, credit, credits, robot, robots, robots.txt
Requires at least: 6.9
Tested up to: 6.9
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Credit the people behind your website in your humans.txt file. Easy to edit, directly within WordPress.


== Description ==

Credit the people behind your website in your **humans.txt** file. Easy to edit, directly within WordPress.

* Use **variables** like a _last-updated_ date, active plugins and [many others...](http://wordpress.org/extend/plugins/humanstxt/other_notes/#Variables)
* Use the `[humanstxt]` shortcode to display your _humans.txt_ on your site
* Add an author link tag to your site's `<head>` tag
* Allow non-admins to edit the _humans.txt_
* Customize everything with custom [filters, actions and pluggable functions](http://wordpress.org/extend/plugins/humanstxt/other_notes/#Plugin-Actions-and-Filters)
* Restore previously saved revisions of your _humans.txt_

More information on the Humans TXT can be found on the [official Humans TXT website](http://humanstxt.org/).

== Installation ==

For detailed installation instructions, please read the [standard installation procedure for WordPress plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

1. Upload the `/humanstxt/` directory and its contents to `/wp-content/plugins/`.
2. Login to your WordPress installation and activate the plugin through the _Plugins_ menu.
3. Activate the plugin and edit your humans.txt file in the _Settings_ menu under _Humans TXT_.

**Please note:** This plugin does not modify or create a physical `humans.txt` file on your server, it serves it dynamically. If your site root contains a physical `humans.txt` file, this file will be shown to the visitor and the one from this plugin will be ignored. To use this plugin, please delete your physical `humans.txt` (but don't forget to migrate/backup it's contents).


== Frequently Asked Questions ==

= Error: The site root already contains a physical humans.txt file. =

If your site root contains a physical `humans.txt` file, this physical file will be shown to the visitor and the one from this plugin will be ignored. To use this plugin, please delete the physical `humans.txt` file on your server (but don't forget to migrate/backup it's contents).

= Error: Please update your permalink structure to something other than the default. =

The plugin will only work, if WordPress is using "Pretty Permalinks". You can activate them in WordPress in the _Settings_ menu under _Permalinks_. Read more about [using permalinks](http://codex.wordpress.org/Using_Permalinks).

= Error: The content has been imported, but the original file could not be renamed. =

The content of the physical `humans.txt` file on your server has been imported, however the original file could not be renamed/moved. To use this plugin, please delete the physical `humans.txt` file on your server (but don't forget to migrate/backup it's contents).

= Error: Import failed. =

The physical `humans.txt` file on your server could not be imported and renamed. To use this plugin, please delete the physical `humans.txt` file on your server (but don't forget to migrate/backup it's contents).

= Why isn't the humans.txt file on my server modified? =

This plugin does not modify or create a physical `humans.txt` file on your server, it serves it dynamically. If your site root contains a physical `humans.txt` file, this file will be shown to the visitor and the one from this plugin will be ignored. To use this plugin, please delete the physical `humans.txt` file on your server (but don't forget to migrate/backup it's contents).

= Where is the humans.txt file located? =

Theoretically in the root of your site, **however** this plugin doesn't create a physical `humans.txt` file on your server, it serves it on the fly.


== Screenshots ==

1. Plugin options page.
2. Plugin revisions page.
3. Shortcode result using `pre` attribute. (Theme: Twenty Eleven)
4. Default shortcode result. (Theme: Twenty Eleven)

== Variables ==

* `$wp-title$` - Name (title) of site/blog
* `$wp-tagline$` - Tagline (description) of site/blog
* `$wp-posts$` - Number of published posts
* `$wp-pages$` - Number of published pages
* `$wp-last-update$` - Date of last modified post or page
* `$wp-authors$` - Active authors and their contact details
* `$wp-language$` - WordPress language(s)
* `$wp-plugins$` - Activated WordPress plugins
* `$wp-charset$` - Encoding used for pages and feeds
* `$wp-version$` - Installed WordPress version
* `$php-version$` - Running PHP parser version
* `$wp-theme$` - Summary of the active WordPress theme
* `$wp-theme-name$` - Name of the active theme
* `$wp-theme-version$` - Version number of the active theme
* `$wp-theme-author$` - Author name of the active theme
* `$wp-theme-author-link$` - Author link of the active theme

== Useful Functions ==

**humanstxt()**
Echos the content of the virtual humans.txt file. Use `get_humanstxt()` to get the contents as a _string_.

**is_humans()**
Determines if the current request is for the virtual humans.txt file.


== Pluggable Functions ==

All callback functions of the default variables can be overridden. The callback functions are located in (humanstxt/callbacks.php)

== Plugin Actions and Filters ==

= Actions =

**do_humans**
Runs when the current request is for the *humans.txt* file, right after the `template_redirect` action.

**do_humanstxt**
Runs right before the *humans.txt* is printed to the screen.

= Filters =

**humans_txt**
Applied to the final content of the virtual humans.txt file.

**humans_author_tag**
Applied to the author link tag.

**humanstxt_content**
Applied to the humans.txt content. Applied prior to the `humans_txt` filter.

**humanstxt_variables**
Applied to the array of content-variables. See `humanstxt_variables()` for details.

**humanstxt_max_revisions**
Applied to the maximum number of stored revisions. If set to `0`, revisions will be disabled. Default is `50`.

**humanstxt_shortcode_headline_replacement**
Applied to replacement string for matched standard headlines: `/* Title */`. See `humanstxt_shortcode()` for details.

**humanstxt_separator**
Applied to the global text separator. Default is a comma followed by a space.

**humanstxt_plugins_separator**
Use to override the global text separator (see `humanstxt_separator` filter) for the list of active WordPress plugins.

**humanstxt_languages_separator**
Use to override the global text separator (see `humanstxt_separator` filter), for the current WordPress language(s).

**humanstxt_postcount**
Applied to the number of published posts: `$wp-posts$`.

**humanstxt_pagecount**
Applied to the number of published pages: `$wp-pages$`.

**humanstxt_wp_theme**
Applied to the summary of the active WordPress theme: `$wp-theme$`.

**humanstxt_plugins**
Applied to the list of active WordPress plugins: `$wp-plugins$`.

**humanstxt_languages**
Applied to current WordPress language(s): `$wp-language$`.

**humanstxt_last_update**
Applied to returned date of the `$wp-last-update$` variable.

**humanstxt_last_update_format**
Applied to the used date-format of the `$wp-last-update$` variable. Default is `Y/m/d`. Read more about [date and time formatting](http://codex.wordpress.org/Formatting_Date_and_Time).

**humanstxt_authors**
Applied to the list of active authors: `$wp-authors$`.

**humanstxt_authors_format**
Applied to the format used for the author list `$wp-authors$` variable. Please see `humanstxt_callback_wp_authors()` in [humanstxt/callbacks.php](http://plugins.trac.wordpress.org/browser/humanstxt/trunk/callbacks.php) for details.
