import { __ } from '@wordpress/i18n';
import { useState, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Button, Label } from '@bsf/force-ui';
import {
	CircleCheck,
	TriangleAlert,
	ChevronUp,
	ChevronDown,
} from 'lucide-react';
import safeUrl from './utils/safeUrl';
import { installAndActivatePlugin } from '@Utils/Helpers';

/**
 * Dashboard panel listing what SureForms has checked on this site.
 *
 * Follows SureRank's Page Checks: one row per problem, with a fix link and, for
 * advisory rows, an Ignore control. Row markup mirrors SureRank's CheckCard so
 * the two plugins look like siblings. Passing checks are not listed -- a panel
 * confirming nothing is wrong is something people learn to skip.
 *
 * Rows are supplied fully formed by the server (see Admin::get_action_items()),
 * so adding a check needs no change here.
 */
const ICONS = {
	// Red for a fault that is losing entries, amber for advice the site owner can
	// act on at their leisure. Both are triangles: the shape says "check", the
	// colour says how urgent.
	//
	// amber-500 rather than force-ui's badge-color-yellow (#A16207): at 16px, next
	// to the red, that token reads brown rather than as a warning. The row's text
	// carries the same meaning, so the icon is not the only thing conveying it.
	error: <TriangleAlert className="size-4 text-badge-color-red shrink-0" />,
	warning: <TriangleAlert className="size-4 text-amber-500 shrink-0" />,
	success: (
		<CircleCheck className="size-4 text-badge-color-green shrink-0" />
	),
};

