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
			<div className="frontend-inputs-holder sf-classic-inputs-holder">
				<label
					className="sf-classic-label-text"
					htmlFor={ 'text-input-' + blockID }
				>
					{ label }
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
						id={ 'text-input-' + blockID }
						type="email"
						value={ defaultValue }
						className={ 'sf-classic-email-element' }
						placeholder={ placeholder }
						required={ required }
					/>
				</div>
				{ isConfirmEmail && (
					<div className="!mt-[24px]">
						<label
							className="sf-classic-label-text"
							htmlFor={ 'text-input-' + blockID }
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
								id={ 'text-input-' + blockID }
								type="email"
								value={ defaultValue }
								className={ 'sf-classic-email-element' }
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
