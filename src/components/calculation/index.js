import { applyFilters } from '@wordpress/hooks';

const Calculation = ( props ) => {
	const { attributes, setAttributes } = props;

	const calculation = applyFilters(
		'srfm.calculation.component',
		null, // Default value
		{
			attributes,
			setAttributes
		}
	);
	
	const isPro = srfm_block_data.is_pro_active;
	return <>{ isPro ? calculation : '' }</>;
};

export default Calculation;
