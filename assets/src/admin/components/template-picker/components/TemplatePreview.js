import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import ICONS from './icons';
import { Link } from 'react-router-dom';

const TemplatePreview = ( {
	templateName,
	formData,
	info,
	templatePreview,
} ) => {
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
			className="srfm-ts-preview-container srfm-content-section">
			<div className="srfm-ts-sidebar">
				<Link
					className="srfm-ts-sidebar-back-btn"
					to={ {
						location: `${ sureforms_admin.site_url }/wp-admin/admin.php`,
						search: `?page=add-new-form&method=template`,
					} }
					>
					{ ICONS.back }
					{ __( 'Back', 'sureforms' ) }
					</Link>
				<div className="srfm-ts-preview-form-info-container">
					<div className="srfm-ts-preview-form-title">
						{ templateName }
					</div>
					<div className="srfm-ts-preview-form-info">{ info }</div>
				</div>
				<button
					className="srfm-common-btn"
					onClick={ () => handleAddNewPost() }
				>
					{ __( 'Use Template', 'sureforms' ) }
				</button>
			</div>
			<div className="srfm-ts-preview">
				{ templatePreview && (
					<div className="srfm-ts-preview-template">
						<img
							className="srfm-ts-preview-image"
							src={ templatePreview }
						/>
					</div>
				) }
			</div>
		</div>
	);
};

export default TemplatePreview;
