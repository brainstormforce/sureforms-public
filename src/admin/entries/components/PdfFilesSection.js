import { applyFilters } from '@wordpress/hooks';

/**
 * PdfFilesSection Component
 * Displays PDF attachments for an entry with attach PDF functionality
 *
 * @param {Object} props
 * @param {Array}  props.pdfLinks - Array of PDF links for the entry
 * @return {JSX.Element} PdfFilesSection component
 */
const PdfFilesSection = ( { pdfLinks } ) => {
	const PdfAttachmentsComponent = applyFilters(
		'srfm-pro.entry-details.render-pdf-attachments-component'
	);

	if ( !! PdfAttachmentsComponent ) {
		return <PdfAttachmentsComponent pdfLinks={ pdfLinks } />;
	}

	return null;
};

export default PdfFilesSection;
