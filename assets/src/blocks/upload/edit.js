/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
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
} from '@fortawesome/free-solid-svg-icons';

export default function Edit( { attributes, setAttributes, isSelected } ) {
	const {
		required,
		label,
		fileSizeLimit,
		allowedFormats,
		customFormats,
		help,
		id,
	} = attributes;
	const maxUploadFileSize = upload_field.upload_max_limit;
	const uploadFormats = upload_field.upload_formats;

	const wpUploadFormats = [ ...uploadFormats, ...customFormats ];

	const blockID = useBlockProps().id.split( '-' ).join( '' );
	useEffect( () => {
		if ( id !== '' ) {
			return;
		}
		setAttributes( { id: blockID } );
	}, [ blockID, id, setAttributes ] );

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
				className={
					'main-container' + ( isSelected ? ' sf--focus' : '' )
				}
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
									style={ { fontSize: '25px', marginBottom: '5px' } }
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
				{ help !== '' && (
					<label
						htmlFor={ 'upload-help-' + blockID }
						className="text-secondary"
					>
						{ help }
					</label>
				) }
			</div>
		</div>
	);
}
