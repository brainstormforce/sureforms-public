import { __, sprintf } from '@wordpress/i18n';
import { Button, Container, Label } from '@bsf/force-ui';
import { ChevronRight, X } from 'lucide-react';
import { addQueryParam, initiateAuth } from '@Utils/Helpers';
import { useEffect } from '@wordpress/element';

const CreditDetailsPopup = ( {
	finalFormCreationCountRemaining = 0,
	setShowBanner,
	showBanner,
	type,
} ) => {
	// On component mount, read banner state from localStorage
	useEffect( () => {
		const savedBannerState = localStorage.getItem(
			'srfm_ai_banner_closed'
		);
		if ( savedBannerState === 'true' ) {
			setShowBanner( false );
		}
	}, [ setShowBanner ] );

	const handleCloseBanner = () => {
		setShowBanner( false );
		localStorage.setItem( 'srfm_ai_banner_closed', 'true' );
	};

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

	let values = null;

	// Determine text and link based on user type
	if ( type === 'non-registered' && finalFormCreationCountRemaining <= 2 ) {
		values = {
			text: __( 'Connect Now', 'sureforms' ),
			link: initiateAuth,
		};
	} else if (
		type === 'registered' &&
		finalFormCreationCountRemaining <= 9
	) {
		values = {
			text: __( 'Upgrade Now', 'sureforms' ),
			link: upgradeLink,
		};
	} else {
		return null; // nothing to show
	}

	// Expanded banner for registered users
	const openCreditsBanner =
		type === 'registered' ? (
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
								onClick={ handleCloseBanner }
							>
								<X className="w-3 h-3" />
							</span>
						</Label>
					</Container.Item>

					<Container className="flex flex-col gap-2">
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
							onClick={ values.link }
						>
							{ values.text }
						</Button>
					</Container.Item>
				</Container>
			</Container.Item>
		) : null;

	const bannerText =
		type === 'non-registered'
			? sprintf(
				// translators: %d is the number of form generations left.
				__(
					'%d AI Generations Left, Connect to SureForms AI to Get 10 More',
					'sureforms'
				),
				finalFormCreationCountRemaining
			  )
			: sprintf(
				// translators: %d is the number of form generations left.
				__(
					'%d AI Generations Left. Upgrade to SureForms Premium',
					'sureforms'
				),
				finalFormCreationCountRemaining
			  );

	// Compact banner
	const compactCreditsBanner = (
		<Container.Item className="fixed bottom-4 right-4 flex items-center justify-between gap-2 p-4 shadow-lg rounded-xl z-[9999] max-w-[360px] bg-brand-background-hover-100 border border-solid border-border-ai-banner">
			<Label variant="neutral" className="text-sm font-semibold">
				{ bannerText }
			</Label>

			<Button
				size="xs"
				variant="secondary"
				className="w-[92px] flex-shrink-0"
				onClick={ values.link }
			>
				{ values.text }
			</Button>
		</Container.Item>
	);

	// Decide which banner to show
	if ( type === 'registered' && finalFormCreationCountRemaining <= 9 ) {
		return showBanner ? openCreditsBanner : compactCreditsBanner;
	} else if (
		type === 'non-registered' &&
		finalFormCreationCountRemaining <= 2
	) {
		return compactCreditsBanner;
	}

	return null;
};

export default CreditDetailsPopup;
