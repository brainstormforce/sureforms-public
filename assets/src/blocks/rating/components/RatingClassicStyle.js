import { RichText } from '@wordpress/block-editor';

export const RatingClassicStyle = ( {
	attributes,
	setAttributes,
	blockID,
} ) => {
	const { required, label, showNumbers, iconShape, maxValue } = attributes;
	const isRequired = required ? 'required' : '';
	let svg = '';
	switch ( iconShape ) {
		case 'star':
			svg = (
				<svg
					xmlns="http://www.w3.org/2000/svg"
					fill="none"
					viewBox="0 0 24 24"
					strokeWidth="1.5"
					stroke="currentColor"
					className="h-8 w-8"
				>
					<path
						strokeLinecap="round"
						strokeLinejoin="round"
						d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"
					/>
				</svg>
			);
			break;
		case 'heart':
			svg = (
				<svg
					xmlns="http://www.w3.org/2000/svg"
					fill="none"
					viewBox="0 0 24 24"
					strokeWidth="1.5"
					stroke="currentColor"
					className="h-8 w-8"
				>
					<path
						strokeLinecap="round"
						strokeLinejoin="round"
						d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
					/>
				</svg>
			);
			break;
		case 'smiley':
			svg = (
				<svg
					xmlns="http://www.w3.org/2000/svg"
					fill="none"
					strokeWidth="1.5"
					stroke="currentColor"
					className="h-8 w-8"
					viewBox="0 0 122.88 122.88"
				>
					<g>
						<path
							style={ {
								fillRule: 'evenodd',
								clipRule: 'evenodd',
							} }
							d="M45.54,2.11c32.77-8.78,66.45,10.67,75.23,43.43c8.78,32.77-10.67,66.45-43.43,75.23 c-32.77,8.78-66.45-10.67-75.23-43.43C-6.67,44.57,12.77,10.89,45.54,2.11L45.54,2.11z"
						/>
						<path
							style={ {
								fillRule: 'evenodd',
								clipRule: 'evenodd',
								fill: '#141518',
							} }
							d="M45.78,31.71c4.3,0,7.78,6.6,7.78,14.75c0,8.15-3.48,14.75-7.78,14.75S38,54.61,38,46.46 C38,38.32,41.48,31.71,45.78,31.71L45.78,31.71z M22.43,80.59c0.42-7.93,4.53-11.46,11.83-11.76l-5.96,5.93 c16.69,21.63,51.01,21.16,65.78-0.04l-5.47-5.44c7.3,0.30,11.4,3.84,11.83,11.76l-3.96-3.93c-16.54,28.07-51.56,29.07-70.70,0.15 L22.43,80.59L22.43,80.59z M77.1,31.71c4.3,0,7.78,6.6,7.78,14.75c0,8.15-3.49,14.75-7.78,14.75s-7.78-6.6-7.78-14.75 C69.31,38.32,72.8,31.71,77.1,31.71L77.1,31.71z"
						/>
					</g>
				</svg>
			);
			break;
		default:
			svg = (
				<svg
					xmlns="http://www.w3.org/2000/svg"
					fill="none"
					viewBox="0 0 24 24"
					strokeWidth="1.5"
					stroke="currentColor"
					className="h-8 w-8"
				>
					<path
						strokeLinecap="round"
						strokeLinejoin="round"
						d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"
					/>
				</svg>
			);
			break;
	}

	const ratingItems = [];
	for ( let i = 0; i < maxValue; i++ ) {
		ratingItems.push(
			<li
				className="srfm-flex srfm-items-center srfm-flex-col-reverse"
				key={ i }
			>
				<span className="srfm-text-primary">
					{ showNumbers ? i + 1 : '' }
				</span>
				<span className="srfm-text-primary" data-te-rating-icon-ref>
					{ svg }
				</span>
			</li>
		);
	}

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
			<ul
				className="srfm-classic-event srfm-mt-2 srfm-flex srfm-list-none srfm-gap-3 srfm-p-0"
				data-te-rating-init
			>
				{ ratingItems }
			</ul>
		</>
	);
};
