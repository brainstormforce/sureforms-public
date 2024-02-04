/** @jsx jsx */
/* eslint-disable react/no-unknown-property */
import { jsx } from '@emotion/react';

/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder, Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import icon from '@Image/Logo.js';

/**
 * Components
 */
import SelectForm from './SelectForm';

export default ( { setAttributes } ) => {
	const [ form, setForm ] = useState( {} );
	const [ formId, setFormId ] = useState();
	const [ value, setValue ] = useState( '' );

	return (
		<div { ...useBlockProps() }>
			<Placeholder
				icon={ icon }
				label={ __( 'Get Started by Selecting a Form.', 'sureforms' ) }
				className="srfm-select-form-placeholder"
			>
				<div className="srfm-select-form-container">
					<SelectForm
						form={ form }
						setForm={ setForm }
						setFormId={ setFormId }
						label="title"
						id="id"
						formId={ formId }
						selectedVal={ value }
						handleChange={ ( val ) => setValue( val ) }
					/>
					<div className="srfm-select-form-button">
						<Button
							variant="primary"
							text={ __( 'Choose', 'sureforms' ) }
							onClick={ () => {
								setAttributes( { id: formId } );
							} }
						/>
						<Button
							variant="secondary"
							text={ __( 'Add New', 'sureforms' ) }
							onClick={ () => {
								window.location.href =
									sfBlockData.template_picker_url;
							} }
						/>
					</div>
				</div>
			</Placeholder>
		</div>
	);
};
