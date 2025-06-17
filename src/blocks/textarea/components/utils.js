import parse from 'html-react-parser';
import svgIcons from '@Svg/svgs.json';

export const modules = ( id ) => ( {
	toolbar: {
		container: `#${ id }`,
	},
} );

export const QuillToolbar = ( { id } ) => {
	return (
		<div id={ id }>
			<span className="ql-formats">
				<select className="ql-header" defaultValue="false">
					<option value="1">Heading 1</option>
				</select>
				<button className="ql-bold" />
				<button className="ql-italic" />
				<button className="ql-underline" />
				<button className="ql-strike" />
				<button className="ql-list" value="ordered" />
				<button className="ql-list" value="bullet" />
				<button className="ql-blockquote" />
				<select className="ql-align" />
				<select className="ql-color" />
				<select className="ql-background" />
				<button className="ql-link" />
				<button className="ql-clean" />
				<button className="ql-undo">{ parse( svgIcons.undo ) }</button>
				<button className="ql-redo">{ parse( svgIcons.redo ) }</button>
			</span>
		</div>
	);
};

export const formats = [
	'header',
	'font',
	'size',
	'bold',
	'italic',
	'underline',
	'align',
	'strike',
	'script',
	'blockquote',
	'background',
	'list',
	'bullet',
	'indent',
	'link',
	'color',
	'code-block',
];
