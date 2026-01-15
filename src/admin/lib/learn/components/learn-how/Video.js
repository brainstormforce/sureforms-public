/**
 * Video Component
 *
 * Renders an embedded video iframe with responsive aspect ratio.
 *
 * @param {Object} props       - Component props.
 * @param {string} props.url   - Video embed URL.
 * @param {string} props.title - Video title for accessibility.
 * @return {JSX.Element} Video component.
 */
const Video = ( { url, title } ) => (
	<div className="aspect-video rounded-lg bg-background-tertiary overflow-clip shadow-sm">
		<iframe
			src={ url }
			title={ title }
			className="w-full h-full border-none"
			allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
			allowFullScreen
		/>
	</div>
);

export default Video;
