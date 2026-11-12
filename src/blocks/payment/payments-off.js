/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';
import { addQueryArgs } from '@wordpress/url';
import { MdOutlinePayment as icon } from 'react-icons/md';

/**
 * Editor view of a payment field while Payments is off (Settings → Features).
 *
 * Registered with the payment block's own metadata and save, so the block's
 * attributes are parsed and saved unchanged and it works again as soon as
 * Payments is turned back on.
 */
const PaymentsOff = () => (
	<div { ...useBlockProps() }>
		<Placeholder
			icon={ icon }
			label={ __( 'Payment', 'sureforms' ) }
			instructions={ __(
				'Payments is turned off, so this field is not shown and the form does not accept submissions. Its settings are kept.',
				'sureforms'
			) }
		>
			<a
				href={ addQueryArgs( 'admin.php', {
					page: 'sureforms_form_settings',
					tab: 'features-settings',
				} ) }
				target="_blank"
				rel="noreferrer"
			>
				{ __( 'Turn on Payments in Settings → Features', 'sureforms' ) }
			</a>
		</Placeholder>
	</div>
);

export default PaymentsOff;
