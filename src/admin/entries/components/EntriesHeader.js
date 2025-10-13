import { __ } from '@wordpress/i18n';

/**
 * EntriesHeader Component
 * Displays the header section with title
 */
const EntriesHeader = () => {
	return (
		<div>
			<h1 className="text-xl whitespace-nowrap font-semibold text-text-primary leading-[30px] tracking-[-0.005em]">
				{ __( 'All form Entries', 'sureforms' ) }
			</h1>
		</div>
	);
};

export default EntriesHeader;
