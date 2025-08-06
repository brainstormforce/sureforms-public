import { __ } from '@wordpress/i18n';
import { Button, Container, Label } from '@bsf/force-ui';
import { Check } from 'lucide-react';
import upgradeToProPlaceholder from '@Image/upgrade-to-pro.svg';
import { addQueryParam } from '@Utils/Helpers';

export default () => {
	const features = [
		[
			__( 'Conversational Forms', 'sureforms' ),
			__( 'File Uploads', 'sureforms' ),

		],
		[
			__( 'Conditional Logic', 'sureforms' ),
			__( 'Signature & Rating Fields', 'sureforms' ),
		],
		[
			__( 'Calculation Forms', 'sureforms' ),
			__( 'And Much More..', 'sureforms' ),
		],
	];

	return (
		<Container
			direction="column"
			className="bg-white p-3 rounded-xl shadow-sm-blur-1 border-0.5 border-solid border-border-subtle gap-2"
		>
			{ /* Illustration */ }
			<Container.Item className="flex justify-center p-2">
				<img
					src={ upgradeToProPlaceholder }
					alt={ __( 'Upgrade to Pro', 'sureforms' ) }
					className="w-60 h-auto"
				/>
			</Container.Item>

			<Container className="gap-1 p-2 w-[22rem]" direction="column">
				{ /* 🚀 Unlock Premium Features */ }
				<Container.Item className="gap-2 text-brand-800 capitalize font-semibold text-xs flex items-center">
					<svg
						width="16"
						height="16"
						viewBox="0 0 16 16"
						fill="none"
						xmlns="http://www.w3.org/2000/svg"
					>
						<path
							d="M3.00033 11C2.00033 11.84 1.66699 14.3333 1.66699 14.3333C1.66699 14.3333 4.16033 14 5.00033 13C5.47366 12.44 5.46699 11.58 4.94033 11.06C4.6812 10.8126 4.33985 10.6697 3.98181 10.6587C3.62376 10.6476 3.27424 10.7691 3.00033 11Z"
							stroke="#D54407"
							strokeWidth="1.25"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
						<path
							d="M8 10.0001L6 8.00008C6.35476 7.0797 6.80147 6.19746 7.33333 5.36675C8.11012 4.12474 9.19175 3.10212 10.4753 2.39614C11.7589 1.69017 13.2018 1.32433 14.6667 1.33342C14.6667 3.14675 14.1467 6.33342 10.6667 8.66675C9.82459 9.19923 8.93123 9.64591 8 10.0001Z"
							stroke="#D54407"
							strokeWidth="1.25"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
						<path
							d="M6.00033 8.00009H2.66699C2.66699 8.00009 3.03366 5.98009 4.00033 5.33343C5.08033 4.61343 7.33366 5.33343 7.33366 5.33343"
							stroke="#D54407"
							strokeWidth="1.25"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
						<path
							d="M8 10.0001V13.3334C8 13.3334 10.02 12.9667 10.6667 12.0001C11.3867 10.9201 10.6667 8.66675 10.6667 8.66675"
							stroke="#D54407"
							strokeWidth="1.25"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
					</svg>
					{ __( 'Unlock Premium Features', 'sureforms' ) }
				</Container.Item>

				{ /* Title */ }
				<Label
					as="h2"
					size="lg"
					className="text-[#141338] font-semibold text-lg"
				>
					{ __( 'Build Better Forms with SureForms', 'sureforms' ) }
				</Label>

				{ /* Paragraph */ }
				<Label
					size="sm"
					className="text-[#4F4E7C] text-sm font-normal leading-relaxed"
				>
					{ __(
						'Add advanced fields, conversational layouts, and smart logic to create forms that engage users and capture better data.',
						'sureforms'
					) }
				</Label>
			</Container>

			{ /* Features Grid */ }
			<Container
				containerType="grid"
				className="grid-cols-2 gap-y-3 gap-x-6 w-full p-2"
			>
				{ features.flat().map( ( item, index ) => (
					<Container.Item
						key={ index }
						className="flex items-start gap-2 text-base text-[#141338]"
					>
						<Check className="w-4 h-4 text-brand-800 mt-0.5" />
						<Label size="sm" className="font-normal">
							{ item }
						</Label>
					</Container.Item>
				) ) }
			</Container>

			{ /* Upgrade Button */ }
			<Container className="pt-1.5 w-full p-2">
				<Button
					className="shadow-sm-blur-1 gap-1 w-full"
					size="md"
					variant="primary"
					onClick={ () =>
						window.open(
							addQueryParam(
								srfm_admin?.pricing_page_url ||
									srfm_admin?.sureforms_pricing_page,
								'dashboard-upgrade-to-pro-banner-cta'
							),
							'_blank',
							'noreferrer'
						)
					}
				>
					{ __( 'Upgrade Now', 'sureforms' ) }
				</Button>
			</Container>
		</Container>
	);
};
