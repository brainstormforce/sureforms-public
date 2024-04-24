import { __ } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';
import {
	useRef,
	useEffect,
	useState,
	useLayoutEffect,
} from '@wordpress/element';
import editorStyles from './editor.lazy.scss';

const Compliance = ( { formCustomCssData } ) => {
	const tabRef = useRef( null );
	const [ customCSS, setCustomCSS ] = useState( formCustomCssData );

	// Use the editor styles. This is important to make sure the editor styles are applied to the custom CSS editor.
	useLayoutEffect( () => {
		editorStyles.use();
		return () => {
			editorStyles.unuse();
		};
	}, [] );

	// Update the custom CSS when the formCustomCssData prop changes.
	useEffect( () => {
		const isExistStyle = document.getElementById(
			'uagb-blocks-editor-custom-css'
		);
		if ( ! isExistStyle ) {
			const node = document.createElement( 'style' );
			node.setAttribute( 'id', 'uagb-blocks-editor-custom-css' );
			node.textContent = customCSS;
			document.head.appendChild( node );
		} else {
			isExistStyle.textContent = customCSS;
		}
	}, [ customCSS ] );

	// Remove the editor when the component is unmounted.
	useEffect( () => {
		return () => {
			const spectraCustomCSSPanel = document.querySelector(
				'.spectra-custom-css-panel'
			);
			const editors =
				spectraCustomCSSPanel?.querySelectorAll( '.CodeMirror-wrap' );

			if ( editors ) {
				editors?.forEach( ( editor ) => {
					editor?.remove();
				} );
			}
		};
	}, [] );

	// Initialize the editor when the component is mounted.
	useEffect( () => {
		const spectraCustomCSSPanel = document.querySelector(
			'.spectra-custom-css-panel'
		);
		const editors =
			spectraCustomCSSPanel?.querySelectorAll( '.CodeMirror-wrap' );

		// Remove the existing editors.
		if ( editors ) {
			editors?.forEach( ( editor ) => {
				editor?.remove();
			} );
		}

		// Initialize the editor.
		const editor = wp?.codeEditor?.initialize( tabRef?.current, {
			...wp.codeEditor.defaultSettings.codemirror,
			scrollbarStyle: null,
		} );

		const codeMirrorEditor = document.querySelector(
			'.spectra-css-editor .CodeMirror-code'
		);

		// Save the custom CSS when the editor value changes.
		if ( codeMirrorEditor ) {
			codeMirrorEditor?.addEventListener( 'keyup', function () {
				editor?.codemirror?.save();
				const value = editor?.codemirror?.getValue();

				setCustomCSS( value );
				dispatch( 'core/editor' ).editPost( {
					meta: { _srfm_form_custom_css: value },
				} );
			} );
		}
	}, [ tabRef ] );

	return (
		<div className="srfm-modal-content spectra-custom-css-panel">
			<div className="srfm-modal-inner-content">
				<div className="srfm-modal-inner-heading">
					<span className="srfm-modal-inner-heading-text">
						<h4>{ __( 'Form Custom CSS', 'sureforms' ) }</h4>
					</span>
				</div>
				<div className="srfm-modal-inner-box">
					<div className="srfm-modal-inner-box-text">
						<h5>{ __( 'Write your CSS here', 'sureforms' ) }</h5>
					</div>
					<div className="srfm-modal-separator"></div>
					<div className="srfm-modal-inner-box-table">
						{ /* <div> */ }
						{ /* <p className="spectra-custom-css-notice">
								{ __(
									'Add your own CSS code here to customize the page as per your expectations.',
									'ultimate-addons-for-gutenberg'
								) }
							</p>
							<hr></hr>
							<p className="spectra-custom-css-example spectra-custom-css-notice">
								{ __(
									`Use custom class added in block's advanced settings to target your desired block. Examples: .my-class {text-align: center;} // my-class is a custom selector`,
									'sureforms'
								) }
							</p> */ }
						<div
							id="spectra-css-editor"
							className="spectra-css-editor"
							style={ {
								width: '100%',
							} }
						>
							<textarea
								value={ customCSS }
								ref={ tabRef }
							></textarea>
						</div>
						{ /* </div> */ }
					</div>
				</div>
			</div>
		</div>
	);
};

export default Compliance;
