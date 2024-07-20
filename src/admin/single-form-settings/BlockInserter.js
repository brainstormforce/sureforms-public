import { Button, Tooltip } from '@wordpress/components';
import { Inserter } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

export function BlockInserter({ rootClientId = null }) {
    return (
        <Inserter
            position="bottom center"
            rootClientId={rootClientId}
            __experimentalIsQuick
            className="srfm-section__empty-block-inserter"
            renderToggle={({ onToggle, disabled, isOpen }) => {
                const label = "Add Block Tooltip";

                return (
                    <Tooltip text={label}>
                        <Button
                            className="block-editor-button-block-appender"
                            onClick={onToggle}
                            aria-haspopup="true"
                            aria-expanded={isOpen}
                            disabled={disabled}
                            label={label}
                        >
                            Drag a block here or click to add a block
                        </Button>
                    </Tooltip>
                );
            }}
            isAppender
        />
    );
}

export const BlockInserterWrapper = () => {
    // This blockWrapper should be visible only when block is not selected.
    const selectedBlock = useSelect((select) =>  select('core/block-editor').getSelectedBlock() );
    console.log("selectedBlock", selectedBlock);


    // if( !! selectedBlock ) {
    //     return null;
    // }

    // return (
    //     <div className="srfm-section__empty-block-inserter-wrapper">
    //         <BlockInserter />
    //     </div>
    // );
}
