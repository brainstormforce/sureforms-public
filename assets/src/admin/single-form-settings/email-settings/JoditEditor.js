import { useRef, useMemo } from '@wordpress/element';
import JoditEditor from 'jodit-react';

const Editor = ( { handleEmailBodyContent, content } ) => {
	const editor = useRef( null );
	const config = useMemo(
		() => ( {
			disablePlugins: 'powered-by-jodit',
			allowResizeY: true,
			height: 'auto',
			minHeight: 300,
			toolbarAdaptive: false,
			buttons: 'bold,italic,underline,strikethrough,eraser,ul,ol,font,fontsize,paragraph,brush,classSpan,lineHeight,superscript,subscript,spellcheck,cut,copy,paste,selectall,source',
		} ),
		[ ]
	);
	return (
		<JoditEditor
			ref={ editor }
			value={ content }
			config={ config }
			onBlur={ ( newContent ) => {
				handleEmailBodyContent( newContent );
			} }
		/>
	);
};

export default Editor;
