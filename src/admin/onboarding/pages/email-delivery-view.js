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
