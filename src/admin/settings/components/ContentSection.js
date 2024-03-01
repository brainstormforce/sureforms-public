import { __ } from '@wordpress/i18n';
import Skeleton from 'react-loading-skeleton';

const ContentSection = ( { loading, title, content } ) => {
	return (
		<>
			<div className="srfm-section-container">
				<div className="srfm-content-title">
					{ loading ? (
						<Skeleton count={ 1 } height={ 30 } width={ 200 } />
					) : (
						title
					) }
				</div>
				<div className="srfm-section-content">
					{ loading ? (
						<Skeleton
							style={ { marginBottom: '1rem' } }
							count={ 5 }
							height={ 25 }
						/>
					) : (
						content
					) }
				</div>
			</div>
		</>
	);
};

export default ContentSection;
