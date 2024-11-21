import { __ } from '@wordpress/i18n';
import { InstantFormToggle } from '../InstantForm';
import { ExternalLink } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';

const ConversationalFormSettings = () => {
	const showComponent = false;
	const switchConversationalForms = applyFilters(
		'srfm.instant_form_settings.conversational_forms',
		showComponent
	);

	return (
		<>
			{ false === switchConversationalForms
				? <div
					className='srfm-conversational-form-settings-ctn'
					>
					<div
						style={ {
							display: 'flex',
							justifyContent: 'space-between',
							alignItems: 'center',
						} }
						className='srfm-conversational-form-settings-'
					>
						<span> { __( 'Conversational Layout', 'sureforms' ) } </span>
						<span
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
						>{ __( 'Business Plan', 'sureforms' ) }</span><InstantFormToggle disabled={ true } /></div>
					<div
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
					</div>
				</div> : switchConversationalForms }
		</>
	);
};

export default ConversationalFormSettings;
