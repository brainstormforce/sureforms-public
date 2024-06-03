import { __ } from '@wordpress/i18n';
import {
	MdOutlineCalendarMonth,
	MdOutlineInsertPageBreak,
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
					icon={
						<MdOutlineCalendarMonth size={ 24 } color="#B4B4B4" />
					}
					label={ __( 'Date & Time Picker', 'sureforms' ) }
				/>
				<IconBlock
					icon={ <FaRegEyeSlash size={ 24 } color="#B4B4B4" /> }
					label={ __( 'Hidden', 'sureforms' ) }
				/>
				<IconBlock
					icon={ <RxSlider size={ 24 } color="#B4B4B4" /> }
					label={ __( 'Number Slider', 'sureforms' ) }
				/>
			</div>
			<div role="presentation">
				<IconBlock
					icon={
						<MdOutlineInsertPageBreak size={ 24 } color="#B4B4B4" />
					}
					label={ __( 'Page Break', 'sureforms' ) }
				/>
				<IconBlock
					icon={ <MdOutlineStarBorder size={ 24 } color="#B4B4B4" /> }
					label={ __( 'Rating Field', 'sureforms' ) }
				/>
				<IconBlock
					icon={
						<MdOutlineCloudUpload size={ 24 } color="#B4B4B4" />
					}
					label={ __( 'Upload Field', 'sureforms' ) }
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
					{ __( 'SureForms Pro', 'sureforms' ) }
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
					{ __( 'Upgrade to Unlock These Fields', 'sureforms' ) }
				</button>
			</div>
			<IconGrid />
		</>
	);
};

export default index;
