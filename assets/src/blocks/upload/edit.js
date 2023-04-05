/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	BaseControl,
	RangeControl,
	Icon,
} from '@wordpress/components';

/**
 * Components dependencies
 */
import CreatableSelect from 'react-select/creatable';

export default function Edit( { attributes, setAttributes } ) {
	const { fileSizeLimit, allowedFormats, customFormats } = attributes;

	const maxUploadFileSize = upload_field.upload_max_limit; //es-lint-disable no-undef
	const uploadFormats = upload_field.upload_formats; //es-lint-disable no-undef

	const wpUploadFormats = [ ...uploadFormats, ...customFormats ];

	const [ inputBoxHeading, setInputBoxHeading ] = useState(
		<>
			<Icon
				icon="upload"
				style={ { fontSize: '25px', marginBottom: '5px' } }
			/>
			Click to choose the file
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
				document
					.getElementById( 'reset-upload-field-' + blockID )
					.removeAttribute( 'hidden' );
				setInputBoxHeading(
					<>
						<div
							style={ {
								display: 'flex',
								gap: '0.4rem',
								alignItems: 'center',
							} }
						>
							<Icon
								icon="text-page"
								style={ {
									fontSize: '25px',
									marginBottom: '5px',
								} }
							/>
							{ file.name +
								' ' +
								( file.size / 1000000 ).toFixed( 2 ) +
								'MB' }
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
				<PanelBody title="Upload Field Settings">
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
				</PanelBody>
			</InspectorControls>
			<input
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
						<Icon
							id={ 'reset-upload-field-' + blockID }
							hidden
							icon="trash"
							style={ { fontSize: '25px', marginBottom: '5px' } }
							onClick={ () => {
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
										<Icon
											icon="upload"
											style={ {
												fontSize: '25px',
												marginBottom: '5px',
											} }
										/>
										Click to choose the file
									</>
								);
								document
									.getElementById( 'upload-label-' + blockID )
									.setAttribute(
										'for',
										'upload-input-field-' + blockID
									);
							} }
						/>
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
							<span>Size Limit</span>
							<span>
								<strong>{ fileSizeLimit }MB</strong>
							</span>
						</div>
						<div
							style={ {
								display: 'flex',
								flexDirection: 'column',
							} }
						>
							<span>Allowed Types</span>
							<span>
								{ }
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
				File Size Exceeded The Limit
			</p>
		</div>
	);
}
