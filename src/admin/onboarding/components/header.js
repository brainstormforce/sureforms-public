import { Text, Title } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';

/**
 * Step title + description.
 *
 * @param {Object}          props
 * @param {string}          props.title       Heading text.
 * @param {string|Node}     props.description Supporting copy.
 * @param {'h2'|'h3'}       props.tag         h2 renders 30/38 semibold, h3 (default) renders 24/32 semibold.
 * @param {'left'|'center'} props.align       Text alignment.
 * @param {string}          props.className   Extra wrapper classes.
 * @return {JSX.Element} The header block.
 */
const Header = ( {
	title,
	description,
	tag = 'h3',
	align = 'left',
	className,
} ) => {
	return (
		<div
			className={ cn(
				'space-y-2',
				align === 'center' && 'text-center',
				className
			) }
		>
			{ tag === 'h2' ? (
				<Text
					as="h2"
					size={ 30 }
					lineHeight={ 38 }
					weight={ 600 }
					color="primary"
				>
					{ title }
				</Text>
			) : (
				<Title tag="h3" title={ title } size="lg" />
			) }
			{ description && (
				<Text as="p" size={ 14 } color="secondary">
					{ description }
				</Text>
			) }
		</div>
	);
};

export default Header;
