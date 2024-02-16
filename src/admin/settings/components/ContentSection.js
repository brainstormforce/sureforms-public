import { __ } from '@wordpress/i18n';

const ContentSection = ({title, content}) => {
	return (
        <>
            <div className="srfm-section-container">
                <div className="srfm-content-title">{title}</div>
                <div className="srfm-section-content">{content}</div>
            </div>
        </>
	);
};

export default ContentSection;