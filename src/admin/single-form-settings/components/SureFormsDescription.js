import { __ } from '@wordpress/i18n';
const SureFormsDescription = () => {
	return (
		<div className="block-editor-block-card">
			<span className="block-editor-block-icon has-colors">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<g clipPath="url(#clip0_15961_16933)">
						<rect width="24" height="24" fill="#D54407" />
						<path d="M6.85742 5.14258H17.1431V8.57115H8.57171L6.85742 10.2854V8.57115V5.14258Z" fill="white" />
						<path d="M6.85742 5.14258H17.1431V8.57115H8.57171L6.85742 10.2854V8.57115V5.14258Z" fill="white" />
						<path d="M6.85742 10.2852H15.4288V13.7137H8.57171L6.85742 15.428V13.7137V10.2852Z" fill="white" />
						<path d="M6.85742 10.2852H15.4288V13.7137H8.57171L6.85742 15.428V13.7137V10.2852Z" fill="white" />
						<path d="M6.85742 15.4287H12.0003V18.8573H6.85742V15.4287Z" fill="white" />
						<path d="M6.85742 15.4287H12.0003V18.8573H6.85742V15.4287Z" fill="white" />
					</g>
					<defs>
						<clipPath id="clip0_15961_16933">
							<rect width="24" height="24" fill="white" />
						</clipPath>
					</defs>
				</svg>
			</span>
			<div className="block-editor-block-card__content">
				<h2 className="block-editor-block-card__title">
					{ __( 'SureForms', 'sureforms' ) }
				</h2>
				<span className="block-editor-block-card__description">
					{ __( 'Customize with SureForms', 'sureforms' ) }
				</span>
			</div>
		</div>
	);
};

export default SureFormsDescription;
