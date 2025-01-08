import { useEffect, useState, useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { createRoot } from 'react-dom/client';
import { RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { BlockInserterWrapper } from '../Inserter.js';

const SubmitButton = ( props ) => {
	const metaValues = useSelect( ( select ) => select( editorStore ).getEditedPostAttribute( 'meta' ) );
	const buttonElement = useRef( null );

	const labelText = metaValues?._srfm_submit_button_text || __( 'Submit', 'sureforms' );

	useEffect( () => {
		const eventToButton = () => {
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
		};

		if ( buttonElement.current ) {
			buttonElement.current.addEventListener( 'click', eventToButton );
		}

		return () => {
			if ( buttonElement.current ) {
				buttonElement.current.removeEventListener( 'click', eventToButton );
			}
		};
	}, [] );

	return <button className="srfm-submit-richtext srfm-button srfm-submit-button srfm-btn-alignment srfm-btn-bg-color" ref={ buttonElement }>
		<RichText
			tagName="label"
			value={ labelText }
			onChange={ ( value ) => props.updateMeta( '_srfm_submit_button_text', value ) }
			placeholder={ __( 'Submit', 'sureforms' ) }
		/>
	</button>;
};

export default function useSubmitButton( args ) {
	const { isInlineButtonBlockPresent, updateMeta } = args;

	const [ codeEditor, setCodeEditor ] = useState( null );

	useEffect( () => {
		setCodeEditor( document.querySelector( '.editor-post-text-editor' ) );
	}, [] );

	function addSubmitButton( elm ) {
		if ( ! elm.closest( 'body' ).querySelector( '.srfm-submit-btn-container' ) ) {
			const appendHtml = `<div class="srfm-custom-block-inserter"></div><div class="srfm-submit-btn-container srfm-submit-btn-font-size"></div>`;

			elm.insertAdjacentHTML( 'afterend', appendHtml );
			// Add block inserter in the srfm-custom-block-inserter div.
			const getBlockInserterDiv = elm.parentElement.querySelector(
				'.srfm-custom-block-inserter'
			);

			if ( getBlockInserterDiv ) {
				createRoot( getBlockInserterDiv ).render( <BlockInserterWrapper /> );
			}
		}

		const buttonCtn = elm.parentElement.querySelector(
			'.srfm-submit-btn-container'
		);

		if ( buttonCtn ) {
			const getButton = buttonCtn?.querySelector( 'button' );
			if ( ! getButton ) {
				createRoot( buttonCtn ).render( <SubmitButton updateMeta={ updateMeta } /> );
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
			if ( ! isInlineButtonBlockPresent ) {
				if ( elm ) {
					addSubmitButton( elm );
				}

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
	}, [ codeEditor, isInlineButtonBlockPresent ] );
}
