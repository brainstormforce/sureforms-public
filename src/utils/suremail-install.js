/**
 * Installing SureMail from the onboarding step and the SMTP page.
 *
 * Both screens get SureMail's install state with the page, and it goes stale:
 * an install can land while its activation fails, or SureMail can be installed
 * from another tab. Installing again then fails ("Destination folder already
 * exists") until the owner reloads. So the state is read again, from the
 * email-delivery status, right before the install starts.
 *
 * No imports, so it can be unit-tested without the admin bundle.
 */

// What Helper::get_plugin_status() reports, and what installAndActivatePlugin() reads.
const PLUGIN_STATES = [ 'Install', 'Installed', 'Activated' ];

/**
 * SureMail's install state from an email-delivery status response.
 *
 * @since x.x.x
 * @param {Object|null} status   Response of /sureforms/v1/email-delivery/status.
 * @param {string}      fallback State to use when the response has none.
 * @return {string} 'Install' | 'Installed' | 'Activated'.
 */
export const getSureMailPluginStatus = ( status, fallback ) => {
	const live = status?.suremail?.plugin_status;
	return PLUGIN_STATES.includes( live ) ? live : fallback;
};

/**
 * Install (only if it is not there) and activate SureMail, going by its
 * current state rather than the one the page loaded with.
 *
 * @since x.x.x
 * @param {Object}   args
 * @param {Object}   args.plugin     Plugin descriptor; its status is the page-load fallback.
 * @param {Function} args.readStatus Resolves with the current email-delivery status.
 * @param {Function} args.install    installAndActivatePlugin().
 * @param {Function} args.onProgress Progress callback passed to install.
 * @return {Promise<string>} The state the install went by.
 */
export const installSureMail = async ( {
	plugin,
	readStatus,
	install,
	onProgress = () => {},
} ) => {
	let current = null;
	try {
		current = await readStatus();
	} catch ( e ) {
		// Unreadable: go by what the page loaded with, as before.
	}
	const pluginStatus = getSureMailPluginStatus( current, plugin?.status );
	await install( { ...plugin, status: pluginStatus }, onProgress );
	return pluginStatus;
};
