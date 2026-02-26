import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Badge, Button, Container, Label, Title } from '@bsf/force-ui';
import { Check, CheckCheck, ChevronDown, ChevronUp, ExternalLink } from 'lucide-react';
import LearnLesson from './LearnLesson';

/**
 * Component for rendering learn modules with lessons
 *
 * @param {Object}                props                          - Component props
 * @param {Object}                props.modules                  - Modules data object
 * @param {string}                props.defaultValue             - Default module ID to open
 * @param {Function}              props.onLessonCompletionChange - Callback when lesson completion status changes
 * @param {Function}              props.onMarkAllComplete        - Callback when mark all as done is clicked for a module
 * @param {number|null|undefined} props.latestFormId
 * @return {JSX.Element} - Rendered modules component
 */
const LearnModules = ( {
	modules,
	defaultValue,
	onLessonCompletionChange,
	onMarkAllComplete,
	latestFormId,
} ) => {
	// Track which module is currently expanded
	const [ expandedModuleId, setExpandedModuleId ] = useState( null );
	// Track which lesson is currently expanded
	const [ expandedLessonId, setExpandedLessonId ] = useState( null );

	// Set default expanded module and lesson on initial load only.
	// `modules` is intentionally omitted from deps: this effect must only run
	// when `defaultValue` first becomes available. Re-running on every lesson
	// toggle (which produces a new `modules` array reference) would collapse
	// any module the user has manually opened back to the first incomplete one.
	// eslint-disable-next-line react-hooks/exhaustive-deps
	useEffect( () => {
		if ( defaultValue && modules.length > 0 ) {
			// Find the first incomplete module and lesson
			for ( const module of modules ) {
				const firstIncompleteLessonInModule = module.steps?.find(
					( lesson ) => ! lesson.completed
				);
				if ( firstIncompleteLessonInModule ) {
					setExpandedModuleId( module.id );
					setExpandedLessonId( firstIncompleteLessonInModule.id );
					break;
				}
			}
		}
	}, [ defaultValue ] );

	const toggleModule = ( moduleId ) => {
		const isClosing = expandedModuleId === moduleId;

		setExpandedModuleId( ( prevId ) =>
			prevId === moduleId ? null : moduleId
		);

		if ( isClosing ) {
			// Reset expanded lesson when closing module
			setExpandedLessonId( null );
		} else {
			// When opening a module, expand the first incomplete lesson (or first lesson if all complete)
			const module = modules.find( ( m ) => m.id === moduleId );
			if ( module?.steps?.length > 0 ) {
				const firstIncompleteLesson = module.steps.find(
					( lesson ) => ! lesson.completed
				);
				setExpandedLessonId(
					firstIncompleteLesson?.id || module.steps[ 0 ].id
				);
			}
		}
	};

	const toggleLesson = ( lessonId ) => {
		setExpandedLessonId( ( prevId ) =>
			prevId === lessonId ? null : lessonId
		);
	};

	const handleMarkModuleAsDone = ( e, moduleId ) => {
		e.stopPropagation();
		if ( onMarkAllComplete && typeof onMarkAllComplete === 'function' ) {
			onMarkAllComplete( moduleId );
		}
	};

	return (
		<Container containerType="flex" direction="column" gap="md">
			{ modules.map( ( module ) => {
				const {
					id: moduleId,
					title,
					description,
					url: moduleDocsUrl,
					steps: lessons = [],
				} = module;

				const videoLessons = lessons.filter(
					( lesson ) => lesson.learn?.type === 'dialog' && lesson.learn?.content?.type === 'video'
				);
				const hasVideoLessons = videoLessons.length > 0;
				const totalLessonsCount = lessons.length;
				const completedLessonsCount = lessons.filter(
					( lesson ) => lesson.completed
				).length;
				const isModuleComplete =
					totalLessonsCount > 0 && completedLessonsCount === totalLessonsCount;
				const isModuleExpanded = expandedModuleId === moduleId;

				return (
					<Container.Item key={ moduleId }>
						<Container
							containerType="flex"
							direction="column"
							className={ `bg-background-primary rounded-xl shadow-sm border border-solid transition-all duration-200 ${
								isModuleExpanded
									? 'border-border-strong'
									: 'border-border-subtle'
							}` }
						>
							{ /* Module Header Section - Clickable */ }
							<Container.Item>
								<div
									className="p-4 sm:p-6 cursor-pointer"
									onClick={ () => toggleModule( moduleId ) }
									onKeyDown={ ( e ) => {
										if (
											e.key === 'Enter' ||
											e.key === ' '
										) {
											e.preventDefault();
											toggleModule( moduleId );
										}
									} }
									role="button"
									tabIndex={ 0 }
									aria-expanded={ isModuleExpanded }
								>
									<Container
										containerType="flex"
										direction="column"
										className="w-full gap-3"
									>
										{ /* First Row: Title + Completion Counter Badge + Chevron */ }
										<Container.Item>
											<Container
												containerType="flex"
												direction="row"
												align="center"
												className="w-full gap-3"
											>
												<Container.Item className="flex-1 min-w-0">
													<Title
														size="sm"
														title={ title }
														className="truncate"
													/>
												</Container.Item>
												{ moduleDocsUrl && (
													<Container.Item>
														<Button
															variant="ghost"
															size="sm"
															icon={ <ExternalLink className="size-3.5" /> }
															iconPosition="right"
															className="text-link-primary p-0 h-auto outline-none shadow-none hover:underline"
															onClick={ ( e ) => {
																e.stopPropagation();
																window.open( moduleDocsUrl, '_blank', 'noopener,noreferrer' );
															} }
														>
															{ __( 'Learn how', 'sureforms' ) }
														</Button>
													</Container.Item>
												) }
												{ totalLessonsCount > 0 && (
													<Container.Item>
														<Badge
															className={ `relative overflow-hidden w-14 sm:w-[62px] text-xs${ completedLessonsCount > 0 ? ' !text-link-primary !border-brand-border-300' : '' }` }
															label={
																<>
																	<span className="sr-only">
																		{ sprintf(
																			/* translators: %1$d is completed count, %2$d is total count. */
																			__( '%1$d of %2$d steps completed', 'sureforms' ),
																			completedLessonsCount,
																			totalLessonsCount
																		) }
																	</span>
																	<span className="flex items-center">
																		{ isModuleComplete && <Check size={ 12 } /> }
																		<span className="px-1 relative z-10">
																			{ completedLessonsCount }/{ totalLessonsCount }
																		</span>
																	</span>
																	<span
																		className="absolute h-full top-0 left-0 bg-badge-background-orange-30 transition-[width] duration-300 ease-in-out"
																		style={ { width: `${ ( completedLessonsCount / totalLessonsCount ) * 100 }%` } }
																	/>
																</>
															}
															variant="neutral"
															size="sm"
															type="pill"
														/>
													</Container.Item>
												) }
												<Container.Item>
													<div className="flex items-center justify-center w-6 h-6">
														{ isModuleExpanded ? (
															<ChevronUp className="size-5 text-text-secondary" />
														) : (
															<ChevronDown className="size-5 text-text-secondary" />
														) }
													</div>
												</Container.Item>
											</Container>
										</Container.Item>

										{ /* Second Row: Description + Mark All as Done */ }
										<Container.Item>
											<Container
												containerType="flex"
												direction="row"
												align="center"
												justify="between"
												className="w-full gap-8"
											>
												<Container.Item className="flex-1 min-w-0">
													<Label className="text-sm text-text-primary font-normal">
														{ description }
													</Label>
												</Container.Item>
												{ hasVideoLessons && (
													<Container.Item className="flex-shrink-0">
														<Button
															variant="outline"
															size="sm"
															icon={
																<CheckCheck className="size-4" />
															}
															onClick={ ( e ) =>
																handleMarkModuleAsDone(
																	e,
																	moduleId
																)
															}
															disabled={ isModuleComplete }
															title={
																isModuleComplete
																	? __(
																		'All lessons in this module are already complete',
																		'sureforms'
																  )
																	: undefined
															}
														>
															{ __(
																'Mark All as Done',
																'sureforms'
															) }
														</Button>
													</Container.Item>
												) }
											</Container>
										</Container.Item>
									</Container>
								</div>
							</Container.Item>

							{ /* Lessons Section - Collapsible */ }
							{ isModuleExpanded && (
								<Container.Item>
									<div className="px-4 sm:px-6 pb-4 sm:pb-6 pt-0">
										<Container
											containerType="flex"
											direction="column"
											gap="sm"
										>
											{ lessons.map( ( lesson ) => (
												<Container.Item
													key={ lesson.id }
												>
													<LearnLesson
														lesson={ lesson }
														moduleId={ moduleId }
														isExpanded={
															expandedLessonId ===
															lesson.id
														}
														onToggle={ () =>
															toggleLesson(
																lesson.id
															)
														}
														onCompletionChange={
															onLessonCompletionChange
														}
														latestFormId={
															latestFormId
														}
													/>
												</Container.Item>
											) ) }
										</Container>
									</div>
								</Container.Item>
							) }
						</Container>
					</Container.Item>
				);
			} ) }
		</Container>
	);
};

export default LearnModules;
