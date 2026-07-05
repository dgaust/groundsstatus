=== Wollongong Sportsground Status ===
Contributors: dgaust
Tags: sportsground, wollongong, status, shortcode, sport
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show the current open/closed status of a Wollongong City Council sportsground with a shortcode or widget.

== Description ==

Wollongong Sportsground Status displays whether a Wollongong City Council
sportsground is currently open or closed. Use it two ways:

* **Shortcode** — drop `[sportsground_status ground="Cawley Park"]` into any
  post, page or block widget area.
* **Widget** — add the **Sportsground Status** widget to any widget area and
  pick a ground from the dropdown.

The status is fetched live from Council's public sportsgrounds page and cached
for 15 minutes, so pages stay fast and Council's server isn't hammered. No
external scraper, FTP upload or hosted JSON file is required — the plugin is
fully self-contained.

= Widget =

Go to Appearance → Widgets, add **Sportsground Status**, choose a ground from
the dropdown (populated live from Council), optionally set a title, a heading
override, and whether to show the "last changed" time and Council link.

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
3. Add `[sportsground_status ground="Your Ground"]` to a post/page, or add the
   **Sportsground Status** widget under Appearance → Widgets.

== Frequently Asked Questions ==

= Where does the data come from? =

Directly from Council's public sportsgrounds page. If Council changes their page
layout the plugin may need updating.

= How often does it update? =

The grounds list and each ground's "last changed" time are cached for 15
minutes.

= How do I place it in a sidebar/widget area? =

Either add the **Sportsground Status** widget (Appearance → Widgets) and pick a
ground, or add a "Shortcode" block to the widget area and paste the shortcode.

== Changelog ==

= 2.1.0 =
* Added the **Sportsground Status** widget with a live ground picker, so you can
  place a ground's status in any widget area without the shortcode.
* The widget shares the shortcode's rendering and caching.

= 2.0.1 =
* Compliance and tooling: WordPress Plugin Check clean; bumped "Tested up to";
  added a CI workflow (PHP lint 7.4–8.4 + Plugin Check). No functional changes.

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
