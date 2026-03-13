import { useState, useEffect, useMemo, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { toast } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';

/**
 * Custom hook for managing Learn modules and lessons
 *
 * @param {Object} config                - Configuration object
 * @param {Array}  config.initialModules - Array of module objects with lessons
 * @param {string} config.saveEndpoint   - API endpoint to save progress (optional)
 * @return {Object} - Object containing modules state and utility functions
 */
const useLearn = ( {
	initialModules = [],
	saveEndpoint = null,
} = {} ) => {
	const [ modules, setModules ] = useState( initialModules );

	// Update modules when initialModules changes (e.g., when API data loads)
	useEffect( () => {
		if ( initialModules.length > 0 ) {
			setModules( initialModules );
		}
	}, [ initialModules ] );

	/**
	 * Update completion status of a specific lesson
	 *
	 * @param {string}  moduleId  - ID of the module containing the lesson
	 * @param {string}  lessonId  - ID of the lesson to update
	 * @param {boolean} completed - New completion status
	 */
	const updateLessonCompletion = useCallback(
		( moduleId, lessonId, completed ) => {
			// Optimistically update UI
			setModules( ( prevModules ) =>
				prevModules.map( ( module ) =>
					module.id === moduleId
						? {
							...module,
							steps: module.steps.map( ( lesson ) =>
								lesson.id === lessonId ? { ...lesson, completed } : lesson
							),
						  }
						: module
				)
			);

			// Save to API if endpoint is provided
			if ( saveEndpoint ) {
				apiFetch( {
					path: saveEndpoint,
					method: 'POST',
					data: {
						chapterId: moduleId,
						stepId: lessonId,
						completed,
					},
				} ).catch( ( error ) => {
					// Revert UI state on error
					setModules( ( prevModules ) =>
						prevModules.map( ( module ) =>
							module.id === moduleId
								? {
									...module,
									steps: module.steps.map( ( lesson ) =>
										lesson.id === lessonId ? { ...lesson, completed: ! completed } : lesson
									),
								  }
								: module
						)
					);

					// Show error toast
					toast.error( __( 'Failed to save progress. Please try again.', 'sureforms' ) );

					// eslint-disable-next-line no-console
					console.error( 'Failed to save progress:', error );
				} );
			}
		},
		[ saveEndpoint ]
	);

	/**
	 * Mark a lesson as completed
	 *
	 * @param {string} moduleId - ID of the module containing the lesson
	 * @param {string} lessonId - ID of the lesson to mark as completed
	 */
	const markLessonCompleted = useCallback(
		( moduleId, lessonId ) => {
			updateLessonCompletion( moduleId, lessonId, true );
		},
		[ updateLessonCompletion ]
	);

	/**
	 * Mark a lesson as incomplete
	 *
	 * @param {string} moduleId - ID of the module containing the lesson
	 * @param {string} lessonId - ID of the lesson to mark as incomplete
	 */
	const markLessonIncomplete = useCallback(
		( moduleId, lessonId ) => {
			updateLessonCompletion( moduleId, lessonId, false );
		},
		[ updateLessonCompletion ]
	);

	/**
	 * Reset all progress
	 */
	const resetProgress = useCallback( () => {
		setModules( ( prevModules ) =>
			prevModules.map( ( module ) => ( {
				...module,
				steps: module.steps.map( ( lesson ) => ( { ...lesson, completed: false } ) ),
			} ) )
		);
	}, [] );

	/**
	 * Mark all lessons as complete for a specific module
	 *
	 * @param {string} moduleId - ID of the module to mark all lessons complete (optional, marks all if not provided)
	 */
	const markAllComplete = useCallback(
		( moduleId = null ) => {
			// Capture previous state for rollback
			const previousModules = modules;

			// Optimistically update UI
			setModules( ( prevModules ) =>
				prevModules.map( ( module ) => {
					// If moduleId is provided, only update that module
					if ( moduleId && module.id !== moduleId ) {
						return module;
					}
					return {
						...module,
						steps: module.steps.map( ( lesson ) => ( {
							...lesson,
							completed: true,
						} ) ),
					};
				} )
			);

			// Save each lesson to API if endpoint is provided
			if ( saveEndpoint ) {
				const modulesToUpdate = moduleId
					? previousModules.filter( ( m ) => m.id === moduleId )
					: previousModules;

				const promises = modulesToUpdate.flatMap( ( module ) =>
					module.steps
						.filter( ( lesson ) => ! lesson.completed )
						.map( ( lesson ) =>
							apiFetch( {
								path: saveEndpoint,
								method: 'POST',
								data: {
									chapterId: module.id,
									stepId: lesson.id,
									completed: true,
								},
							} )
						)
				);

				Promise.all( promises ).catch( ( error ) => {
					// Revert UI state on error
					setModules( previousModules );

					// Show error toast
					toast.error(
						__(
							'Failed to save progress. Please try again.',
							'sureforms'
						)
					);
					// eslint-disable-next-line no-console
					console.error( 'Failed to mark all as complete:', error );
				} );
			}
		},
		[ modules, saveEndpoint ]
	);

	/**
	 * Get the first incomplete module ID
	 * Used for default accordion open state
	 */
	const firstIncompleteModuleId = useMemo( () => {
		const incompleteModule = modules.find(
			( module ) =>
				module.steps.length !==
				module.steps.filter( ( lesson ) => lesson.completed ).length
		);
		return incompleteModule?.id;
	}, [ modules ] );

	/**
	 * Get overall progress statistics
	 */
	const progressStats = useMemo( () => {
		const totalLessons = modules.reduce( ( sum, module ) => sum + module.steps.length, 0 );
		const completedLessons = modules.reduce(
			( sum, module ) =>
				sum + module.steps.filter( ( lesson ) => lesson.completed ).length,
			0
		);
		const completionPercentage =
			totalLessons > 0 ? Math.round( ( completedLessons / totalLessons ) * 100 ) : 0;

		return {
			totalModules: modules.length,
			totalLessons,
			completedLessons,
			completionPercentage,
			isFullyCompleted: totalLessons > 0 && completedLessons === totalLessons,
		};
	}, [ modules ] );

	/**
	 * Get module-specific statistics
	 *
	 * @param {string} moduleId - ID of the module
	 * @return {Object} - Module statistics
	 */
	const getModuleStats = useCallback(
		( moduleId ) => {
			const module = modules.find( ( m ) => m.id === moduleId );
			if ( ! module ) {
				return null;
			}

			const totalLessons = module.steps.length;
			const completedLessons = module.steps.filter( ( lesson ) => lesson.completed ).length;

			return {
				totalLessons,
				completedLessons,
				isCompleted: totalLessons > 0 && completedLessons === totalLessons,
				completionPercentage:
					totalLessons > 0 ? Math.round( ( completedLessons / totalLessons ) * 100 ) : 0,
			};
		},
		[ modules ]
	);

	return {
		modules,
		updateLessonCompletion,
		markLessonCompleted,
		markLessonIncomplete,
		markAllComplete,
		resetProgress,
		firstIncompleteModuleId,
		progressStats,
		getModuleStats,
	};
};

export default useLearn;
