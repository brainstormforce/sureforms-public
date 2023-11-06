export const PasswordClassicStyle = ( { attributes, blockID } ) => {
	const { label, placeholder, required, confirmLabel, isConfirmPassword } =
		attributes;

	return (
		<>
			<label
				className="srfm-classic-label-text"
				htmlFor={ 'srfm-text-input-' + blockID }
			>
				{ label }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
			<div className= "srfm-relative srfm-mt-2">
				<input
					id={ 'srfm-text-input-' + blockID }
					type="password"
					className={ 'srfm-classic-pwd-element' }
					placeholder={ placeholder }
					required={ required }
				/>
				<div className="srfm-pointer-events-none srfm-absolute srfm-inset-y-0 srfm-right-0 srfm-flex srfm-items-center srfm-pr-3">
					<svg
						className="srfm-h-5 srfm-w-5 srfm-text-gray-400"
						viewBox="0 0 30 30"
						fill="currentColor"
						aria-hidden="true"
					>
						<path d="M 15 2 C 11.145666 2 8 5.1456661 8 9 L 8 11 L 6 11 C 4.895 11 4 11.895 4 13 L 4 25 C 4 26.105 4.895 27 6 27 L 24 27 C 25.105 27 26 26.105 26 25 L 26 13 C 26 11.895 25.105 11 24 11 L 22 11 L 22 9 C 22 5.2715823 19.036581 2.2685653 15.355469 2.0722656 A 1.0001 1.0001 0 0 0 15 2 z M 15 4 C 17.773666 4 20 6.2263339 20 9 L 20 11 L 10 11 L 10 9 C 10 6.2263339 12.226334 4 15 4 z"></path>
					</svg>
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
							<span style={ { color: 'red' } }> *</span>
						) }
					</label>
					<div className= "srfm-relative srfm-mt-2">
						<input
							id={ 'confirm-srfm-text-input-' + blockID }
							type="password"
							className={ 'srfm-classic-pwd-element' }
							placeholder={ placeholder }
							required={ required }
						/>
						<div className="srfm-pointer-events-none srfm-absolute srfm-inset-y-0 srfm-right-0 srfm-flex srfm-items-center srfm-pr-3">
							<svg
								className="srfm-h-5 srfm-w-5 srfm-text-gray-400"
								viewBox="0 0 30 30"
								fill="currentColor"
								aria-hidden="true"
							>
								<path d="M 15 2 C 11.145666 2 8 5.1456661 8 9 L 8 11 L 6 11 C 4.895 11 4 11.895 4 13 L 4 25 C 4 26.105 4.895 27 6 27 L 24 27 C 25.105 27 26 26.105 26 25 L 26 13 C 26 11.895 25.105 11 24 11 L 22 11 L 22 9 C 22 5.2715823 19.036581 2.2685653 15.355469 2.0722656 A 1.0001 1.0001 0 0 0 15 2 z M 15 4 C 17.773666 4 20 6.2263339 20 9 L 20 11 L 10 11 L 10 9 C 10 6.2263339 12.226334 4 15 4 z"></path>
							</svg>
						</div>
					</div>
				</div>
			) }
		</>
	);
};
