import { __ } from '@wordpress/i18n';

export const FieldsPreview = ( { fieldName } ) => {
	return (
		<div style={ { textAlign: 'center' } }>
			<img
				src={ fieldName }
				alt={ __( 'Field preview', 'sureforms' ) }
				style={ {
					height: '415px',
					width: 'auto',
					textAlign: 'center',
				} }
			/>
		</div>
	);
};
