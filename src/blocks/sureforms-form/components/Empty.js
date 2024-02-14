/** @jsx jsx */
/* eslint-disable react/no-unknown-property */
import { jsx } from '@emotion/react';

/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder, Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Components
 */
import SelectForm from './SelectForm';

export default ( { setAttributes } ) => {
	const [ form, setForm ] = useState( {} );
	const [ formId, setFormId ] = useState();
	const [ value, setValue ] = useState( '' );

	return (
		<div { ...useBlockProps() }>
			<Placeholder
				icon={
					<svg
						width="128"
						height="36"
						viewBox="0 0 142 24"
						fill="none"
						xmlns="http://www.w3.org/2000/svg"
						data-type="sureforms-logo"
					>
						<path
							fill-rule="evenodd"
							clip-rule="evenodd"
							d="M29 16.6032L29.9925 15.1445C30.448 15.6702 31.0539 16.1125 31.8101 16.4717C32.5664 16.8309 33.3613 17.0105 34.1949 17.0105C35.1488 17.0105 35.8922 16.8046 36.4249 16.3928C36.9664 15.9811 37.2371 15.4424 37.2371 14.7766C37.2371 14.3561 37.091 14.0013 36.7988 13.7122C36.5066 13.4231 36.1285 13.1997 35.6644 13.042C35.2003 12.8844 34.689 12.7398 34.1305 12.6084C33.5718 12.477 33.009 12.3237 32.4418 12.1485C31.8832 11.9733 31.3719 11.7586 30.9078 11.5046C30.4437 11.2505 30.0656 10.8913 29.7734 10.4271C29.4812 9.95398 29.3352 9.39335 29.3352 8.74508C29.3352 7.67627 29.7562 6.78712 30.5984 6.07753C31.4406 5.35917 32.5965 5 34.066 5C36.0082 5 37.5808 5.63075 38.7839 6.89224L37.8687 8.28518C37.4734 7.79455 36.9406 7.40035 36.2703 7.10249C35.6085 6.79588 34.8738 6.64257 34.066 6.64257C33.2152 6.64257 32.532 6.83968 32.0164 7.2339C31.5008 7.61936 31.2429 8.10552 31.2429 8.69249C31.2429 9.06922 31.389 9.38896 31.6813 9.65179C31.9734 9.90585 32.3472 10.1073 32.8027 10.2562C33.2668 10.3964 33.7781 10.5322 34.3367 10.6636C34.9039 10.7862 35.4667 10.9396 36.0253 11.1235C36.5925 11.3075 37.1038 11.5352 37.5593 11.8068C38.0234 12.0696 38.4015 12.4507 38.6937 12.95C38.9859 13.4494 39.132 14.0407 39.132 14.724C39.132 15.8629 38.6937 16.8046 37.8171 17.5492C36.9492 18.2851 35.7246 18.6531 34.1433 18.6531C32.0035 18.6531 30.2891 17.9698 29 16.6032ZM41.2986 14.5006V5.32852H43.2709V13.883C43.2709 14.9693 43.5158 15.7359 44.0057 16.1826C44.4955 16.6206 45.2088 16.8397 46.1455 16.8397C46.8845 16.8397 47.5978 16.6513 48.2853 16.2746C48.9728 15.8979 49.5099 15.4336 49.8966 14.8817V5.32852H51.8818V18.3246H49.8966V16.498C49.3638 17.1113 48.6978 17.6237 47.8986 18.0355C47.0993 18.4472 46.2529 18.6531 45.3591 18.6531C42.6521 18.6531 41.2986 17.2689 41.2986 14.5006ZM54.7831 18.3246H56.7554V9.12613C57.0476 8.59176 57.5503 8.1143 58.2636 7.69382C58.9769 7.26456 59.6386 7.04993 60.2488 7.04993C60.4464 7.04993 60.6999 7.06745 61.0093 7.10249V5.02628C60.1843 5.02628 59.4066 5.24529 58.6761 5.68331C57.9456 6.11257 57.3054 6.68638 56.7554 7.40473V5.32852H54.7831V18.3246ZM61.6935 11.8068C61.6935 10.5628 61.9599 9.424 62.4927 8.3903C63.0342 7.34778 63.7861 6.52431 64.7486 5.91984C65.7111 5.30661 66.7896 5 67.9841 5C69.2388 5 70.3345 5.311 71.2712 5.93298C72.2077 6.55497 72.9127 7.39158 73.3855 8.44282C73.8663 9.4853 74.1071 10.6636 74.1071 11.9776V12.4901H63.8076C63.8849 13.7691 64.3318 14.8379 65.1482 15.6964C65.9732 16.5549 67.0474 16.9842 68.3708 16.9842C69.1185 16.9842 69.8361 16.8397 70.5236 16.5506C71.2197 16.2615 71.8255 15.8498 72.3413 15.3153L73.2821 16.6294C71.9329 17.9785 70.2443 18.6531 68.2161 18.6531C66.3083 18.6531 64.7443 18.0179 63.524 16.7477C62.3036 15.4774 61.6935 13.8305 61.6935 11.8068ZM63.7818 10.979H72.148C72.1394 10.4796 72.0488 9.98463 71.8771 9.49408C71.7138 9.00353 71.4689 8.54355 71.1423 8.1143C70.8158 7.68505 70.3774 7.33902 69.8275 7.07621C69.2775 6.80464 68.6544 6.66885 67.9583 6.66885C67.3052 6.66885 66.7123 6.80026 66.1795 7.06307C65.6466 7.32588 65.2126 7.67195 64.8775 8.1012C64.5509 8.52168 64.2931 8.98158 64.104 9.48091C63.915 9.97154 63.8076 10.4709 63.7818 10.979Z"
							fill="#002E33"
						/>
						<path
							d="M78.8791 18.7626H76.1199V5.56776H85.2914V8.04056H78.8791V10.8101H85.1554V13.2829H78.8791V18.7626Z"
							fill="#002E33"
						/>
						<path
							d="M93.3803 19C89.4553 19 86.5989 16.1513 86.5989 12.1751C86.5989 8.19882 89.4553 5.35016 93.3803 5.35016C97.286 5.35016 100.142 8.19882 100.142 12.1751C100.142 16.1513 97.286 19 93.3803 19ZM93.3803 16.5074C95.7704 16.5074 97.3054 14.6281 97.3054 12.1751C97.3054 9.70228 95.7704 7.84274 93.3803 7.84274C90.9709 7.84274 89.4358 9.70228 89.4358 12.1751C89.4358 14.6281 90.9709 16.5074 93.3803 16.5074Z"
							fill="#002E33"
						/>
						<path
							d="M112.591 18.7626H109.404L106.859 14.0742H104.838V18.7626H102.079V5.56776H108.141C110.842 5.56776 112.513 7.36796 112.513 9.82098C112.513 12.1355 111.056 13.4016 109.657 13.7379L112.591 18.7626ZM107.753 11.6014C108.841 11.6014 109.676 10.9288 109.676 9.80119C109.676 8.71316 108.841 8.04056 107.753 8.04056H104.838V11.6014H107.753Z"
							fill="#002E33"
						/>
						<path
							d="M128.613 18.7626H125.835V9.24729L122.182 18.7626H120.977L117.324 9.24729V18.7626H114.565V5.56776H118.431L121.579 13.817L124.727 5.56776H128.613V18.7626Z"
							fill="#002E33"
						/>
						<path
							d="M135.812 19C133.305 19 131.518 18.1494 130.293 16.9031L131.809 14.7072C132.722 15.6766 134.141 16.5074 135.928 16.5074C137.464 16.5074 138.183 15.815 138.183 15.0435C138.183 12.7488 130.721 14.3511 130.721 9.38576C130.721 7.18992 132.586 5.36994 135.637 5.36994C137.697 5.36994 139.407 6.00298 140.689 7.2097L139.154 9.30664C138.105 8.31752 136.706 7.86252 135.384 7.86252C134.219 7.86252 133.558 8.37686 133.558 9.16816C133.558 11.2453 141 9.84076 141 14.7666C141 17.18 139.29 19 135.812 19Z"
							fill="#002E33"
						/>
						<path
							fill-rule="evenodd"
							clip-rule="evenodd"
							d="M12.0078 24C18.6352 24 24.0078 18.6274 24.0078 12C24.0078 5.37259 18.6352 0 12.0078 0C5.3804 0 0.0078125 5.37259 0.0078125 12C0.0078125 18.6274 5.3804 24 12.0078 24ZM12.0595 6C11.0959 6 9.76255 6.55103 9.08115 7.23077L7.2307 9.07692H16.4543L19.5384 6H12.0595ZM14.9189 16.7692C14.2376 17.449 12.9041 18 11.9406 18H4.46169L7.54585 14.9231H16.7694L14.9189 16.7692ZM17.9166 10.6154H5.69197L5.11453 11.1923C3.74722 12.4231 4.15274 13.3846 6.0676 13.3846H18.3253L18.903 12.8077C20.257 11.5841 19.8315 10.6154 17.9166 10.6154Z"
							fill="#0E4372"
						/>
					</svg>
				}
				className="srfm-select-form-placeholder"
			>
				<div className="srfm-select-form-container">
					<SelectForm
						form={ form }
						setForm={ setForm }
						setFormId={ setFormId }
						label="title"
						id="id"
						formId={ formId }
						selectedVal={ value }
						handleChange={ ( val ) => setValue( val ) }
					/>
					<div className="srfm-select-form-button">
						<Button
							variant="primary"
							text={ __( 'Choose', 'sureforms' ) }
							onClick={ () => {
								setAttributes( { id: formId } );
							} }
						/>
						<Button
							variant="secondary"
							text={ __( 'Add New', 'sureforms' ) }
							onClick={ () => {
								// open in a new tab
								window.open( sfBlockData.template_picker_url );
								// window.location.href =
								// 	sfBlockData.template_picker_url;
							} }
						/>
					</div>
				</div>
			</Placeholder>
		</div>
	);
};
