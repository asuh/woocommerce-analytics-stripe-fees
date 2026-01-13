<?php
/**
 * Plugin Name: Woocommerce Analytics Stripe Fees
 * Version: 0.5.0
 * Author: Asuh
 * Author URI: https://asuh.com
 * Text Domain: woocommerce-analytics-stripe-fees
 * Domain Path: /languages
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package extension
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'MAIN_PLUGIN_FILE' ) ) {
	define( 'MAIN_PLUGIN_FILE', __FILE__ );
}

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload_packages.php';

use WoocommerceAnalyticsStripeFees\Admin\Setup;

// phpcs:disable WordPress.Files.FileName

/**
 * WooCommerce fallback notice.
 *
 * @since 0.1.0
 */
function woocommerce_analytics_stripe_fees_missing_wc_notice() {
	/* translators: %s WC download URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Woocommerce Analytics Stripe Fees requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce_analytics_stripe_fees' ), '<a href="https://woo.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

register_activation_hook( __FILE__, 'woocommerce_analytics_stripe_fees_activate' );

/**
 * Activation hook.
 *
 * @since 0.1.0
 */
function woocommerce_analytics_stripe_fees_activate() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'woocommerce_analytics_stripe_fees_missing_wc_notice' );
		return;
	}
}

if ( ! class_exists( 'woocommerce_analytics_stripe_fees' ) ) :
	/**
	 * The woocommerce_analytics_stripe_fees class.
	 */
	class woocommerce_analytics_stripe_fees {
		/**
		 * This class instance.
		 *
		 * @var \woocommerce_analytics_stripe_fees single instance of this class.
		 */
		private static $instance;

		/**
		 * Constructor.
		 */
		public function __construct() {
			if ( is_admin() ) {
				new Setup();
			}
		}

		/**
		 * Cloning is forbidden.
		 */
		public function __clone() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'woocommerce_analytics_stripe_fees' ), $this->version );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 */
		public function __wakeup() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'woocommerce_analytics_stripe_fees' ), $this->version );
		}

		/**
		 * Gets the main instance.
		 *
		 * Ensures only one instance can be loaded.
		 *
		 * @return \woocommerce_analytics_stripe_fees
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
endif;

add_action( 'plugins_loaded', 'woocommerce_analytics_stripe_fees_init', 10 );

/**
 * Initialize the plugin.
 *
 * @since 0.1.0
 */
function woocommerce_analytics_stripe_fees_init() {
	load_plugin_textdomain( 'woocommerce_analytics_stripe_fees', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'woocommerce_analytics_stripe_fees_missing_wc_notice' );
		return;
	}

	woocommerce_analytics_stripe_fees::instance();

}


/**
 * Show the stripe fee of the order in the order analytics table
 * @param $results
 * @param $args
 * @return mixed
 */
add_filter('woocommerce_analytics_orders_select_query', function ($results, $args) {

    if ($results && isset($results->data) && !empty($results->data)) {
        foreach ($results->data as $key => $result) {
            $order = wc_get_order($result['order_id']);
            $results->data[$key]['stripe_fee'] = $order->get_meta('_stripe_fee', true);
        }
    }

    return $results;
}, 10, 2);

/**
 * Add the stripe fee column to the CSV file
 * @param $export_columns
 * @return mixed
 */
add_filter('woocommerce_report_orders_export_columns', function ($export_columns) {
    $export_columns['stripe_fee'] = 'Stripe Fee';
    return $export_columns;
});

/**
 * Add the stripe fee data to the CSV file
 * @param $export_item
 * @param $item
 * @return mixed
 */
add_filter('woocommerce_report_orders_prepare_export_item', function ($export_item, $item) {
    $export_item['stripe_fee'] = $item['stripe_fee'];
    return $export_item;
}, 10, 2);

/**
 * Add stripe fee totals to the Revenue report data.
 * This aggregates stripe fees for each interval (day/week/month/quarter/year).
 *
 * @param object $results The revenue report results.
 * @param array  $args    Query arguments.
 * @return object Modified results with stripe_fee data.
 */
add_filter('woocommerce_analytics_revenue_select_query', function ($results, $args) {
    // Safety check - ensure we have valid results
    if (!$results || is_wp_error($results)) {
        return $results;
    }

    // Check if intervals exist (could be array or property)
    $intervals = null;
    if (isset($results->intervals)) {
        $intervals = &$results->intervals;
    } elseif (isset($results->data->intervals)) {
        $intervals = &$results->data->intervals;
    }

    if (empty($intervals) || !is_array($intervals)) {
        return $results;
    }

    global $wpdb;
    $total_stripe_fees = 0;

    // Get stripe fees for each interval
    foreach ($intervals as $key => &$interval) {
        // Get date range - handle both array and object formats
        $start_date = is_array($interval) ? ($interval['date_start'] ?? null) : ($interval->date_start ?? null);
        $end_date = is_array($interval) ? ($interval['date_end'] ?? null) : ($interval->date_end ?? null);

        if (!$start_date || !$end_date) {
            continue;
        }

        // Query orders in this interval and sum their stripe fees
        // Using HPOS-compatible query that works with both legacy and new order tables
        $stripe_fee_total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2)))
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key = '_stripe_fee'
             AND p.post_type IN ('shop_order', 'shop_order_placehold')
             AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
             AND p.post_date >= %s
             AND p.post_date < %s",
            $start_date,
            $end_date
        ));

        $fee = (float) ($stripe_fee_total ?? 0);
        $total_stripe_fees += $fee;

        // Handle both array and object subtotals
        if (is_array($interval)) {
            if (!isset($intervals[$key]['subtotals'])) {
                $intervals[$key]['subtotals'] = array();
            }
            if (is_array($intervals[$key]['subtotals'])) {
                $intervals[$key]['subtotals']['stripe_fee'] = $fee;
            } elseif (is_object($intervals[$key]['subtotals'])) {
                $intervals[$key]['subtotals']->stripe_fee = $fee;
            }
        } elseif (is_object($interval)) {
            if (!isset($interval->subtotals)) {
                $interval->subtotals = new stdClass();
            }
            if (is_object($interval->subtotals)) {
                $interval->subtotals->stripe_fee = $fee;
            } elseif (is_array($interval->subtotals)) {
                $interval->subtotals['stripe_fee'] = $fee;
            }
        }
    }
    unset($interval); // Break reference

    // Also add to totals
    if (isset($results->totals)) {
        if (is_object($results->totals)) {
            $results->totals->stripe_fee = $total_stripe_fees;
        } elseif (is_array($results->totals)) {
            $results->totals['stripe_fee'] = $total_stripe_fees;
        }
    }

    return $results;
}, 10, 2);

/**
 * Add stripe fee column to the Revenue report CSV export.
 *
 * @param array $export_columns Existing export columns.
 * @return array Modified export columns.
 */
add_filter('woocommerce_report_revenue_export_columns', function ($export_columns) {
    $export_columns['stripe_fee'] = __('Stripe Fees', 'woocommerce-analytics-stripe-fees');
    return $export_columns;
});

/**
 * Add stripe fee data to the Revenue report CSV export item.
 *
 * @param array $export_item The export item data.
 * @param array $item        The original item data.
 * @return array Modified export item.
 */
add_filter('woocommerce_report_revenue_prepare_export_item', function ($export_item, $item) {
    $export_item['stripe_fee'] = $item['subtotals']['stripe_fee'] ?? 0;
    return $export_item;
}, 10, 2);
