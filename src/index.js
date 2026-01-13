/**
 * External dependencies
 */

import * as Woo from "@woocommerce/components";
import { Dropdown } from "@wordpress/components";
import { Fragment } from "@wordpress/element";
import { addFilter } from "@wordpress/hooks";
import { __ } from "@wordpress/i18n";

/**
 * Internal dependencies
 */
import "./index.scss";

/**
 * Add Stripe Fee column to the Orders report table.
 *
 * @param {Object} reportTableData - Table data object containing headers, rows, and items.
 * @return {Object} Modified table data with Stripe Fee column added.
 */
const addTableColumn = (reportTableData) => {
	if ("orders" !== reportTableData.endpoint) {
		return reportTableData;
	}

	// Add the Stripe Fee header
	const newHeaders = [
		...reportTableData.headers,
		{
			label: __("Stripe Fee", "woocommerce-analytics-stripe-fees"),
			key: "stripe_fee",
			required: false,
			isSortable: false,
			isNumeric: true,
		},
	];

	// Add the Stripe Fee data to each row
	const newRows = reportTableData.rows.map((row, index) => {
		const item = reportTableData.items.data[index];
		const stripeFee = item?.stripe_fee ?? "";
		const newRow = [
			...row,
			{
				display: stripeFee ? `$${stripeFee}` : "-",
				value: stripeFee || 0,
			},
		];
		return newRow;
	});

	reportTableData.headers = newHeaders;
	reportTableData.rows = newRows;

	return reportTableData;
};

addFilter(
	"woocommerce_admin_report_table",
	"woocommerce-analytics-stripe-fees",
	addTableColumn,
);

/**
 * Add Stripe Fee chart option to the Revenue report.
 *
 * @param {Array} charts - Array of chart configurations.
 * @return {Array} Modified charts array with Stripe Fee chart added.
 */
const addStripeFeeChart = (charts) => {
	return [
		...charts,
		{
			key: "stripe_fee",
			label: __("Stripe Fees", "woocommerce-analytics-stripe-fees"),
			order: "desc",
			orderby: "stripe_fee",
			type: "currency",
			isReverseTrend: true, // Lower fees are better
		},
	];
};

addFilter(
	"woocommerce_admin_revenue_report_charts",
	"woocommerce-analytics-stripe-fees",
	addStripeFeeChart,
);

/**
 * Add Stripe Fee column to the Revenue report table.
 *
 * @param {Object} reportTableData - Table data object.
 * @return {Object} Modified table data with Stripe Fee column.
 */
const addRevenueTableColumn = (reportTableData) => {
	if ("revenue" !== reportTableData.endpoint) {
		return reportTableData;
	}

	// Add the Stripe Fee header
	const newHeaders = [
		...reportTableData.headers,
		{
			label: __("Stripe Fees", "woocommerce-analytics-stripe-fees"),
			key: "stripe_fee",
			required: false,
			isSortable: true,
			isNumeric: true,
		},
	];

	// Add the Stripe Fee data to each row
	const newRows = reportTableData.rows.map((row, index) => {
		const item = reportTableData.items.data[index];
		const stripeFee = item?.subtotals?.stripe_fee ?? 0;
		const newRow = [
			...row,
			{
				display: stripeFee ? `$${stripeFee.toFixed(2)}` : "-",
				value: stripeFee,
			},
		];
		return newRow;
	});

	reportTableData.headers = newHeaders;
	reportTableData.rows = newRows;

	return reportTableData;
};

addFilter(
	"woocommerce_admin_report_table",
	"woocommerce-analytics-stripe-fees-revenue",
	addRevenueTableColumn,
);

const MyExamplePage = () => (
	<Fragment>
		<Woo.Section component="article">
			<Woo.SectionHeader
				title={__("Search", "woocommerce-analytics-stripe-fees")}
			/>
			<Woo.Search
				type="products"
				placeholder="Search for something"
				selected={[]}
				onChange={(items) => setInlineSelect(items)}
				inlineTags
			/>
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader
				title={__("Dropdown", "woocommerce-analytics-stripe-fees")}
			/>
			<Dropdown
				renderToggle={({ isOpen, onToggle }) => (
					<Woo.DropdownButton
						onClick={onToggle}
						isOpen={isOpen}
						labels={["Dropdown"]}
					/>
				)}
				renderContent={() => <p>Dropdown content here</p>}
			/>
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader
				title={__("Pill shaped container", "woocommerce-analytics-stripe-fees")}
			/>
			<Woo.Pill className={"pill"}>
				{__("Pill Shape Container", "woocommerce-analytics-stripe-fees")}
			</Woo.Pill>
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader
				title={__("Spinner", "woocommerce-analytics-stripe-fees")}
			/>
			<Woo.H>I am a spinner!</Woo.H>
			<Woo.Spinner />
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader
				title={__("Datepicker", "woocommerce-analytics-stripe-fees")}
			/>
			<Woo.DatePicker
				text={__("I am a datepicker!", "woocommerce-analytics-stripe-fees")}
				dateFormat={"MM/DD/YYYY"}
			/>
		</Woo.Section>
	</Fragment>
);

addFilter(
	"woocommerce_admin_pages_list",
	"analytics/woocommerce-analytics-stripe-fee",
	(pages) => {
		return [
			...pages,
			{
				container: MyExamplePage,
				path: "/analytics/woocommerce-analytics-stripe-fee",
				breadcrumbs: [
					__("Analytics", "woocommerce-analytics-stripe-fees"),
					__("Stripe Fees", "woocommerce-analytics-stripe-fees"),
				],
				navArgs: {
					id: "woocommerce-analytics-stripe-fee",
				},
			},
		];
	},
);
