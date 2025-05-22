import LoadingSkeleton from '@Admin/components/LoadingSkeleton';

const ContentCard = ( { loading, content } ) => {
	return (
		<>
			{ loading ? (
				<>
					<LoadingSkeleton
						count={ 3 }
						height={ 25 }
					/>
					<LoadingSkeleton
						count={ 1 }
						height={ 25 }
					/>
				</>
			) : (
				content
			) }
		</>
	);
};

export default ContentCard;
