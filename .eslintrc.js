module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	rules: {
		'react/react-in-jsx-scope': 'off',
		'@wordpress/i18n-text-domain': [
			'error',
			{
				allowedTextDomain: 'woocommerce-analytics-stripe-fees',
			},
		],
	},
	settings: {
		'import/resolver': {
			node: {},
		},
	},
};
