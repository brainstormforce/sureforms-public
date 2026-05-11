import { Container } from '@bsf/force-ui';
import svg from '@Svg/svgs.json';
import parse from 'html-react-parser';

/**
 * Warning box component for displaying warning messages.
 *
 * @param {Object} props         - Component props.
 * @param {string} props.message - The warning message to display.
 * @return {JSX.Element} The warning box component.
 */
const WarningBox = ( { message } ) => {
	return (
		<Container className="w-full p-3 gap-2 border border-solid border-alert-border-warning bg-alert-background-warning rounded-lg">
			<span className="size-5">{ parse( svg?.warning ) }</span>
			<span className="text-sm font-normal">{ message }</span>
		</Container>
	);
};

export default WarningBox;
