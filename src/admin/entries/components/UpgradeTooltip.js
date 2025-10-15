import { __ } from '@wordpress/i18n';
import { addQueryParam } from '@Utils/Helpers';
import Tooltip from '@Admin/components/Tooltip';

/**
 * Show upgrade message via tooltip for PRO features
 *
 * @param {Object}                    props           - Props object
 * @param {string}                    props.heading   - Tooltip heading text
 * @param {string}                    props.content   - Tooltip content text
 * @param {string}                    props.placement - Tooltip position (top, bottom, left, right)
 * @param {string}                    props.utmMedium - UTM medium for tracking
 * @param {import('react').ReactNode} props.children  - Child components to wrap with the tooltip
 * @return {JSX.Element} UpgradeTooltip component
 */
const UpgradeTooltip = ( { heading, content, placement, utmMedium = '', children } ) => {
	return (
		<Tooltip
			className="z-999999 max-w-[268px]"
			content={
				<div className="tooltip-content space-y-1">
					<div className="tooltip-text space-y-1">
						<p className="text-text-inverse text-xs font-semibold">
							{ heading }
						</p>
						<p className="text-text-inverse text-xs">
							{ content }
						</p>
					</div>
					<a
						className="block w-fit tooltip-link text-link-inverse text-xs font-semibold no-underline hover:no-underline"
						target="_blank"
						href={ addQueryParam(
							srfm_admin?.pricing_page_url ||
									srfm_admin?.sureforms_pricing_page,
							utmMedium
						) }
						rel="noreferrer"
					>
						{ __( 'Upgrade', 'sureforms' ) }
					</a>
				</div>
			}
			placement={ placement }
			interactive
		>
			{ children }
		</Tooltip>
	);
};

export default UpgradeTooltip;
