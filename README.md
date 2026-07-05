# Wollongong Sportsground Status

A WordPress plugin that shows the current **open/closed** status of a
[Wollongong City Council sportsground](https://wollongong.nsw.gov.au/places/sport-and-fitness/sportsgrounds)
via a shortcode.

```text
[sportsground_status ground="Cawley Park"]
```

The plugin fetches Council's public page directly (WordPress HTTP API), parses
it, and caches the result in a transient for 15 minutes. It is fully
self-contained — **no external scraper, FTP upload, or hosted JSON file**.

## Install

1. Download/clone this repository and copy the folder into
   `wp-content/plugins/`, **or** zip it and install via
   *Plugins → Add New → Upload Plugin*.
2. Activate **Wollongong Sportsground Status**.
3. Add the shortcode to a post, page, or a *Shortcode* block in a widget area.

Requires WordPress 6.0+ and PHP 7.4+.

## Shortcode

`[sportsground_status ground="Cawley Park"]`

| Attribute | Default | Description |
|---|---|---|
| `ground` | *(required)* | Ground to show. Accepts the name (`Cawley Park` or `Cawley Park, Russell Vale`) or the URL slug (`cawley-park`). |
| `name` | Council's name | Override the heading shown on the card. |
| `show_updated` | `yes` | Show Council's "Status last changed" time (from the ground's detail page). |
| `link` | `yes` | Link to the ground's Council page. |

Examples:

```text
[sportsground_status ground="cawley-park"]
[sportsground_status ground="Cawley Park" name="Our Home Ground" link="no"]
```

## How it works

- **Live fetch + cache** — `wp_remote_get()` pulls Council's listing page; the
  parsed grounds are stored in a transient (`wsg_grounds_v2`, 15 min). Each
  ground's "last changed" time is fetched from its detail page and cached per
  URL.
- **Robust parsing** — `DOMDocument` + `DOMXPath` over Council's
  `sportsgrounds__item` markup, rather than brittle string matching.
- **Secure output** — attributes are sanitised; everything rendered is escaped
  (`esc_html`, `esc_attr`, `esc_url`).

## Upgrading from 1.x (2014)

The original plugin used WordPress widget APIs
(`register_sidebar_widget()` / `register_widget_control()`) that have since been
**removed from WordPress**, and it depended on an external pipeline: a Python
script scraped Council, wrote `test.json`, and uploaded it over FTP for the
widget to read.

Version 2.0 replaces all of that:

- The sidebar widget → the `[sportsground_status]` **shortcode** (works in
  posts, pages, and block widget areas via a Shortcode block).
- The Python scraper, FTP upload, and hosted `test.json` → **direct, cached
  fetching inside the plugin**.
- The `sensor.php` Home Assistant endpoint has been retired — there is now a
  dedicated [Home Assistant integration](https://github.com/dgaust/wollongong-sportsgrounds)
  for that.

The old files (`cawleyparkstatus.php`, `groundstatus.py`,
`groundstatus_nobs4.py`, `sensor.php`) have been removed; see the git history if
you need them.

## Related

- [Wollongong Sportsgrounds — Home Assistant integration](https://github.com/dgaust/wollongong-sportsgrounds)

## License

GPL-2.0-or-later. This is an unofficial project and is not affiliated with or
endorsed by Wollongong City Council. Always confirm play with your club or the
ground's official channels.
