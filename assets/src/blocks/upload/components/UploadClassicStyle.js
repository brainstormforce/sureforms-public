import { faCloudArrowUp } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { __ } from '@wordpress/i18n';

export const UploadClassicStyle = ( { attributes, blockID } ) => {
	const { required, label, fileSizeLimit, allowedFormats } = attributes;
	const firstFive = allowedFormats.slice( 0, 5 );

	return (
		<>
			<div className="col-span-full">
				<label
					className="srfm-classic-label-text"
					htmlFor={ 'srfm-text-input-' + blockID }
				>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<div className="srfm-classic-upload-div">
					<div className="text-center">
						<div
							style={ { fontSize: '35px' } }
							className="text-center text-gray-300"
						>
							<FontAwesomeIcon
								style={ { margin: 'auto' } }
								height={ '45px' }
								width={ '35px' }
								icon={ faCloudArrowUp }
							/>
						</div>
						<div className="mt-2 flex text-sm leading-6 text-gray-600">
							<label className="srfm-classic-upload-label">
								<span>
									{ __(
										'Click to upload the file',
										'sureforms'
									) }
								</span>
								<input
									type="file"
									aria-required={
										required ? 'true' : 'false'
									}
									className="srfm-upload-field sr-only"
								/>
							</label>
						</div>
						<p className="mb-1 text-xs leading-5 text-gray-600">
							<span className="font-semibold">
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
			</div>
		</>
	);
};
