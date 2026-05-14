import { __, sprintf } from '@wordpress/i18n';

/**
 * Validate From Email and return appropriate warning message.
 *
 * @param {string} fromEmail - The from email value to validate.
 * @return {string|JSX.Element|undefined} Warning message or undefined if valid.
 */
/**
 * Resolve the bare site host (e.g. "localhost", "example.com").
 *
 * srfm_block_data exposes this directly (block editor only). On the global
 * settings page only srfm_admin is available, where site_url is the full URL
 * — so we derive the hostname from it.
 *
 * @return {string} Bare host without protocol or "www.".
 */
// SF-2815 start: shared host resolver for form editor + global settings.
const getSiteHost = () => {
	const blockHost = window?.srfm_block_data?.site_url;
	if ( blockHost ) {
		return blockHost;
	}
	const adminUrl = window?.srfm_admin?.site_url;
	if ( ! adminUrl ) {
		return '';
	}
	try {
		return new URL( adminUrl ).hostname.replace( /^www\./, '' );
	} catch ( e ) {
		return '';
	}
};
// SF-2815 end.

export const getFromEmailWarningMessage = ( fromEmail ) => {
	const email = fromEmail || '';
	const userEnteredUrl = email?.split( '@' )[ 1 ] || '';
	const siteUrl = getSiteHost();

	// If value is a smart tag (starts with '{' and ends with '}').
	if ( email.startsWith( '{' ) && email.endsWith( '}' ) ) {
		if ( window.srfm_block_data?.is_suremails_active ) {
			return sprintf(
				/* translators: %1$s: site URL, %2$s: site URL. */
				__(
					"The current 'From Email' address may not match your website domain name (%1$s). This can cause your notification emails to be blocked or marked as spam. Alternately, try using a From Address that matches your website domain (admin@%2$s).",
					'sureforms'
				),
				siteUrl,
				siteUrl
			);
		}
		return {
			type: 'jsx',
			siteUrl,
			messageType: 'smart_tag',
		};
	}

	const isValidEmail =
		/^[\p{L}\p{N}._%+-]+@[\p{L}\p{N}.-]+\.[\p{L}]{2,}$/u.test( email );

	// If email is empty or invalid.
	if ( email === '' || ! isValidEmail ) {
		return __(
			"Please enter a valid email address. Your notifications won't be sent if the field is not filled in correctly.",
			'sureforms'
		);
	}

	// If email domain doesn't match site domain.
	if ( userEnteredUrl !== siteUrl ) {
		if ( window.srfm_block_data?.is_suremails_active ) {
			return sprintf(
				/* translators: %1$s: site URL, %2$s: site URL. */
				__(
					"The current 'From Email' address does not match your website domain name (%1$s). This can cause your notification emails to be blocked or marked as spam. Alternately, try using a From Address that matches your website domain (admin@%2$s).",
					'sureforms'
				),
				siteUrl,
				siteUrl
			);
		}
		return {
			type: 'jsx',
			siteUrl,
			messageType: 'domain_mismatch',
		};
	}

	return undefined;
};

/**
 * Render the SureMail recommendation JSX message.
 *
 * @param {string} siteUrl     - The site URL.
 * @param {string} messageType - Type of message: 'smart_tag' or 'domain_mismatch'.
 * @return {JSX.Element} The warning message JSX.
 */
export const renderSureMailRecommendation = ( siteUrl, messageType ) => {
	const smartTagPrefix = sprintf(
		/* translators: %s: site URL. */
		__(
			"The current 'From Email' address may not match your website domain name (%s). This can cause your notification emails to be blocked or marked as spam. ",
			'sureforms'
		),
		siteUrl
	);
	const domainMismatchPrefix = sprintf(
		/* translators: %s: site URL. */
		__(
			"The current 'From Email' address does not match your website domain name (%s). This can cause your notification emails to be blocked or marked as spam. ",
			'sureforms'
		),
		siteUrl
	);
	const messagePrefix =
		messageType === 'smart_tag' ? smartTagPrefix : domainMismatchPrefix;

	const alternateMessage = sprintf(
		/* translators: %s: site URL. */
		__(
			' Alternately, try using a From Address that matches your website domain (admin@%s).',
			'sureforms'
		),
		siteUrl
	);

	return (
		<>
			{ messagePrefix }
			{ __(
				'We strongly recommend that you install the free ',
				'sureforms'
			) }
			<a
				href="https://suremails.com?utm_medium=sureforms"
				target="_blank"
				rel="noopener noreferrer"
			>
				SureMail
			</a>
			{ __(
				' plugin! The Setup Wizard makes it easy to fix your emails. ',
				'sureforms'
			) }
			{ alternateMessage }
		</>
	);
};
