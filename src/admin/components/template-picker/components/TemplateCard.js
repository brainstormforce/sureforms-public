import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Link } from 'react-router-dom';
import { handleAddNewPost } from '@Utils/Helpers';

const TemplateCard = ( {
	templateName,
	templateId,
	templatePreview,
	formData,
	color,
} ) => {
	const [ hoverClass, setHoverClass ] = useState( '' );
	const blankImg = srfm_admin.preview_images_url + 'blank.svg';

	const handleMouseEnter = () => {
		setHoverClass( ' hovered' );
	};

	const handleMouseLeave = () => {
		setHoverClass( '' );
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
								onClick={ () =>
									handleAddNewPost( formData, templateName )
								}
							>
								{ __( 'Use Template', 'sureforms' ) }
							</button>
							{ /* Might be used later */ }
							{ /* <Link
								to={ {
									location: `${ srfm_admin.site_url }/wp-admin/admin.php`,
									search: `?page=add-new-form&method=template&template-id=${ templateId }`,
								} }
							>
								<button className="srfm-tc-hover-preview-btn srfm-common-btn">
									{ __( 'Preview', 'sureforms' ) }
									{ ICONS.eye }
								</button>
							</Link> */ }
						</div>
					</>
					<img
						className="srfm-ts-preview-image"
						src={ templatePreview }
						alt={ __( 'Template preview image', 'sureforms' ) }
					/>
				</div>
			) : (
				<Link
					to={ `${ srfm_admin.site_url }/wp-admin/post-new.php?post_type=sureforms_form` }
					reloadDocument
					className="srfm-ts-blank-form"
				>
					<img
						className="srfm-ts-preview-image"
						src={ blankImg }
						alt={ __( 'Blank template image', 'sureforms' ) }
					/>
				</Link>
			) }
			<div className="srfm-ts-template-name">{ templateName }</div>
		</div>
	);
};

export default TemplateCard;
