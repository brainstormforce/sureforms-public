import { __, sprintf } from '@wordpress/i18n';
import { Button, Container, Label } from '@bsf/force-ui';
import { ChevronRight, X } from 'lucide-react';
import { addQueryParam, initiateAuth } from '@Utils/Helpers';

const CreditDetailsPopup = ( {
	finalFormCreationCountRemaining = 0,
	setShowBanner,
	type,
} ) => {
	const upgradeLink = () =>
		window.open(
			addQueryParam(
				srfm_admin?.pricing_page_url ||
					srfm_admin?.sureforms_pricing_page,
				'ai-form-builder-banner-cta'
			),
			'_blank',
			'noreferrer'
		);

	const features = [
		__( 'Create Unlimited Forms with AI', 'sureforms' ),
		__( 'Add Advanced Field Types', 'sureforms' ),
		__( 'Create Calculator, Surveys, etc.', 'sureforms' ),
		__( 'Design Multistep Forms', 'sureforms' ),
		__( 'Send Forms Submissions to Your CRM or Any App', 'sureforms' ),
	];

	// Expanded banner
	const openCreditsBanner = (
		<Container.Item>
			<Container className="flex flex-col w-[450px] min-h-[268px] bg-brand-background-hover-100 p-5 gap-2 shadow-sm-blur-1 rounded-xl border border-border-subtle overflow-hidden">
				<Container.Item className="relative">
					<Label
						variant="neutral"
						className="text-sm font-semibold flex gap-3"
					>
						{ sprintf(
							// translators: %d is the number of form generations left.
							__(
								'%d AI Generations Left. SureForms Premium allows:',
								'sureforms'
							),
							finalFormCreationCountRemaining
						) }
						<span
							className="absolute -top-1 -right-1 cursor-pointer"
							onClick={ () => setShowBanner( false ) }
						>
							<X className="w-3 h-3" />
						</span>
					</Label>
				</Container.Item>

				<Container className="flex flex-col gap-2">
					{ /* List of features */ }
					{ features.map( ( item, index ) => (
						<Container.Item
							key={ index }
							className="flex flex-row gap-1.5 text-sm text-text-primary items-center"
						>
							<ChevronRight className="w-3.5 h-3.5" />
							<Label
								size="sm"
								className="font-normal break-words"
							>
								{ item }
							</Label>
						</Container.Item>
					) ) }

					{ /* "and more…" as clickable link */ }
					<Container.Item className="flex flex-row gap-1.5 text-sm text-text-primary items-center">
						<ChevronRight className="w-3.5 h-3.5" />
						<Container.Item className="flex flex-row text-sm gap-1">
							<span>{ __( 'and ', 'sureforms' ) }</span>
							<Label
								as="a"
								size="sm"
								className="font-normal break-words underline cursor-pointer text-brand-800 hover:text-brand-900"
								onClick={ () =>
									window.open(
										'https://sureforms.com',
										'_blank',
										'noreferrer'
									)
								}
							>
								{ __( 'more…', 'sureforms' ) }
							</Label>
						</Container.Item>
					</Container.Item>
				</Container>

				<Container.Item className="flex flex-col w-full gap-3 mt-auto">
					<Button
						size="sm"
						variant="secondary"
						className="w-full"
						onClick={ upgradeLink }
					>
						{ __( 'Upgrade Now', 'sureforms' ) }
					</Button>
				</Container.Item>
			</Container>
		</Container.Item>
	);

	// Compact banner
	const compactCreditsBanner = (
		<Container.Item className="fixed bottom-4 right-4 flex items-center justify-between gap-2 p-4 shadow-lg rounded-xl z-[9999] max-w-[360px] bg-brand-background-hover-100 border border-solid border-border-ai-banner">
			<Label variant="neutral" className="text-sm font-semibold">
				{ sprintf(
					// translators: %d is the number of form generations left.
					__(
						'%d AI Generations Left, Connect to SureForms AI to Get 10 More',
						'sureforms'
					),
					finalFormCreationCountRemaining
				) }
			</Label>

			<Button
				size="xs"
				variant="secondary"
				className="w-[92px] flex-shrink-0"
				onClick={ initiateAuth }
			>
				{ __( 'Connect Now', 'sureforms' ) }
			</Button>
		</Container.Item>
	);

	// Determine which banner to show
	let values = null;
	if ( type === 'non-registered' && finalFormCreationCountRemaining <= 2 ) {
		values = {
			banner: compactCreditsBanner,
			text: __( 'Connect Now', 'sureforms' ),
			link: initiateAuth,
		};
	} else if (
		type === 'registered' &&
		finalFormCreationCountRemaining <= 9
	) {
		values = {
			banner: openCreditsBanner,
			text: __( 'Upgrade Now', 'sureforms' ),
			link: upgradeLink,
		};
	}

	return values?.banner || null;
};

export default CreditDetailsPopup;
