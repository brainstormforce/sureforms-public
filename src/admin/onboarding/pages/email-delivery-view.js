/**
 * Pure helpers for the onboarding email delivery step, kept apart from the
 * component so they can be unit tested without rendering it.
 */

/**
 * Which screen the step shows for an email delivery status.
 *
 * Keyed off the next step the status offers first, so a failure whose fix is
 * still to set up or connect SureMail leads there. A failure with any other
 * fix gets its own screen with that fix, never the "done" screen.
 *
 * @param {Object|null} status Email delivery status from the REST endpoint.
 * @return {'install'|'connect'|'limited'|'attention'|'done'} View name.
 */
export const getView = ( status ) => {
	const state = status?.state;
	const action = status?.action?.type;

	if ( 'install_activate' === action || 'activate' === action ) {
		return 'install';
	}
	if ( 'open_suremail' === action ) {
		return 'limited' === state ? 'limited' : 'connect';
	}
	if ( 'limited' === state ) {
		return 'limited';
	}
	if ( 'attention' === state ) {
		return 'attention';
	}
	return 'done';
};

/**
 * Analytics action for leaving the "done" screen.
 *
 * @param {Object|null} status Email delivery status.
 * @return {string} Action name.
 */
export const getDoneAction = ( status ) => {
	if ( 'other_mailer' === status?.state ) {
		return 'continue_other_mailer';
	}
	if ( 'ready' === status?.state ) {
		return 'continue_ready';
	}
	return 'continue_not_set_up';
};

/**
 * Sort a failed SureContact sign-up into what the step shows next.
 *
 * @param {Object} provisionError Error body apiFetch rejected with.
 * @return {'already_registered'|'unavailable'|'failed'} Outcome.
 */
export const getProvisionFailure = ( provisionError ) => {
	if ( 'email_already_registered' === provisionError?.reason ) {
		return 'already_registered';
	}
	// No route: SureMail too old, or the endpoint moved. Hand over to SureMail.
	if ( 'rest_no_route' === provisionError?.code ) {
		return 'unavailable';
	}
	return 'failed';
};

/**
 * Whether SureContact refused the site itself: it calls the site back to
 * confirm ownership, so local, private or firewalled sites are refused.
 *
 * @param {Object} provisionError Error body apiFetch rejected with.
 * @return {boolean} Refused for the site.
 */
export const isSiteRefused = ( provisionError ) =>
	[ 'site_unreachable', 'site_not_verified' ].includes(
		provisionError?.reason
	) || /^site_url:/.test( provisionError?.message ?? '' );

/**
 * Whether a host looks local or private, where SureContact cannot call the
 * site back to confirm it owns it, so the one-click sign-up would be refused.
 *
 * A hint only: the sign-up is still allowed, and its own refusal is handled.
 *
 * @param {string} host Hostname, without port.
 * @return {boolean} Looks local or private.
 */
export const isLikelyLocalHost = ( host = '' ) => {
	let name = String( host ).toLowerCase().replace( /^\[|\]$/g, '' );
	// An IPv4 address written as IPv6 (::ffff:127.0.0.1) is still IPv4.
	name = name.replace( /^::ffff:/, '' );
	if ( ! name || 'localhost' === name || '::1' === name || '::' === name ) {
		return true;
	}
	if (
		/\.(test|local|localhost|invalid|example|internal|lan|home\.arpa)$/.test(
			name
		)
	) {
		return true;
	}
	// IPv6 unique-local (fc00::/7) and link-local (fe80::/10).
	if ( name.includes( ':' ) ) {
		return /^(f[cd][0-9a-f]{0,2}|fe[89ab][0-9a-f]?):/.test( name );
	}
	const ip = name.match( /^(\d{1,3})\.(\d{1,3})\.\d{1,3}\.\d{1,3}$/ );
	if ( ! ip ) {
		return false;
	}
	const [ a, b ] = [ Number( ip[ 1 ] ), Number( ip[ 2 ] ) ];
	return (
		0 === a ||
		10 === a ||
		127 === a ||
		( 100 === a && b >= 64 && b <= 127 ) ||
		( 192 === a && 168 === b ) ||
		( 172 === a && b >= 16 && b <= 31 ) ||
		( 169 === a && 254 === b )
	);
};

/**
 * Which option the step opens with.
 *
 * SureContact unless it cannot work here: a local or private site, or a
 * SureMail too old to connect SureContact.
 *
 * @param {Object|null} status    Email delivery status.
 * @param {boolean}     localSite Whether the site looks local or private.
 * @return {'surecontact'|'other_smtp'} Option.
 */
export const getDefaultOption = ( status, localSite ) => {
	if ( localSite ) {
		return 'other_smtp';
	}
	// Only known while SureMail is active: an inactive plugin's version cannot
	// be read, so the status reports false then. Installing or activating it
	// from this step brings the latest version, which supports it.
	if (
		'Activated' === status?.suremail?.plugin_status &&
		false === status?.suremail?.supports_surecontact
	) {
		return 'other_smtp';
	}
	return 'surecontact';
};

/**
 * Whether a SureContact account may be created right now.
 *
 * Read at the moment of the request, not when the button was clicked: install
 * takes seconds, and an owner who unticks consent or switches option in that
 * time has withdrawn it.
 *
 * @param {Object}  args
 * @param {boolean} args.consented Whether the consent box is ticked now.
 * @param {string}  args.option    The open option card now.
 * @param {string}  args.email     Address the account would be created for.
 * @return {boolean} May create.
 */
export const canCreateAccount = ( { consented, option, email } ) =>
	true === consented && 'surecontact' === option && Boolean( email );

/**
 * SureMail's SureContact sign-up screen, which carries "Already have a
 * SureContact account? Connect it instead": the closest SureMail lets another
 * plugin take an existing account, since it only completes a sign-in its own
 * screen started.
 *
 * @param {Object|null} status Email delivery status.
 * @return {string} URL, or '' when SureMail's screen is unknown.
 */
export const getSureContactSignInUrl = ( status ) => {
	const base = status?.action?.url || status?.links?.setup || '';
	if ( ! base ) {
		return '';
	}
	const hash = base.indexOf( '#' );
	return ( -1 === hash ? base : base.slice( 0, hash ) ) + '#/onboarding/connect';
};
