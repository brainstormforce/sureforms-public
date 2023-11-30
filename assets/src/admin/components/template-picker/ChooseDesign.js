/* eslint-disable react/no-unknown-property */
/** @jsx jsx */
import { css, jsx } from '@emotion/react';
import { __ } from '@wordpress/i18n';
import Thumbnail from '../template-picker/Thumbnail';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import PlaceholderTemplate from '../template-picker/PlaceholderTemplate';

export default ( { templates, template, handleTemplatePicker } ) => {
	const [ choice, setChoice ] = useState( template );

	const imageCSS = css`
		margin: auto;
		height: 300px !important;
		object-fit: contain;
	`;

	return (
		<PlaceholderTemplate
			footerRight={
				<Button
					variant="primary"
					disabled={ ! choice }
					onClick={ () => {
						handleTemplatePicker( choice );
					} }
				>
					{ __( 'Select Template', 'sureforms' ) }
				</Button>
			}
		>
			<div>
				<div
					css={ css`
						display: grid;
						padding: 32px;
						flex: 1 1 0%;
						grid-gap: 32px;
						@media ( min-width: 768px ) {
							grid-template-columns: repeat( 2, 1fr );
						}
						@media ( min-width: 960px ) {
							grid-template-columns: repeat( 2, 1fr );
						}
						overflow-y: scroll;
						overflow-x: visible;
					` }
				>
					{ templates.map( ( singleTemplate, key ) => {
						const url = sfBlockData?.plugin_url;
						const name = singleTemplate.name.replace(
							'sureforms/',
							''
						);
						return (
							<Thumbnail
								key={ key }
								label={ singleTemplate?.title }
								selected={ choice === name }
								onSelect={ () => setChoice( name ) }
							>
								<img
									alt="template"
									css={ imageCSS }
									src={ `${ url }/templates/forms/${ name }.png` }
								/>
							</Thumbnail>
						);
					} ) }
				</div>
				{ /* <Button
					variant="primary"
					disabled={ ! choice }
					onClick={ () => {
						handleTemplatePicker( choice );
					} }
					style={ { position: 'fixed' } }
				>
					{ __( 'Select Template', 'sureforms' ) }
				</Button> */ }
			</div>
		</PlaceholderTemplate>
	);
};
