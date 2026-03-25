/**
 * Quiz Empty State page component for free users.
 *
 * Mirrors the pro QuizEntriesListPlaceHolder layout with the shared
 * dashboard Header so the page feels like a first-class admin screen.
 *
 * @since x.x.x
 */

import { createRoot } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Container, Button, Text } from '@bsf/force-ui';
import { ArrowUpRight } from 'lucide-react';
import { addQueryParam, cn } from '@Utils/Helpers';
import Header from '@Admin/components/Header';
import AdminNotice from '@Admin/components/AdminNotice';
import QuizPlaceholder from '@Image/quiz-no-entries-placeholder.svg';
import './tw-base.scss';

const features = [
	__( 'Build interactive quizzes with scoring and instant feedback', 'sureforms' ),
	__( 'Auto-grade responses and calculate scores in real time', 'sureforms' ),
	__( 'Track submissions, view answers, and analyze performance', 'sureforms' ),
	__( 'Export detailed quiz reports for insights and follow-ups', 'sureforms' ),
];

const QuizEmptyState = () => {
	const baseUrl = window.srfm_admin?.sureforms_pricing_page || '';
	const pricingUrl = addQueryParam(
		addQueryParam( baseUrl, 'astra-sites', 'utm_source' ),
		'quiz-entries-upgrade-cta'
	);

	return (
		<>
			<div className="z-50 relative">
				<Header />
			</div>
			{ window.srfm_admin?.notices?.length > 0 && (
				<div className="px-8 py-4">
					<AdminNotice currentPage="sureforms_quiz_entries" />
				</div>
			) }
			<div
				className={ cn(
					'srfm-single-payment-wrapper min-h-screen bg-background-secondary px-8 pb-8',
					! window.srfm_admin?.notices?.length && 'pt-8'
				) }
			>
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
										'Quiz Entries Placeholder',
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
										'Create Engaging Quizzes with SureForms Pro',
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
											'Turn your forms into powerful quizzes. Upgrade to SureForms Pro to unlock quiz capabilities:',
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
											'noreferrer'
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
										'Upgrade to SureForms Pro',
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

// Render the QuizEmptyState component when the DOM is ready.
document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'srfm-quiz-entries-root' );
	if ( container ) {
		const root = createRoot( container );
		root.render( <QuizEmptyState /> );
	}
} );

export default QuizEmptyState;
