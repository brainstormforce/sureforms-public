import svg from '@Svg/svgs.json';
import parse from 'html-react-parser';
import { Container } from '@bsf/force-ui';

const ModalWarning = ( { message } ) => {
	return (
		<Container
			className="w-full p-3 gap-2 border border-solid border-alert-border-warning bg-alert-background-warning"
		>
			<span className="size-5">{ parse( svg?.warning ) }</span>
			<span className="text-sm font-normal">{ message }</span>
		</Container>
	);
};

export default ModalWarning;