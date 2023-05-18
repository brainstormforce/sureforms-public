/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	TextControl,
	BaseControl,
	RangeControl,
} from '@wordpress/components';

/**
 * Components dependencies
 */
import CreatableSelect from 'react-select/creatable';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
	faCloudArrowUp,
	faTrashCan,
	faFileLines,
} from '@fortawesome/free-solid-svg-icons';

export default function Edit( { attributes, setAttributes } ) {
	const {
		required,
		label,
		fileSizeLimit,
		allowedFormats,
		customFormats,
		help,
	} = attributes;
	const maxUploadFileSize = upload_field.upload_max_limit;
	const uploadFormats = upload_field.upload_formats;

	const wpUploadFormats = [ ...uploadFormats, ...customFormats ];

	const [ inputBoxHeading, setInputBoxHeading ] = useState(
		<>
			<FontAwesomeIcon
				icon={ faCloudArrowUp }
				style={ { fontSize: '25px', marginBottom: '5px' } }
			/>
			{ __( 'Click to choose the file', 'sureforms' ) }
		</>
	);

	const blockID = useBlockProps().id;

	function checkFileSizeLimit( e ) {
		const maxFileSize = fileSizeLimit * 1024 * 1024;

		const file = e.target.files[ 0 ];
		if ( file ) {
			if ( file.size > maxFileSize ) {
				e.target.value = '';
				document
					.getElementById( 'upload-field-error-' + blockID )
					.removeAttribute( 'hidden' );
			} else {
				document
					.getElementById( 'upload-field-error-' + blockID )
					.setAttribute( 'hidden', true );
				document
					.getElementById( 'upload-label-' + blockID )
					.removeAttribute( 'for' );
				document
					.getElementById( 'upload-attributes-' + blockID )
					.setAttribute( 'hidden', true );
				const fileName =
					file.name.length > 20
						? file.name.substring( 0, 17 ) +
						  '...' +
						  file.name.split( '.' ).pop()
						: file.name;
				setInputBoxHeading(
					<>
						<div
							style={ {
								display: 'flex',
								gap: '0.4rem',
								alignItems: 'center',
							} }
						>
							<FontAwesomeIcon
								icon={ faFileLines }
								style={ {
									fontSize: '25px',
									marginBottom: '5px',
								} }
							/>

							{ fileName +
								' ' +
								( file.size / 1000000 ).toFixed( 2 ) +
								'MB' }
							<FontAwesomeIcon
								icon={ faTrashCan }
								id={ 'reset-upload-field-' + blockID }
								style={ {
									fontSize: '25px',
									marginBottom: '5px',
								} }
								onClick={ () => {
									document.getElementById(
										'upload-input-field-' + blockID
									).value = '';
									document
										.getElementById(
											'upload-attributes-' + blockID
										)
										.removeAttribute( 'hidden' );
									document
										.getElementById(
											'reset-upload-field-' + blockID
										)
										.setAttribute( 'hidden', true );
									setInputBoxHeading(
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
									);
									document
										.getElementById(
											'upload-label-' + blockID
										)
										.setAttribute(
											'for',
											'upload-input-field-' + blockID
										);
								} }
							/>
						</div>
					</>
				);
			}
		}
	}

	const firstFive = allowedFormats.slice( 0, 5 );

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title="Attributes">
					<PanelRow>
						<ToggleControl
							label={ __( 'Required', 'sureforms' ) }
							checked={ required }
							onChange={ ( checked ) =>
								setAttributes( { required: checked } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Label', 'sureforms' ) }
							value={ label }
							onChange={ ( value ) =>
								setAttributes( { label: value } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<RangeControl
							label={ __( 'File Size Limit', 'sureforms' ) }
							help={ __(
								'Select the maximum file size limit of the file that can be upload',
								'sureforms'
							) }
							value={ fileSizeLimit }
							min={ 1 }
							max={ maxUploadFileSize }
							step={ 1 }
							onChange={ ( value ) =>
								setAttributes( { fileSizeLimit: value } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<BaseControl
							id="for-allowed-types"
							label={ __( 'Allowed Types', 'sureforms' ) }
							help={ __(
								'Search for the File type or you can add your custom File types.',
								'sureforms'
							) }
						>
							<CreatableSelect
								options={ wpUploadFormats.map( ( format ) => {
									return { value: format, label: format };
								} ) }
								value={ allowedFormats }
								isMulti
								isClearable
								onChange={ ( value ) => {
									setAttributes( {
										allowedFormats: [ ...value ],
									} );
								} }
							/>
						</BaseControl>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Help', 'sureforms' ) }
							value={ help }
							onChange={ ( value ) =>
								setAttributes( { help: value } )
							}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '0.5rem',
				} }
			>
				<label htmlFor={ 'upload-input-field-' + blockID }>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				{ help !== '' && (
					<label
						htmlFor={ 'upload-help-' + blockID }
						style={ { color: '#ddd' } }
					>
						{ help }
					</label>
				) }
				<input
					required={ required }
					type="file"
					id={ 'upload-input-field-' + blockID }
					hidden
					onChange={ ( e ) => checkFileSizeLimit( e ) }
					accept={ allowedFormats
						.map( ( obj ) => `.${ obj.value }` )
						.join( ',' ) }
				/>
				<div style={ { border: '1px solid black' } }>
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
							{ inputBoxHeading }
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
			</div>
		</div>
	);
}
