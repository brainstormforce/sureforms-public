import { __ } from '@wordpress/i18n';
import { GoLinkExternal } from 'react-icons/go';

const Header = () => {
	return (
		<header
			className="srfm-flex srfm-justify-between srfm-p-5 srfm-shadow-md srfm-bg-[#FBFBFC]"
			style={ { borderBottom: '1px solid #E4E7EB' } }
		>
			<div className="srfm-flex srfm-items-center srfm-gap-1">
				<span className="srfm-text-2xl srfm-text-[#333333] srfm-font-semibold">
					{ __( ' Settings', 'sureforms' ) }
				</span>
			</div>
			<div>
				<button className="srfm-cursor-pointer srfm-bg-transparent srfm-text-[#575E68] srfm-font-semibold hover:srfm-text-[#333333] srfm-py-2 srfm-px-4 srfm-border srfm-border-[#575E68] hover:srfm-border-[#333333] srfm-rounded srfm-transition-colors srfm-duration-300 srfm-ease-in-out">
					<span className="srfm-flex srfm-items-center srfm-gap-2">
						{ __( ' Documentation', 'sureforms' ) }
						<GoLinkExternal />
					</span>
				</button>
			</div>
		</header>
	);
};

export default Header;
