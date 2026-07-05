<?php
/**
 * Uninstall cleanup for Wollongong Sportsground Status.
 *
 * @package WollongongSportsgroundStatus
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove the cached grounds list.
delete_transient( 'wsg_grounds_v2' );

// Best-effort removal of the per-ground "last changed" transients.
global $wpdb;
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	 WHERE option_name LIKE '\_transient\_wsg\_lc\_%'
	    OR option_name LIKE '\_transient\_timeout\_wsg\_lc\_%'"
);
