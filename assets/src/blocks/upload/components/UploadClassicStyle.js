export const UploadClassicStyle = ( { attributes, blockID } ) => {
	const {
		required,
		label,
		fileSizeLimit,
		allowedFormats,
	} = attributes;

	return (
		<>
			<div class="col-span-full">
				<label
					className="sf-classic-label-text"
					htmlFor={ 'text-input-' + blockID }
				>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
			</div>
		</>
	);
};
