/**
 * Per-option description, shown behind a menu button on each option row.
 *
 * Mirrors SureDonation's Donation Amount block (src/blocks/donation-amount/edit.js)
 * so the setting looks and behaves the same in both products: one description
 * open at a time, a 120 character cap with a live counter, focus moved into the
 * field when it opens, and the row closed again when the block is deselected.
 *
 * @since x.x.x
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import { MoreVertical } from 'lucide-react';
import SRFMTextControl from '@Components/text-control';
import { OPTION_DESCRIPTION_MAX_LENGTH } from '@Blocks/util';

/**
 * Which option has its description open. Held per block rather than per row so
 * the menu button and a click on the option in the canvas drive the same
 * disclosure, and only one is ever open.
 *
 * @param {boolean} isSelected Whether the block is selected.
 * @return {Object} `{ openDescription, setOpenDescription, openDescriptionRef }`.
 */
export const useOptionDescription = ( isSelected ) => {
	const [ openDescription, setOpenDescription ] = useState( null );
	// Only one description row renders at a time, so one ref reaches it.
	const openDescriptionRef = useRef( null );

	// The author's next action is to type, and focusing also scrolls the field
	// into view, which in a long inspector it otherwise would not be.
	useEffect( () => {
		openDescriptionRef.current?.querySelector( 'textarea' )?.focus();
	}, [ openDescription ] );

	// Clicking away from the block closes the open description, so the canvas
	// stops showing that option as the one being edited.
	useEffect( () => {
		if ( ! isSelected ) {
			setOpenDescription( null );
		}
	}, [ isSelected ] );

	return { openDescription, setOpenDescription, openDescriptionRef };
};

const controlId = ( blockId, index ) =>
	`srfm-option-description-${ blockId }-${ index }`;

/**
 * The menu button that opens and closes one option's description.
 *
 * @param {Object}   props
 * @param {string}   props.blockId            Block id, for aria-controls.
 * @param {number}   props.index              Option index.
 * @param {string}   props.optionLabel        Option title, for the accessible name.
 * @param {string}   props.description        Current description.
 * @param {?number}  props.openDescription    Index of the open description.
 * @param {Function} props.setOpenDescription Setter for the open index.
 */
export const OptionDescriptionToggle = ( {
	blockId,
	index,
	optionLabel,
	description,
	openDescription,
	setOpenDescription,
} ) => (
	<div className="srfm-option-description-menu">
		<Button
			className={ `srfm-option-description-toggle${
				description?.trim() ? ' srfm-has-description' : ''
			}` }
			onClick={ () =>
				setOpenDescription( openDescription === index ? null : index )
			}
			aria-expanded={ openDescription === index }
			aria-controls={ controlId( blockId, index ) }
			// An action, not the written text: Button falls its accessible name
			// back to `label`, and the description would make that 120
			// characters that change on every keystroke.
			label={ sprintf(
				/* translators: %s: the option title. */
				__( 'Edit description for %s', 'sureforms' ),
				optionLabel
			) }
			showTooltip
		>
			<MoreVertical style={ { width: '20px', height: '20px' } } />
		</Button>
	</div>
);

/**
 * The description field for the open option, on its own row under it.
 *
 * @param {Object}   props
 * @param {string}   props.blockId            Block id, for the element id.
 * @param {number}   props.index              Option index.
 * @param {string}   props.optionLabel        Option title, for the field label.
 * @param {string}   props.description        Current description.
 * @param {Object}   props.openDescriptionRef Ref from useOptionDescription().
 * @param {Function} props.onChange           Receives the new description.
 */
export const OptionDescriptionControl = ( {
	blockId,
	index,
	optionLabel,
	description,
	openDescriptionRef,
	onChange,
} ) => (
	<div
		ref={ openDescriptionRef }
		className="srfm-option-description-control"
		id={ controlId( blockId, index ) }
	>
		<SRFMTextControl
			variant="textarea"
			// Let TextareaControl own the label so it emits a real <label for>.
			showHeaderControls={ false }
			label={ sprintf(
				/* translators: %s: the option title. */
				__( 'Description for %s', 'sureforms' ),
				optionLabel
			) }
			maxLength={ OPTION_DESCRIPTION_MAX_LENGTH }
			value={ description || '' }
			onChange={ onChange }
		/>
		<span className="srfm-option-description-count">
			{
				/* Code units, because that is what the native maxlength
				   counts: an emoji stops two characters short of the cap, and
				   the counter has to say so. */
				( description || '' ).length
			}
			/{ OPTION_DESCRIPTION_MAX_LENGTH }
		</span>
	</div>
);
