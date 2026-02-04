/**
 * Learn Library
 *
 * Reusable learning module system with progress tracking.
 *
 * Exposes:
 * - <Learn />            → Full learning UI (API or direct data)
 * - useLearn()           → Headless state management hook
 * - <LearnModules />     → Module accordion UI
 * - <LearnLesson />      → Individual lesson UI
 * - <LearnSkeleton />    → Loading state UI
 *
 * For detailed usage examples, see project documentation.
 */

// Main component (recommended for most use cases)
export { default } from './components/Learn';
export { default as Learn } from './components/Learn';

// Hook and individual components (for advanced use cases)
export { default as useLearn } from './useLearn';
export { default as LearnModules } from './components/LearnModules';
export { default as LearnLesson } from './components/LearnLesson';
export { default as LearnSkeleton } from './components/LearnSkeleton';
