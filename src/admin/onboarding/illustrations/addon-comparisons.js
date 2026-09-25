import multistepFree from '@Image/onboarding/addon-multistep-free.svg';
import multistepPro from '@Image/onboarding/addon-multistep-pro.svg';
import conditionalFree from '@Image/onboarding/addon-conditional-free.svg';
import conditionalPro from '@Image/onboarding/addon-conditional-pro.svg';
import calculationFree from '@Image/onboarding/addon-calculation-free.svg';
import calculationPro from '@Image/onboarding/addon-calculation-pro.svg';
import conversationalFree from '@Image/onboarding/addon-conversational-free.svg';
import conversationalPro from '@Image/onboarding/addon-conversational-pro.svg';

/*
 * Free vs Pro mock cards for the add-ons step, one pair per tab, exported at
 * 283x246 from the Figma "Upgrade Comparison Visuals" frame.
 *
 * These were hand-built in JSX until the designs settled, and the copies drifted
 * apart: a card's chip claimed eight fields while the rows below it drew seven.
 * The artwork and the labels that describe it now come from one file each, so
 * they cannot disagree again. Sample names, prices and percentages are
 * illustrative.
 */

// Decorative: the tab label and description next to the slider carry the
// meaning, and the slider itself owns the accessible name.
const Card = ( { src } ) => (
	<img
		src={ src }
		alt=""
		width={ 283 }
		height={ 246 }
		className="block h-[246px] w-[283px]"
	/>
);

const ADDON_COMPARISONS = {
	multistep: {
		free: <Card src={ multistepFree } />,
		pro: <Card src={ multistepPro } />,
	},
	conditional: {
		free: <Card src={ conditionalFree } />,
		pro: <Card src={ conditionalPro } />,
	},
	calculation: {
		free: <Card src={ calculationFree } />,
		pro: <Card src={ calculationPro } />,
	},
	conversational: {
		free: <Card src={ conversationalFree } />,
		pro: <Card src={ conversationalPro } />,
	},
};

export default ADDON_COMPARISONS;
