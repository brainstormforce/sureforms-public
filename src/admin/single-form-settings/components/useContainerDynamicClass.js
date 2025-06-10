import { useEffect } from '@wordpress/element';
import { validateClassName } from '../utils';
const useContainerDynamicClass = ( sureformsKeys ) => {
	const addRootClass = () => {
		// Find the root container of the form
		const formRootContainer = document.querySelector(
			'.editor-styles-wrapper'
		);

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
	}, [ sureformsKeys?._srfm_additional_classes ] );
};

export default useContainerDynamicClass;
