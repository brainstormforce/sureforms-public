import { useState } from 'react';
import ICONS from './icons';
import { __ } from '@wordpress/i18n';
import { Link } from 'react-router-dom';
import apiFetch from '@wordpress/api-fetch';

const TemplateCard = ( {
	templateName,
	templateId,
	templatePreview,
	formData,
	color,
} ) => {
	const [ isHovered, setIsHovered ] = useState( false );

	const handleMouseEnter = () => {
		setIsHovered( true );
	};

	const handleMouseLeave = () => {
		setIsHovered( false );
	};

	const handleAddNewPost = async () => {
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
				{ isHovered && templateId !== 'form-1' && (
					<>
						<button
							className="srfm-tc-hover-use-btn"
							onClick={ () => handleAddNewPost() }
						>
							{ __( 'Use Template', 'sureforms' ) }
						</button>
						<Link
							to={ {
								pathname: 'wp-admin/admin.php',
								search: `?page=add-new-form&method=template&template-id=${ templateId }`,
							} }
						>
							<button className="srfm-tc-hover-preview-btn">
								{ ICONS.eye }
								{ __( 'Preview', 'sureforms' ) }
							</button>
						</Link>
					</>
				) }
			</div>
			{ templatePreview && templateId !== 'form-1' ? (
				<div
					className="srfm-ts-preview-container"
					style={ { backgroundColor: color, zIndex: -1 } }
				>
					<img
						className="srfm-ts-preview-image"
						src={ templatePreview }
					/>
				</div>
			) : (
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
