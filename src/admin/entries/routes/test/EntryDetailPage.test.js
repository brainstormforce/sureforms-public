/* eslint-env jest */
/**
 * EntryDetailPage normalises the `:id` route param before it reaches any
 * child, hook or filter. Pro's note/log handlers write that id into REST
 * paths, so a raw route string must never be passed on.
 */
import { createElement } from '@wordpress/element';
import { useParams } from 'react-router-dom';
import { applyFilters } from '@wordpress/hooks';
import { useEntryDetail } from '../../hooks/useEntriesQuery';
import NotesSection from '../../components/NotesSection';
import EntryLogsSection from '../../components/EntryLogsSection';
import EntryDetailPage from '../EntryDetailPage';

// Components use classic-runtime JSX; webpack provides React globally.
global.React = require( '@wordpress/element' );
// react-dom/server needs TextEncoder, which jsdom lacks.
global.TextEncoder = require( 'util' ).TextEncoder;
const { renderToString } = require( 'react-dom/server' );

jest.mock( 'react-router-dom', () => ( {
	useParams: jest.fn(),
	useNavigate: () => jest.fn(),
	useSearchParams: () => [ new URLSearchParams(), jest.fn() ],
} ) );
jest.mock( '@wordpress/hooks', () => ( {
	applyFilters: jest.fn( () => null ),
} ) );
jest.mock( '../../hooks/useEntriesQuery', () => ( {
	useEntryDetail: jest.fn( () => ( { data: undefined, isLoading: false } ) ),
	useUpdateEntriesReadStatus: () => ( { mutate: jest.fn() } ),
} ) );
jest.mock( '../../utils/entryHelpers', () => ( {
	transformEntryDetail: () => null,
} ) );
jest.mock( '@bsf/force-ui', () => ( {
	Button: () => null,
	Text: ( { children } ) => children,
} ) );
jest.mock( 'lucide-react', () => ( { ArrowLeft: () => null } ) );
jest.mock( '@Utils/Helpers', () => ( { cn: () => '' } ), { virtual: true } );
jest.mock( '@Admin/components/ConfirmationDialog', () => () => null, {
	virtual: true,
} );
// Children are stubbed so the test sees exactly the props the page passes.
jest.mock( '../../components/EntryDataSection', () => jest.fn( () => null ) );
jest.mock( '../../components/SubmissionInfoSection', () => jest.fn( () => null ) );
jest.mock( '../../components/NotesSection', () => jest.fn( () => null ) );
jest.mock( '../../components/PdfFilesSection', () => jest.fn( () => null ) );
jest.mock( '../../components/EntryLogsSection', () => jest.fn( () => null ) );
jest.mock( '../../components/EntryDetailSkeleton', () => jest.fn( () => null ) );
jest.mock( '../../components/EntryNavigation', () => jest.fn( () => null ) );
jest.mock( '../../components/UpgradeTooltip', () => jest.fn( () => null ) );

const extraActionsContext = () =>
	applyFilters.mock.calls.find(
		( [ hook ] ) => hook === 'srfm.entryDetail.extraActions'
	)?.[ 2 ];

beforeEach( () => {
	jest.clearAllMocks();
} );

it.each( [
	[ '42', 42 ],
	[ 'x?rest_route=/wp/v2/users&_method=POST&roles=administrator', null ],
	[ '1/../../wp/v2/users', null ],
	[ 'abc', null ],
] )( 'route id %p reaches children as %p', ( routeId, expected ) => {
	useParams.mockReturnValue( { id: routeId } );

	renderToString( createElement( EntryDetailPage ) );

	expect( useEntryDetail ).toHaveBeenCalledWith( expected );
	expect( NotesSection.mock.calls[ 0 ][ 0 ].entryId ).toBe( expected );
	expect( EntryLogsSection.mock.calls[ 0 ][ 0 ].entryId ).toBe( expected );
	expect( extraActionsContext().entryId ).toBe( expected );
} );
