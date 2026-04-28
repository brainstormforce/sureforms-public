import { __ } from '@wordpress/i18n';
import EditorPremiumBadge from '../../admin/components/EditorPremiumBadge';
import { chevronDown, chevronUp } from '@wordpress/icons';
import { ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

const LocationServicesPreview = () => {
	const [ isExpanded, setIsExpanded ] = useState( false );
	return (
		<div className="srfm-panel-preview">
			<div
				className="components-panel__body"
				onClick={ () => {
					setIsExpanded( ! isExpanded );
				} }
			>
				<h2 className="components-panel__body-title">
					{ ' ' }
					{ __( 'Location Services', 'sureforms' ) }{ ' ' }
				</h2>
				<EditorPremiumBadge
					tooltipHeading={ __(
						'Unlock Address Autocomplete',
						'sureforms'
					) }
					tooltipContent={ __(
						'Upgrade to enable Google Address Autocomplete with interactive map preview, making address entry faster and more accurate for your users.',
						'sureforms'
					) }
					tooltipPosition={ 'bottom' }
					utmMedium={ 'editor_blocks_location_services' }
				/>
				{ ! isExpanded ? chevronDown : chevronUp }
			</div>
			{ isExpanded ? (
				<div className="components-panel__body-content">
					<ToggleControl
						label={ __(
							'Enable Google Autocomplete',
							'sureforms'
						) }
						checked={ false }
						disabled={ true }
					/>
					<ToggleControl
						label={ __(
							'Show Interactive Map',
							'sureforms'
						) }
						checked={ false }
						disabled={ true }
					/>
				</div>
			) : null }
		</div>
	);
};

export default LocationServicesPreview;
