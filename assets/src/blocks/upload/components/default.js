import { faCloudArrowUp } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';
import parse from 'html-react-parser';
import svgIcons from '@Svg/svgs.json';

export const UploadComponent = ( {
	attributes,
	blockID,
	setAttributes,
} ) => {
	const { required, label, fileSizeLimit, allowedFormats } = attributes;
	const firstFive = allowedFormats.slice( 0, 5 );
	const isRequired = required ? ' srfm-required' : '';
	const uploadIcon = parse( svgIcons['upload'] );
	const slug = "upload";
	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-block-label${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			<div className="srfm-block-wrap">
				<span className={`srfm-icon srfm-${slug}-icon`}>
					{uploadIcon}
				</span>
				<div className={`srfm-${slug}-wrap`}>
					<label className={`srfm-classic-${slug}-label`}>
						<p>
							{ __(
								'Click to upload the file',
								'sureforms'
							) }
						</p>
						<input
							type="file"
							aria-required={
								required ? 'true' : 'false'
							}
							className={`srfm-input-${slug}`}
						/>
					</label>
					<p>
						<span>
							{ firstFive.length !== 0
								? firstFive.map( ( obj, index ) => {
									if ( index < 4 ) {
										if (
											firstFive.length ===
												index + 1
										) {
											return ' ' + obj.value;
										}
										return ' ' + obj.value + ',';
									} else if ( index === 4 ) {
										return ' ' + obj.value + '...';
									}
									return '';
									} )
								: 'All types' }
						</span>
						{ __( ' up to ', 'sureforms' ) }
						{ fileSizeLimit
							? `${ fileSizeLimit } MB`
							: 'Not Defined' }
					</p>
				</div>
			</div>
		</>
	);
};
