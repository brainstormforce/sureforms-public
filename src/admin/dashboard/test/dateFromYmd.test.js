/* eslint-env jest */
/**
 * The entries chart labels each { date: 'yyyy-MM-dd' } row from
 * get_entries_chart_data() with this Date. It must be local midnight on that
 * calendar day in every browser timezone, including west of UTC where a bare
 * 'yyyy-MM-dd' string parses to the previous evening.
 *
 * Jest's sandbox ignores a runtime TZ change and CI runs in UTC, where both
 * spellings agree, so each timezone is checked in a child Node process.
 */
import { execFileSync } from 'child_process';
import { resolve } from 'path';
import { pathToFileURL } from 'url';

const helperUrl = pathToFileURL(
	resolve( __dirname, '../utils/dateFromYmd.js' )
).href;

const partsIn = ( tz ) =>
	JSON.parse(
		execFileSync(
			process.execPath,
			[
				'--no-warnings',
				'--input-type=module',
				'-e',
				`const { default: dateFromYmd } = await import( ${ JSON.stringify(
					helperUrl
				) } );
				const d = dateFromYmd( '2026-01-05' );
				process.stdout.write( JSON.stringify( [ d.getFullYear(), d.getMonth(), d.getDate(), d.getHours() ] ) );`,
			],
			{ env: { ...process.env, TZ: tz } }
		).toString()
	);

describe( 'dateFromYmd', () => {
	it.each( [
		'America/Los_Angeles',
		'UTC',
		'Asia/Kolkata',
		'Pacific/Kiritimati',
	] )( 'is local midnight on that day in %s', ( tz ) => {
		expect( partsIn( tz ) ).toEqual( [ 2026, 0, 5, 0 ] );
	} );
} );
