import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faStar, faHeart, faSmile } from '@fortawesome/free-solid-svg-icons';

function RatingIcon( props ) {
	let icon;
	switch ( props.iconShape ) {
		case 'star':
			icon = <FontAwesomeIcon icon={ faStar } { ...props.iconProps } />;
			break;
		case 'heart':
			icon = <FontAwesomeIcon icon={ faHeart } { ...props.iconProps } />;
			break;
		case 'smiley':
			icon = <FontAwesomeIcon icon={ faSmile } { ...props.iconProps } />;
			break;
		default:
			icon = <FontAwesomeIcon icon={ faStar } { ...props.iconProps } />;
	}
	return icon;
}

export default RatingIcon;
