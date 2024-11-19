import { __ } from '@wordpress/i18n';
import { InstantFormToggle } from '../InstantForm';
import { ExternalLink } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';

const ConversationalFormSettings = () => {
	const showSwitch = false;
	const switchConversationalForms = applyFilters(
		'srfm.instant_form_settings.conversational_forms',
		showSwitch
	);

	const isBusinessPlan = srfm_admin?.pro_plugin_name.split( ' ' )[ 1 ] === 'Business';

	return (
		<div
			style={ {
				display: 'flex',
				flexDirection: 'column',
				gap: '8px',
			} }>
			<div
				style={ {
					display: 'flex',
					justifyContent: 'space-between',
					alignItems: 'center',
				} }
			>
				<span> { __( 'Conversational Layout', 'sureforms' ) } </span>
				{!isBusinessPlan&&<span
					style={ {
						minWidth: '16px',
						maxHeight: '16px',
						padding: '2px',
						gap: '2px',
						borderRadius: '4px',
						background: '#1F2937',
						color: '#fff',
						border: '0.5px solid #374151',
						fontSize: '9px',
						fontWeight: '600',
						lineHeight: 'normal',
						textAlign: 'center',
					} }
				>{ __( 'Business Plan', 'sureforms' ) }</span>}
				{ false === switchConversationalForms ? <InstantFormToggle
					disabled={ true }
				/> : switchConversationalForms }
			</div>
			{!isBusinessPlan&&<div
				style={ {
					padding: '4px',
					borderRadius: '6px',
					background: '#F0F9FF',
					color: '#1E293B',
					border: '0.5px solid #BAE6FD',
					fontSize: '13px',
					fontWeight: '400',
					lineHeight: '20px',
					textAlign: 'left',
				} }
			>
				{ __( 'To use this feature you need to upgrade to the Business plan. ', 'sureforms' ) }
				<ExternalLink href="https://sureforms.com/pricing" target="_blank">
					{ __( 'Upgrade now', 'sureforms' ) }
				</ExternalLink>
			</div>}
		</div>
	);
};

export default ConversationalFormSettings;
