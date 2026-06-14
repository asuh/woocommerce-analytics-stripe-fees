<?php
/**
 * Stripe fee helpers.
 *
 * @package extension
 */

namespace WoocommerceAnalyticsStripeFees;

/**
 * Shared helpers for reading and writing Stripe fee analytics values.
 */
class StripeFees {
	/**
	 * Stripe fee order meta key.
	 */
	public const META_KEY = '_stripe_fee';

	/**
	 * Normalize a stored fee value to a float.
	 *
	 * @param mixed $value Stored fee value.
	 * @return float
	 */
	public static function normalize_fee( $value ): float {
		if ( is_int( $value ) || is_float( $value ) ) {
			return (float) $value;
		}

		if ( is_string( $value ) ) {
			$value = trim( $value );

			if ( '' === $value ) {
				return 0.0;
			}

			$normalized = preg_replace( '/[^0-9.\-]/', '', $value );

			return is_numeric( $normalized ) ? (float) $normalized : 0.0;
		}

		return 0.0;
	}

	/**
	 * Read a Stripe fee from an order-like object.
	 *
	 * @param mixed $order WooCommerce order object.
	 * @return float
	 */
	public static function get_order_stripe_fee( $order ): float {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) {
			return 0.0;
		}

		return self::normalize_fee( $order->get_meta( self::META_KEY, true ) );
	}

	/**
	 * Sum Stripe fees from order-like objects.
	 *
	 * @param array $orders WooCommerce order objects.
	 * @return float
	 */
	public static function sum_order_stripe_fees( array $orders ): float {
		$total = 0.0;

		foreach ( $orders as $order ) {
			$total += self::get_order_stripe_fee( $order );
		}

		return $total;
	}

	/**
	 * Read an array or object value.
	 *
	 * @param mixed  $source  Array or object source.
	 * @param string $key     Value key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get_value( $source, string $key, $default = null ) {
		if ( is_array( $source ) && array_key_exists( $key, $source ) ) {
			return $source[ $key ];
		}

		if ( is_object( $source ) && isset( $source->{$key} ) ) {
			return $source->{$key};
		}

		return $default;
	}

	/**
	 * Set an array or object value.
	 *
	 * @param mixed  $target Array or object target.
	 * @param string $key    Value key.
	 * @param mixed  $value  Value to set.
	 * @return void
	 */
	public static function set_value( &$target, string $key, $value ): void {
		if ( is_array( $target ) ) {
			$target[ $key ] = $value;
			return;
		}

		if ( is_object( $target ) ) {
			$target->{$key} = $value;
		}
	}

	/**
	 * Read start and end dates from a report interval.
	 *
	 * @param mixed $interval Report interval array or object.
	 * @return array{0:?string,1:?string}
	 */
	public static function get_interval_dates( $interval ): array {
		return array(
			self::get_value( $interval, 'date_start' ),
			self::get_value( $interval, 'date_end' ),
		);
	}

	/**
	 * Set the Stripe fee subtotal on an interval.
	 *
	 * @param mixed $interval Report interval array or object.
	 * @param float $fee      Stripe fee total.
	 * @return void
	 */
	public static function set_interval_stripe_fee( &$interval, float $fee ): void {
		$subtotals = self::get_value( $interval, 'subtotals' );

		if ( null === $subtotals ) {
			$subtotals = is_array( $interval ) ? array() : new \stdClass();
		}

		self::set_value( $subtotals, 'stripe_fee', $fee );
		self::set_value( $interval, 'subtotals', $subtotals );
	}

	/**
	 * Read the Stripe fee subtotal from an interval.
	 *
	 * @param mixed $interval Report interval array or object.
	 * @return float
	 */
	public static function get_interval_stripe_fee( $interval ): float {
		$subtotals = self::get_value( $interval, 'subtotals', array() );

		return self::normalize_fee( self::get_value( $subtotals, 'stripe_fee', 0 ) );
	}
}
