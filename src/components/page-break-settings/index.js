/**
 * Shared Page Break Settings controls.
 *
 * Reads and writes `_srfm_page_break_settings` post meta via the
 * 'core/editor' store. Rendered in Form > Page Break (GeneralSettings); the
 * Page Break block itself only points here.
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { SelectControl, ToggleControl } from '@wordpress/components';
import { Tooltip } from '@bsf/force-ui';
import { Info } from 'lucide-react';
import SRFMTextControl from '@Components/text-control';

/**
 * Toggle label with its explanation in an info tooltip.
 *
 * Same Tooltip and icon as the Post Meta label in the custom post type
 * settings. Tailwind only applies inside the containers listed in
 * tailwind.config.js, and the block editor sidebar is not one of them, so the
 * tooltip portals into #srfm-dialog-root -- always present in the editor and
 * inside that scope -- and the icon takes the icon-secondary colour directly.
 *
 * @param {Object} props
 * @param {string} props.label Toggle label.
 * @param {string} props.help  Explanation shown in the tooltip.
 */
const LabelWithTooltip = ( { label, help } ) => (
	<span style={ { display: 'inline-flex', alignItems: 'center', gap: 4 } }>
		{ label }
		<Tooltip
			tooltipPortalId="srfm-dialog-root"
			// Above the Instant Form popover (z-index 1000000). Important
			// because `#srfm-dialog-root > div[data-floating-ui-portal] > div`
			// pins portaled content to 999999 with higher specificity.
			className="!z-[1000001]"
			arrow
			content={ <span>{ help }</span> }
			placement="top"
			triggers={ [ 'hover', 'focus' ] }
			variant="dark"
		>
			{ /* The icon sits inside the toggle's <label>; without this a click
			meant to read the tooltip would flip the setting. */ }
			<Info
				size={ 16 }
				color="#6B7280"
				onClick={ ( event ) => event.preventDefault() }
			/>
		</Tooltip>
	</span>
);

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
			<ToggleControl
				label={
					<LabelWithTooltip
						label={ __(
							'Auto-Advance to Next Step',
							'sureforms'
						) }
						help={ __(
							'Go to the next step as soon as someone picks an option. Works on steps with a single Multiple Choice or Dropdown field. The last step always shows Submit.',
							'sureforms'
						) }
					/>
				}
				checked={ !! pageBreakSettings?.auto_advance }
				onChange={ ( value ) =>
					updatePageBreakSettings( 'auto_advance', value )
				}
			/>
			{ pageBreakSettings?.auto_advance && (
				<ToggleControl
					label={
						<LabelWithTooltip
							label={ __(
								'Hide Next Button',
								'sureforms'
							) }
							help={ __(
								'Hide the Next button on steps that auto-advance. It reappears when reached with the keyboard.',
								'sureforms'
							) }
						/>
					}
					checked={
						!! pageBreakSettings?.auto_advance_hide_next
					}
					onChange={ ( value ) =>
						updatePageBreakSettings(
							'auto_advance_hide_next',
							value
						)
					}
				/>
			) }
			<ToggleControl
				label={
					<LabelWithTooltip
						label={ __( 'Review Step Before Submit', 'sureforms' ) }
						help={ __(
							'Adds a last page that lists every answer, with an Edit link back to each one, so people can check before they submit.',
							'sureforms'
						) }
					/>
				}
				checked={ !! pageBreakSettings?.review_step }
				onChange={ ( value ) =>
					updatePageBreakSettings( 'review_step', value )
				}
			/>
			{ pageBreakSettings?.review_step && (
				<SRFMTextControl
					data={ {
						value: pageBreakSettings?.review_step_label,
						label: 'review_step_label',
					} }
					label={ __( 'Review Step Label', 'sureforms' ) }
					// Shown when empty, which is also what the page falls back to.
					placeholder={ __( 'Review', 'sureforms' ) }
					value={ pageBreakSettings?.review_step_label }
					onChange={ ( value ) =>
						updatePageBreakSettings( 'review_step_label', value )
					}
					isFormSpecific={ true }
				/>
			) }
			{ pageBreakSettings?.review_step && (
				<SRFMTextControl
					variant="textarea"
					data={ {
						value: pageBreakSettings?.review_step_notice,
						label: 'review_step_notice',
					} }
					label={ __( 'Review Step Message', 'sureforms' ) }
					// Shown when empty, which is also what the page falls back to.
					placeholder={ __(
						'Please take a moment to review your answers before submitting.',
						'sureforms'
					) }
					value={ pageBreakSettings?.review_step_notice }
					onChange={ ( value ) =>
						updatePageBreakSettings( 'review_step_notice', value )
					}
					isFormSpecific={ true }
				/>
			) }
		</>
	);
};

export default PageBreakSettings;
