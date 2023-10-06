import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCloudArrowUp } from '@fortawesome/free-solid-svg-icons';

export const UploadThemeStyle = ( { attributes, blockID } ) => {
	const {
		required,
		label,
		fileSizeLimit,
		allowedFormats,
	} = attributes;

	return (
		<>
            	<label
					className="sf-text-primary"
					htmlFor={ 'upload-input-field-' + blockID }
				>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<input
					required={ required }
					type="file"
					id={ 'upload-input-field-' + blockID }
					hidden
					onClick={ ( e ) => e.preventDefault() }
					accept={ allowedFormats
						.map( ( obj ) => `.${ obj.value }` )
						.join( ',' ) }
				/>
				<div
					className={ 'sureforms-upload-inner-div' }
					style={ { border: '2px solid' } }
				>
					<label
						id={ 'upload-label-' + blockID }
						htmlFor={ 'upload-input-field-' + blockID }
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
										fontSize: '25px',
										marginBottom: '5px',
									} }
								/>
								{ __(
									'Click to choose the file',
									'sureforms'
								) }
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
								<span>
									{ __( 'Allowed Types', 'sureforms' ) }
								</span>
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
					id={ 'upload-field-error-' + blockID }
					style={ { color: 'red' } }
				>
					{ __( 'File Size Exceeded The Limit', 'sureforms' ) }
				</p>
		</>
	);
};
