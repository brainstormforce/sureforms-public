/**
 * Shared Page Break Settings controls.
 *
 * Reads and writes `_srfm_page_break_settings` post meta via the
 * 'core/editor' store. Renders the same control set whether mounted in the
 * Form Options document panel (GeneralSettings) or the Page Break block's
 * own Inspector Controls.
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { SelectControl, ToggleControl } from '@wordpress/components';
import SRFMTextControl from '@Components/text-control';

const PageBreakSettings = () => {
	const pageBreakSettings = useSelect( ( select ) => {
		const meta =
			select( 'core/editor' ).getEditedPostAttribute( 'meta' );
		return meta?._srfm_page_break_settings || {};
	} );

	const { editPost } = useDispatch( 'core/editor' );

	function updatePageBreakSettings( option, value ) {
		editPost( {
			meta: {
				_srfm_page_break_settings: {
					...pageBreakSettings,
					[ option ]: value,
				},
			},
		} );
	}

	return (
		<>
			{ pageBreakSettings?.progress_indicator_type !== 'none' && (
				<>
					<ToggleControl
						label={ __( 'Show Labels', 'sureforms' ) }
						checked={ pageBreakSettings?.toggle_label }
						onChange={ ( value ) =>
							updatePageBreakSettings( 'toggle_label', value )
						}
					/>
					{ pageBreakSettings?.toggle_label && (
						<SRFMTextControl
							label={ __(
								'First Page Label',
								'sureforms'
							) }
							value={ pageBreakSettings?.first_page_label }
							data={ {
								value: pageBreakSettings?.first_page_label,
								label: 'first_page_label',
							} }
							onChange={ ( value ) =>
								updatePageBreakSettings(
									'first_page_label',
									value
								)
							}
							isFormSpecific={ true }
						/>
					) }
				</>
			) }
			<SelectControl
				__next40pxDefaultSize
				label={ __( 'Progress Indicator', 'sureforms' ) }
				value={ pageBreakSettings?.progress_indicator_type }
				className="srfm-progress-control"
				options={ [
					{
						label: __( 'None', 'sureforms' ),
						value: 'none',
					},
					{
						label: __( 'Progress Bar', 'sureforms' ),
						value: 'progress-bar',
					},
					{
						label: __( 'Connector', 'sureforms' ),
						value: 'connector',
					},
					{
						label: __( 'Steps', 'sureforms' ),
						value: 'steps',
					},
				] }
				onChange={ ( value ) =>
					updatePageBreakSettings(
						'progress_indicator_type',
						value
					)
				}
				__nextHasNoMarginBottom
			/>
			<SRFMTextControl
				data={ {
					value: pageBreakSettings?.next_button_text,
					label: 'next_button_text',
				} }
				label={ __( 'Next Button Text', 'sureforms' ) }
				value={ pageBreakSettings?.next_button_text }
				onChange={ ( value ) =>
					updatePageBreakSettings( 'next_button_text', value )
				}
				isFormSpecific={ true }
			/>
			<SRFMTextControl
				data={ {
					value: pageBreakSettings?.back_button_text,
					label: 'back_button_text',
				} }
				label={ __( 'Back Button Text', 'sureforms' ) }
				value={ pageBreakSettings?.back_button_text }
				onChange={ ( value ) =>
					updatePageBreakSettings( 'back_button_text', value )
				}
				isFormSpecific={ true }
			/>
		</>
	);
};

export default PageBreakSettings;
