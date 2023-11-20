import { RichText } from '@wordpress/block-editor';

export const PasswordClassicStyle = ( {
	attributes,
	blockID,
	setAttributes,
} ) => {
	const { label, placeholder, required, confirmLabel, isConfirmPassword } =
		attributes;

	const isRequired = required ? 'srfm-required' : '';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-classic-label-text ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			<div className="srfm-relative srfm-mt-2">
				<input
					id={ 'srfm-text-input-' + blockID }
					type="password"
					className={ 'srfm-classic-pwd-element' }
					placeholder={ placeholder }
					required={ required }
				/>
				<div className="srfm-pointer-events-none srfm-absolute srfm-inset-y-0 srfm-right-0 srfm-flex srfm-items-center srfm-pr-3">
					<i
						className="fa fa-lock srfm-text-gray-400 srfm-text-[18px]"
						aria-hidden="true"
					></i>
				</div>
			</div>
			{ isConfirmPassword && (
				<div className="!srfm-mt-[24px]">
					<label
						className="srfm-classic-label-text"
						htmlFor={ 'confirm-srfm-text-input-' + blockID }
					>
						{ confirmLabel }
						{ required && confirmLabel && (
							<span className="srfm-text-red"> *</span>
						) }
					</label>
					<div className="srfm-relative srfm-mt-2">
						<input
							id={ 'confirm-srfm-text-input-' + blockID }
							type="password"
							className={ 'srfm-classic-pwd-element' }
							placeholder={ placeholder }
							required={ required }
						/>
						<div className="srfm-pointer-events-none srfm-absolute srfm-inset-y-0 srfm-right-0 srfm-flex srfm-items-center srfm-pr-3">
							<i
								className="fa fa-lock srfm-text-gray-400 srfm-text-[18px]"
								aria-hidden="true"
							></i>
						</div>
					</div>
				</div>
			) }
		</>
	);
};
