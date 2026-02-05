import { __ } from '@wordpress/i18n';
import {
	MdOutlineCalendarMonth,
	MdOutlineInsertPageBreak,
	MdOutlineStarBorder,
	MdOutlineCloudUpload,
	MdAccessTime,
	MdCode,
	MdFormatListBulletedAdd,
} from 'react-icons/md';
import { FaRegEyeSlash } from 'react-icons/fa';
import svgIcons from '@Svg/svgs.json';
import parse from 'html-react-parser';
import { RxSlider } from 'react-icons/rx';
import { addQueryParam } from '@Utils/Helpers';
import { LuLogIn, LuUserPlus, LuKeyRound } from 'react-icons/lu';

const ResetPasswordIcon = () => (
	<svg
		width="24"
		height="24"
		viewBox="0 0 24 24"
		xmlns="http://www.w3.org/2000/svg"
		style={ { fill: 'none' } }
	>
		<path
			d="M15.5296 8.1082L14.623 9.01475M14.623 9.01475L15.9829 10.3746L14.3964 11.961L13.0366 10.6012M14.623 9.01475L13.0366 10.6012M11.1736 12.4642C11.4077 12.6951 11.5937 12.97 11.7211 13.2732C11.8484 13.5763 11.9145 13.9016 11.9157 14.2304C11.9168 14.5592 11.8528 14.885 11.7275 15.1889C11.6022 15.4929 11.418 15.7691 11.1855 16.0016C10.953 16.2341 10.6768 16.4183 10.3728 16.5436C10.0689 16.6689 9.74309 16.7329 9.4143 16.7318C9.08551 16.7307 8.76019 16.6645 8.45706 16.5372C8.15393 16.4098 7.87898 16.2238 7.64806 15.9897C7.19393 15.5196 6.94265 14.8898 6.94833 14.2362C6.95401 13.5825 7.2162 12.9572 7.67842 12.495C8.14065 12.0328 8.76593 11.7706 9.41959 11.7649C10.0732 11.7592 10.7034 12.0101 11.1736 12.4642ZM11.1736 12.4642L13.0366 10.6012M1 1V7.95317H7.95317M1.12988 13.6662C1.37595 15.9908 2.39581 18.1654 4.0258 19.8409C5.65578 21.5165 7.80139 22.5959 10.1183 22.9059C12.4353 23.216 14.7892 22.7387 16.8024 21.5507C18.8156 20.3626 20.3713 18.5327 21.2199 16.3546C22.0685 14.1765 22.1608 11.7765 21.482 9.53962C20.8032 7.30276 19.3926 5.35879 17.4766 4.01965C15.5606 2.6805 13.2503 2.02383 10.9164 2.15501C8.58251 2.28618 6.36033 3.1976 4.60647 4.743L1.12988 7.87193"
			stroke="#B4B4B4"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
	</svg>
);

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
		icon: <MdCode size={ 24 } color="#B4B4B4" />,
		label: __( 'HTML', 'sureforms' ),
	},
	{
		icon: <MdFormatListBulletedAdd size={ 24 } color="#B4B4B4" />,
		label: __( 'Repeater', 'sureforms' ),
		showIn: [ 'free', 'starter', 'pro' ],
	},
	{
		icon: parse( svgIcons.signature ),
		label: __( 'Signature', 'sureforms' ),
		showIn: [ 'free', 'starter' ],
	},
	{
		icon: (
			<LuLogIn size={ 24 } color="#B4B4B4" style={ { fill: 'none' } } />
		),
		label: __( 'Login', 'sureforms' ),
		showIn: [ 'free', 'starter', 'pro' ],
	},
	{
		icon: (
			<LuUserPlus
				size={ 24 }
				color="#B4B4B4"
				style={ { fill: 'none' } }
			/>
		),
		label: __( 'Register', 'sureforms' ),
		showIn: [ 'free', 'starter', 'pro' ],
	},
	{
		icon: (
			<LuKeyRound
				size={ 24 }
				color="#B4B4B4"
				style={ { fill: 'none' } }
			/>
		),
		label: __( 'Lost Password', 'sureforms' ),
		showIn: [ 'free', 'starter', 'pro' ],
	},
	{
		icon: <ResetPasswordIcon />,
		label: __( 'Reset Password', 'sureforms' ),
		showIn: [ 'free', 'starter', 'pro' ],
	},
];

const filterIconBlocks = iconBlocks.filter( ( block ) => {
	const srfmProduct = srfm_block_data?.pro_plugin_name
		.split( ' ' )[ 1 ]
		?.toLowerCase();

	if ( ! block.showIn ) {
		block.showIn = [ 'free' ];
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
					acc.push(
						<div role="presentation" key={ `group-${ index }` }>
							{ [] }
						</div>
					);
				}
				// add the block to the last group
				acc[ acc.length - 1 ].props.children.push(
					<IconBlock
						key={ index }
						icon={ block.icon }
						label={ block.label }
					/>
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
						window.open(
							addQueryParam(
								srfm_admin?.sureforms_pricing_page,
								'sureforms_editor'
							),
							'_blank'
						);
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
