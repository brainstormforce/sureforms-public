/* eslint-disable react/no-unknown-property */
/** @jsx jsx */
import { css, jsx } from '@emotion/react';
import { __ } from '@wordpress/i18n';
import Thumbnail from './Thumbnail';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import PlaceholderTemplate from './PlaceholderTemplate';
const { dispatch } = wp.data;

export default ( { templates, template, setTemplate } ) => {
	const [ choice, setChoice ] = useState( template );

	const imageCSS = css`
		margin: auto;
		height: 300px !important;
		object-fit: contain;
	`;

	// Force Form panel open.
	const forcePanel = () => {
		dispatch( 'core/edit-post' ).openGeneralSidebar( 'edit-post/document' );
	};

	return (
		<PlaceholderTemplate
			header={ __( 'Choose A Starting Template', 'sureforms' ) }
			footerRight={
				<Button
					variant="primary"
					disabled={ ! choice }
					onClick={ () => {
						forcePanel();
						setTemplate( choice );
					} }
				>
					{ __( 'Select Template', 'sureforms' ) }
				</Button>
			}
			maxHeight={ '300px' }
		>
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
		</PlaceholderTemplate>
	);
};
