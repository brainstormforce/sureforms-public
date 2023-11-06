export const EmailClassicStyle = ( { attributes, blockID } ) => {
	const {
		label,
		placeholder,
		required,
		defaultValue,
		isConfirmEmail,
		confirmLabel,
	} = attributes;
	return (
		<>
			<div className="srfm-frontend-inputs-holder srfm-classic-inputs-holder">
				<label
					className="srfm-classic-label-text"
					htmlFor={ 'srfm-text-input-' + blockID }
				>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<div className= "srfm-relative srfm-mt-2 srfm-rounded-md srfm-shadow-sm">
					<div className="srfm-pointer-events-none srfm-absolute srfm-inset-y-0 srfm-right-0 srfm-flex srfm-items-center srfm-pr-3">
						<svg
							className="srfm-h-5 srfm-w-5 srfm-text-gray-400"
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
					<div className="!srfm-mt-[24px]">
						<label
							className="srfm-classic-label-text"
							htmlFor={ 'srfm-text-input-' + blockID }
						>
							{ confirmLabel }
							{ required && label && (
								<span style={ { color: 'red' } }> *</span>
							) }
						</label>
						<div className= "srfm-relative srfm-mt-2 srfm-rounded-md srfm-shadow-sm">
							<div className="srfm-pointer-events-none srfm-absolute srfm-inset-y-0 srfm-right-0 srfm-flex srfm-items-center srfm-pr-3">
								<svg
									className="srfm-h-5 srfm-w-5 srfm-text-gray-400"
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
