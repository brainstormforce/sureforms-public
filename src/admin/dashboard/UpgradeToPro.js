import { __ } from '@wordpress/i18n';
import { Button, Container, Label } from '@bsf/force-ui';
import { Check } from 'lucide-react';
import upgradeToProPlaceholder from '@Image/upgrade-to-pro.svg';
import { addQueryParam } from '@Utils/Helpers';
import svgIcons from '@Svg/svgs.json';
import parse from 'html-react-parser';

export default () => {
	const features = [
		[
			__( 'Conversational Forms', 'sureforms' ),
			__( 'File Uploads', 'sureforms' ),
		],
		[
			__( 'Conditional Logic', 'sureforms' ),
			__( 'Signature & Rating', 'sureforms' ),
		],
		[
			__( 'Calculation Forms', 'sureforms' ),
			__( 'And Much More…', 'sureforms' ),
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

			<Container className="gap-1 p-2 w-full" direction="column">
				{ /* Unlock Premium Features */ }
				<Container.Item className="gap-2 text-brand-800 capitalize font-semibold text-xs flex items-center">
					{ parse( svgIcons.rocket ) }
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
