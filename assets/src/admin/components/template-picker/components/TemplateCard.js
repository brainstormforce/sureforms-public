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
	const [ hoverClass, setHoverClass ] = useState( '' );
	const blankImg = sureforms_admin.preview_images_url + 'blank.svg';

	const handleMouseEnter = () => {
		setHoverClass( ' hovered' );
	};

	const handleMouseLeave = () => {
		setHoverClass( '' );
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
				window.location.href = `${ sureforms_admin.site_url }/wp-admin/post.php?post=${ postId }&action=edit`;
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

			{ templatePreview && templateId !== 'form-1' ? (
				<div
					className={ `srfm-ts-preview-wrap${ hoverClass }` }
					style={ { backgroundColor: color } }
				>
					<>
						<div className="srfm-tc-btn-container">
							<button
								className="srfm-tc-hover-use-btn srfm-common-btn"
								onClick={ () => handleAddNewPost() }
							>
								{ __( 'Use Template', 'sureforms' ) }
							</button>
							<Link
								to={ {
									location: `${ sureforms_admin.site_url }/wp-admin/admin.php`,
									search: `?page=add-new-form&method=template&template-id=${ templateId }`,
								} }
							>
								<button className="srfm-tc-hover-preview-btn srfm-common-btn">
									{ __( 'Preview', 'sureforms' ) }
									{ ICONS.eye }
								</button>
							</Link>
						</div>
					</>

					<img
						className="srfm-ts-preview-image"
						src={ templatePreview }
					/>
				</div>
			) : (
				<Link
					to={ `${ sureforms_admin.site_url }/wp-admin/post-new.php?post_type=sureforms_form` }
					reloadDocument
					className="srfm-ts-blank-form"
				>
					<img
						className="srfm-ts-preview-image"
						src={ blankImg }
					/>

				</Link>
			) }
			<div className="srfm-ts-template-name">{ templateName }</div>
		</div>
	);
};

export default TemplateCard;
