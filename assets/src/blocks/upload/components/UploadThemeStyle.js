import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCloudArrowUp } from '@fortawesome/free-solid-svg-icons';
import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

export const UploadThemeStyle = ( { attributes, blockID, setAttributes } ) => {
	const { required, label, fileSizeLimit, allowedFormats } = attributes;
	const firstFive = allowedFormats.slice( 0, 5 );
	const isRequired = required ? 'srfm-required' : '';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-text-primary ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			<input
				required={ required }
				type="file"
				id={ 'srfm-upload-input-field-' + blockID }
				hidden
				onClick={ ( e ) => e.preventDefault() }
				accept={ allowedFormats
					.map( ( obj ) => `.${ obj.value }` )
					.join( ',' ) }
			/>
			<div
				className={ 'srfm-upload-inner-div' }
				style={ { border: '2px solid' } }
			>
				<label
					id={ 'upload-label-' + blockID }
					htmlFor={ 'srfm-upload-input-field-' + blockID }
				>
					<div
						style={ {
							display: 'flex',
							alignItems: 'center',
							marginLeft: '12px',
							marginTop: '12px',
							fontSize: '25px',
							gap: '10px',
						} }
					>
						<>
							<FontAwesomeIcon
								icon={ faCloudArrowUp }
								style={ {
									marginBottom: '5px',
								} }
								height={ '45px' }
								width={ '35px' }
							/>
							{ __( 'Click to choose the file', 'sureforms' ) }
						</>
					</div>
					<div
						style={ {
							display: 'flex',
							justifyContent: 'space-between',
							padding: '1rem',
						} }
						id={ 'upload-attributes-' + blockID }
					>
						<div
							style={ {
								display: 'flex',
								flexDirection: 'column',
							} }
						>
							<span>{ __( 'Size Limit', 'sureforms' ) }</span>
							<span>
								<strong>
									{ fileSizeLimit }
									{ __( 'MB', 'sureforms' ) }
								</strong>
							</span>
						</div>
						<div
							style={ {
								display: 'flex',
								flexDirection: 'column',
							} }
						>
							<span>{ __( 'Allowed Types', 'sureforms' ) }</span>
							<span>
								<strong>
									{ firstFive.length !== 0
										? firstFive.map(
											( obj ) => obj.value + ', '
										  ) + '...'
										: 'All types' }
								</strong>
							</span>
						</div>
					</div>
				</label>
			</div>
			<p
				hidden
				id={ 'srfm-upload-field-error-' + blockID }
				style={ { color: 'red' } }
			>
				{ __( 'File Size Exceeded The Limit', 'sureforms' ) }
			</p>
		</>
	);
};
