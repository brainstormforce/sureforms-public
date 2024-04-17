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
	isPro,
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
					{ isPro && (
						<div
							style={ {
								background: '#ffc107',
								color: '#000',
								padding: '1px 10px',
								fontWeight: '500',
								fontSize: '0.6875em',
								top: '-10px',
								right: '-10px',
								textTransform: 'uppercase',
								position: 'absolute',
								zIndex: '1',
								borderRadius: '6px',
								letterSpacing: '.3px',
							} }
						>
							{ __( 'PREMIUM', 'sureforms' ) }
						</div>
					) }
					<div className="srfm-tc-btn-container">
						{ isPro && ! srfm_admin.is_pro_active ? (
							<button className="srfm-tc-hover-use-btn srfm-common-btn">
								<div
									style={ {
										display: 'flex',
										alignItems: 'center',
										justifyContent: 'center',
										gap: '5px',
									} }
								>
									<MdLockOutline />
									{ __( 'Upgrade to pro', 'sureforms' ) }
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
