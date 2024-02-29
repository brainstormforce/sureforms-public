import Skeleton from 'react-loading-skeleton';

const SkeletonLoading = () => {
	return (
		<>
			<Skeleton
				style={ {
					marginBottom: '1rem',
				} }
				count={ 1 }
				height={ 30 }
				width={ 200 }
			/>
			<Skeleton
				style={ {
					marginBottom: '1rem',
				} }
				count={ 6 }
				height={ 20 }
			/>
		</>
	);
};

export default SkeletonLoading;
