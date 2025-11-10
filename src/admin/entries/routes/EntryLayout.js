import { __, sprintf } from '@wordpress/i18n';
import { Link, Outlet, useParams } from 'react-router-dom';
import Header from '@Admin/components/Header';
import { cn } from '@Utils/Helpers';

/**
 * FormPageHeader Component
 *
 * @param {Object} props           Component props
 * @param {string} props.className - Additional class names for styling
 * @return {JSX.Element}            FormPageHeader component
 */
const PageHeader = ( { className } ) => {
	const { id } = useParams( { strict: false } );

	return (
		<div className={ cn( 'z-50 relative', className ) }>
			<Header
				breadCrumb={ [
					{
						type: 'separator',
					},
					{
						text: __( 'Entries', 'sureforms' ),
						linkProps: {
							as: Link,
							to: '/',
						},
					},
					{
						type: 'separator',
					},
					{
						text: sprintf(
							/* translators: %s: Entry ID */
							__( 'Entry #%s', 'sureforms' ),
							id || ''
						),
						type: 'page',
					},
				] }
			/>
		</div>
	);
};

/**
 * EntryLayout Component
 * Layout wrapper specifically for individual entry detail pages
 * Provides common layout and functionality for all entry-related views
 */
const EntryLayout = () => {
	return (
		<>
			<PageHeader />
			<Outlet />
		</>
	);
};

export default EntryLayout;
