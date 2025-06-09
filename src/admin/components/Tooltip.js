import { Tooltip as TooltipComponent } from '@bsf/force-ui';

const Tooltip = ( {
	title,
	content,
	placement = 'bottom',
	arrow = true,
	interactive = false,
	portalId = 'srfm-settings-container',
	children,
	...props
} ) => {
	if ( ! children ) {
		return null;
	}

	return (
		<TooltipComponent
			title={ title }
			content={ content }
			placement={ placement }
			arrow={ arrow }
			interactive={ interactive }
			tooltipPortalId={ portalId }
			{ ...props }
		>
			{ children }
		</TooltipComponent>
	);
};

export default Tooltip;
