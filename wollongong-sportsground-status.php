<?php
/**
 * Plugin Name:       Wollongong Sportsground Status
 * Plugin URI:        https://github.com/dgaust/groundsstatus
 * Description:       Show the current open/closed status of a Wollongong City Council sportsground with the [sportsground_status] shortcode or the Sportsground Status widget. Status is fetched live from Council and cached.
 * Version:           2.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            dgaust
 * Author URI:        https://github.com/dgaust
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wollongong-sportsground-status
 *
 * @package WollongongSportsgroundStatus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Fetches, caches and renders Wollongong City Council sportsground status.
 *
 * The 2014 version of this plugin relied on a Python scraper uploading a JSON
 * file over FTP, which the widget then read. This rewrite fetches Council's
 * public page directly with the WordPress HTTP API, parses it with DOMDocument,
 * and caches the result in a transient — no external pipeline required.
 */
final class WSG_Sportsground_Status {

	const SOURCE_URL   = 'https://wollongong.nsw.gov.au/places/sport-and-fitness/sportsgrounds';
	const CACHE_KEY    = 'wsg_grounds_v2';
	const CACHE_TTL    = 15 * MINUTE_IN_SECONDS;
	const DETAIL_TTL   = 15 * MINUTE_IN_SECONDS;
	const STYLE_HANDLE = 'wollongong-sportsground-status';
	const VERSION      = '2.1.0';
	const USER_AGENT   = 'WollongongSportsgroundStatus/2.0 (+https://github.com/dgaust/groundsstatus)';

	/**
	 * Singleton instance.
	 *
	 * @var WSG_Sportsground_Status|null
	 */
	private static $instance = null;

	/**
	 * Boot the plugin.
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook up the shortcode, widget and assets.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_assets' ) );
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
		add_shortcode( 'sportsground_status', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Register the Sportsground Status widget.
	 */
	public function register_widgets() {
		register_widget( 'WSG_Sportsground_Widget' );
	}

	/**
	 * Public list of grounds, for building UI such as the widget dropdown.
	 *
	 * @return array List of ground arrays (slug, name, status, url); empty on error.
	 */
	public function get_ground_options() {
		$grounds = $this->get_grounds();
		return is_wp_error( $grounds ) ? array() : $grounds;
	}

	/**
	 * Register the (inline-only) stylesheet so it can be enqueued on demand.
	 */
	public function register_assets() {
		wp_register_style( self::STYLE_HANDLE, false, array(), self::VERSION );
		wp_add_inline_style( self::STYLE_HANDLE, $this->inline_css() );
	}

