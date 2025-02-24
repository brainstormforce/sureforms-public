import { __ } from '@wordpress/i18n';
import { Badge, Tooltip } from '@bsf/force-ui';
import { LockKeyhole } from 'lucide-react';

export default ( { title, description, btnText, placement = 'bottom', arrow = true, interactive = true, portalId = 'srfm-add-new-form-container' } ) => {
	const tooltipContent = (
		<div>
			<div
				className="text-sm text-text-secondary font-normal"
			>
				{ description }
			</div>
			<div className="mt-2">
				<a
					href={ srfm_admin?.pricing_page_url }
					target="_blank"
					className="text-xs font-semibold no-underline text-link-premium-badge hover:no-underline focus:no-underline hover:text-link-premium-badge focus:text-link-premium-badge"
					rel="noreferrer"
				>
					{ btnText }
				</a>
			</div>
		</div>
	);

	const tooltipTitle =
		<div
			className="text-lg font-semibold"
		>
			<span>{ title }</span>
		</div>;

	return (
		<Tooltip
			title={ tooltipTitle }
			content={ tooltipContent }
			placement={ placement }
			triggers={ [
				'hover',
				'focus',
			] }
			variant="dark"
			className="cursor-pointer shadow-sm-blur-2"
			interactive={ interactive }
			arrow={ arrow }
			tooltipPortalId={ portalId }
		>
			<Badge
				icon={ <LockKeyhole /> }
				label={ __( 'Premium', 'sureforms' ) }
				size="md"
				type="pill"
				variant="inverse"
				className="cursor-pointer font-semibold text-sm"
			/>
		</Tooltip>
	);
};
