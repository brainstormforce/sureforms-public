import { useEffect, useState } from '@wordpress/element';
import { createRoot } from 'react-dom/client';
import { RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { BlockInserterWrapper } from '../Inserter.js';

export default function useSubmitButton( args ) {
	const {
		sureformsKeys,
		blockCount,
		isInlineButtonBlockPresent,
		updateMeta,
	} = args;

	const [ codeEditor, setCodeEditor ] = useState( null );
	const [ submitBtnContainer, setSubmitBtnContainer ] = useState( null );

	useEffect( () => {
		setCodeEditor( document.querySelector( '.editor-post-text-editor' ) );
		setSubmitBtnContainer( document.querySelector( '.srfm-submit-btn-container' ) );
	}, [] );

	function addSubmitButton( elm ) {
		const inheritClass = 'srfm-btn-alignment wp-block-button__link';
		const customClass =
			'srfm-button srfm-submit-button srfm-btn-alignment srfm-btn-bg-color';
		const btnClass =
			sureformsKeys?._srfm_inherit_theme_button &&
			sureformsKeys._srfm_inherit_theme_button
				? inheritClass
				: customClass;
		const btnCtnClass =
			sureformsKeys?._srfm_inherit_theme_button &&
			sureformsKeys._srfm_inherit_theme_button
				? 'wp-block-button'
				: 'srfm-submit-btn-font-size';

		const appendHtml = `<div class="srfm-custom-block-inserter"></div><div class="srfm-submit-btn-container ${ btnCtnClass }"><button class="srfm-submit-richtext ${ btnClass }"></button></div>`;

		if ( elm ) {
			if (
				! elm
					.closest( 'body' )
					.querySelector( '.srfm-submit-btn-container' )
			) {
				elm.insertAdjacentHTML( 'afterend', appendHtml );

				// If the normal button is present, add RichText to the button.
				const elementParent = elm.parentElement;

				const buttonContainer = elementParent.querySelector(
					'.srfm-submit-btn-container'
				);

				const button = buttonContainer.querySelector(
					'.srfm-submit-richtext'
				);

				const submitBtnText = sureformsKeys?._srfm_submit_button_text;

				// Add block inserter in the srfm-custom-block-inserter div.
				const getBlockInserterDiv = elementParent.querySelector(
					'.srfm-custom-block-inserter'
				);

				if ( getBlockInserterDiv ) {
					createRoot( getBlockInserterDiv ).render( <BlockInserterWrapper /> );
				}

				createRoot( button ).render(
					<RichText
						tagName="label"
						value={
							submitBtnText
								? submitBtnText
								: __( 'Submit', 'sureforms' )
						}
						onChange={ ( value ) =>
							updateMeta( '_srfm_submit_button_text', value )
						}
						placeholder={ __( 'Submit', 'sureforms' ) }
					/>
				);

				button.addEventListener( 'click', () => {
					// need multiple timeouts for DOM elements to find.
					// click on form section
					setTimeout( () => {
						const editPostTab = document.getElementById( 'tabs-0-edit-post/document' );

						editPostTab?.click();
					}, 100 );

					// click on style tab
					setTimeout( () => {
						// elements for submit button event listener
						const styleTabElement = document.querySelectorAll( '.srfm-inspector-tabs div' )[ 1 ]; // Style Tab
						styleTabElement?.click();
					}, 150 );
				} );
			}
		}
	}

	useEffect( () => {
		setTimeout( () => {
			const elm = document.querySelector(
				'.block-editor-block-list__layout'
			);

			// If Custom Button is present, remove the default button.
			if ( isInlineButtonBlockPresent ) {
				const submitBtn = document.querySelectorAll(
					'.srfm-submit-btn-container'
				);
				if ( submitBtn.length > 0 ) {
					submitBtn[ 0 ].remove();
				}
			}

			// If Custom Button is not present, add the default button. Remove the default button if there are more than one.
			if ( ! submitBtnContainer && ! isInlineButtonBlockPresent ) {
				addSubmitButton( elm );

				// remove duplicated submit button from the view after inline button is removed
				const submitBtn = document.querySelectorAll(
					'.srfm-submit-btn-container'
				);
				if ( submitBtn.length > 1 ) {
					submitBtn[ 1 ].remove();
				}

				// remove duplicated inserter from the view after inline button is removed
				const appender = document.querySelectorAll(
					'.srfm-custom-block-inserter'
				);
				if ( appender.length > 1 ) {
					appender[ 1 ].remove();
				}
			}
		}, 200 );
	}, [
		sureformsKeys,
		codeEditor,
		blockCount,
		isInlineButtonBlockPresent,
		submitBtnContainer,
	] );
}
