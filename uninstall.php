<?php
/**
 * SureForms Uninstall
 *
 * Fired when the plugin is deleted (not deactivated), and only when the site
 * owner has opted in from the deactivation survey, which offers the choice at
 * the moment the plugin is being switched off. Default is off, so deleting the
 * plugin changes no data unless that box was ticked first.
 *
 * Scope is deliberately narrow: the analytics buffer inside the `srfm_options`
 * row. Those are counters and a queue of events not yet sent, and on a reinstall
 * they come back and are reported again as if they were new. Nothing else is
 * touched - not a form, an entry, an upload, a table, a transient, a cron entry
 * or any other option. The `srfm_options` row itself is rewritten in place
 * rather than deleted, so a reinstall keeps the site's configuration.
 *
 * Shared Brainstorm Force keys (`bsf_analytics_*`, `bsf_usage_*`,
 * `sureforms_usage_optin`, `bsf_product_referers`) belong to every BSF product
 * on the site and are never ours to remove.
 *
 * Pro needs no equivalent file. SureForms Pro keeps no analytics storage of its
 * own: it queues events through this plugin's Analytics::events(), so its data
 * is already inside the row cleaned here.
 *
 * Runs with the plugin NOT loaded - WordPress includes this file standalone
 * (see uninstall_plugin() in wp-admin/includes/plugin.php), so no SureForms
 * class, constant or autoloader is available here. Everything is plain core API.
 *
 * Multisite: deleting through the admin UI is a network-admin action, so this
 * file runs once against the main site's options table and sub-site rows are
 * left in place. There is no per-site loop in core and none is added here.
 * `wp plugin uninstall --url=<subsite>` is the exception: WP-CLI runs it in that
 * sub-site's context, so it cleans that sub-site instead.
 *
 * @package sureforms
 * @since 2.13.0
 */

// Exit if not called by WordPress uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/*
 * Respect the opt-in, before anything at all happens.
 *
 * With the toggle off this file is a complete no-op: deleting the plugin leaves
 * the site exactly as it was found. Nothing may move above this gate.
 *
 * The check is `true !==` rather than `! empty()` on purpose. Under empty(), a
 * stray string such as "false" or "no" - which the ajax handler cannot write,
 * but a hand-edited row or an options editor can - would read as consent. The
 * handler stores a real boolean (see Analytics::save_uninstall_preference), so
 * anything that is not boolean true fails closed.
 *
 * The plugin is not loaded, so read the consolidated row directly rather than
 * through Helper::get_srfm_option().
 */
$srfm_options = get_option( 'srfm_options', [] );

if ( ! is_array( $srfm_options ) || true !== ( $srfm_options['remove_analytics_data_on_uninstall'] ?? false ) ) {
	return;
}

/*
 * The analytics buffer: counters and queued events, and nothing else.
 *
 * An explicit remove list, not a keep list. The two drift in opposite
 * directions, and only one of them is safe: a remove list that misses a newly
 * added counter leaves a stale number behind, while a keep list that misses a
 * newly added setting deletes a site's Stripe keys. This file is the
 * destructive one, so it names what it takes.
 *
 * Each entry, and who writes it:
 *
 *  - usage_events_pending       inc/lib/bsf-analytics/class-bsf-analytics-events.php
 *                               (events queued for the next send, never sent)
 *  - action_item_events         admin/admin.php
 *  - action_item_impressions    admin/admin.php
 *  - ai_dashboard_widget_uses   admin/admin.php
 *  - edit_form_button_clicks    admin/admin.php
 *  - ai_starter_usage           admin/analytics.php
 *  - onboarding_analytics       inc/rest-api.php
 *  - user_active                admin/analytics.php (derived from the forms on
 *                               the site, so it is a cache, not a record)
 *  - email_delivery_state_tracked
 *                               admin/analytics.php. Not in the original issue
 *                               list, which predates it (@since 2.12.9). It is a
 *                               fingerprint of the last reported mailer state -
 *                               a change detector, not a once-per-site guard -
 *                               so dropping it costs one re-report of the state
 *                               as it stands after a reinstall, which is the
 *                               correct value anyway.
 *
 * Plus the opt-in itself. Consent is deliberately not inherited: re-persisting
 * the live toggle would mean a reinstall silently carries a previous operator's
 * decision, so a reinstall starts opted out and consent has to be given again.
 *
 * Deliberately kept, though they sit in the same neighbourhood:
 *
 *  - usage_events_pushed        the permanent, once-per-site dedupe list for
 *                               milestone events. Remove it and a reinstall
 *                               re-fires every one-time event, double-counting
 *                               the activation funnel.
 *  - onboarding_event_v2_flushed a dedupe flag of the same kind, for the
 *                               onboarding event.
 *  - first_form_created_at      milestone state, and the timestamp several of
 *                               those one-time events are measured against.
 *
 * Every other key - payment_settings, forms_per_page, dismissed_action_items,
 * the pointer-popup flags, plugin_banner_rotation, remaining_promos,
 * db_repair_attempts, the onboarding settings - is configuration or UI state and
 * survives untouched.
 */
$srfm_analytics_keys = [
	'usage_events_pending',
	'action_item_events',
	'action_item_impressions',
	'ai_dashboard_widget_uses',
	'edit_form_button_clicks',
	'ai_starter_usage',
	'onboarding_analytics',
	'user_active',
	'email_delivery_state_tracked',
	'remove_analytics_data_on_uninstall',
];

$srfm_removed = false;

foreach ( $srfm_analytics_keys as $srfm_analytics_key ) {
	if ( array_key_exists( $srfm_analytics_key, $srfm_options ) ) {
		unset( $srfm_options[ $srfm_analytics_key ] );
		$srfm_removed = true;
	}
}

if ( ! $srfm_removed ) {
	return;
}

if ( [] === $srfm_options ) {
	/*
	 * Nothing but the buffer was ever in there. Take the row rather than leave
	 * an empty one behind; a reinstall rebuilds it from defaults on first write.
	 */
	delete_option( 'srfm_options' );
} else {
	update_option( 'srfm_options', $srfm_options );
}

/*
 * Evict the cached copy as well.
 *
 * update_option() and delete_option() already maintain the option cache, so
 * this is belt and braces rather than a fix for a known gap - but the row is
 * autoloaded, the write happens with the plugin unloaded and nothing left on
 * this site will ever correct a stale entry under a persistent backend.
 */
wp_cache_delete( 'srfm_options', 'options' );
wp_cache_delete( 'alloptions', 'options' );
wp_cache_delete( 'notoptions', 'options' );
