import { Dialog } from '@bsf/force-ui';
import RenderContent from './learn-how/RenderContent';

/**
 * LearnHowDialog Component
 *
 * A dialog component for displaying educational content with various content types
 * (video, images, links, paragraphs, lists, checklists, buttons).
 *
 * @param {Object}   props         - Component props.
 * @param {boolean}  props.open    - Controls dialog visibility.
 * @param {Function} props.setOpen - Callback to update dialog open state.
 * @param {Object}   props.item    - Content item with title and content array.
 * @return {JSX.Element} LearnHowDialog component.
 */
const LearnHowDialog = ( { open = false, setOpen, item } ) => {
	const handleDialogClose = () => {
		if ( setOpen && typeof setOpen === 'function' ) {
			setOpen( false );
		}
	};

	return (
		<Dialog
			design="simple"
			exitOnEsc
			scrollLock
			exitOnClickOutside
			open={ open }
			setOpen={ handleDialogClose }
		>
			<Dialog.Backdrop />
			<Dialog.Panel className="w-[90vw] max-w-5xl gap-2 max-h-[85vh] sm:max-h-[80vh] mx-auto">
				<Dialog.Header className="px-4 sm:px-5 pb-0">
					<div className="flex items-center justify-between">
						<Dialog.Title className="text-base">
							{ item?.title }
						</Dialog.Title>
						<Dialog.CloseButton />
					</div>

					<Dialog.Description>
						{ item?.description }
					</Dialog.Description>
				</Dialog.Header>

				<Dialog.Body className="pt-2 px-4 sm:px-5 pb-4 sm:pb-5 flex flex-col gap-2 sm:gap-2.5 overflow-auto">
					<RenderContent items={ item?.learn?.content || {} } />
				</Dialog.Body>
			</Dialog.Panel>
		</Dialog>
	);
};

export default LearnHowDialog;
