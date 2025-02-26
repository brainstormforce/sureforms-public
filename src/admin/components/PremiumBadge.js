import { __ } from '@wordpress/i18n';
import { addQueryParam } from '../../utils/Helpers';
import { Badge } from '@bsf/force-ui';
import Tooltip from './Tooltip';

const PremiumBadge = ( { tooltipHeading = '', tooltipContent = '', tooltipPosition = 'bottom', utmMedium = '' } ) => {
	return (
		<div className="tooltip">
			<Tooltip
				content={ (
					<div className="tooltip-content space-y-1">
						<div className="tooltip-text space-y-1">
							<p className="text-text-inverse text-xs font-medium">{ tooltipHeading }</p>
							<p className="text-text-inverse text-xs">{ tooltipContent }</p>
						</div>
						<a
							className="block w-fit tooltip-link text-link-inverse text-xs no-underline hover:no-underline"
							target="_blank"
							href={ addQueryParam( srfm_admin?.pricing_page_url || srfm_admin?.sureforms_pricing_page, utmMedium ) }
							rel="noreferrer"
						>
							{ __( 'Upgrade', 'sureforms' ) }
						</a>
					</div>
				) }
				placement={ tooltipPosition }
				interactive
			>
				<Badge
					label={ __( 'Premium', 'sureforms' ) }
					closable={ false }
					disableHover
					variant="inverse"
				/>
			</Tooltip>
		</div>
	);
};

export default PremiumBadge;
