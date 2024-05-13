import Skeleton from 'react-loading-skeleton';

const ContentCard = ( { loading, content } ) => {
	return (
		<>

			{ loading ? (
				<>
					<Skeleton
						className="srfm-skeleton-container"
						count={ 3 }
						height={ 25 }
					/>
					<Skeleton count={ 1 } height={ 25 } />
				</>
			) : (
				content
			) }
		</>
	);
};

export default ContentCard;
