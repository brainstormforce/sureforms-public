import { __ } from '@wordpress/i18n';
import ICONS from './icons';
import { Link } from 'react-router-dom';
import { handleAddNewPost } from '@Utils/Helpers';

const TemplatePreview = ( {
	templateName,
	formData,
	info,
	templatePreview,
} ) => {
	return (
		<div className="srfm-ts-preview-container srfm-content-section">
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
					onClick={ () => handleAddNewPost( formData, templateName ) }
				>
					{ __( 'Use Template', 'sureforms' ) }
				</button>
			</div>
			<div className="srfm-ts-preview">
				{ templatePreview && (
					<div className="srfm-ts-preview-template">
						<img
							alt={ __( 'Template preview image', 'sureforms' ) }
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
