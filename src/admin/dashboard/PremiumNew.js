import { __ } from '@wordpress/i18n';
import { Badge, Tooltip } from '@bsf/force-ui';
import { LockKeyhole } from 'lucide-react';
import { addQueryParam } from '../../utils/Helpers';

export default ( { title, description, btnText, placement = 'bottom', arrow = true, interactive = true, portalId = '', utmMedium = '' } ) => {
	const tooltipContent = (
		<div>
			<div>
				{ description }
			</div>
			<div>
				<a
					href={ addQueryParam( srfm_admin?.pricing_page_url || srfm_admin?.sureforms_pricing_page, utmMedium ) }
					target="_blank"
					className="no-underline text-link-premium-badge hover:no-underline focus:no-underline hover:text-link-premium-badge focus:text-link-premium-badge"
					rel="noreferrer"
				>
					{ btnText }
				</a>
			</div>
		</div>
	);

	return (
		<Tooltip
			title={ title }
			content={ tooltipContent }
			placement={ placement }
			triggers={ [
				'hover',
				'focus',
			] }
			variant="dark"
			className="cursor-pointer"
			interactive={ interactive }
			arrow={ arrow }
			tooltipPortalId={ portalId }
		>
			<Badge
				icon={ <LockKeyhole /> }
				label={ __( 'Premium', 'sureforms' ) }
				size="sm"
				type="pill"
				variant="inverse"
			/>
		</Tooltip>
	);
};
