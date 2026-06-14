/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

const toNumber = ( value ) => {
	if ( typeof value === 'number' ) {
		return Number.isFinite( value ) ? value : 0;
	}

	if ( typeof value !== 'string' ) {
		return 0;
	}

	const normalized = value.replace( /[^0-9.-]/g, '' );
	const parsed = Number.parseFloat( normalized );

	return Number.isFinite( parsed ) ? parsed : 0;
};

const getCurrencyConfig = () => {
	const settings =
		typeof wcSettings !== 'undefined' && wcSettings.currency
			? wcSettings.currency
			: {};

	return {
		symbol: settings.symbol || settings.currency_symbol || '$',
		symbolPosition:
			settings.symbolPosition || settings.currency_pos || 'left',
		decimalSeparator:
			settings.decimalSeparator || settings.decimal_separator || '.',
		thousandSeparator:
			settings.thousandSeparator || settings.thousand_separator || ',',
		precision: Number.parseInt(
			settings.precision || settings.price_num_decimals || 2,
			10
		),
	};
};

const addThousandsSeparators = ( value, separator ) =>
	value.replace( /\B(?=(\d{3})+(?!\d))/g, separator );

const formatCurrency = ( value, emptyValue = '-' ) => {
	const numericValue = toNumber( value );

	if ( numericValue === 0 ) {
		return emptyValue;
	}

	const {
		symbol,
		symbolPosition,
		decimalSeparator,
		thousandSeparator,
		precision,
	} = getCurrencyConfig();
	const sign = numericValue < 0 ? '-' : '';
	const fixedValue = Math.abs( numericValue ).toFixed( precision );
	const [ integerPart, decimalPart ] = fixedValue.split( '.' );
	const amount = `${ addThousandsSeparators(
		integerPart,
		thousandSeparator
	) }${ precision > 0 ? decimalSeparator + decimalPart : '' }`;

	switch ( symbolPosition ) {
		case 'right':
			return `${ sign }${ amount }${ symbol }`;
		case 'right_space':
			return `${ sign }${ amount } ${ symbol }`;
		case 'left_space':
			return `${ sign }${ symbol } ${ amount }`;
		case 'left':
		default:
			return `${ sign }${ symbol }${ amount }`;
	}
};

const hasHeader = ( headers, key ) =>
	headers.some( ( header ) => header.key === key );

const getHeaderIndex = ( headers, key ) =>
	headers.findIndex( ( header ) => header.key === key );

const addMoneyColumns = ( reportTableData, config ) => {
	const { endpoint, baseTotalKey, feeValue } = config;

	if (
		reportTableData.endpoint !== endpoint ||
		! Array.isArray( reportTableData.headers ) ||
		! Array.isArray( reportTableData.rows )
	) {
		return reportTableData;
	}

	if (
		hasHeader( reportTableData.headers, 'stripe_fee' ) ||
		hasHeader( reportTableData.headers, 'net_after_fees' )
	) {
		return reportTableData;
	}

	const baseTotalIndex = getHeaderIndex(
		reportTableData.headers,
		baseTotalKey
	);
	const items = reportTableData.items?.data || [];

	return {
		...reportTableData,
		headers: [
			...reportTableData.headers,
			{
				label: __( 'Stripe Fees', 'woocommerce-analytics-stripe-fees' ),
				key: 'stripe_fee',
				required: false,
				isSortable: false,
				isNumeric: true,
			},
			{
				label: __(
					'Net After Fees',
					'woocommerce-analytics-stripe-fees'
				),
				key: 'net_after_fees',
				required: false,
				isSortable: false,
				isNumeric: true,
			},
		],
		rows: reportTableData.rows.map( ( row, index ) => {
			const item = items[ index ] || {};
			const stripeFee = toNumber( feeValue( item ) );
			const baseTotal =
				baseTotalIndex >= 0
					? toNumber( row[ baseTotalIndex ]?.value )
					: 0;
			const netAfterFees = baseTotal - stripeFee;

			return [
				...row,
				{
					display: formatCurrency( stripeFee ),
					value: stripeFee,
				},
				{
					display: formatCurrency( netAfterFees ),
					value: netAfterFees,
				},
			];
		} ),
	};
};

const addOrdersTableColumns = ( reportTableData ) =>
	addMoneyColumns( reportTableData, {
		endpoint: 'orders',
		baseTotalKey: 'net_total',
		feeValue: ( item ) => item?.stripe_fee,
	} );

addFilter(
	'woocommerce_admin_report_table',
	'woocommerce-analytics-stripe-fees-orders',
	addOrdersTableColumns
);

const addRevenueTableColumns = ( reportTableData ) =>
	addMoneyColumns( reportTableData, {
		endpoint: 'revenue',
		baseTotalKey: 'net_revenue',
		feeValue: ( item ) => item?.subtotals?.stripe_fee,
	} );

addFilter(
	'woocommerce_admin_report_table',
	'woocommerce-analytics-stripe-fees-revenue',
	addRevenueTableColumns
);

const stripeFeeChart = {
	key: 'stripe_fee',
	label: __( 'Stripe Fees', 'woocommerce-analytics-stripe-fees' ),
	order: 'desc',
	orderby: 'stripe_fee',
	type: 'currency',
	isReverseTrend: true,
};

const hasStripeFeeChart = ( charts ) =>
	charts.some( ( chart ) => chart.key === 'stripe_fee' );

const addStripeFeeChart = ( charts ) => {
	if ( hasStripeFeeChart( charts ) ) {
		return charts;
	}

	return [ ...charts, stripeFeeChart ];
};

addFilter(
	'woocommerce_admin_revenue_report_charts',
	'woocommerce-analytics-stripe-fees-revenue-chart',
	addStripeFeeChart
);

const addDashboardStripeFeeChart = ( charts ) => {
	if ( hasStripeFeeChart( charts ) ) {
		return charts;
	}

	return [
		...charts,
		{
			...stripeFeeChart,
			endpoint: 'revenue',
		},
	];
};

addFilter(
	'woocommerce_admin_dashboard_charts_filter',
	'woocommerce-analytics-stripe-fees-dashboard-chart',
	addDashboardStripeFeeChart
);
