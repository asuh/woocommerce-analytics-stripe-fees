/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { Dropdown } from '@wordpress/components';
import * as Woo from '@woocommerce/components';
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './index.scss';


const addTableColumn = reportTableData => {
    if ('orders' !== reportTableData.endpoint) {
        return reportTableData;
    }

    const newHeaders = [
        ...reportTableData.headers,
        {
            label: 'Stripe Fee',
            key: 'stripe_fee',
            required: false,
        },
    ];

    const newRows = reportTableData.rows.map((row, index) => {
        const item = reportTableData.items.data[index];
        const newRow = [
            ...row,
            {
                display: item.stripe_fee,
                value: item.stripe_fee,
            },
        ];
        return newRow;
    });

    reportTableData.headers = newHeaders;
    reportTableData.rows = newRows;

    return reportTableData;
};

addFilter('woocommerce_admin_report_table', 'wg-woocommerce-addon', addTableColumn);

const MyExamplePage = () => (
	<Fragment>
		<Woo.Section component="article">
			<Woo.SectionHeader
				title={ __( 'Search', 'woocommerce-analytics-stripe-fees' ) }
			/>
			<Woo.Search
				type="products"
				placeholder="Search for something"
				selected={ [] }
				onChange={ ( items ) => setInlineSelect( items ) }
				inlineTags
			/>
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader
				title={ __( 'Dropdown', 'woocommerce-analytics-stripe-fees' ) }
			/>
			<Dropdown
				renderToggle={ ( { isOpen, onToggle } ) => (
					<Woo.DropdownButton
						onClick={ onToggle }
						isOpen={ isOpen }
						labels={ [ 'Dropdown' ] }
					/>
				) }
				renderContent={ () => <p>Dropdown content here</p> }
			/>
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader
				title={ __(
					'Pill shaped container',
					'woocommerce-analytics-stripe-fees'
				) }
			/>
			<Woo.Pill className={ 'pill' }>
				{ __(
					'Pill Shape Container',
					'woocommerce-analytics-stripe-fees'
				) }
			</Woo.Pill>
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader
				title={ __( 'Spinner', 'woocommerce-analytics-stripe-fees' ) }
			/>
			<Woo.H>I am a spinner!</Woo.H>
			<Woo.Spinner />
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader
				title={ __(
					'Datepicker',
					'woocommerce-analytics-stripe-fees'
				) }
			/>
			<Woo.DatePicker
				text={ __(
					'I am a datepicker!',
					'woocommerce-analytics-stripe-fees'
				) }
				dateFormat={ 'MM/DD/YYYY' }
			/>
		</Woo.Section>
	</Fragment>
);

addFilter(
	'woocommerce_admin_pages_list',
	'analytics/woocommerce-analytics-stripe-fees',
	( pages ) => {
		// pages.push( {
		// 	container: MyExamplePage,
		// 	path: '/woocommerce-analytics-stripe-fees',
		// 	breadcrumbs: [
		// 		__(
		// 			'Woocommerce Analytics Stripe Fees',
		// 			'woocommerce-analytics-stripe-fees'
		// 		),
		// 	],
		// 	navArgs: {
		// 		id: 'woocommerce_analytics_stripe_fees',
		// 	},
		// } );

		// return pages;

		return [
			...pages,
			{
				report: '/analytics/woocommerce-analytics-stripe-fees',
				title: __('Stripe Fees', 'woocommerce-analytics-stripe-fees'),
				component: MyExamplePage
			}
		]
	}
);