export default () => {
	// Read here rather than at module scope. Optional chaining does not protect
	// against an undeclared identifier, so a bundle enqueued without its localize
	// data would throw at module eval and take the whole dashboard down instead of
	// just this panel.
	const [ dismissed, setDismissed ] = useState( [] );
	const [ open, setOpen ] = useState( true );
	// Item id and installer stage while a one-click install runs.
	const [ installing, setInstalling ] = useState( { id: '', stage: '' } );

	const items = useMemo( () => {
		const all =
			typeof srfm_admin === 'undefined'
				? []
				: srfm_admin?.action_items || [];

		return all.filter( ( item ) => ! dismissed.includes( item.id ) );
	}, [ dismissed ] );

	if ( ! items.length ) {
		return null;
	}

	/**
	 * POST to admin-ajax, fire and forget.
	 *
	 * @param {string} action The admin-ajax action.
	 * @param {string} nonce  Its nonce.
	 * @param {Object} extra  Extra body fields.
	 * @return {Promise} The fetch, ignored by every caller.
	 */
	const post = ( action, nonce, extra = {} ) =>
		fetch( srfm_admin?.ajax_url || '', {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams( {
				action,
				nonce: nonce || '',
				...extra,
			} ),
		} ).catch( () => {} );

	const handleIgnore = ( item ) => () => {
		post(
			'srfm_dismiss_action_item',
			srfm_admin?.dismiss_action_item_nonce,
			{
				item_id: item.id,
			}
		);

		setDismissed( ( prev ) => [ ...prev, item.id ] );
	};

	// Self-serve first. Someone who can fix it themselves should see that before
	// they are pointed at a support queue, and the emphasis follows the order
	// rather than the identity -- whichever action leads reads as the primary one,
	// so an item with no guide still has Contact Support in front.
	const actionsFor = ( item ) => [
		...( item.guide_label && item.guide_url
			? [
				{
					name: item.guide_action,
					label: item.guide_label,
					url: safeUrl( item.guide_url ),
					external: true,
				},
			  ]
			: [] ),
		...( item.cta_label && item.cta_url
			? [
				{
					name: item.cta_action,
					label: item.cta_label,
					url: safeUrl( item.cta_url ),
					install: item.cta_install || '',
					// A mailto must reach the mail client, not a browser tab --
					// there is no document to open, so _blank leaves a blank one
					// behind. srfm_action_items is public, so an http(s) item can
					// still arrive. Matched case-insensitively: a MAILTO: from the
					// filter would otherwise get target="_blank".
					// The plugin installer is part of wp-admin, not a page to keep
					// beside the dashboard.
					external:
							! item.cta_install && ! /^mailto:/i.test( item.cta_url ),
				},
			  ]
			: [] ),
	];

	const handleFix = ( item, action ) => () =>
		post( 'srfm_notice_response', srfm_admin?.notice_response_nonce, {
			notice_id: item.id,
			// Named by the server, so the button key is not duplicated here and in
			// the allowlist that has to accept it.
			button: action,
		} );

	/**
	 * Install and activate a plugin in place, then continue to its setup.
	 *
	 * The link itself points at WordPress's installer, so it still works if any
	 * of this fails: every failure path falls back to following it.
	 *
	 * @param {Object} item   Action item.
	 * @param {Object} action Resolved action.
	 * @return {Function} Click handler.
	 */
	const handleInstall = ( item, action ) => async ( event ) => {
		const plugin = srfm_admin?.integrations?.[ action.install ];
		if ( ! plugin || installing.id ) {
			return;
		}

		event.preventDefault();
		handleFix( item, action.name )();
		setInstalling( { id: item.id, stage: 'installing' } );

		try {
			await installAndActivatePlugin( plugin, ( stage ) =>
				setInstalling( { id: item.id, stage } )
			);
			// SureMail skips its own welcome redirect for an AJAX activation, so
			// ask where its setup lives now that it is loaded.
			const status = await apiFetch( {
				path: '/sureforms/v1/email-delivery/status',
			} );
			window.location.href =
				safeUrl( status?.action?.url || status?.links?.setup ) ||
				action.url;
		} catch ( error ) {
			window.location.href = action.url;
		}
	};

	const labelFor = ( item, action ) => {
		if ( installing.id !== item.id || ! action.install ) {
			return action.label;
		}
		return 'activating' === installing.stage
			? __( 'Activating SureMail…', 'sureforms' )
			: __( 'Installing SureMail…', 'sureforms' );
	};

	return (
		<div className="w-full bg-background-primary border-0.5 border-solid rounded-xl border-border-subtle p-3 gap-2 shadow-sm-blur-1">
			<div className="flex items-center justify-between p-1">
				<Label size="sm" className="font-semibold">
					{ __( 'Form Checks', 'sureforms' ) }
				</Label>
				<button
					type="button"
					onClick={ () => setOpen( ( prev ) => ! prev ) }
					aria-expanded={ open }
					aria-label={
						open
							? __( 'Collapse form checks', 'sureforms' )
							: __( 'Expand form checks', 'sureforms' )
					}
					className="flex items-center bg-transparent border-0 p-0.5 cursor-pointer text-icon-secondary hover:text-icon-primary"
				>
					{ open ? (
						<ChevronUp className="size-4" />
					) : (
						<ChevronDown className="size-4" />
					) }
				</button>
			</div>
			{ /* Same nesting as Quick Access below it: a grey well inside the white
			     card, holding white rows. The two sit in one column and were
			     reading as different components. */ }
			{ open && (
				<div className="flex flex-col bg-background-secondary gap-1 p-1 rounded-lg">
					{ items.map( ( item ) => (
						<div
							key={ item.id }
							className="relative flex flex-col gap-1 p-3 rounded-md bg-background-primary shadow-sm-blur-1"
						>
							<div className="w-full flex items-start gap-2">
								{ ICONS[ item.status ] ?? ICONS.warning }
								<div className="flex-1">
									<Label
										size="sm"
										className="font-medium block"
									>
										{ item.title }
									</Label>
									{ !! item.message && (
										<Label
											size="xs"
											variant="help"
											className="font-normal block pt-0.5"
										>
											{ item.message }
										</Label>
									) }
								</div>
								{ !! item.dismissible && (
									<Button
										variant="link"
										size="xs"
										onClick={ handleIgnore( item ) }
										className="font-medium no-underline hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-1 focus:[box-shadow:none] [&>span]:px-0 text-text-secondary shrink-0"
									>
										{ __( 'Ignore', 'sureforms' ) }
									</Button>
								) }
							</div>
							{ !! actionsFor( item ).length && (
								<div className="pl-6 flex items-center gap-4">
									{ actionsFor( item ).map( ( action, index ) => (
										<Button
											key={
												action.name ||
												`${ item.id }-${ index }`
											}
											variant="link"
											size="xs"
											tag="a"
											href={ action.url }
											{ ...( action.external && {
												target: '_blank',
												rel: 'noopener noreferrer',
											} ) }
											// Only when the server named one.
											// An unnamed action posts
											// "undefined" as the button and is
											// rejected by the allowlist anyway.
											{ ...( action.name && {
												onClick: action.install
													? handleInstall(
														item,
														action
													  )
													: handleFix(
														item,
														action.name
													  ),
											} ) }
											// Underlined on hover only, matching
											// Quick Access below it. Three
											// underlined links stacked in a narrow
											// column read as a block of noise
											// rather than as actions.
											className={ `font-medium no-underline hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-1 focus:[box-shadow:none] [&>span]:px-0${
												index > 0
													? ' text-text-secondary'
													: ''
											}` }
										>
											{ labelFor( item, action ) }
										</Button>
									) ) }
								</div>
							) }
						</div>
					) ) }
				</div>
			) }
		</div>
	);
};
