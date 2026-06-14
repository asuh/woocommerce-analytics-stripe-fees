<?php

namespace WoocommerceAnalyticsStripeFees\Admin;

/**
 * WoocommerceAnalyticsStripeFees Setup Class
 */
class Setup {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
	}

	/**
	 * Load all necessary dependencies.
	 *
	 * @since 1.0.0
	 */
	public function register_scripts() {
		if ( ! method_exists( 'Automattic\WooCommerce\Admin\PageController', 'is_admin_or_embed_page' ) ||
		! \Automattic\WooCommerce\Admin\PageController::is_admin_or_embed_page()
		) {
			return;
		}

		$script_path       = '/build/index.js';
		$script_file       = dirname( WOOCOMMERCE_ANALYTICS_STRIPE_FEES_FILE ) . $script_path;
		$script_asset_path = dirname( WOOCOMMERCE_ANALYTICS_STRIPE_FEES_FILE ) . '/build/index.asset.php';

		if ( ! file_exists( $script_file ) ) {
			return;
		}

		$script_asset      = file_exists( $script_asset_path )
		? require $script_asset_path
		: array(
			'dependencies' => array(),
			'version'      => filemtime( $script_file ),
		);
		$script_url        = plugins_url( $script_path, WOOCOMMERCE_ANALYTICS_STRIPE_FEES_FILE );

		wp_register_script(
			'woocommerce-analytics-stripe-fees',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_enqueue_script( 'woocommerce-analytics-stripe-fees' );
	}
}
