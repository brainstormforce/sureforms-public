import { Text } from '@bsf/force-ui';
import { Check } from 'lucide-react';
import { cn } from '@Utils/Helpers';

/**
 * Check-marked list of short benefit lines, with an optional lead-in heading.
 *
 * @param {Object}   props
 * @param {string}   props.heading   Optional 14/500 heading rendered above the list.
 * @param {string[]} props.items     Lines to render.
 * @param {Function} props.icon      lucide icon component, defaults to Check.
 * @param {boolean}  props.inline    Render as a centered horizontal row (welcome trust strip).
 * @param {number}   props.size      Item font size in px, defaults to 14.
 * @param {string}   props.className Extra classes for the wrapper.
 * @return {JSX.Element} The list.
 */
const FeatureList = ( {
	heading,
	items = [],
	icon: Icon = Check,
	inline = false,
	size = 14,
	className,
} ) => (
	<div className={ cn( 'space-y-2', className ) }>
		{ heading && (
			<Text size={ 14 } weight={ 500 } color="primary">
				{ heading }
			</Text>
		) }
		<ul
			className={ cn(
				'm-0 p-0 list-none',
				inline
					? 'flex flex-wrap items-center justify-center gap-x-6 gap-y-2'
					: 'space-y-1.5'
			) }
		>
			{ items.map( ( item, index ) => (
				<li key={ index } className="m-0 flex items-center gap-1.5">
					<Icon className="size-4 shrink-0 text-icon-interactive" />
					<Text size={ size } weight={ 400 } color="label">
						{ item }
					</Text>
				</li>
			) ) }
		</ul>
	</div>
);

export default FeatureList;
