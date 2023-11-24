import { useEffect, useState } from '@wordpress/element';
import ChooseDesign from './ChooseDesign';
export default ( { onCreate, templates, handleTemplatePicker } ) => {
	const [ template, setTemplate ] = useState( '' );

	useEffect( () => {
		if ( template ) {
			onCreate( template );
		}
	}, [ template, onCreate ] );

	if ( ! template ) {
		return (
			<ChooseDesign
				templates={ templates }
				template={ template }
				setTemplate={ setTemplate }
				handleTemplatePicker={ handleTemplatePicker }
			/>
		);
	}
	return null;
};
