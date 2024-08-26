import { __ } from '@wordpress/i18n';
const SureFormsDescription = () => {
	return (
		<div className="block-editor-block-card">
			<span className="block-editor-block-icon has-colors">
				<svg
					width="24"
					height="24"
					viewBox="0 0 24 24"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
				>
					<path
						fillRule="evenodd"
						clipRule="evenodd"
						d="M12.0078 24C18.6352 24 24.0078 18.6274 24.0078 12C24.0078 5.37259 18.6352 0 12.0078 0C5.3804 0 0.0078125 5.37259 0.0078125 12C0.0078125 18.6274 5.3804 24 12.0078 24ZM12.0595 6C11.0959 6 9.76255 6.55103 9.08115 7.23077L7.2307 9.07692H16.4543L19.5384 6H12.0595ZM14.9189 16.7692C14.2376 17.449 12.9041 18 11.9406 18H4.46169L7.54585 14.9231H16.7694L14.9189 16.7692ZM17.9166 10.6154H5.69197L5.11453 11.1923C3.74722 12.4231 4.15274 13.3846 6.0676 13.3846H18.3253L18.903 12.8077C20.257 11.5841 19.8315 10.6154 17.9166 10.6154Z"
						fill="#D54407"
					/>
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
