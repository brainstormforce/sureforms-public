import { __ } from '@wordpress/i18n';
import { MdOutlineCalendarMonth } from 'react-icons/md';
import { FaRegEyeSlash } from 'react-icons/fa';
import { RxSlider } from 'react-icons/rx';
import { MdOutlineInsertPageBreak } from 'react-icons/md';
import { MdOutlinePassword } from 'react-icons/md';
import { MdOutlineStarBorder } from 'react-icons/md';
import { MdOutlineCloudUpload } from 'react-icons/md';
import { FiShieldOff } from 'react-icons/fi';

const IconBlock = ( { icon, label } ) => (
	<div
		style={ {
			textAlign: 'center',
		} }
	>
		<p style={ { fontSize: '24px', marginBottom: '10px' } }>{ icon }</p>
		<p>{ label }</p>
	</div>
);

const IconGrid = () => (
	<div
		style={ {
			color: '#A6A6A6',
			display: 'grid',
			gridTemplateColumns: 'repeat(3, 1fr)',
			gap: '10px',
		} }
	>
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
		<IconBlock
			icon={ <MdOutlineCloudUpload /> }
			label={ __( 'Upload', 'sureforms' ) }
		/>
	</div>
);

const index = () => {
	return (
		<div>
			<h3
				style={ {
					fontSize: '20px',
					fontWeight: 'bold',
					display: 'flex',
					alignItems: 'center',
					gap: '5px',
				} }
			>
				{ __( 'Pro Fields', 'sureforms' ) }
				<FiShieldOff />
			</h3>
			<a
				href="https://sureforms.com/pricing"
				target="_blank"
				style={ { textDecoration: 'none' } }
			>
				<button
					style={ {
						backgroundColor: '#1E1E1E',
						color: '#fff',
						padding: '8px 15px',
						fontSize: '12px',
						width: '100%',
						border: 'none',
						borderRadius: '5px',
						cursor: 'pointer',
						transition: 'background-color 0.3s',
					} }
				>
					{ __( 'Upgrade to unlock the Pro Fields', 'sureforms' ) }
				</button>
			</a>

			<IconGrid />
		</div>
	);
};

export default index;
