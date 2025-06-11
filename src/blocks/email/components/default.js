import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';
import HelpText from '@Components/misc/HelpText';

export const EmailComponent = ( { attributes, blockID, setAttributes } ) => {
	const {
		label,
		placeholder,
		required,
		defaultValue,
		isConfirmEmail,
		confirmLabel,
		help,
		readOnly,
	} = attributes;

	const slug = 'email';

	const isRequired = required ? ' srfm-required' : '';
	return (
		<>
			<div className={ `srfm-${ slug }-block` }>
				<RichText
					tagName="label"
					value={ label }
					onChange={ ( value ) => {
						setAttributes( { label: decodeHtmlEntities( value ) } );
					} }
					className={ `srfm-block-label${ isRequired }` }
					multiline={ false }
					id={ blockID }
					allowedFormats={ [] }
				/>
				<HelpText
					help={ help }
					setAttributes={ setAttributes }
					block_id={ blockID }
				/>
				<div className={ `srfm-block-wrap${ readOnly ? ' srfm-read-only' : '' }` }>
					<input
						id={ `srfm-${ slug }-${ blockID }` }
						type="email"
						value={ defaultValue }
						className={ `srfm-input-common srfm-input-${ slug }` }
						placeholder={ placeholder }
						required={ required }
					/>
				</div>
				<div className="srfm-error-wrap"></div>
			</div>

			{ isConfirmEmail && (
				<div className={ `srfm-${ slug }-confirm-block` }>
					<RichText
						tagName="label"
						value={ confirmLabel }
						onChange={ ( value ) => {
							setAttributes( {
								confirmLabel: decodeHtmlEntities( value ),
							} );
						} }
						className={ `srfm-block-label${ isRequired }` }
						multiline={ false }
						id={ blockID }
						allowedFormats={ [] }
					/>
					<div className={ `srfm-block-wrap${ readOnly ? ' srfm-read-only' : '' }` }>
						<input
							id={ `srfm-${ slug }-confirm-${ blockID }` }
							type="email"
							value={ defaultValue }
							className={ `srfm-input-common srfm-${ slug }-email-confirm` }
							placeholder={ placeholder }
							required={ required }
						/>
					</div>
					<div className="srfm-error-wrap"></div>
				</div>
			) }
		</>
	);
};
