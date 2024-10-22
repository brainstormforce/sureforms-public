export const modules = {
	toolbar: {
		container: '#toolbar',
	},
	history: {
		delay: 500,
		maxStack: 100,
		userOnly: true,
	},
	clipboard: {
		matchVisual: false,
	},
};

export const QuillToolbar = () => (
	<div id="toolbar">
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
			<button className="ql-clean" />
			<button className="ql-link" />
		</span>
	</div>
);

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
