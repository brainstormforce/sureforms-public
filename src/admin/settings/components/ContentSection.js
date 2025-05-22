import LoadingSkeleton from '@Admin/components/LoadingSkeleton';

const ContentSection = ( { loading, title, content } ) => {
	return (
		<>
			<div>
				<div>
					{ loading ? (
						<LoadingSkeleton count={ 1 } className="w-48 h-8 rounded-sm" />
					) : (
						<span className="sr-only">{ title }</span>
					) }
				</div>
				<div className="space-y-6">
					{ loading ? (
						<div>
							<LoadingSkeleton count={ 3 } className="h-6 rounded-sm" />
							<LoadingSkeleton count={ 1 } className="h-6 rounded-sm" />
						</div>
					) : (
						content
					) }
				</div>
			</div>
		</>
	);
};

export default ContentSection;
