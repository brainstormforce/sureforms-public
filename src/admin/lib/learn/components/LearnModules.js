import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Button, Container, Label, ProgressBar, Title } from '@bsf/force-ui';
import { CheckCheck, ChevronDown, ChevronUp } from 'lucide-react';
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

	// Set default expanded module and lesson on mount
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
	}, [ defaultValue, modules ] );

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
					steps: lessons = [],
				} = module;

				const videoLessons = lessons.filter(
					( lesson ) => lesson.learn?.type === 'dialog' && lesson.learn?.content?.type === 'video'
				);
				const hasVideoLessons = videoLessons.length > 0;
				const totalLessonsCount = videoLessons.length;
				const completedLessonsCount = videoLessons.filter(
					( lesson ) => lesson.completed
				).length;
				const progressPercentage =
					totalLessonsCount > 0
						? ( completedLessonsCount / totalLessonsCount ) * 100
						: 0;
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
										{ /* First Row: Title + Progress + Chevron */ }
										<Container.Item>
											<Container
												containerType="flex"
												direction="row"
												align="center"
												className="w-full gap-4 flex-wrap sm:flex-nowrap"
											>
												<Container.Item className="flex-1 min-w-0">
													<Title
														size="sm"
														title={ title }
														className="truncate"
													/>
												</Container.Item>
												{ hasVideoLessons && (
													<Container.Item>
														<Container
															containerType="flex"
															direction="row"
															align="center"
															gap="sm"
														>
															<Container.Item>
																<Label className="text-sm text-text-secondary whitespace-nowrap">
																	{ sprintf(
																		// translators: %1$d is completed count, %2$d is total count.
																		__(
																			'%1$d/%2$d completed',
																			'sureforms'
																		),
																		completedLessonsCount,
																		totalLessonsCount
																	) }
																</Label>
															</Container.Item>
															<Container.Item className="w-24 sm:w-32">
																<ProgressBar
																	progress={
																		progressPercentage
																	}
																	className="h-2"
																/>
															</Container.Item>
														</Container>
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
