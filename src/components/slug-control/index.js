/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import { cleanForSlug } from '@wordpress/url';
import { select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import SRFMTextControl from '@Components/text-control';
import { setFormSpecificSmartTags, lockBlockSlugByBlockId } from '@Utils/Helpers';

/**
 * Collect all slug values in the editor, excluding the block identified
 * by `excludeClientId`, so we can check for duplicates.
 *
 * @param {Array}  blocks          Blocks from the block editor.
 * @param {string} excludeClientId Client ID of the block being edited.
 * @return {Set} Set of existing slug strings.
 */
const collectExistingSlugs = ( blocks, excludeClientId ) => {
	const slugs = new Set();
	const traverse = ( blockList ) => {
		for ( const block of blockList ) {
			if (
				block.clientId !== excludeClientId &&
				block.attributes?.slug
			) {
				slugs.add( block.attributes.slug );
			}
			if ( block.innerBlocks?.length ) {
				traverse( block.innerBlocks );
			}
		}
	};
	traverse( blocks );
	return slugs;
};

const SlugControl = ( { slug, setAttributes, clientId, blockId } ) => {
	const [ localSlug, setLocalSlug ] = useState( slug );
	const [ isDuplicate, setIsDuplicate ] = useState( false );
	const slugCommittedRef = useRef( false );

	// Refs to track latest values for the unmount cleanup.
	const localSlugRef = useRef( localSlug );
	const slugRef = useRef( slug );
	const isDuplicateRef = useRef( isDuplicate );
	localSlugRef.current = localSlug;
	slugRef.current = slug;
	isDuplicateRef.current = isDuplicate;

	// Sync local state if slug changes externally (e.g. block duplication resets it)
	useEffect( () => {
		setLocalSlug( slug );
		setIsDuplicate( false );
	}, [ slug ] );

	// Commit pending slug change on unmount (e.g. block deselected by clicking outside).
	useEffect( () => {
		return () => {
			const pending = localSlugRef.current;
			if ( pending && pending !== slugRef.current && ! isDuplicateRef.current ) {
				if ( blockId ) {
					lockBlockSlugByBlockId( blockId );
				}
				setAttributes( { slug: pending } );
			}
		};
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	// Refresh smart tags after the store has processed the slug update.
	// This runs when the slug prop changes (i.e. after setAttributes has been
	// committed to Redux), so getBlocks() inside setFormSpecificSmartTags will
	// read the up-to-date slug value.
	useEffect( () => {
		if ( slugCommittedRef.current ) {
			slugCommittedRef.current = false;
			setFormSpecificSmartTags(
				dispatch( 'core/block-editor' ).updateBlockAttributes
			);
		}
	}, [ slug ] );

	const handleChange = ( value ) => {
		const cleaned = cleanForSlug( value );
		setLocalSlug( cleaned );

		// Real-time duplicate check so the user gets immediate feedback
		if ( cleaned && cleaned !== slug ) {
			const allBlocks = select( 'core/block-editor' ).getBlocks();
			const existingSlugs = collectExistingSlugs( allBlocks, clientId );
			setIsDuplicate( existingSlugs.has( cleaned ) );
		} else {
			setIsDuplicate( false );
		}
	};

	const commitSlug = () => {
		// Revert if cleared — empty slug is not allowed
		if ( ! localSlug ) {
			setLocalSlug( slug );
			setIsDuplicate( false );
			return;
		}

		// No-op if unchanged
		if ( localSlug === slug ) {
			return;
		}

		// Revert if duplicate
		if ( isDuplicate ) {
			setLocalSlug( slug );
			setIsDuplicate( false );
			return;
		}

		// Lock the slug so prepareBlockSlugs no longer re-derives it on label change.
		if ( blockId ) {
			lockBlockSlugByBlockId( blockId );
		}

		// Commit the new slug. The useEffect watching `slug` will refresh
		// smart tags once the store has processed this update.
		slugCommittedRef.current = true;
		setAttributes( { slug: localSlug } );
	};

	return (
		<>
			<SRFMTextControl
				label={ __( 'Slug', 'sureforms' ) }
				value={ localSlug }
				data={ { value: localSlug, label: 'slug' } }
				placeholder={
					slug || __( 'Auto-generated on save', 'sureforms' )
				}
				onChange={ handleChange }
				onBlur={ commitSlug }
			/>
			<Notice
				status={ isDuplicate ? 'error' : 'warning' }
				isDismissible={ false }
			>
				{ isDuplicate
					? __(
						'This slug is already used by another field. It will revert to the previous value.',
						'sureforms'
					  )
					: __(
						'Changing the slug may break form submissions, conditional logic, integrations, or any other feature currently referencing this slug. You will need to update all such references manually.',
						'sureforms'
					  ) }
			</Notice>
		</>
	);
};

export default SlugControl;