	/**
	 * Render the [sportsground_status] shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string HTML.
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'ground'       => '',
				'name'         => '',
				'show_updated' => 'yes',
				'link'         => 'yes',
			),
			$atts,
			'sportsground_status'
		);

		wp_enqueue_style( self::STYLE_HANDLE );

		$query = sanitize_text_field( (string) $atts['ground'] );
		if ( '' === $query ) {
			return $this->notice(
				__( 'No ground specified. Use e.g. [sportsground_status ground="Cawley Park"].', 'wollongong-sportsground-status' )
			);
		}

		$grounds = $this->get_grounds();
		if ( is_wp_error( $grounds ) ) {
			return $this->notice(
				__( 'Sportsground status is temporarily unavailable. Please try again later.', 'wollongong-sportsground-status' )
			);
		}

		$ground = $this->match_ground( $grounds, $query );
		if ( null === $ground ) {
			/* translators: %s: the ground name the user searched for. */
			return $this->notice( sprintf( __( 'Sportsground "%s" was not found.', 'wollongong-sportsground-status' ), $query ) );
		}

		return $this->render_card( $ground, $atts );
	}

	/**
	 * Build the status card markup.
	 *
	 * @param array $ground Ground data (slug, name, status, url).
	 * @param array $atts   Sanitised shortcode attributes.
	 * @return string HTML.
	 */
	private function render_card( array $ground, array $atts ) {
		$is_open  = ( 'open' === strtolower( trim( $ground['status'] ) ) );
		$heading  = ( '' !== $atts['name'] ) ? sanitize_text_field( (string) $atts['name'] ) : $ground['name'];
		$show_upd = ( 'yes' === strtolower( (string) $atts['show_updated'] ) );
		$show_lnk = ( 'yes' === strtolower( (string) $atts['link'] ) );
		$updated  = $show_upd ? $this->get_last_changed( $ground['url'] ) : '';
		$classes  = 'wsg-card ' . ( $is_open ? 'wsg-open' : 'wsg-closed' );

		ob_start();
		?>
		<div class="<?php echo esc_attr( $classes ); ?>">
			<div class="wsg-header">
				<span class="wsg-name"><?php echo esc_html( $heading ); ?></span>
				<span class="wsg-badge"><?php echo esc_html( $ground['status'] ); ?></span>
			</div>
			<?php if ( '' !== $updated ) : ?>
				<p class="wsg-updated">
					<?php echo esc_html__( 'Status last changed:', 'wollongong-sportsground-status' ); ?>
					<?php echo esc_html( $updated ); ?>
				</p>
			<?php endif; ?>
			<?php if ( $show_lnk ) : ?>
				<a class="wsg-link" href="<?php echo esc_url( $ground['url'] ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html__( 'View on Council website', 'wollongong-sportsground-status' ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php
		return trim( (string) ob_get_clean() );
	}

	/**
	 * Match a user query against a ground by slug, exact name, name-before-comma,
	 * or partial name (in that order of preference).
	 *
	 * The "name-before-comma" match keeps backward compatibility with the old
	 * widget, which stored the ground name without its suburb.
	 *
	 * @param array  $grounds List of ground arrays.
	 * @param string $query   User-supplied ground.
	 * @return array|null Matched ground or null.
	 */
	private function match_ground( array $grounds, $query ) {
		$needle = strtolower( trim( $query ) );

		foreach ( $grounds as $g ) {
			if ( strtolower( $g['slug'] ) === $needle ) {
				return $g;
			}
		}
		foreach ( $grounds as $g ) {
			$name         = strtolower( $g['name'] );
			$before_comma = strtolower( trim( explode( ',', $g['name'] )[0] ) );
			if ( $name === $needle || $before_comma === $needle ) {
				return $g;
			}
		}
		foreach ( $grounds as $g ) {
			if ( '' !== $needle && false !== strpos( strtolower( $g['name'] ), $needle ) ) {
				return $g;
			}
		}
		return null;
	}

	/**
	 * Get all grounds, using a cached copy when available.
	 *
	 * @return array|WP_Error List of grounds or an error.
	 */
	private function get_grounds() {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$body = $this->fetch( self::SOURCE_URL );
		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$grounds = $this->parse_grounds( $body );
		if ( empty( $grounds ) ) {
			return new WP_Error( 'wsg_parse', 'No sportsgrounds could be parsed from the Council page.' );
		}

		set_transient( self::CACHE_KEY, $grounds, self::CACHE_TTL );
		return $grounds;
	}

	/**
	 * Get Council's "Status last changed" time for a ground's detail page.
	 *
	 * Cached per URL. Returns the raw Council text (e.g. "03 Jul 2026 11:51am")
	 * or an empty string if unavailable.
	 *
	 * @param string $url Ground detail URL.
	 * @return string
	 */
	private function get_last_changed( $url ) {
		$key    = 'wsg_lc_' . md5( $url );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return (string) $cached;
		}

		$value = '';
		$body  = $this->fetch( $url );
		if ( ! is_wp_error( $body ) ) {
			$pattern = '/Status last changed:\s*<\/em>\s*'
				. '([0-9]{1,2}\s+[A-Za-z]{3}\s+[0-9]{4}\s+[0-9]{1,2}:[0-9]{2}\s*(?:am|pm))/i';
			if ( preg_match( $pattern, $body, $m ) ) {
				$value = preg_replace( '/\s+/', ' ', trim( $m[1] ) );
			}
		}

		set_transient( $key, $value, self::DETAIL_TTL );
		return $value;
	}

	/**
	 * Fetch a URL with the WordPress HTTP API.
	 *
	 * @param string $url URL to fetch.
	 * @return string|WP_Error Response body or error.
	 */
	private function fetch( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 15,
				'user-agent' => self::USER_AGENT,
				'headers'    => array( 'Accept' => 'text/html' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'wsg_http', 'Unexpected HTTP status from Council.' );
		}
		return (string) wp_remote_retrieve_body( $response );
	}

	/**
	 * Parse the listing page into a list of grounds.
	 *
	 * @param string $html Page HTML.
	 * @return array List of ['slug','name','status','url'].
	 */
	private function parse_grounds( $html ) {
		$grounds = array();

		$previous = libxml_use_internal_errors( true );
		$doc      = new DOMDocument();
		// Prepend an encoding hint so DOMDocument reads UTF-8 correctly.
		$doc->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$xpath = new DOMXPath( $doc );
		$items = $xpath->query( '//div[contains(concat(" ", normalize-space(@class), " "), " sportsgrounds__item ")]' );
		if ( ! $items ) {
			return $grounds;
		}

		foreach ( $items as $item ) {
			$link   = $xpath->query( './/div[contains(@class, "sportsgrounds__name")]/a', $item )->item( 0 );
			$status = $xpath->query(
				'.//div[contains(@class, "sportsgrounds__status")]//span[contains(concat(" ", normalize-space(@class), " "), " status ")]',
				$item
			)->item( 0 );

			if ( ! $link || ! $status ) {
				continue;
			}

			$url  = trim( $link->getAttribute( 'href' ) );
			$slug = $this->slug_from_url( $url );
			if ( '' === $slug ) {
				continue;
			}

			$grounds[] = array(
				'slug'   => $slug,
				'name'   => trim( $link->textContent ),
				'status' => trim( $status->textContent ),
				'url'    => esc_url_raw( $url ),
			);
		}

		return $grounds;
	}

	/**
	 * Extract the trailing slug from a ground detail URL.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function slug_from_url( $url ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( ! $path ) {
			return '';
		}
		$parts = explode( '/', rtrim( $path, '/' ) );
		return (string) end( $parts );
	}

	/**
	 * Wrap a message in a plain notice card.
	 *
	 * @param string $message Plain (unescaped) message.
	 * @return string
	 */
	private function notice( $message ) {
		return '<div class="wsg-card wsg-notice"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Inline CSS for the status card. Uses fixed status colours (green/red)
	 * with neutral, theme-friendly surrounds.
	 *
	 * @return string
	 */
	private function inline_css() {
		return '
.wsg-card{border:1px solid rgba(0,0,0,.12);border-radius:8px;padding:12px 14px;margin:0 0 1em;line-height:1.4}
.wsg-header{display:flex;align-items:center;justify-content:space-between;gap:10px}
.wsg-name{font-weight:600}
.wsg-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.72em;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#fff;white-space:nowrap}
.wsg-open{border-left:4px solid #2e7d32}
.wsg-closed{border-left:4px solid #c62828}
.wsg-open .wsg-badge{background:#2e7d32}
.wsg-closed .wsg-badge{background:#c62828}
.wsg-updated{margin:.5em 0 0;font-size:.85em;opacity:.75}
.wsg-link{display:inline-block;margin-top:.5em;font-size:.85em}
.wsg-notice{opacity:.8}
';
	}
}

/**
 * Sportsground Status widget.
 *
 * A classic WP_Widget so the status can be dropped into any widget area (and,
 * on block-based themes, via the "Legacy Widget" block). The settings form
 * offers a live dropdown of grounds. Rendering and caching are shared with the
 * shortcode via WSG_Sportsground_Status::render_shortcode().
 */
class WSG_Sportsground_Widget extends WP_Widget {

	/**
	 * Register the widget with its name and description.
	 */
	public function __construct() {
		parent::__construct(
			'wsg_sportsground_status',
			__( 'Sportsground Status', 'wollongong-sportsground-status' ),
			array(
				'description' => __( 'Show whether a Wollongong sportsground is open or closed.', 'wollongong-sportsground-status' ),
				'classname'   => 'wsg-widget',
			)
		);
	}

	/**
	 * Front-end output.
	 *
	 * @param array $args     Theme widget wrappers.
	 * @param array $instance Saved settings.
	 */
	public function widget( $args, $instance ) {
		$ground = isset( $instance['ground'] ) ? trim( (string) $instance['ground'] ) : '';
		if ( '' === $ground ) {
			return;
		}

		$card = WSG_Sportsground_Status::instance()->render_shortcode(
			array(
				'ground'       => $ground,
				'name'         => isset( $instance['name'] ) ? $instance['name'] : '',
				'show_updated' => empty( $instance['show_updated'] ) ? 'no' : 'yes',
				'link'         => empty( $instance['link'] ) ? 'no' : 'yes',
			)
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme-provided wrapper markup.
		echo $args['before_widget'];

		$title = apply_filters(
			'widget_title',
			isset( $instance['title'] ) ? $instance['title'] : '',
			$instance,
			$this->id_base
		);
		if ( '' !== $title ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme wrapper markup around an escaped title.
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Card HTML is already built from esc_html()/esc_attr()/esc_url().
		echo $card;

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme-provided wrapper markup.
		echo $args['after_widget'];
	}

	/**
	 * Sanitise settings on save.
	 *
	 * @param array $new_instance Submitted values.
	 * @param array $old_instance Previous values.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		return array(
			'title'        => isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '',
			'ground'       => isset( $new_instance['ground'] ) ? sanitize_text_field( $new_instance['ground'] ) : '',
			'name'         => isset( $new_instance['name'] ) ? sanitize_text_field( $new_instance['name'] ) : '',
			'show_updated' => empty( $new_instance['show_updated'] ) ? 0 : 1,
			'link'         => empty( $new_instance['link'] ) ? 0 : 1,
		);
	}

	/**
	 * Settings form shown in the admin.
	 *
	 * @param array $instance Saved settings.
	 */
	public function form( $instance ) {
		$title        = isset( $instance['title'] ) ? (string) $instance['title'] : '';
		$ground       = isset( $instance['ground'] ) ? (string) $instance['ground'] : '';
		$name         = isset( $instance['name'] ) ? (string) $instance['name'] : '';
		// New widgets default the toggles on.
		$show_updated = ! isset( $instance['show_updated'] ) || $instance['show_updated'];
		$link         = ! isset( $instance['link'] ) || $instance['link'];

		$grounds     = WSG_Sportsground_Status::instance()->get_ground_options();
		$known_slugs = wp_list_pluck( $grounds, 'slug' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'wollongong-sportsground-status' ); ?></label>
			<input class="widefat" type="text"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'ground' ) ); ?>"><?php esc_html_e( 'Sportsground:', 'wollongong-sportsground-status' ); ?></label>
			<?php if ( ! empty( $grounds ) ) : ?>
				<select class="widefat"
					id="<?php echo esc_attr( $this->get_field_id( 'ground' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'ground' ) ); ?>">
					<option value=""><?php esc_html_e( '— Select a ground —', 'wollongong-sportsground-status' ); ?></option>
					<?php foreach ( $grounds as $g ) : ?>
						<option value="<?php echo esc_attr( $g['slug'] ); ?>" <?php selected( $ground, $g['slug'] ); ?>>
							<?php echo esc_html( $g['name'] ); ?>
						</option>
					<?php endforeach; ?>
					<?php if ( '' !== $ground && ! in_array( $ground, $known_slugs, true ) ) : ?>
						<option value="<?php echo esc_attr( $ground ); ?>" selected>
							<?php echo esc_html( $ground ); ?>
						</option>
					<?php endif; ?>
				</select>
			<?php else : ?>
				<input class="widefat" type="text"
					id="<?php echo esc_attr( $this->get_field_id( 'ground' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'ground' ) ); ?>"
					value="<?php echo esc_attr( $ground ); ?>"
					placeholder="<?php esc_attr_e( 'e.g. Cawley Park or cawley-park', 'wollongong-sportsground-status' ); ?>" />
				<small><?php esc_html_e( 'Could not load the ground list right now — enter the ground name or slug.', 'wollongong-sportsground-status' ); ?></small>
			<?php endif; ?>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'name' ) ); ?>"><?php esc_html_e( 'Heading override (optional):', 'wollongong-sportsground-status' ); ?></label>
			<input class="widefat" type="text"
				id="<?php echo esc_attr( $this->get_field_id( 'name' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'name' ) ); ?>"
				value="<?php echo esc_attr( $name ); ?>" />
		</p>
		<p>
			<input type="checkbox" value="1" <?php checked( $show_updated ); ?>
				id="<?php echo esc_attr( $this->get_field_id( 'show_updated' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'show_updated' ) ); ?>" />
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_updated' ) ); ?>"><?php esc_html_e( 'Show "Status last changed" time', 'wollongong-sportsground-status' ); ?></label>
		</p>
		<p>
			<input type="checkbox" value="1" <?php checked( $link ); ?>
				id="<?php echo esc_attr( $this->get_field_id( 'link' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'link' ) ); ?>" />
			<label for="<?php echo esc_attr( $this->get_field_id( 'link' ) ); ?>"><?php esc_html_e( 'Show link to Council page', 'wollongong-sportsground-status' ); ?></label>
		</p>
		<?php
	}
}

WSG_Sportsground_Status::instance();
