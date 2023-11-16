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

	return (
		<div { ...useBlockProps() }>
			<Placeholder
				icon={ icon }
				label={ __(
					'Get started by selecting a SureForm.',
					'sureforms'
				) }
				style={ {
					display: 'flex',
					alignItems: 'center',
				} }
			>
				<div
					style={ {
						display: 'flex',
						margin: 'auto',
						flexDirection: 'column',
						gap: '16px',
					} }
				>
					<SelectForm form={ form } setForm={ setForm } />
					<div style={ { textAlign: 'center' } }>
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
