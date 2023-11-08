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
				<div className="srfm-relative srfm-mt-2 srfm-rounded-md srfm-shadow-sm">
					<div className="srfm-pointer-events-none srfm-absolute srfm-inset-y-0 srfm-right-0 srfm-flex srfm-items-center srfm-pr-3">
						<i
							className="fa fa-envelope srfm-text-gray-400 srfm-text-[18px]"
							aria-hidden="true"
						></i>
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
						<div className="srfm-relative srfm-mt-2 srfm-rounded-md srfm-shadow-sm">
							<div className="srfm-pointer-events-none srfm-absolute srfm-inset-y-0 srfm-right-0 srfm-flex srfm-items-center srfm-pr-3">
								<i
									className="fa fa-envelope srfm-text-gray-400 srfm-text-[18px]"
									aria-hidden="true"
								></i>
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
