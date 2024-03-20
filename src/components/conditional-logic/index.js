import { applyFilters } from '@wordpress/hooks';

const ConditionalLogic = ( props ) => {
	const { attributes, setAttributes } = props;
	const conditionalSettings = applyFilters(
		'srfm.conditional_logic.tab_advance',
		attributes,
		setAttributes
	);
	const isPro = srfm_block_data.is_pro_active;
	return <>{ isPro ? conditionalSettings : '' }</>;
};

export default ConditionalLogic;
