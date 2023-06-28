import { __ } from '@wordpress/i18n';
import { GoLinkExternal } from 'react-icons/go';

const Header = () => {
	return (
		<header
			className="flex justify-between p-5 shadow-md bg-[#FBFBFC]"
			style={ { borderBottom: '1px solid #E4E7EB' } }
		>
			<div className="flex items-center gap-1">
				<span className="text-2xl text-[#333333] font-semibold">
					{ __( ' Settings', 'sureforms' ) }
				</span>
			</div>
			<div>
				<button className="cursor-pointer bg-transparent  text-[#575E68] font-semibold hover:text-[#333333] py-2 px-4 border border-[#575E68] hover:border-[#333333] rounded transition-colors duration-300 ease-in-out">
					<span className="flex items-center gap-2">
						{ __( ' Documentation', 'sureforms' ) }
						<GoLinkExternal />
					</span>
				</button>
			</div>
		</header>
	);
};

export default Header;
