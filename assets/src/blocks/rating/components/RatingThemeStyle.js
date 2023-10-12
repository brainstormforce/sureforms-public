import RatingIcon from './RatingIcon.jsx';
import { useState } from '@wordpress/element';

export const RatingThemeStyle = ( { attributes, blockID } ) => {
	const {
		required,
		label,
		width,
		iconColor,
		showNumbers,
		iconShape,
		maxValue,
	} = attributes;

	const [ rating, setRating ] = useState( null );
	// eslint-disable-next-line no-unused-vars
	const [ hover, setHover ] = useState( null );

	const arrayRating = [];

	for ( let i = 1; i <= maxValue; i++ ) {
		arrayRating.push( i );
	}

	return (
		<>
			<label
				className="sf-text-primary"
				htmlFor={ 'rating-block-' + blockID }
			>
				{ label }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
			<div
				id={ 'rating-block-' + blockID }
				style={ {
					justifyContent:
						width === 'fullWidth'
							? 'space-between'
							: 'space-evenly',
					display: 'flex',
					alignItems: 'center',
				} }
			>
				{ arrayRating.map( ( index ) => {
					const ratingValue = index;
					const iconProps = {
						color:
							ratingValue <= ( hover || rating )
								? iconColor
								: '#ddd',
						fontSize: ratingValue === rating ? '30px' : null,
						// Might be used later.
						// onMouseEnter: () => setHover( ratingValue ),
						// onMouseLeave: () => setHover( null ),
					};

					return (
						<div key={ index }>
							<div
								style={ {
									display: 'flex',
									flexDirection: 'column',
									alignItems: 'center',
								} }
							>
								<label
									style={ { fontSize: '25px' } }
									htmlFor={ blockID + 'i-' + index }
								>
									<RatingIcon
										iconShape={ iconShape }
										iconProps={ iconProps }
									/>
								</label>
								<div style={ { color: 'black' } }>
									{ showNumbers && index }
								</div>

								<input
									required={ index === 1 && required }
									type="radio"
									id={ blockID + 'i-' + index }
									style={ { display: 'none' } }
									value={ ratingValue }
									onClick={ () => {
										setRating( ratingValue );
									} }
								/>
							</div>
						</div>
					);
				} ) }
			</div>
		</>
	);
};
