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

			const isRootContainer =
				target.classList.contains( 'is-root-container' ) ||
				target.closest( '.is-root-container' );

			// Elements that should NOT trigger deselection when clicked.
			if ( isRootContainer ) {
				return;
			}

			// Check if click is within the main editor area.
			const editorWrapper = target.closest( '.editor-styles-wrapper' );

			// Clear selection if clicking on:
			if ( editorWrapper ) {
				// Check if there's a focused element ( contenteditable ).
				const { ownerDocument } = target;
				const activeElement = ownerDocument.activeElement;
				const isFocusedElement =
					activeElement &&
					( activeElement.isContentEditable ||
						activeElement.closest( '[contenteditable="true"]' ) );

				// Check if there's selected/highlighted text.
				const selection = ownerDocument.getSelection?.();
				const hasTextSelection =
					selection &&
					! selection.isCollapsed &&
					selection.rangeCount > 0;

				// If an input/label is focused or text is selected, clear it first to break the focus cycle.
				if ( isFocusedElement || hasTextSelection ) {
					event.preventDefault();

					// Clear any text selection.
					if ( hasTextSelection ) {
						selection.removeAllRanges();
					}

					// Blur the focused element.
					if ( isFocusedElement ) {
						activeElement.blur();
					}

					// Use requestAnimationFrame to ensure blur and selection clear complete before clearing block selection.
					ownerDocument.defaultView.requestAnimationFrame( () => {
						clearSelectedBlock();
					} );
				} else {
					clearSelectedBlock();
				}
			}
		};

		// Add event listener to the document.
		const timeoutId = setTimeout( () => {
			document.addEventListener( 'click', handleCanvasClick, true );
		}, 500 );

		// Cleanup.
		return () => {
			clearTimeout( timeoutId );
			document.removeEventListener( 'click', handleCanvasClick, true );
		};
	}, [ selectedBlock, clearSelectedBlock ] );

	// This blockWrapper should be visible only when block is not selected.
	return !! selectedBlock ? null : <BlockInserter />;
};
