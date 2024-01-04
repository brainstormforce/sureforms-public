const CustomUndo = () => (
	<svg viewBox="0 0 18 18">
		<polygon className="ql-fill ql-stroke" points="6 10 4 12 2 10 6 10" />
		<path
			className="ql-stroke"
			d="M8.09,13.91A4.6,4.6,0,0,0,9,14,5,5,0,1,0,4,9"
		/>
	</svg>
);

// Redo button icon component for Quill editor
const CustomRedo = () => (
	<svg viewBox="0 0 18 18">
		<polygon
			className="ql-fill ql-stroke"
			points="12 10 14 12 16 10 12 10"
		/>
		<path
			className="ql-stroke"
			d="M9.91,13.91A4.6,4.6,0,0,1,9,14a5,5,0,1,1,5-5"
		/>
	</svg>
);

// Undo and redo functions for Custom Toolbar
function undoChange() {
	this.quill.history.undo();
}
function redoChange() {
	this.quill.history.redo();
}

// Modules object for setting up the Quill editor
export const modules = {
	toolbar: {
		container: '#toolbar',
		handlers: {
			undo: undoChange,
			redo: redoChange,
		},
	},
	history: {
		delay: 500,
		maxStack: 100,
		userOnly: true,
	},
};

// Formats objects for setting up the Quill editor
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
	'image',
	'color',
	'code-block',
];

// Quill Toolbar component
export const QuillToolbar = () => (
	<div id="toolbar">
		<span className="ql-formats">
			<select className="ql-header" defaultValue="false">
				<option value="1">Heading 1</option>
				<option value="2">Heading 2</option>
				<option value="3">Heading 3</option>
				<option value="4">Heading 4</option>
				<option value="5">Heading 5</option>
				<option value="6">Heading 6</option>
				<option value="false">Normal</option>
			</select>
			<button className="ql-bold" />
			<button className="ql-italic" />
			<button className="ql-underline" />
			<button className="ql-strike" />
			<button className="ql-list" value="ordered" />
			<button className="ql-list" value="bullet" />
			<button className="ql-indent" value="-1" />
			<button className="ql-indent" value="+1" />
			<button className="ql-blockquote" />
			<select className="ql-align" />
			<select className="ql-color" />
			<select className="ql-background" />
			<button className="ql-link" />
			<button className="ql-clean" />
			<button className="ql-undo">
				<CustomUndo />
			</button>
			<button className="ql-redo">
				<CustomRedo />
			</button>
		</span>
	</div>
);

export default QuillToolbar;
