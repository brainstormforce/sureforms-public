import { __ } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';
import {
	useRef,
	useEffect,
	useState,
	useLayoutEffect,
} from '@wordpress/element';
import editorStyles from './editor.lazy.scss';

const FormCustomCssPanel = ( { formCustomCssData } ) => {
	const tabRef = useRef( null );
	const [ customCSS, setCustomCSS ] = useState( formCustomCssData );

	// Use the editor styles. This is important to make sure the editor styles are applied to the custom CSS editor.
	useLayoutEffect( () => {
		editorStyles.use();
		return () => {
			editorStyles.unuse();
		};
	}, [] );

	// Initialize the editor when the component is mounted and cleanup the editor when the component is unmounted.
	useEffect( () => {
		const cleanupEditors = () => {
			const srfmCustomCSSPanel = document.querySelector(
				'.srfm-custom-css-panel'
			);
			const editors =
				srfmCustomCSSPanel?.querySelectorAll( '.CodeMirror-wrap' );
			editors?.forEach( ( editor ) => {
				editor?.remove();
			} );
		};

		cleanupEditors(); // Remove existing editors when the component is mounted

		const initializeEditor = () => {
			const editor = wp?.codeEditor?.initialize( tabRef?.current, {
				...wp.codeEditor.defaultSettings.codemirror,
				scrollbarStyle: null,
			} );

			const codeMirrorEditor = document.querySelector(
				'.srfm-css-editor .CodeMirror-code'
			);

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
		};

		initializeEditor(); // Initialize the editor when the component is mounted

		return cleanupEditors; // Remove existing editors when the component is unmounted
	}, [ tabRef ] );

	return (
		<div className="srfm-modal-content srfm-custom-css-panel">
			<div className="srfm-modal-inner-content">
				<div className="srfm-modal-inner-heading">
					<span className="srfm-modal-inner-heading-text">
						<h4>{ __( 'Custom CSS', 'sureforms' ) }</h4>
					</span>
				</div>
				<div className="srfm-modal-inner-box">
					<div className="srfm-modal-inner-box-table">
						<span>
							{ __(
								'The following CSS styles added below will only apply to this form container.',
								'sureforms'
							) }
						</span>
						<div
							id="srfm-css-editor"
							className="srfm-css-editor"
							style={ {
								width: '100%',
							} }
						>
							<textarea value={ customCSS } ref={ tabRef } />
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default FormCustomCssPanel;
