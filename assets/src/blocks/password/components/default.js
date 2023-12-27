import { RichText } from '@wordpress/block-editor';

export const PasswordComponent = ( { attributes, blockID, setAttributes } ) => {
	const { label, placeholder, required, confirmLabel, isConfirmPassword } =
		attributes;

	const isRequired = required ? ' srfm-required' : '';
	const slug = 'password';
	return (
		<>
			<div className={ `srfm-block srfm-${ slug }-block` }>
				<RichText
					tagName="label"
					value={ label }
					onChange={ ( value ) => setAttributes( { label: value } ) }
					className={ `srfm-block-label${ isRequired }` }
					multiline={ false }
					id={ blockID }
				/>
				<div className="srfm-block-wrap">
					<input
						id={ `srfm-${ slug }-${ blockID }` }
						type="password"
						className={ `srfm-input-common srfm-input-${ slug }` }
						placeholder={ placeholder }
						required={ required }
					/>
				</div>
			</div>
			{ isConfirmPassword && (
				<div className={ `srfm-block srfm-${ slug }-confirm-block` }>
					<label
						className={`srfm-block-label${ isRequired }`}
						htmlFor={ `srfm-${ slug }-confirm-${ blockID }` }
					>
						{ confirmLabel }
					</label>
					<div className="srfm-block-wrap">
						<input
							id={ `srfm-${ slug }-confirm-${ blockID }` }
							type="password"
							className={ `srfm-input-common srfm-input-${ slug }-confirm` }
							placeholder={ placeholder }
							required={ required }
						/>
					</div>
				</div>
			) }
		</>
	);
};
