import { __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import CreditCardPaymentMethod from './credit_card/credit-card';

const settings = getSetting( 'netpay_data', {} )
const defaultLabel = __( 'Credit/Debit card', 'netpay' );
const label = decodeEntities( settings.title ) || defaultLabel;
window.NETPAY_CUSTOM_FONT_OTHER = 'Other';

const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components
	return <PaymentMethodLabel text={ label } />
}

const Content = (props) => {
	return <CreditCardPaymentMethod
		{...props}
		settings={settings}
	/>
}

registerPaymentMethod({
	name: settings.name || "",
	label: <Label />,
	content: <Content settings={settings} />,
	edit: <Content settings={settings} />,
	canMakePayment: () => settings.is_active,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	}
})
