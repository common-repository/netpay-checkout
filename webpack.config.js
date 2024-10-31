const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');
const path = require('path');

const wcDepMap = {
	'@woocommerce/blocks-registry': ['wc', 'wcBlocksRegistry'],
	'@woocommerce/settings'       : ['wc', 'wcSettings']
};

const wcHandleMap = {
	'@woocommerce/blocks-registry': 'wc-blocks-registry',
	'@woocommerce/settings'       : 'wc-settings'
};

const requestToExternal = (request) => {
	if (wcDepMap[request]) {
		return wcDepMap[request];
	}
};

const requestToHandle = (request) => {
	if (wcHandleMap[request]) {
		return wcHandleMap[request];
	}
};

// Export configuration.
module.exports = {
	...defaultConfig,
	entry: {
		'credit_card': '/includes/blocks/assets/js/netpay-credit-card.js',
		'netpay-one-click-apms': '/includes/blocks/assets/js/netpay-one-click-apms.js',
		'netpay-mobilebanking': '/includes/blocks/assets/js/netpay-mobilebanking.js',
		'netpay_installment': '/includes/blocks/assets/js/netpay-installment.js',
		'netpay_fpx': '/includes/blocks/assets/js/netpay-fpx.js',
		'netpay_atome': '/includes/blocks/assets/js/netpay-atome.js',
		'netpay_truemoney': '/includes/blocks/assets/js/netpay-truemoney.js',
		'netpay_googlepay': '/includes/blocks/assets/js/netpay-googlepay.js',
		'netpay_internetbanking': '/includes/blocks/assets/js/netpay-internetbanking.js',
		'netpay_duitnow_obw': '/includes/blocks/assets/js/netpay-duitnow-obw.js',
		'netpay_konbini': '/includes/blocks/assets/js/netpay-konbini.js',
	},
	output: {
		path: path.resolve( __dirname, 'includes/blocks/assets/js/build' ),
		filename: '[name].js',
	},
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin({
			requestToExternal,
			requestToHandle
		}),
	]
};
