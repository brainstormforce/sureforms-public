export default () => {
    return <InspectorControls>
    <PanelBody title={__('Form Template', 'sureforms')}>
        <PanelRow>
            <div>
                <Button isPrimary onClick={changeTemplate}>
                    {__('Change Template', 'sureforms')}
                </Button>
            </div>
        </PanelRow>
    </PanelBody>
    <PanelBody title={__('Style', 'sureforms')}>
        <PanelRow>
            <BaseControl.VisualLabel>
                {__('Form Highlight Color', 'sureforms')}
            </BaseControl.VisualLabel>
            <ColorPopup
                color={color}
                setColor={(color) => {
                    setAttributes({ color: color?.hex });
                }}
            />
        </PanelRow>
        <PanelRow>
            <UnitControl
                label={__('Row Gap')}
                onChange={(gap) => setAttributes({ gap })}
                value={gap}
                help={__(
                    'The this is the space between the rows of form elements.',
                    'sureforms'
                )}
                units={[
                    { value: 'px', label: 'px', default: 0 },
                    { value: 'em', label: 'em', default: 0 },
                ]}
            />
        </PanelRow>
    </PanelBody>
    <PanelBody title={__('Thank You Page', 'sureforms')}>
        <PanelRow>
            <ToggleControl
                label={__('Custom Thank You Page', 'sureforms')}
                checked={custom_success_url}
                onChange={(custom_success_url) =>
                    setCustomSuccessUrl(custom_success_url)
                }
            />
        </PanelRow>
        {custom_success_url && (
            <PanelRow>
                <div
                    css={css`
                        border: 1px solid #ddd;
                        box-sizing: border-box;
                        .block-editor-link-control {
                            min-width: 248px;
                            max-width: 248px;
                            overflow: hidden;
                        }

                        .block-editor-link-control__search-item-header {
                            white-space: normal;
                            overflow-wrap: anywhere;
                        }
                    `}
                >
                    <LinkControl
                        value={{ url: success_url }}
                        settings={{}}
                        shownUnlinkControl={true}
                        noURLSuggestion
                        showInitialSuggestions
                        onChange={(nextValue) => {
                            setAttributes({
                                success_url: nextValue.url,
                            });
                        }}
                    />
                </div>
            </PanelRow>
        )}
    </PanelBody>
    <PanelBody
        title={__('Loading Text', 'sureforms')}
        initialOpen={false}
    >
        <PanelRow>
            <TextControl
                label={__('Submitting Order', 'sureforms')}
                value={loading_text?.finalizing}
                placeholder={__('Submitting Order...', 'sureforms')}
                onChange={(finalizing) =>
                    setAttributes({
                        loading_text: {
                            ...loading_text,
                            finalizing,
                        },
                    })
                }
            />
        </PanelRow>
        <PanelRow>
            <TextControl
                label={__('Processing Payment', 'sureforms')}
                value={loading_text?.paying}
                placeholder={__(
                    'Processing payment...',
                    'sureforms'
                )}
                onChange={(paying) =>
                    setAttributes({
                        loading_text: {
                            ...loading_text,
                            paying,
                        },
                    })
                }
            />
        </PanelRow>
        <PanelRow>
            <TextControl
                label={__('Confirming Payment', 'sureforms')}
                value={loading_text?.confirming}
                placeholder={__('Finalizing order...', 'sureforms')}
                onChange={(confirming) =>
                    setAttributes({
                        loading_text: {
                            ...loading_text,
                            confirming,
                        },
                    })
                }
            />
        </PanelRow>
        <PanelRow>
            <TextControl
                label={__('Success Text', 'sureforms')}
                value={loading_text?.confirmed}
                placeholder={__(
                    'Success! Redirecting...',
                    'sureforms'
                )}
                onChange={(confirmed) =>
                    setAttributes({
                        loading_text: {
                            ...loading_text,
                            confirmed,
                        },
                    })
                }
            />
        </PanelRow>
    </PanelBody>
</InspectorControls>
}