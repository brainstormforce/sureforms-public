import { Text } from '@bsf/force-ui';
import { classnames } from '../../helpers';

/**
 * Paragraph Component
 *
 * Renders text as either a paragraph or heading.
 *
 * @param {Object}  props           - Component props.
 * @param {string}  props.text      - Text content to display.
 * @param {boolean} props.isHeading - If true, renders as h3 with heading styles.
 * @return {JSX.Element} Paragraph component.
 */
const Paragraph = ( { text, isHeading = false } ) => (
	<Text
		as={ isHeading ? 'h3' : 'p' }
		weight={ isHeading ? 600 : 400 }
		size={ isHeading ? 18 : 14 }
		color={ isHeading ? 'primary' : 'secondary' }
		className={ classnames( 'leading-relaxed', isHeading ? 'mt-4' : '' ) }
	>
		{ text }
	</Text>
);

export default Paragraph;
