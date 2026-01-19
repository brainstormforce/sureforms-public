import { __, sprintf } from '@wordpress/i18n';
import { Accordion, Badge, Text } from '@bsf/force-ui';
import { Check, ChevronRight, ExternalLink } from 'lucide-react';
import LearnLesson from './LearnLesson';

/**
 * Component for rendering learn modules with lessons
 *
 * @param {Object}   props                          - Component props
 * @param {Object}   props.modules                  - Modules data object
 * @param {string}   props.defaultValue             - Default module ID to open
 * @param {Function} props.onLessonCompletionChange - Callback when lesson completion status changes
 * @param {Function} props.onLearnHowClick          - Callback to open learn how dialog
 * @return {JSX.Element} - Rendered modules component
 */
const LearnModules = ( { modules, defaultValue, onLessonCompletionChange, onLearnHowClick } ) => {
	const handleLearnHowClick = ( event, url ) => {
		event.stopPropagation();
		if ( url ) {
			window.open( url, '_blank', 'noopener,noreferrer' );
			return;
		}

		console.info( 'Empty or missing URL!!!' );
	};

	return (
		<Accordion
			type="boxed"
			autoClose={ true }
			defaultValue={ defaultValue }
		>
			{ modules.map( ( module ) => {
				const {
					id,
					title,
					description,
					url,
					steps: lessons,
				} = module;

				const totalLessonsCount = lessons.length;
				const completedLessonsCount = lessons.filter( ( lesson ) => lesson.completed ).length;
				const isCompleted = totalLessonsCount === completedLessonsCount;

				const getBadgeColor = () => {
					if ( isCompleted ) {
						return 'green';
					}
					if ( completedLessonsCount > 0 ) {
						return 'blue';
					}
					return 'gray';
				};

				return (
					<Accordion.Item
						key={ id }
						className="bg-background-primary border-0.5 [&:hover>h3]:bg-transparent rounded-lg overflow-hidden [&:has([aria-expanded='true'])]:shadow-xs transition-all duration-200 ease-in-out"
						value={ id }
					>
						<Accordion.Trigger className="group p-3 sm:p-4 hover:bg-transparent [&>svg]:hidden [&>div]:flex-grow [&[aria-expanded='true']_.learn-chevron-right]:rotate-90 [&[aria-expanded='true']_.learn-more-btn]:flex">
							<div className="flex items-center gap-2 sm:gap-3 flex-1">
								<ChevronRight
									className="learn-chevron-right transition-transform duration-200 ease-in-out"
									size={ 20 }
								/>

								<div className="flex-1 text-left">
									<Text size={ 14 } className="sm:text-base" weight={ 600 }>
										{ title }
									</Text>
								</div>

								{ url && (
									<span
										className="hover:underline underline-offset-2 learn-more-btn hidden transition-all cursor-pointer text-link-primary outline-link-primary items-center gap-1 text-xs px-2 py-1"
										onClick={ ( event ) => handleLearnHowClick( event, url ) }
										role="link"
										tabIndex={ 0 }
										onKeyDown={ ( e ) => {
											if ( e.key === 'Enter' || e.key === ' ' ) {
												handleLearnHowClick( e, url );
											}
										} }
									>
										<span className="hidden sm:inline">
											{ __( 'Learn how', 'sureforms' ) }
										</span>
										<ExternalLink size={ 16 } strokeWidth={ 1.25 } />
									</span>
								) }

								<Badge
									className="relative overflow-hidden w-14 sm:w-[62px] text-xs"
									label={
										<>
											<span className="sr-only">
												{ sprintf(
													// translators: %1$d is the number of completed lessons, %2$d is the total number of lessons.
													__( '%1$d of %2$d lessons completed', 'sureforms' ),
													completedLessonsCount,
													totalLessonsCount
												) }
											</span>

											<span className="flex items-center">
												{ isCompleted && <Check size={ 12 } /> }
												<span className="px-1 relative z-10">
													{ completedLessonsCount }/{ totalLessonsCount }
												</span>
											</span>

											<span
												className="absolute h-full top-0 left-0 bg-[#BAE6FD]/40 transition-[width] duration-300 ease-in-out"
												style={ {
													width: `${ ( completedLessonsCount / totalLessonsCount ) * 100 }%`,
												} }
											/>
										</>
									}
									variant={ getBadgeColor() }
								/>
							</div>
						</Accordion.Trigger>

						<Accordion.Content className="overflow-visible [&>div]:p-0">
							{ description && (
								<Text
									className="px-3 sm:px-4 ml-7 mr-0 sm:mx-8 -mt-2 pb-4 max-w-full sm:max-w-[72%]"
									size={ 14 }
									color="secondary"
									weight={ 400 }
								>
									{ description }
								</Text>
							) }

							<span className="block w-full h-[0.5px] bg-border-subtle" />

							<div className="px-3 sm:px-4 flex flex-col bg-[#FCFCFD]">
								{ lessons.map( ( lesson, index ) => (
									<LearnLesson
										key={ lesson.id }
										lesson={ lesson }
										moduleId={ id }
										isLast={ index === lessons.length - 1 }
										onCompletionChange={ onLessonCompletionChange }
										onLearnHowClick={ onLearnHowClick }
									/>
								) ) }
							</div>
						</Accordion.Content>
					</Accordion.Item>
				);
			} ) }
		</Accordion>
	);
};

export default LearnModules;
