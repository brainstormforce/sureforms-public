/**
 * Survey Reports Empty State page component for free users.
 *
 * Mirrors the QuizEmptyState layout with the shared dashboard Header
 * so the page feels like a first-class admin screen.
 *
 * @since x.x.x
 */

import { createRoot } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Container, Button, Text } from '@bsf/force-ui';
import { ArrowUpRight } from 'lucide-react';
import { addQueryParam } from '@Utils/Helpers';
import Header from '@Admin/components/Header';
import QuizPlaceholder from '@Image/quiz-no-entries-placeholder.svg';
import './tw-base.scss';

const features = [
	__(
		'Turn any form into a survey or poll with one click',
		'sureforms'
	),
	__(
		'Visualize responses with bar charts, NPS scores, and real-time results',
		'sureforms'
	),
	__(
		'Share results via shortcodes, smart tags, or exportable reports',
		'sureforms'
	),
];

const SurveyEmptyState = () => {
	const baseUrl =
		window.srfm_admin?.pricing_page_url ||
		window.srfm_admin?.sureforms_pricing_page ||
		'https://sureforms.com/pricing/';
	const pricingUrl = addQueryParam( baseUrl, 'survey-reports-upgrade-cta' );

	return (
		<>
			<div className="z-50 relative">
				<Header />
			</div>
			<div className="srfm-single-payment-wrapper min-h-screen bg-background-secondary px-8 pb-8 pt-8">
				<Container
					containerType="flex"
					direction="column"
					gap="xs"
					className="w-full bg-background-primary border-0.5 border-solid rounded-xl border-border-subtle p-4 gap-2 shadow-sm"
				>
					<Container
						containerType="flex"
						gap="xs"
						className="w-full bg-background-secondary rounded-xl p-2 shadow-sm"
					>
						<Container
							containerType="flex"
							gap="xs"
							className="w-full bg-background-primary border-0.5 border-solid rounded-xl border-border-subtle p-6 shadow-sm flex items-center gap-6"
						>
							<div className="min-h-[240px] min-w-[240px] bg-background-secondary">
								<img
									src={ QuizPlaceholder }
									alt={ __(
										'Survey Reports Placeholder',
										'sureforms'
									) }
									className="w-60 h-60"
								/>
							</div>
							<div className="flex flex-col gap-2">
								<Text
									size={ 20 }
									lineHeight={ 30 }
									weight={ 600 }
									letterSpacing={ -0.5 }
									color="primary"
								>
									{ __(
										'Create Surveys & Polls with SureForms',
										'sureforms'
									) }
								</Text>
								<div className="flex flex-col">
									<Text
										size={ 16 }
										lineHeight={ 24 }
										weight={ 400 }
										color="secondary"
									>
										{ __(
											'Collect feedback, run polls, and visualize results. Upgrade to SureForms to unlock survey capabilities:',
											'sureforms'
										) }
									</Text>
									<ul className="flex flex-col list-disc list-inside leading-7 ml-2.5 mt-2 mb-0 mr-0">
										{ features.map( ( point, index ) => (
											<li
												key={ index }
												className="m-0"
											>
												<Text
													className="inline-block"
													size={ 16 }
													lineHeight={ 28 }
													weight={ 400 }
													color="secondary"
												>
													{ point }
												</Text>
											</li>
										) ) }
									</ul>
								</div>
								<Button
									onClick={ () =>
										window.open(
											pricingUrl,
											'_blank',
											'noopener,noreferrer'
										)
									}
									variant="primary"
									size="md"
									className="w-fit flex"
									icon={
										<ArrowUpRight className="!size-4" />
									}
									iconPosition="right"
								>
									{ __(
										'Upgrade to SureForms',
										'sureforms'
									) }
								</Button>
							</div>
						</Container>
					</Container>
				</Container>
			</div>
		</>
	);
};

// Render the SurveyEmptyState component when the DOM is ready.
document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'srfm-survey-empty-state-root' );
	if ( container ) {
		const root = createRoot( container );
		root.render( <SurveyEmptyState /> );
	}
} );

export default SurveyEmptyState;
