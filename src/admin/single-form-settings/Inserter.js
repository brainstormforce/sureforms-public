import { Button, Tooltip } from '@wordpress/components';
import { Inserter } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

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

	const { clearSelectedBlock } = useDispatch( 'core/block-editor' );

	useEffect( () => {
		const handleCanvasClick = ( event ) => {
			// Check if a block is currently selected.
			if ( ! selectedBlock ) {
				return;
			}

			// Get the target element.
			const target = event.target;

			// Elements that should NOT trigger deselection when clicked.
			// Specifically: blocks themselves, toolbars, sidebars, and interactive elements.
			const isBlockContent = target.closest(
				'.block-editor-block-list__block'
			);
			const isToolbar = target.closest( '.block-editor-block-toolbar' );
			const isInserter =
				target.closest( '.block-editor-inserter' ) ||
				target.closest( '.srfm-section__empty-block-inserter' );
			const isPopover = target.closest( '.components-popover' );
			const isSidebar = target.closest(
				'.interface-interface-skeleton__sidebar'
			);
			const isHeader =
				target.closest( '.interface-interface-skeleton__header' ) ||
				target.closest( '.edit-post-header' );
			const isRichText =
				target.classList.contains(
					'block-editor-rich-text__editable'
				) || target.closest( '.block-editor-rich-text__editable' );

			// Don't deselect if clicking on these elements.
			if (
				isBlockContent ||
				isToolbar ||
				isInserter ||
				isPopover ||
				isSidebar ||
				isHeader ||
				isRichText
			) {
				return;
			}

			// Check if click is within the main editor area.
			// The .editor-styles-wrapper contains everything including the submit button.
			const editorWrapper = target.closest( '.editor-styles-wrapper' );
			const isRootContainer =
				target.classList.contains( 'is-root-container' ) ||
				target.closest( '.is-root-container' );
			const isEditorCanvas =
				target.classList.contains(
					'edit-post-visual-editor__content-area'
				) || target.closest( '.edit-post-visual-editor__content-area' );

			// Check if clicking on the submit button container or custom inserter (areas below blocks).
			const isSubmitContainer =
				target.classList.contains( 'srfm-submit-btn-container' ) ||
				target.closest( '.srfm-submit-btn-container' );
			const isCustomInserter =
				target.classList.contains( 'srfm-custom-block-inserter' ) ||
				target.closest( '.srfm-custom-block-inserter' );

			// Clear selection if clicking on:
			// 1. The editor wrapper itself (empty areas).
			// 2. The root container directly.
			// 3. The submit button container (below the form).
			// 4. The custom block inserter area.
			// 5. The main editor canvas.
			if (
				editorWrapper ||
				isRootContainer ||
				isEditorCanvas ||
				isSubmitContainer ||
				isCustomInserter
			) {
				clearSelectedBlock();
			}
		};

		// Add event listener to the document.
		document.addEventListener( 'click', handleCanvasClick, true );

		// Cleanup.
		return () => {
			document.removeEventListener( 'click', handleCanvasClick, true );
		};
	}, [ selectedBlock, clearSelectedBlock ] );

	// This blockWrapper should be visible only when block is not selected.
	return !! selectedBlock ? null : <BlockInserter />;
};
