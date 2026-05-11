import { __ } from '@wordpress/i18n';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch } from '@wordpress/data';
import { Title } from '@bsf/force-ui';
import TabContentWrapper from '@Components/tab-content-wrapper';
import { ComplianceFields } from '@Admin/shared-components/compliance';

const Compliance = ( { complianceData } ) => {
	const { editPost } = useDispatch( editorStore );

	/**
	 * Handle field change and update form meta.
	 *
	 * @param {string} key   - Field key to update
	 * @param {*}      value - New value
	 */
	const handleChange = ( key, value ) => {
		const updatedData = complianceData.map( ( item ) => ( {
			...item,
			[ key ]: value,
		} ) );

		editPost( {
			meta: {
				_srfm_compliance: updatedData,
			},
		} );
	};

	return (
		<TabContentWrapper className="!mt-0">
			<>
				<Title
					size="xs"
					className="mb-4"
					title={ __( 'Compliance Settings', 'sureforms' ) }
				/>
				<ComplianceFields
					context="form"
					values={ complianceData[ 0 ] }
					onChange={ handleChange }
				/>
			</>
		</TabContentWrapper>
	);
};

export default Compliance;
