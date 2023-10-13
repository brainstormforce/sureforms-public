export const SwitchThemeStyle = ( { attributes, blockID } ) => {
	const { label, checked: isChecked, required } = attributes;

	const inputStyle = {
		position: 'absolute',
		opacity: 0,
		width: 0,
		height: 0,
	};

	const switchStyle = {
		display: 'inline-block',
		position: 'relative',
		flex: '0 0 50px',
		height: '25px',
		borderRadius: '25px',
		backgroundColor: isChecked ? '#007CBA' : '#dcdcdc',
		transition: 'background-color 0.2s',
		cursor: 'pointer',
	};

	const thumbStyle = {
		display: 'inline-block',
		position: 'absolute',
		width: '21px',
		height: '21px',
		borderRadius: '50%',
		backgroundColor: '#fff',
		top: '2px',
		left: isChecked ? '27px' : '2px',
		transition: 'left 0.2s',
	};

	return (
		<>
			<div style={ switchStyle }>
				<input
					type="checkbox"
					checked={ isChecked }
					style={ inputStyle }
				/>
				<div style={ thumbStyle }></div>
			</div>
			<label
				className="sf-text-primary"
				htmlFor={ 'switch-block-' + blockID }
			>
				{ label }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
		</>
	);
};
