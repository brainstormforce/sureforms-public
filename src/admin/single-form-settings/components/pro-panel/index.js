import { __ } from '@wordpress/i18n';
import {
	MdOutlineCalendarMonth,
	MdOutlineInsertPageBreak,
	MdOutlineStarBorder,
	MdOutlineCloudUpload,
	MdAccessTime,
} from 'react-icons/md';
import { FaFileSignature } from 'react-icons/fa';
import { FaRegEyeSlash } from 'react-icons/fa';
import { RxSlider } from 'react-icons/rx';
import { addQueryParam } from '@Utils/Helpers';

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

// const IconGrid = () => (
// 	<div className="block-editor-inserter__panel-content">
// 		<div className="block-editor-block-types-list">
// 			<div role="presentation">
// 				<IconBlock
// 					icon={
// 						<MdOutlineCalendarMonth size={ 24 } color="#B4B4B4" />
// 					}
// 					label={ __( 'Date Picker', 'sureforms' ) }
// 				/>
// 				<IconBlock
// 					icon={
// 						<MdAccessTime size={ 24 } color="#B4B4B4" />
// 					}
// 					label={ __( 'Time Picker', 'sureforms' ) }
// 				/>
// 				<IconBlock
// 					icon={ <FaRegEyeSlash size={ 24 } color="#B4B4B4" /> }
// 					label={ __( 'Hidden', 'sureforms' ) }
// 				/>
// 			</div>
// 			<div role="presentation">
// 				<IconBlock
// 					icon={ <RxSlider size={ 24 } color="#B4B4B4" /> }
// 					label={ __( 'Slider', 'sureforms' ) }
// 				/>
// 				<IconBlock
// 					icon={
// 						<MdOutlineInsertPageBreak size={ 24 } color="#B4B4B4" />
// 					}
// 					label={ __( 'Page Break', 'sureforms' ) }
// 				/>
// 				<IconBlock
// 					icon={ <MdOutlineStarBorder size={ 24 } color="#B4B4B4" /> }
// 					label={ __( 'Rating', 'sureforms' ) }
// 				/>
// 			</div>
// 			<div role="presentation">
// 				<IconBlock
// 					icon={
// 						<MdOutlineCloudUpload size={ 24 } color="#B4B4B4" />
// 					}
// 					label={ __( 'Upload', 'sureforms' ) }
// 				/>
// 			</div>
// 		</div>
// 	</div>
// );

const iconBlocks = [
	{
		icon: <MdOutlineCalendarMonth size={24} color="#B4B4B4" />, 
		label: __('Date Picker', 'sureforms')
	},
	{
		icon: <MdAccessTime size={24} color="#B4B4B4" />, 
		label: __('Time Picker', 'sureforms')
	},
	{
		icon: <FaRegEyeSlash size={24} color="#B4B4B4" />, 
		label: __('Hidden', 'sureforms')
	},
	{
		icon: <RxSlider size={24} color="#B4B4B4" />, 
		label: __('Slider', 'sureforms')
	},
	{
		icon: <MdOutlineInsertPageBreak size={24} color="#B4B4B4" />, 
		label: __('Page Break', 'sureforms')
	},
	{
		icon: <MdOutlineStarBorder size={24} color="#B4B4B4" />, 
		label: __('Rating', 'sureforms')
	},
	{
		icon: <MdOutlineCloudUpload size={24} color="#B4B4B4" />, 
		label: __('Upload', 'sureforms')
	},
	{
		icon: <FaFileSignature size={24} color="#B4B4B4" />, 
		label: __('Signature', 'sureforms')
	}
];

const IconGrid = () => (
	<div className="block-editor-inserter__panel-content">
		<div className="block-editor-block-types-list">
			{iconBlocks.reduce((acc, block, index) => {
				if (index % 3 === 0) {
					acc.push(<div role="presentation" key={`group-${index}`}>{[]}</div>);
				}
				acc[acc.length - 1].props.children.push(
					<IconBlock key={index} icon={block.icon} label={block.label} />
				);
				return acc;
			}, [])}
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
						window.open( addQueryParam( srfm_admin?.sureforms_pricing_page, 'sureforms_editor' ), '_blank' );
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
