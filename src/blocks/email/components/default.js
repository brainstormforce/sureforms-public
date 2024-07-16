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
	} = attributes;

	const slug = 'email';

	const isRequired = required ? ' srfm-required' : '';
	return (
		<>
			<div className={ `srfm-block srfm-${ slug }-block` }>
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
				<div className="srfm-block-wrap">
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
				<div className={ `srfm-block srfm-${ slug }-confirm-block` }>
					<label
						className={ `srfm-block-label${ isRequired }` }
						htmlFor={ `srfm-${ slug }-confirm-${ blockID }` }
					>
						{ confirmLabel }
					</label>
					<div className="srfm-block-wrap">
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
