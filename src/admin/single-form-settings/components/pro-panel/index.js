import { __ } from '@wordpress/i18n';
import {
	MdOutlineCalendarMonth,
	MdOutlineInsertPageBreak,
	MdOutlineStarBorder,
	MdOutlineCloudUpload,
	MdAccessTime,
} from 'react-icons/md';
import { FaFileSignature , FaRegEyeSlash } from 'react-icons/fa';
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

const iconBlocks = [
	{
		icon: <MdOutlineCalendarMonth size={ 24 } color="#B4B4B4" />,
		label: __( 'Date Picker', 'sureforms' ),
		hideIn: [
			'starter',
			'pro',
			'business',
		],
	},
	{
		icon: <MdAccessTime size={ 24 } color="#B4B4B4" />,
		label: __( 'Time Picker', 'sureforms' ),
		hideIn: [
			'starter',
			'pro',
			'business',
		],
	},
	{
		icon: <FaRegEyeSlash size={ 24 } color="#B4B4B4" />,
		label: __( 'Hidden', 'sureforms' ),
		hideIn: [
			'starter',
			'pro',
			'business',
		],
	},
	{
		icon: <RxSlider size={ 24 } color="#B4B4B4" />,
		label: __( 'Slider', 'sureforms' ),
		hideIn: [
			'starter',
			'pro',
			'business',
		],
	},
	{
		icon: <MdOutlineInsertPageBreak size={ 24 } color="#B4B4B4" />,
		label: __( 'Page Break', 'sureforms' ),
		hideIn: [
			'starter',
			'pro',
			'business',
		],
	},
	{
		icon: <MdOutlineStarBorder size={ 24 } color="#B4B4B4" />,
		label: __( 'Rating', 'sureforms' ),
		hideIn: [
			'starter',
			'pro',
			'business',
		],
	},
	{
		icon: <MdOutlineCloudUpload size={ 24 } color="#B4B4B4" />,
		label: __( 'Upload', 'sureforms' ),
		hideIn: [
			'starter',
			'pro',
			'business',
		],
	},
	{
		icon: <FaFileSignature size={ 24 } color="#B4B4B4" />,
		label: __( 'Signature', 'sureforms' ),
		hideIn: [
			'pro',
			'business',
		],
	},
];

// filter the blocks based on the package name
const filterIconBlocks = iconBlocks.filter( ( block ) => {
	// if there is no package name, that means it is free plugin.
	// so, show all the blocks preview
	if ( ! srfm_block_data?.pro_plugin_name.split( ' ' )[ 1 ] ) {
		return true;
	}

	// if current package is starter, then do not show the block preview with package starter
	if ( srfm_block_data?.pro_plugin_name.split( ' ' )[ 1 ].toLowerCase() === 'starter' ) {
		return block.hideIn.includes( 'starter' ) ? false : true;
	// if current package is pro, then do not show the block preview with package starter and pro
	} else if ( srfm_block_data?.pro_plugin_name.split( ' ' )[ 1 ].toLowerCase() === 'pro' ) {
		return block.hideIn.includes( 'pro' ) || block.hideIn.includes( 'starter' ) ? false : true;
	// if current package is business, then do not show the block preview with package starter, pro and business
	} else if ( srfm_block_data?.pro_plugin_name.split( ' ' )[ 1 ].toLowerCase() === 'business' ) {
		return block.hideIn.includes( 'business' ) || block.hideIn.includes( 'pro' ) || block.hideIn.includes( 'starter' ) ? false : true;
	}
	return true;
} );

const IconGrid = () => (
	<div className="block-editor-inserter__panel-content">
		<div className="block-editor-block-types-list">
			{ filterIconBlocks.reduce( ( acc, block, index ) => {
				if ( index % 3 === 0 ) {
					acc.push( <div role="presentation" key={ `group-${ index }` }>{ [] }</div> );
				}
				acc[ acc.length - 1 ].props.children.push(
					<IconBlock key={ index } icon={ block.icon } label={ block.label } />
				);
				return acc;
			}, [] ) }
		</div>
	</div>
);

const index = () => {
	// if there are no blocks to show, return null
	if ( filterIconBlocks.length === 0 ) {
		return null;
	}

	return (
		<>
			<div className="block-editor-inserter__panel-header">
				<h2 className="block-editor-inserter__panel-title">
					{ __( 'SUREFORMS PREMIUM FIELDS', 'sureforms' ) }
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
