/**
 * The Quick Access Sidebar.
 */
import { useLayoutEffect } from '@wordpress/element';
import style from '../editor.lazy.scss';
import Blocks from './blocks';

const Sidebar = () => {
	useLayoutEffect( () => {
		style.use();
		return () => {
			style.unuse();
		};
	}, [] );

	return (
		<div className="srfm-ee-quick-access__sidebar">
			{ /* The block selection buttons will come here. */ }
			<div className="srfm-ee-quick-access__sidebar--blocks">
				<Blocks />
			</div>
			{ /* The sidebar actions will come here - like the plus sign. */ }
			<div className="srfm-ee-quick-access__sidebar--actions"></div>
		</div>
	);
};

export default Sidebar;
