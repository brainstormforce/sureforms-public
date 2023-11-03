import { RichText } from '@wordpress/block-editor';

export const EmailClassicStyle = ( { attributes, blockID, setAttributes } ) => {
	const {
		label,
		placeholder,
		required,
		defaultValue,
		isConfirmEmail,
		confirmLabel,
	} = attributes;

	const isRequired = required ? 'required' : '';

	return (
		<>
			<div className="srfm-frontend-inputs-holder srfm-classic-inputs-holder">
				<RichText
					tagName="label"
					value={ label }
					onChange={ ( value ) => setAttributes( { label: value } ) }
					className={ `srfm-classic-label-text ${ isRequired }` }
					multiline={ false }
					id={ blockID }
				/>
				<div className="relative mt-2 rounded-md shadow-sm">
					<div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
						<svg
							className="h-5 w-5 text-gray-400"
							viewBox="0 0 20 20"
							fill="currentColor"
							aria-hidden="true"
						>
							<path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z" />
							<path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" />
						</svg>
					</div>
					<input
						id={ 'srfm-text-input-' + blockID }
						type="email"
						value={ defaultValue }
						className={ 'srfm-classic-email-element' }
						placeholder={ placeholder }
						required={ required }
					/>
				</div>
				{ isConfirmEmail && (
					<div className="!mt-[24px]">
						<label
							className="srfm-classic-label-text"
							htmlFor={ 'srfm-text-input-' + blockID }
						>
							{ confirmLabel }
							{ required && label && (
								<span style={ { color: 'red' } }> *</span>
							) }
						</label>
						<div className="relative mt-2 rounded-md shadow-sm">
							<div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
								<svg
									className="h-5 w-5 text-gray-400"
									viewBox="0 0 20 20"
									fill="currentColor"
									aria-hidden="true"
								>
									<path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z" />
									<path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" />
								</svg>
							</div>
							<input
								id={ 'srfm-text-input-' + blockID }
								type="email"
								value={ defaultValue }
								className={ 'srfm-classic-email-element' }
								placeholder={ placeholder }
								required={ required }
							/>
						</div>
					</div>
				) }
			</div>
		</>
	);
};
