import { useEffect } from '@wordpress/element';
import { validateClassName } from '../utils';
const useContainerDynamicClass = ( props ) => {
	const { sureformsKeys, documentBody, shouldIframe, editorMode } = props;

	const getStyleWrapper = () => {
		if ( shouldIframe ) {
			return documentBody;
		}

		return documentBody?.querySelector( '.editor-styles-wrapper' );
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
		shouldIframe,
	] );
};

export default useContainerDynamicClass;
