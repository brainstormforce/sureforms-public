import { useState, useRef, useMemo } from '@wordpress/element';
import JoditEditor from 'jodit-react';

const Editor = ({ placeholder }) => {
	const editor = useRef(null);
	const [content, setContent] = useState('');

	const config = useMemo(
        () => ({
            readonly: false,
            placeholder: placeholder || 'Start typings...',
            showCharsCounter: false,
            showWordsCounter: false,
            showXPathInStatusbar: false
        }),
        [placeholder]
    );

	return (
		<JoditEditor
			ref={editor}
			value={content}
			config={config}
			tabIndex={1}
			onBlur={newContent => setContent(newContent)}
			onChange={newContent => {}}
		/>
	);
};

export default Editor;