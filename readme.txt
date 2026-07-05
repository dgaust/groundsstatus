=== Wollongong Sportsground Status ===
Contributors: dgaust
Tags: sportsground, wollongong, status, shortcode, sport
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show the current open/closed status of a Wollongong City Council sportsground with a simple shortcode.

== Description ==

Wollongong Sportsground Status displays whether a Wollongong City Council
sportsground is currently open or closed. Drop a shortcode into any post, page
or block widget area:

`[sportsground_status ground="Cawley Park"]`

The status is fetched live from Council's public sportsgrounds page and cached
for 15 minutes, so pages stay fast and Council's server isn't hammered. No
external scraper, FTP upload or hosted JSON file is required — the plugin is
fully self-contained.

= Shortcode attributes =

* `ground` (required) — the ground to show. Accepts the ground name (with or
  without the suburb, e.g. `Cawley Park` or `Cawley Park, Russell Vale`) or the
  slug from Council's URL (e.g. `cawley-park`).
* `name` — override the heading shown on the card. Defaults to Council's name.
* `show_updated` — `yes` (default) or `no`. When `yes`, shows Council's
  "Status last changed" time from the ground's detail page.
* `link` — `yes` (default) or `no`. When `yes`, links to the ground's Council page.

= Examples =

`[sportsground_status ground="cawley-park"]`
`[sportsground_status ground="Cawley Park" name="Our Home Ground" link="no"]`

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the ZIP via
   Plugins → Add New → Upload Plugin.
2. Activate the plugin through the Plugins menu in WordPress.
3. Add `[sportsground_status ground="Your Ground"]` to a post, page or a
   Shortcode block in a widget area.

== Frequently Asked Questions ==

= Where does the data come from? =

Directly from Council's public sportsgrounds page. If Council changes their page
layout the plugin may need updating.

= How often does it update? =

The grounds list and each ground's "last changed" time are cached for 15
minutes.

= How do I place it in a sidebar/widget area? =

Add a "Shortcode" block (or the legacy Text/Shortcode widget) to the widget area
and paste the shortcode.

== Changelog ==

= 2.0.0 =
* Complete rewrite for modern WordPress.
* Replaced the removed `register_sidebar_widget()` / `register_widget_control()`
  APIs with a `[sportsground_status]` shortcode.
* Fetches Council's page directly via the WordPress HTTP API and caches it in a
  transient — removes the old Python scraper, FTP upload and hosted `test.json`.
* Parses with DOMDocument/DOMXPath; all output escaped and attributes sanitised.
* Shows Council's own "Status last changed" time.

= 1.0 =
* Original widget (2014): read a JSON file produced by an external Python
  scraper and uploaded over FTP.

== Upgrade Notice ==

= 2.0.0 =
Major rewrite. The old sidebar widget is replaced by the
[sportsground_status] shortcode, and the external Python/FTP data pipeline is no
longer needed.
