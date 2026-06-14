<?php
/**
 * Stripe fee helper tests.
 *
 * @package extension
 */

use PHPUnit\Framework\TestCase;
use WoocommerceAnalyticsStripeFees\StripeFees;

/**
 * Tests for Stripe fee helper behavior.
 */
final class StripeFeesTest extends TestCase {
	/**
	 * It normalizes common fee values.
	 */
	public function test_normalize_fee(): void {
		$this->assertSame( 1.23, StripeFees::normalize_fee( '1.23' ) );
		$this->assertSame( 1234.56, StripeFees::normalize_fee( '$1,234.56' ) );
		$this->assertSame( -2.5, StripeFees::normalize_fee( '-2.50' ) );
		$this->assertSame( 0.0, StripeFees::normalize_fee( '' ) );
		$this->assertSame( 0.0, StripeFees::normalize_fee( array( 'invalid' ) ) );
	}

	/**
	 * It sums fees from order-like objects.
	 */
	public function test_sum_order_stripe_fees(): void {
		$orders = array(
			new class() {
				public function get_meta(): string {
					return '1.25';
				}
			},
			new class() {
				public function get_meta(): string {
					return '$2.75';
				}
			},
			false,
		);

		$this->assertSame( 4.0, StripeFees::sum_order_stripe_fees( $orders ) );
	}

	/**
	 * It sets interval subtotals for array intervals.
	 */
	public function test_set_interval_stripe_fee_for_array_interval(): void {
		$interval = array(
			'date_start' => '2026-01-01 00:00:00',
			'date_end'   => '2026-01-31 23:59:59',
		);

		StripeFees::set_interval_stripe_fee( $interval, 12.34 );

		$this->assertSame( 12.34, $interval['subtotals']['stripe_fee'] );
		$this->assertSame( 12.34, StripeFees::get_interval_stripe_fee( $interval ) );
	}

	/**
	 * It sets interval subtotals for object intervals.
	 */
	public function test_set_interval_stripe_fee_for_object_interval(): void {
		$interval             = new stdClass();
		$interval->date_start = '2026-01-01 00:00:00';
		$interval->date_end   = '2026-01-31 23:59:59';

		StripeFees::set_interval_stripe_fee( $interval, 56.78 );

		$this->assertSame( 56.78, $interval->subtotals->stripe_fee );
		$this->assertSame( 56.78, StripeFees::get_interval_stripe_fee( $interval ) );
	}
}
