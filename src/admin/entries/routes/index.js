import { HashRouter, Routes, Route } from 'react-router-dom';
import EntriesListingPage from './EntriesListingPage';
import EntryDetailPage from './EntryDetailPage';
import RootLayout from './RootLayout';
import EntryLayout from './EntryLayout';

// Router component with hash routing
export const AppRouter = () => {
	return (
		<HashRouter
			basename="/"
			future={ {
				v7_startTransition: true,
				v7_relativeSplatPath: true,
			} }
		>
			<Routes>
				{ /* Listing page with RootLayout */ }
				<Route path="/" element={ <RootLayout /> }>
					<Route index element={ <EntriesListingPage /> } />
				</Route>

				{ /* Entry detail page with EntryLayout */ }
				<Route path="/entry/:id" element={ <EntryLayout /> }>
					<Route index element={ <EntryDetailPage /> } />
				</Route>
			</Routes>
		</HashRouter>
	);
};
