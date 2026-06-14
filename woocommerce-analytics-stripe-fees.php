<?php
/**
 * Plugin Name: Woocommerce Analytics Stripe Fees
 * Description: Adds Stripe fee analytics to WooCommerce Analytics reports.
 * Version: 1.1.0
 * Requires PHP: 8.3
 * Requires at least: 6.9
 * WC requires at least: 10.4
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

if ( ! defined( 'WOOCOMMERCE_ANALYTICS_STRIPE_FEES_FILE' ) ) {
	define( 'WOOCOMMERCE_ANALYTICS_STRIPE_FEES_FILE', __FILE__ );
}

if ( ! defined( 'WOOCOMMERCE_ANALYTICS_STRIPE_FEES_VERSION' ) ) {
	define( 'WOOCOMMERCE_ANALYTICS_STRIPE_FEES_VERSION', '1.1.0' );
}

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload_packages.php';

use WoocommerceAnalyticsStripeFees\Admin\Setup;
use WoocommerceAnalyticsStripeFees\StripeFees;

/**
 * WooCommerce fallback notice.
 *
 * @since 0.1.0
 */
function woocommerce_analytics_stripe_fees_missing_wc_notice() {
	$woocommerce_link = sprintf(
		'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
		esc_url( 'https://woo.com/' ),
		esc_html__( 'WooCommerce', 'woocommerce-analytics-stripe-fees' )
	);

	$message = sprintf(
		/* translators: %s WooCommerce download URL link. */
		__( 'Woocommerce Analytics Stripe Fees requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-analytics-stripe-fees' ),
		$woocommerce_link
	);

	printf( '<div class="error"><p><strong>%s</strong></p></div>', wp_kses_post( $message ) );
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
	}
}

if ( ! class_exists( 'woocommerce_analytics_stripe_fees' ) ) :
	/**
	 * Main plugin class.
	 */
	class woocommerce_analytics_stripe_fees {
		/**
		 * This class instance.
		 *
		 * @var \woocommerce_analytics_stripe_fees
		 */
		private static $instance;

		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		public $version = WOOCOMMERCE_ANALYTICS_STRIPE_FEES_VERSION;

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
			wc_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'woocommerce-analytics-stripe-fees' ), $this->version );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 */
		public function __wakeup() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'woocommerce-analytics-stripe-fees' ), $this->version );
		}

		/**
		 * Gets the main instance.
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
	load_plugin_textdomain( 'woocommerce-analytics-stripe-fees', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'woocommerce_analytics_stripe_fees_missing_wc_notice' );
		return;
	}

	woocommerce_analytics_stripe_fees::instance();
}

add_filter( 'woocommerce_analytics_orders_select_query', 'woocommerce_analytics_stripe_fees_add_order_report_data', 10, 2 );

/**
 * Add Stripe fee data to the Orders report rows.
 *
 * @param object $results Orders report results.
 * @param array  $args    Query arguments.
 * @return object
 */
function woocommerce_analytics_stripe_fees_add_order_report_data( $results, $args ) {
	if ( ! $results || is_wp_error( $results ) || empty( $results->data ) || ! is_array( $results->data ) ) {
		return $results;
	}

	foreach ( $results->data as $key => $result ) {
		$order_id = StripeFees::get_value( $result, 'order_id' );

		if ( ! $order_id ) {
			StripeFees::set_value( $results->data[ $key ], 'stripe_fee', 0.0 );
			continue;
		}

		$order = wc_get_order( $order_id );
		StripeFees::set_value( $results->data[ $key ], 'stripe_fee', StripeFees::get_order_stripe_fee( $order ) );
	}

	return $results;
}

add_filter( 'woocommerce_report_orders_export_columns', 'woocommerce_analytics_stripe_fees_add_order_export_column' );

/**
 * Add the Stripe fee column to the Orders CSV export.
 *
 * @param array $export_columns Existing export columns.
 * @return array
 */
function woocommerce_analytics_stripe_fees_add_order_export_column( $export_columns ) {
	$export_columns['stripe_fee'] = __( 'Stripe Fee', 'woocommerce-analytics-stripe-fees' );

	return $export_columns;
}

add_filter( 'woocommerce_report_orders_prepare_export_item', 'woocommerce_analytics_stripe_fees_prepare_order_export_item', 10, 2 );

/**
 * Add Stripe fee data to an Orders CSV export item.
 *
 * @param array $export_item The export item data.
 * @param array $item        The original item data.
 * @return array
 */
function woocommerce_analytics_stripe_fees_prepare_order_export_item( $export_item, $item ) {
	$export_item['stripe_fee'] = StripeFees::normalize_fee( StripeFees::get_value( $item, 'stripe_fee', 0 ) );

	return $export_item;
}

add_filter( 'woocommerce_analytics_revenue_select_query', 'woocommerce_analytics_stripe_fees_add_revenue_report_data', 10, 2 );

/**
 * Add Stripe fee totals to the Revenue report data.
 *
 * @param object $results Revenue report results.
 * @param array  $args    Query arguments.
 * @return object
 */
