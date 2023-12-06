import { useEffect, useState, render } from '@wordpress/element';
import ChooseDesign from './ChooseDesign';
import apiFetch from '@wordpress/api-fetch';
import { createBlocksFromInnerBlocksTemplate, parse } from '@wordpress/blocks';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { Modal } from '@wordpress/components';
import Header from './components/Header.js';
import { __ } from '@wordpress/i18n';
import StartingPoint from './components/StartingPoint.js';
import ICONS from './images/icons.js';
import { BrowserRouter as Router, useLocation, Link } from 'react-router-dom';
import TemplateScreen from './components/TemplateScreen.js';

const TemplatePicker = ( { clientId } ) => {
	// Remove admin bar padding.
	useEffect( () => {
		document.querySelector( 'html.wp-toolbar' ).style.paddingTop = 0;
	}, [] );

	const [ template, setTemplate ] = useState( '' );
	const [ templatePickerVisible, setTemplatePickerVisible ] =
		useState( false );

	const postStatus = useSelect( ( select ) => {
		return select( 'core/editor' ).getEditedPostAttribute( 'status' );
	}, [] );

	useEffect( () => {
		getPatterns();
	}, [] );

	const [ patterns, setPatterns ] = useState( [] );
	const { replaceInnerBlocks, resetBlocks } = useDispatch( blockEditorStore );
	// const blockCount = useSelect( ( select ) =>
	// 	select( 'core/editor' ).getBlockCount( clientId )
	// );
	let sureforms_keys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);
	const { editPost } = useDispatch( editorStore );

	useEffect( () => {
		if ( template && postStatus !== 'publish' ) {
			onCreate( template );
		} else if ( template ) {
			onCreate( template );
		}
	}, [ template, onCreate ] );

	// useEffect( () => {
	// 	if (
	// 		sureforms_keys._srfm_form_template === '' &&
	// 		postStatus !== 'publish'
	// 	) {
	// 		setTemplatePickerVisible( true );
	// 	}
	// }, [] );

	const getPatterns = async () => {
		const newPatterns = await apiFetch( {
			path: '/sureforms/v1/form-patterns',
		} );
		setPatterns( newPatterns );
	};

	/**
	 * Maybe create the template for the form.
	 *
	 */
	const maybeCreateTemplate = async () => {
		const newPattern = patterns.find(
			( singlePattern ) =>
				singlePattern.name === `sureforms/${ template }`
		);

		if ( ! newPattern ) {
			alert( 'Something went wrong' );
			return;
		}
		// parse blocks.
		const parsed = parse( newPattern.content );

		return parsed;
	};

	const onCreate = async () => {
		const option_array = {};
		option_array[ '_srfm_form_template' ] = template;
		editPost( {
			meta: option_array,
		} );
		const result = await maybeCreateTemplate( {
			template,
		} );
		resetBlocks( [] );
		replaceInnerBlocks(
			clientId,
			createBlocksFromInnerBlocksTemplate( result ),
			false
		);
	};

	const handleTemplatePicker = ( choice ) => {
		setTemplatePickerVisible( ! templatePickerVisible );
		if ( typeof choice !== 'object' ) {
			setTemplate( choice );
		}
	};

	useEffect( () => {
		if ( template ) {
			onCreate( template );
		}
	}, [ template, onCreate ] );

	// Starting screen navigation
	function useQuery() {
		return new URLSearchParams( useLocation().search );
	}

	function QueryScreen() {
		const query = useQuery();
		const method = query.get( 'method' );
		switch ( method ) {
			case 'template':
				return (
					<>
						<Header />
						<TemplateScreen />
					</>
				);
			default:
				return (
					<div className="srfm-tp-sp-container">
						<Header />
						<div className="srfm-tp-sp-methods-container">
							<h1 className="srfm-tp-sp-heading">
								{ __( 'Create a New Form', 'sureforms' ) }
							</h1>
							<div className="srfm-tp-sp-methods">
								<Link
									to={ {
										pathname: 'wp-admin/post-new.php',
										search: `?post_type=sureforms_form`,
									} }
									reloadDocument
								>
									<StartingPoint
										icon={ ICONS.scratch }
										title={ __(
											'Start from Scratch',
											'sureforms'
										) }
										description={ __(
											'Tailoring your form precisely to your unique needs. No coding skills required—just unleash your creativity.',
											'sureforms'
										) }
									/>
								</Link>
								<Link
									to={ {
										pathname: 'wp-admin/admin.php',
										search: `?page=sureforms_add_new_form&method=template`,
									} }
								>
									<StartingPoint
										icon={ ICONS.template }
										title={ __(
											'Use a Template',
											'sureforms'
										) }
										description={ __(
											'Save time and jumpstart your form creation process with our extensive library of professionally designed templates.',
											'sureforms'
										) }
									/>
								</Link>
								<StartingPoint
									icon={ ICONS.ai }
									title={ __(
										'Create with AI',
										'sureforms'
									) }
									description={ __(
										'Experience the future of form building. AI-powered algorithms analyze your requirements and generate a tailor-made form.',
										'sureforms'
									) }
									isComingSoon={ true }
								/>
							</div>
						</div>
					</div>
				);
		}
		return '';
	}

	// if ( ! template ) {
	return (
		<>
			{ /* <Modal
					// focusOnMount
					// shouldCloseOnEsc
					// shouldCloseOnClickOutside
					overlayClassName="srfm-template-picker-modal-overlay"
					// title={ __( 'Choose A Starting Template', 'sureforms' ) }
					onRequestClose={ handleTemplatePicker }
				>
					<ChooseDesign
						templates={ patterns }
						template={ template }
						setTemplate={ setTemplate }
						handleTemplatePicker={ handleTemplatePicker }
					/>
				</Modal> */ }
			<Router>
				<QueryScreen />
			</Router>
		</>
	);
};
// return null;
// };

export default TemplatePicker;

( function () {
	const app = document.getElementById( 'srfm-add-new-form-container' );

	function renderApp() {
		if ( null !== app ) {
			render( <TemplatePicker />, app );
		}
	}

	document.addEventListener( 'DOMContentLoaded', renderApp );
} )();
