import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Link } from 'react-router-dom';
import { handleAddNewPost } from '@Utils/Helpers';
import { MdLockOutline } from 'react-icons/md';

const TemplateCard = ( {
	templateName,
	templateSlug,
	templatePreview,
	formData,
	color,
	templateMetas,
	isProTemplate,
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
			{ templatePreview && templateSlug !== 'blank-form' ? (
				<div
					className={ `srfm-ts-preview-wrap${ hoverClass }` }
					style={ { backgroundColor: color } }
				>
					{ isProTemplate && (
						<div className="srfm-tc-pro-badge">
							{ __( 'PREMIUM', 'sureforms' ) }
						</div>
					) }
					<div className="srfm-tc-btn-container">
						{ isProTemplate && ! srfm_admin.is_pro_active ? (
							<button className="srfm-tc-hover-use-btn srfm-common-btn">
								<div className="srfm-tc-upgrade-to-pro-btn">
									<MdLockOutline />
									{ __( 'Upgrade To Pro', 'sureforms' ) }
								</div>
							</button>
						) : (
							<button
								className="srfm-tc-hover-use-btn srfm-common-btn"
								onClick={ () =>
									handleAddNewPost(
										formData,
										templateName,
										templateMetas
									)
								}
							>
								{ __( 'Use Template', 'sureforms' ) }
							</button>
						) }
					</div>
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