function woocommerce_analytics_stripe_fees_add_revenue_report_data( $results, $args ) {
	if ( ! $results || is_wp_error( $results ) ) {
		return $results;
	}

	if ( isset( $results->intervals ) && is_array( $results->intervals ) ) {
		$intervals = &$results->intervals;
	} elseif ( isset( $results->data->intervals ) && is_array( $results->data->intervals ) ) {
		$intervals = &$results->data->intervals;
	} else {
		return $results;
	}

	if ( empty( $intervals ) ) {
		return $results;
	}

	$fees_by_interval = woocommerce_analytics_stripe_fees_get_revenue_fees_by_interval( $intervals, $args );
	$total_fees       = 0.0;

	foreach ( $intervals as $key => &$interval ) {
		$fee         = $fees_by_interval[ $key ] ?? 0.0;
		$total_fees += $fee;

		StripeFees::set_interval_stripe_fee( $interval, $fee );
	}
	unset( $interval );

	if ( isset( $results->totals ) ) {
		StripeFees::set_value( $results->totals, 'stripe_fee', $total_fees );
	} elseif ( isset( $results->data->totals ) ) {
		StripeFees::set_value( $results->data->totals, 'stripe_fee', $total_fees );
	}

	return $results;
}

/**
 * Get Stripe fees grouped by Revenue report interval.
 *
 * @param array $intervals Revenue report intervals.
 * @param array $args      Query arguments.
 * @return array
 */
function woocommerce_analytics_stripe_fees_get_revenue_fees_by_interval( array $intervals, array $args ): array {
	$ranges = array();

	foreach ( $intervals as $key => $interval ) {
		list( $start_date, $end_date ) = StripeFees::get_interval_dates( $interval );

		if ( ! $start_date || ! $end_date ) {
			continue;
		}

		$start_timestamp = strtotime( $start_date );
		$end_timestamp   = strtotime( $end_date );

		if ( false === $start_timestamp || false === $end_timestamp ) {
			continue;
		}

		$ranges[ $key ] = array(
			'start'      => $start_date,
			'end'        => $end_date,
			'start_time' => $start_timestamp,
			'end_time'   => $end_timestamp,
			'fee'        => 0.0,
		);
	}

	if ( empty( $ranges ) ) {
		return array();
	}

	$first_range = reset( $ranges );
	$last_range  = end( $ranges );

	$order_args = array(
		'limit'        => -1,
		'status'       => woocommerce_analytics_stripe_fees_get_order_statuses( $args ),
		'date_created' => $first_range['start'] . '...' . $last_range['end'],
		'return'       => 'objects',
	);

	/**
	 * Filters the WooCommerce order query used to calculate Revenue report Stripe fees.
	 *
	 * @param array $order_args WooCommerce order query arguments.
	 * @param array $args       Revenue report query arguments.
	 * @param array $intervals  Revenue report intervals.
	 */
	$order_args = apply_filters( 'woocommerce_analytics_stripe_fees_revenue_order_query_args', $order_args, $args, $intervals );
	$orders     = wc_get_orders( $order_args );

	if ( empty( $orders ) ) {
		return wp_list_pluck( $ranges, 'fee' );
	}

	foreach ( $orders as $order ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_date_created' ) ) {
			continue;
		}

		$date_created = $order->get_date_created();

		if ( ! $date_created || ! method_exists( $date_created, 'getTimestamp' ) ) {
			continue;
		}

		$order_time = $date_created->getTimestamp();
		$order_fee  = StripeFees::get_order_stripe_fee( $order );

		if ( 0.0 === $order_fee ) {
			continue;
		}

		foreach ( $ranges as &$range ) {
			if ( $order_time >= $range['start_time'] && $order_time <= $range['end_time'] ) {
				$range['fee'] += $order_fee;
				break;
			}
		}
		unset( $range );
	}

	return wp_list_pluck( $ranges, 'fee' );
}

/**
 * Infer order statuses for the Stripe fee order query.
 *
 * @param array $args Revenue report query arguments.
 * @return array
 */
function woocommerce_analytics_stripe_fees_get_order_statuses( array $args ): array {
	foreach ( array( 'status', 'statuses', 'order_status', 'order_statuses', 'status_is' ) as $key ) {
		if ( empty( $args[ $key ] ) ) {
			continue;
		}

		$statuses = is_array( $args[ $key ] ) ? $args[ $key ] : array( $args[ $key ] );

		return array_values( array_filter( array_map( 'sanitize_text_field', $statuses ) ) );
	}

	return array( 'wc-completed', 'wc-processing', 'wc-on-hold' );
}

add_filter( 'woocommerce_report_revenue_export_columns', 'woocommerce_analytics_stripe_fees_add_revenue_export_column' );

/**
 * Add the Stripe fee column to the Revenue CSV export.
 *
 * @param array $export_columns Existing export columns.
 * @return array
 */
function woocommerce_analytics_stripe_fees_add_revenue_export_column( $export_columns ) {
	$export_columns['stripe_fee'] = __( 'Stripe Fees', 'woocommerce-analytics-stripe-fees' );

	return $export_columns;
}

add_filter( 'woocommerce_report_revenue_prepare_export_item', 'woocommerce_analytics_stripe_fees_prepare_revenue_export_item', 10, 2 );

/**
 * Add Stripe fee data to a Revenue CSV export item.
 *
 * @param array $export_item The export item data.
 * @param array $item        The original item data.
 * @return array
 */
function woocommerce_analytics_stripe_fees_prepare_revenue_export_item( $export_item, $item ) {
	$export_item['stripe_fee'] = StripeFees::get_interval_stripe_fee( $item );

	return $export_item;
}
