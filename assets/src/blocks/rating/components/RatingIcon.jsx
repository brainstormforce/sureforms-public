import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faStar, faHeart, faSmile } from '@fortawesome/free-solid-svg-icons';

function RatingIcon( props ) {
	let icon;
	switch ( props.iconShape ) {
		case 'star':
			icon = (
				<FontAwesomeIcon
					height={ '25px' }
					width={ '25px' }
					icon={ faStar }
					{ ...props.iconProps }
				/>
			);
			break;
		case 'heart':
			icon = (
				<FontAwesomeIcon
					icon={ faHeart }
					height={ '25px' }
					width={ '25px' }
					{ ...props.iconProps }
				/>
			);
			break;
		case 'smiley':
			icon = (
				<FontAwesomeIcon
					icon={ faSmile }
					height={ '25px' }
					width={ '25px' }
					{ ...props.iconProps }
				/>
			);
			break;
		default:
			icon = (
				<FontAwesomeIcon
					icon={ faStar }
					height={ '25px' }
					width={ '25px' }
					{ ...props.iconProps }
				/>
			);
	}
	return icon;
}

export default RatingIcon;
