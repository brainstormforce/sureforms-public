import { __ } from '@wordpress/i18n';
import {
	MdOutlineCalendarMonth,
	MdOutlineInsertPageBreak,
	MdOutlinePassword,
	MdOutlineStarBorder,
	MdOutlineCloudUpload,
} from 'react-icons/md';
import { FaRegEyeSlash } from 'react-icons/fa';
import { RxSlider } from 'react-icons/rx';

const IconBlock = ( { icon, label } ) => (
	<div className="block-editor-block-types-list__list-item">
		<button
			type="button"
			tabIndex="-1"
			role="option"
			className="components-button block-editor-block-types-list__item"
			disabled
		>
			<span className="block-editor-block-types-list__item-icon">
				<span className="block-editor-block-icon">{ icon }</span>
			</span>
			<span className="block-editor-block-types-list__item-title">
				<span className="components-truncate">{ label }</span>
			</span>
		</button>
	</div>
);

const IconGrid = () => (
	<div className="block-editor-inserter__panel-content">
		<div className="block-editor-block-types-list">
			<div role="presentation">
				<IconBlock
					icon={ <MdOutlineCalendarMonth /> }
					label={ __( 'Date & Time', 'sureforms' ) }
				/>
				<IconBlock
					icon={ <FaRegEyeSlash /> }
					label={ __( 'Hidden', 'sureforms' ) }
				/>
				<IconBlock
					icon={ <RxSlider /> }
					label={ __( 'Slider', 'sureforms' ) }
				/>
			</div>
			<div role="presentation">
				<IconBlock
					icon={ <MdOutlineInsertPageBreak /> }
					label={ __( 'Page Break', 'sureforms' ) }
				/>
				<IconBlock
					icon={ <MdOutlinePassword /> }
					label={ __( 'Password', 'sureforms' ) }
				/>
				<IconBlock
					icon={ <MdOutlineStarBorder /> }
					label={ __( 'Rating', 'sureforms' ) }
				/>
			</div>
			<div role="presentation">
				<IconBlock
					icon={ <MdOutlineCloudUpload /> }
					label={ __( 'Upload', 'sureforms' ) }
				/>
			</div>
		</div>
	</div>
);

const index = () => {
	return (
		<>
			<div className="block-editor-inserter__panel-header">
				<h2 className="block-editor-inserter__panel-title">
					{ __( 'Pro Fields', 'sureforms' ) }
				</h2>
			</div>
			<div className="srfm-upgrade-pro-btn-container">
				<button
					style={ {} }
					className="srfm-upgrade-pro-btn"
					onClick={ () => {
						window.open( '/', '_blank' );
					} }
				>
					{ __( 'Upgrade to unlock the Pro Fields', 'sureforms' ) }
				</button>
			</div>
			<IconGrid />
		</>
	);
};

export default index;
