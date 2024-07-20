import { Button, Tooltip } from '@wordpress/components';
import { Inserter } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import ICONS from '../components/template-picker/components/icons';

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
							{ ICONS.plus }
						</Button>
					</Tooltip>
				);
			} }
			isAppender
		/>
	);
}

export const BlockInserterWrapper = () => {
	const selectedBlock = useSelect( ( select ) => select( 'core/block-editor' ).getSelectedBlock() );

	// This blockWrapper should be visible only when block is not selected.
	return !! selectedBlock ? null : <BlockInserter />;
};
