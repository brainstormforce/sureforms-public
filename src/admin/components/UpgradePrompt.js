import { __ } from '@wordpress/i18n';
import blurImage from './blur.png'; // Make sure the path is correct

const UpgradePrompt = () => {
	return (
		<div className="relative flex flex-col items-center justify-center text-center py-16 px-8 bg-white/90 overflow-hidden rounded-2xl">
			<div
				className="relative w-full h-[156px] bg-cover bg-center rounded-2xl flex flex-col items-center justify-center"
				style={{ backgroundImage: `url(${blurImage})` }}
				aria-hidden="true"
			>
				{/* Main Content on top of the image */}
			  <div className="relative z-20 max-w-xl">
					<h2 className="text-[22px] font-semibold text-[#1c1e21] mb-3">Unlock Advanced Styling</h2>
					<p className="text-[18px] text-[#3c4043] leading-relaxed mb-6">
					Get full control over your form’s look with custom colors, fonts, and layouts.
					</p>
					<button className="text-[20px] font-semibold text-[#c64b23] hover:text-[#b03d17] transition-colors">
					Upgrade To Unlock
					</button>
				</div>
			</div>

		</div>
	);
};

export default UpgradePrompt;
