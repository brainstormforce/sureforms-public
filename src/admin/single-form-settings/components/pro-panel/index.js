import { __ } from '@wordpress/i18n';
import {
	MdOutlineCalendarMonth,
	MdOutlineInsertPageBreak,
	MdOutlineStarBorder,
	MdOutlineCloudUpload,
	MdAccessTime,
	MdOutlinePassword,
} from 'react-icons/md';
import { FaRegEyeSlash } from 'react-icons/fa';
import svgIcons from '@Svg/svgs.json';
import parse from 'html-react-parser';
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
	},
	{
		icon: <MdAccessTime size={ 24 } color="#B4B4B4" />,
		label: __( 'Time Picker', 'sureforms' ),
	},
	{
		icon: <FaRegEyeSlash size={ 24 } color="#B4B4B4" />,
		label: __( 'Hidden', 'sureforms' ),
	},
	{
		icon: <RxSlider size={ 24 } color="#B4B4B4" />,
		label: __( 'Slider', 'sureforms' ),
	},
	{
		icon: <MdOutlineInsertPageBreak size={ 24 } color="#B4B4B4" />,
		label: __( 'Page Break', 'sureforms' ),
	},
	{
		icon: <MdOutlineStarBorder size={ 24 } color="#B4B4B4" />,
		label: __( 'Rating', 'sureforms' ),
	},
	{
		icon: <MdOutlineCloudUpload size={ 24 } color="#B4B4B4" />,
		label: __( 'Upload', 'sureforms' ),
	},
	{
		icon: parse( svgIcons.signature ),
		label: __( 'Signature', 'sureforms' ),
		showIn: [
			'free',
			'starter',
		],
	},
];

const filterIconBlocks = iconBlocks.filter( ( block ) => {
	const srfmProduct = srfm_block_data?.pro_plugin_name.split( ' ' )[ 1 ]?.toLowerCase();

	if ( ! block.showIn ) {
		block.showIn = [
			'free',
		];
	}

	// if current package is mathcing with the block package, add the block to the list.
	switch ( srfmProduct ) {
		case 'free':
			return block.showIn.includes( 'free' );
		case 'starter':
			return block.showIn.includes( 'starter' );
		case 'pro':
			return block.showIn.includes( 'pro' );
		case 'business':
			return block.showIn.includes( 'business' );
		default:
			return true;
	}
} );

const IconGrid = () => (
	<div className="block-editor-inserter__panel-content">
		<div className="block-editor-block-types-list">
			{ filterIconBlocks.reduce( ( acc, block, index ) => {
				// if the index is divisible by 3, create a new group to show 3 blocks in a row
				if ( index % 3 === 0 ) {
					// in accumulator, create a new group
					acc.push( <div role="presentation" key={ `group-${ index }` }>{ [] }</div> );
				}
				// add the block to the last group
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
