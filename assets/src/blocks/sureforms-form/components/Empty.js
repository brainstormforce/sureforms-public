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
	const [ value, setValue ] = useState( '' );

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
						label="title"
						id="id"
						selectedVal={ value }
						handleChange={ ( val ) => setValue( val ) }
					/>
					<div className="srfm-select-form-button">
						<Button
							variant="primary"
							onClick={ () => {
								setAttributes( { id: form?.id } );
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
