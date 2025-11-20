import { Skeleton } from '@bsf/force-ui';

/**
 * EntryDetailSkeleton Component
 * Displays a loading skeleton that mimics the EntryDetailPage layout
 */
const EntryDetailSkeleton = () => {
	return (
		<div className="p-8 bg-background-secondary min-h-screen space-y-6">
			{ /* Header Skeleton */ }
			<div className="flex items-center gap-3 mx-auto max-w-[1500px]">
				<Skeleton className="w-8 h-8 rounded-md" />
				<Skeleton className="h-8 w-48" />
			</div>

			<div className="mx-auto max-w-[1500px]">
				<div className="space-y-6">
					{ /* Main Content Grid Skeleton */ }
					<div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
						{ /* Left Column */ }
						<div className="lg:col-span-2 space-y-6">
							{ /* EntryDataSection Skeleton */ }
							<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
								<div className="pb-0 px-4 pt-4">
									<div className="flex items-center justify-between">
										<Skeleton className="h-5 w-32" />
										<Skeleton className="w-24 h-8 rounded-md" />
									</div>
								</div>
								<div className="p-4 space-y-1 relative before:content-[''] before:block before:absolute before:inset-3 before:bg-background-secondary before:rounded-lg">
									{ /* Simulate multiple fields */ }
									{ Array.from( { length: 5 } ).map(
										( _, index ) => (
											<div
												key={ index }
												className="p-3 relative bg-background-primary rounded-md shadow-sm"
											>
												<div className="flex gap-4">
													<Skeleton className="w-40 h-4" />
													<Skeleton className="flex-1 h-4" />
												</div>
											</div>
										)
									) }
								</div>
							</div>

							{ /* SubmissionInfoSection Skeleton */ }
							<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
								<div className="pb-0 px-4 pt-4">
									<Skeleton className="h-5 w-40 mb-4" />
								</div>
								<div className="p-4 space-y-4">
									{ /* Simulate info rows */ }
									{ Array.from( { length: 4 } ).map(
										( _, index ) => (
											<div
												key={ index }
												className="flex gap-4"
											>
												<Skeleton className="w-32 h-4" />
												<Skeleton className="flex-1 h-4" />
											</div>
										)
									) }
								</div>
							</div>
						</div>

						{ /* Right Column */ }
						<div className="space-y-4">
							{ /* NotesSection Skeleton */ }
							<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
								<div className="pb-0 px-4 pt-4">
									<Skeleton className="h-5 w-24 mb-4" />
								</div>
								<div className="p-4">
									<Skeleton className="w-full h-20 rounded-md" />
								</div>
							</div>

							{ /* EntryLogsSection Skeleton */ }
							<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
								<div className="pb-0 px-4 pt-4">
									<Skeleton className="h-5 w-28 mb-4" />
								</div>
								<div className="p-4 space-y-3">
									{ /* Simulate log entries */ }
									{ Array.from( { length: 3 } ).map(
										( _, index ) => (
											<div
												key={ index }
												className="flex gap-3"
											>
												<Skeleton className="w-8 h-8 rounded-full" />
												<div className="flex-1 space-y-1">
													<Skeleton className="h-4 w-3/4" />
													<Skeleton className="h-3 w-1/2" />
												</div>
											</div>
										)
									) }
								</div>
							</div>

							{ /* Action Button Skeleton */ }
							<div className="ml-0.5">
								<Skeleton className="w-full h-10 rounded-md" />
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default EntryDetailSkeleton;
