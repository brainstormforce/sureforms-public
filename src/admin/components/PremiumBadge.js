import { __ } from '@wordpress/i18n';
import { Badge, Tooltip, Label } from '@bsf/force-ui';
import { LockKeyhole } from 'lucide-react';
import { addQueryParam } from '../../utils/Helpers';

export default ( {
	tooltipHeading,
	tooltipContent,
	btnText = __( 'Upgrade', 'sureforms' ),
	tooltipPosition = 'bottom',
	arrow = true,
	interactive = true,
	portalId = '',
	utmMedium = '' } ) => {
	const tooltipDescription = (
		<div>
			<div className="mt-1 font-normal">
				{ tooltipContent }
			</div>
			<a
				href={ addQueryParam( srfm_admin?.pricing_page_url || srfm_admin?.sureforms_pricing_page, utmMedium ) }
				target="_blank"
				className="font-semibold my-1 block no-underline text-link-premium-badge hover:no-underline focus:no-underline hover:text-link-premium-badge focus:text-link-premium-badge"
				rel="noreferrer"
			>
				{ btnText }
			</a>
		</div>
	);

	return (
		<Tooltip
			title={ tooltipHeading }
			content={ tooltipDescription }
			placement={ tooltipPosition }
			triggers={ [
				'hover',
				'focus',
			] }
			variant="dark"
			className="cursor-pointer text-xs"
			interactive={ interactive }
			arrow={ arrow }
			tooltipPortalId={ portalId }
		>
			<Label
				variant="neutral"
				className="cursor-pointer"
				onClick={ ( e ) => {
					e.stopPropagation();
					window.open( addQueryParam( srfm_admin?.pricing_page_url || srfm_admin?.sureforms_pricing_page, utmMedium ), '_blank', 'noreferrer' ); // both srfm_admin are in different js file context one is being loaded in Dashboard and other in Form Editor
				}
				}
			>
				<Badge
					icon={ <LockKeyhole /> }
					label={ __( 'Premium', 'sureforms' ) }
					size="sm"
					type="pill"
					variant="inverse"
					className="cursor-pointer !text-white !font-normal !text-sm"
				/>
			</Label>
		</Tooltip>
	);
};
