import { RichText } from '@wordpress/block-editor';

export const DatetimepickerClassicStyle = ( {
	attributes,
	setAttributes,
	blockID,
} ) => {
	const { label, required, fieldType } = attributes;

	const isRequired = required ? 'required' : '';

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
			<div className="srfm-classic-date-time-picker relative mt-2 rounded-md shadow-sm datepicker-with-limits">
				<div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
					{ 'time' === fieldType ? (
						<svg
							xmlns="http://www.w3.org/2000/svg"
							viewBox="0 0 24 24"
							fill="#9BA3AF"
							className="w-5 h-5"
						>
							<path
								strokeLinecap="round"
								strokeLinejoin="round"
								d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"
							></path>
						</svg>
					) : (
						<svg
							xmlns="http://www.w3.org/2000/svg"
							viewBox="0 0 24 24"
							fill="#9BA3AF"
							className="w-5 h-5"
						>
							<path
								fillRule="evenodd"
								d="M6.75 2.25A.75.75 0 017.5 3v1.5h9V3A.75.75 0 0118 3v1.5h.75a3 3 0 013 3v11.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3V7.5a3 3 0 013-3H6V3a.75.75 0 01.75-.75zm13.5 9a1.5 1.5 0 00-1.5-1.5H5.25a1.5 1.5 0 00-1.5 1.5v7.5a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5v-7.5z"
								clipRule="evenodd"
							></path>
						</svg>
					) }
				</div>
				<input
					id={ 'srfm-text-input-' + blockID }
					type="text"
					className={ 'srfm-classic-email-element' }
					required={ required }
				/>
			</div>
		</>
	);
};
