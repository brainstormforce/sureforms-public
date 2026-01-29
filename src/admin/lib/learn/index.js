/**
 * Learn Library
 *
 * A reusable library for managing learning modules and lessons
 * with progress tracking. Supports both direct data and API endpoints.
 *
 * Usage (Simple - With API):
 * import Learn from '@Admin/lib/learn';
 *
 * const MyComponent = () => {
 *   return (
 *     <Learn
 *       endpoints={{
 *         get: '/sureforms/v1/get-learn-chapters',
 *         set: '/sureforms/v1/update-learn-progress'
 *       }}
 *     />
 *   );
 * };
 *
 * Usage (With Direct Data):
 * import Learn from '@Admin/lib/learn';
 *
 * const MyComponent = () => {
 *   return (
 *     <Learn
 *       modules={myModulesData}
 *     />
 *   );
 * };
 *
 * Usage (Advanced - Custom Implementation):
 * import { useLearn, LearnModules, LearnLesson } from '@Admin/lib/learn';
 *
 * const MyComponent = () => {
 *   const {
 *     modules,
 *     updateLessonCompletion,
 *     firstIncompleteModuleId,
 *     progressStats
 *   } = useLearn({
 *     initialModules: myModulesData,
 *     saveEndpoint: '/sureforms/v1/update-learn-progress'
 *   });
 *
 *   return (
 *     <div>
 *       <LearnModules
 *         modules={modules}
 *         defaultValue={firstIncompleteModuleId}
 *         onLessonCompletionChange={updateLessonCompletion}
 *       />
 *     </div>
 *   );
 * };
 */

// Main component (recommended for most use cases)
export { default } from './components/Learn';
export { default as Learn } from './components/Learn';

// Hook and individual components (for advanced use cases)
export { default as useLearn } from './useLearn';
export { default as LearnModules } from './components/LearnModules';
export { default as LearnLesson } from './components/LearnLesson';
export { default as LearnSkeleton } from './components/LearnSkeleton';
