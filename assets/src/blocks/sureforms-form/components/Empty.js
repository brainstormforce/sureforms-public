/** @jsx jsx */
/* eslint-disable react/no-unknown-property */
import { jsx } from '@emotion/react';

/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder, Button, Icon } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import icon from '../../../../../images/Logo.js';

/**
 * Components
 */
import SelectForm from './SelectForm';

export default ( { setAttributes } ) => {
	const [ form, setForm ] = useState( {} );
	const [ formId, setFormId ] = useState();

	return (
		<div { ...useBlockProps() }>
			<Placeholder
				icon={ icon }
				label={ __(
					'Get started by selecting a SureForm.',
					'sureforms'
				) }
				className="srfm-select-form-placeholder"
			>
				<div className="srfm-select-form-container">
					<SelectForm
						form={ form }
						setForm={ setForm }
						setFormId={ setFormId }
					/>
					<div className="srfm-select-form-button">
						<Button
							variant="primary"
							onClick={ () => {
								setAttributes( { id: formId } );
							} }
						>
							{ __( 'Choose', 'sureforms' ) }
							<Icon icon={ 'arrow-right' }></Icon>
						</Button>
					</div>
				</div>
			</Placeholder>
		</div>
	);
};
