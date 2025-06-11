import { Button, Tooltip } from '@wordpress/components';
import { Inserter } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

export function BlockInserter( { rootClientId = null } ) {
	return (
		<Inserter
			position="bottom center"
			rootClientId={ rootClientId }
			__experimentalIsQuick
			className="srfm-section__empty-block-inserter"
			renderToggle={ ( { onToggle, disabled, isOpen } ) => {
				return (
					<Tooltip text={ __( 'Add Block', 'sureforms' ) }>
						<Button
							className="block-editor-button-block-appender srfm-custom-block-inserter-button"
							onClick={ onToggle }
							aria-haspopup="true"
							aria-expanded={ isOpen }
							disabled={ disabled }
						>
							<svg
								xmlns="http://www.w3.org/2000/svg"
								viewBox="0 0 24 24"
								width="24"
								height="24"
								focusable="false"
							>
								<path d="M11 12.5V17.5H12.5V12.5H17.5V11H12.5V6H11V11H6V12.5H11Z"></path>
							</svg>
						</Button>
					</Tooltip>
				);
			} }
			isAppender
		/>
	);
}

export const BlockInserterWrapper = () => {
	const selectedBlock = useSelect( ( select ) =>
		select( 'core/block-editor' ).getSelectedBlock()
	);

	// This blockWrapper should be visible only when block is not selected.
	return !! selectedBlock ? null : <BlockInserter />;
};
