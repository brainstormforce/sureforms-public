/**
 * Local-midnight Date for a 'yyyy-MM-dd' day key.
 *
 * Built from its parts so the Date is that calendar day in every browser
 * timezone; a bare 'yyyy-MM-dd' string is parsed as UTC midnight.
 *
 * @param {string} ymd Day key, e.g. '2026-01-05'.
 * @return {Date} Local midnight on that day.
 */
const dateFromYmd = ( ymd ) => {
	const [ year, month, day ] = ymd.split( '-' ).map( Number );
	return new Date( year, month - 1, day );
};

export default dateFromYmd;
