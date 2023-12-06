import React, { useState, useEffect } from 'react';
import ICONS from '../images/icons';
import { randomNiceColor } from '@Utils/Helpers';
import { __ } from '@wordpress/i18n';
import { Link } from 'react-router-dom';
import apiFetch from '@wordpress/api-fetch';
import templatesMarkup from '../templates/templatesMarkup';

const TemplateCard = ( { templateName, templatePreview } ) => {
	const [ isHovered, setIsHovered ] = useState( false );

	const [ color, setColor ] = useState( randomNiceColor() );

	const handleMouseEnter = () => {
		setIsHovered( true );
	};

	const handleMouseLeave = () => {
		setIsHovered( false );
	};

	function toCamelCase( inputString ) {
		return inputString
			.replace( /(?:^\w|[A-Z]|\b\w)/g, function ( word, index ) {
				return index === 0 ? word.toLowerCase() : word.toUpperCase();
			} )
			.replace( /\s+/g, '' );
	}

	const handleAddNewPost = async ( templateName ) => {
		const formTemplateName = toCamelCase( templateName );

		const formData = templatesMarkup[ formTemplateName ];

		if ( '1' !== sureforms_admin.capability ) {
			console.error( 'User does not have permission to create posts' );
			return;
		}

		try {
			const response = await apiFetch( {
				path: 'sureforms/v1/create-new-form',
				method: 'POST',
				headers: {
					'Content-Type': 'text/html',
				},
				data: formData,
			} );

			if ( response.id ) {
				const postId = response.id;

				// Redirect to the newly created post
				window.location.href = `/wp-admin/post.php?post=${ postId }&action=edit`;
			} else {
				console.error(
					'Error creating sureforms_form:',
					response.message
				);
			}
		} catch ( error ) {
			console.log( error );
		}
	};

	return (
		<div
			className="srfm-ts-template-card"
			onMouseEnter={ handleMouseEnter }
			onMouseLeave={ handleMouseLeave }
		>
			<div className="srfm-tc-btn-container">
				{ isHovered && templateName !== 'Blank Form' && (
					<>
						<button
							className="srfm-tc-hover-use-btn"
							onClick={ () => handleAddNewPost( templateName ) }
						>
							{ __( 'Use Template', 'astra-addon' ) }
						</button>
						<Link
							to={ {
								pathname: 'wp-admin/admin.php',
								search: `?page=sureforms_add_new_form&method=template&template=${ templateName }`,
							} }
						>
							<button className="srfm-tc-hover-preview-btn">
								{ ICONS.eye }
								{ __( 'Preview', 'astra-addon' ) }
							</button>
						</Link>
					</>
				) }
			</div>
			<div
				className="srfm-ts-preview-container"
				style={
					templateName !== 'Blank Form'
						? { backgroundColor: color, zIndex: -1 }
						: {}
				}
			>
				{ templatePreview && (
					<img
						className="srfm-ts-preview-image"
						src={ templatePreview }
					/>
				) }
			</div>
			{ templateName === 'Blank Form' && (
				<Link
					to={ {
						pathname: 'wp-admin/post-new.php',
						search: `?post_type=sureforms_form`,
					} }
					reloadDocument
					className="srfm-ts-blank-form"
				>
					{ ICONS.plus }
				</Link>
			) }
			<div className="srfm-ts-template-name">{ templateName }</div>
		</div>
	);
};

export default TemplateCard;
