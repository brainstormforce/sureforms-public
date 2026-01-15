/**
 * Learn Library
 *
 * A reusable library for managing learning chapters and steps
 * with progress tracking. Supports both direct data and API endpoints.
 * Includes LearnHowDialog for rich educational content (videos, guides, etc).
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
 *       chapters={myChaptersData}
 *     />
 *   );
 * };
 *
 * Usage (Advanced - Custom Implementation):
 * import { useLearn, LearnChapters, LearnStep } from '@Admin/lib/learn';
 *
 * const MyComponent = () => {
 *   const {
 *     chapters,
 *     updateStepCompletion,
 *     firstIncompleteChapterId,
 *     progressStats
 *   } = useLearn({
 *     initialChapters: myChaptersData,
 *     saveEndpoint: '/sureforms/v1/update-learn-progress'
 *   });
 *
 *   return (
 *     <div>
 *       <LearnChapters
 *         chapters={chapters}
 *         defaultValue={firstIncompleteChapterId}
 *         onStepCompletionChange={updateStepCompletion}
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
export { default as LearnChapters } from './components/LearnChapters';
export { default as LearnStep } from './components/LearnStep';
export { default as LearnSkeleton } from './components/LearnSkeleton';

// LearnHow Dialog components
export { default as LearnHowDialog } from './components/LearnHowDialog';
export { default as RenderContent } from './components/learn-how/RenderContent';
