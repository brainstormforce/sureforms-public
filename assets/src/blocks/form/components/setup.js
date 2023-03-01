import { useEffect, useState } from '@wordpress/element';
import ChooseDesign from './ChooseDesign';
export default ({ onCreate, templates }) => {
    const [template, setTemplate] = useState('');
    useEffect(() => {
        if (template) {
            onCreate(template);
        }
    }, [template]);
    if (!template) {
		return (
			<ChooseDesign
				templates={templates}
				template={template}
				setTemplate={setTemplate}
			/>
		);
	}
    return null;
}