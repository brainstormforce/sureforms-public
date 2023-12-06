import { useState, useRef, useMemo } from '@wordpress/element';
import JoditEditor from 'jodit-react';

const Editor = ({ placeholder }) => {
	const editor = useRef(null);
	const [content, setContent] = useState('');

	const config = useMemo(
        () => ({
            readonly: false,
            placeholder: placeholder || 'Start typings...',
            useSearch: false,
			iframe: true,
			minHeight: 240,
			spellcheck: true,
			toolbarButtonSize: "small",
			disablePlugins: "about,fullsize,file,image,image-processor,image-properties,media,powered-by-jodit,speech-recognize,video",
			buttons: "bold,italic,underline,strikethrough,eraser,ul,ol,font,fontsize,paragraph,classSpan,lineHeight,superscript,subscript,spellcheck,cut,copy,paste,selectall",
  			toolbarInlineForSelection: true,
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
			onChange={newContent => {
				console.log({newContent})
			}}
		/>
	);
};

export default Editor;