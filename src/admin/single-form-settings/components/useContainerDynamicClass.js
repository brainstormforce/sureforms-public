import { useEffect } from '@wordpress/element';
import { validateClassName } from '../utils';
const useContainerDynamicClass = ( props ) => {
	const { sureformsKeys, documentBody, editorMode } = props;

	const getStyleWrapper = () => {
		// Detect from the DOM rather than the `shouldIframe` prediction:
		// when the iframe body actually rendered, `documentBody` IS the
		// `.block-editor-iframe__body` and is already the style wrapper.
		// Falling back to `.editor-styles-wrapper` covers the non-iframe
		// case where `documentBody` is the top-level `<body>`.
		if ( documentBody?.classList?.contains( 'block-editor-iframe__body' ) ) {
			return documentBody;
		}

		return (
			documentBody?.querySelector( '.editor-styles-wrapper' ) ??
			documentBody
		);
	};

	const addRootClass = () => {
		// Find the root container of the form
		const formRootContainer = getStyleWrapper();

		if ( formRootContainer && sureformsKeys?._srfm_additional_classes ) {
			if ( ! formRootContainer.hasAttribute( 'data-existing-classes' ) ) {
				// Add existing classes in the root container by creating an attribute data-existing-classes.
				const getExistingClasses =
					formRootContainer.getAttribute( 'class' );

				if ( getExistingClasses ) {
					formRootContainer.setAttribute(
						'data-existing-classes',
						getExistingClasses
					);
				}
			}

			// Split the classes string by spaces
			let classesArray =
				sureformsKeys._srfm_additional_classes.split( ' ' );

			const getExistingClassAttribute = formRootContainer.getAttribute(
				'data-existing-classes'
			);

			if ( getExistingClassAttribute ) {
				classesArray = [
					...getExistingClassAttribute.split( ' ' ),
					...classesArray,
				];
			}

			let classString = '';

			// Add classes individually
			classesArray.forEach( ( classname ) => {
				const validClass = validateClassName( classname );
				if ( validClass ) {
					classString += ` ${ classname }`;
				}
			} );

			formRootContainer.setAttribute( 'class', classString );
		}
	};

	useEffect( () => {
		addRootClass();
	}, [
		sureformsKeys?._srfm_additional_classes,
		editorMode,
		documentBody,
	] );
};

export default useContainerDynamicClass;
