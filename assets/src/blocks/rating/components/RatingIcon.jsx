import { AiFillStar, AiFillHeart, AiFillSmile } from 'react-icons/ai';

function RatingIcon( props ) {
	let icon;
	switch ( props.iconShape ) {
		case 'star':
			icon = <AiFillStar { ...props.iconProps } />;
			break;
		case 'heart':
			icon = <AiFillHeart { ...props.iconProps } />;
			break;
		case 'smiley':
			icon = <AiFillSmile { ...props.iconProps } />;
			break;
		default:
			icon = <AiFillStar { ...props.iconProps } />;
	}
	return icon;
}

export default RatingIcon;
