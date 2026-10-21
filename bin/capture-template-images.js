#!/usr/bin/env node
/**
 * Captures the pictures of the form templates, images/templates/<id>.webp:
 * the library card shows the top of each one and a locked Premium template's
 * preview shows the whole picture.
 *
 * Each picture is the template's real preview page (/?srfm_template_preview=<id>),
 * the same page the library's live preview loads: the white form on the
 * template's page color, in its default style, full height.
 *
 * Usage (from the plugin folder, on a local site that runs this checkout;
 * capture the free ones with SureForms Pro deactivated, then activate it):
 *
 *   node bin/capture-template-images.js --plan=free
 *   node bin/capture-template-images.js --plan=premium
 *   node bin/capture-template-images.js --plan=pro
 *   node bin/capture-template-images.js --plan=free --only=contact-us,rsvp
 *
 * Options:
 *   --site=<url>   Site URL. Default http://localhost:8888, or SRFM_SITE.
 *   --user=<name>  Administrator login. Default admin, or SRFM_USER.
 *   --pass=<pass>  Password. Default admin, or SRFM_PASS.
 *   --plan=<plan>  `free`: the free templates, as the free plugin builds them.
 *                  The site must run SureForms without SureForms Pro.
 *                  `premium`: the Premium templates (requires !== 'free'), as
 *                  the Business plan builds them. The site must run a
 *                  SureForms Pro Business build that supports the templates
 *                  (SRFM_PRO_TEMPLATE_API 2 or later). Faking the plan with
 *                  the `srfm_pro_tier` filter is not enough without SureForms
 *                  Pro: its blocks (calculations, signatures, uploads...)
 *                  would not render.
 *                  `pro`: the free templates again, as the Business plan
 *                  builds them (step dividers, Pro fields), saved as
 *                  <id>-pro.webp. Sites with SureForms Pro show that picture.
 *                  When it comes out the same as the free picture it is not
 *                  kept, so only templates Pro changes have one.
 *   --only=<ids>   Comma-separated template ids to capture.
 *   --out=<dir>    Output folder. Default images/templates.
 *
 * The pictures ship to every site, so nothing in them comes from the capture
 * machine or site: the browser runs as en-US in New York (a phone field that
 * detects the visitor's country shows +1, the plugin's own fallback), the
 * admin-only Stripe test-mode notice is hidden, and the "Connect Stripe" note
 * is drawn as the card fields a connected site shows. A conversational
 * template (welcome screen, one question per screen) is drawn as its welcome
 * card with the first question under it.
 *
 * Needs Google Chrome (Playwright's `chrome` channel) and `cwebp`
 * (brew install webp). Theme fonts come from the site's active theme; the
 * shipped pictures use Twenty Twenty-Five.
 */

/* eslint-disable no-console */
const { execFileSync } = require( 'child_process' );
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );
const { chromium } = require( '@playwright/test' );

const args = Object.fromEntries(
	process.argv.slice( 2 ).map( ( arg ) => {
		const [ key, ...value ] = arg.replace( /^--/, '' ).split( '=' );
		return [ key, value.join( '=' ) ];
	} )
);

const SITE = ( args.site || process.env.SRFM_SITE || 'http://localhost:8888' ).replace( /\/$/, '' );
const USER = args.user || process.env.SRFM_USER || 'admin';
const PASS = args.pass || process.env.SRFM_PASS || 'admin';
const PLAN = args.plan;
const ONLY = args.only ? args.only.split( ',' ).map( ( id ) => id.trim() ) : null;
const OUT = path.resolve( __dirname, '..', args.out || 'images/templates' );

// Same width as the preview page's form (650px less its 24px sides), plus
// room on each side, so on a ~380px card the form sits about 24px from the
// tile's sides and, with the page's own 40px top padding, about 22px from its top.
const FORM_WIDTH = 602;
const SIDE = 44;
// 760px: twice the widest card tile (~381px at 1440), and a little over
// the 690px the locked preview shows the picture at.
const OUTPUT_WIDTH = 760;
const MAX_BYTES = 58 * 1024;

if ( ! [ 'free', 'premium', 'pro' ].includes( PLAN ) ) {
	console.error( 'Pass --plan=free, --plan=premium or --plan=pro. See the header of this file.' );
	process.exit( 1 );
}

/**
 * The page color behind a template's form in its default style, like
 * resolveLook() in template-colors.js.
 *
 * @param {string} theme  Template's theme id.
 * @param {Array}  themes Presets from the templates endpoint.
 * @return {string} Hex color.
 */
const pageColor = ( theme, themes ) => {
	const own = themes.find( ( preset ) => preset.id === theme ) || themes[ 0 ] || {};
	return { ...own, ...( own.default || {} ) }.page || '#F3F4F6';
};

/**
 * Encodes a PNG as WebP within the size budget, lowering the quality as needed.
 *
 * @param {string} png  PNG path.
 * @param {string} webp WebP path.
 * @return {{quality: number, bytes: number}} What was written.
 */
const toWebp = ( png, webp ) => {
	for ( let quality = 82; quality >= 40; quality -= 6 ) {
		execFileSync( 'cwebp', [ '-quiet', '-m', '6', '-sharp_yuv', '-q', String( quality ), '-resize', String( OUTPUT_WIDTH ), '0', '-metadata', 'none', png, '-o', webp ] );
		const bytes = fs.statSync( webp ).size;
		if ( bytes <= MAX_BYTES ) {
			return { quality, bytes };
		}
	}
	throw new Error( `${ path.basename( webp ) } stays over ${ MAX_BYTES } bytes.` );
};

/**
 * A picture's pixels, decoded with `dwebp` (part of the same webp package).
 *
 * @param {string} webp Picture.
 * @return {{width: number, height: number, data: Buffer}} RGBA pixels.
 */
const readPixels = ( webp ) => {
	const pam = execFileSync( 'dwebp', [ '-quiet', webp, '-pam', '-o', '-' ], { maxBuffer: 64 * 1024 * 1024 } );
	const end = pam.indexOf( 'ENDHDR\n' ) + 7;
	const header = pam.subarray( 0, end ).toString();
	return {
		width: Number( /WIDTH (\d+)/.exec( header )[ 1 ] ),
		height: Number( /HEIGHT (\d+)/.exec( header )[ 1 ] ),
		data: pam.subarray( end ),
	};
};

/**
 * Whether two pictures show the same thing: same size and no more than a few
 * pixels apart (font smoothing can flip a pixel or two between renders).
 *
 * @param {string} a Picture.
 * @param {string} b Picture.
 * @return {boolean} Same.
 */
const looksTheSame = ( a, b ) => {
	const one = readPixels( a );
	const two = readPixels( b );
	if ( one.width !== two.width || one.height !== two.height ) {
		return false;
	}
	let different = 0;
	for ( let i = 0; i < one.data.length; i += 4 ) {
		const gap = Math.max(
			Math.abs( one.data[ i ] - two.data[ i ] ),
			Math.abs( one.data[ i + 1 ] - two.data[ i + 1 ] ),
			Math.abs( one.data[ i + 2 ] - two.data[ i + 2 ] )
		);
		if ( gap > 20 && ++different > 50 ) {
			return false;
		}
	}
	return true;
};

/**
 * Encode a capture to its picture. A `pro` capture that shows the same as the
 * free picture is dropped: the free picture already shows that template on a
 * Pro site.
 *
 * @param {string} id  Template id.
 * @param {string} png Captured PNG.
 * @return {{quality: number, bytes: number}} Encoding used.
 */
const save = ( id, png ) => {
	const free = path.join( OUT, `${ id }.webp` );
	const target = 'pro' === PLAN ? path.join( OUT, `${ id }-pro.webp` ) : free;
	const result = toWebp( png, target );
	if ( 'pro' === PLAN && fs.existsSync( free ) && looksTheSame( free, target ) ) {
		fs.rmSync( target );
		console.log( `${ id }: same as the free picture, no Pro picture kept` );
	}
	return result;
};

( async () => {
	const browser = await chromium.launch( { channel: 'chrome' } );
	const context = await browser.newContext( {
		viewport: { width: 1000, height: 900 },
		deviceScaleFactor: 2,
		ignoreHTTPSErrors: true,
		reducedMotion: 'reduce',
		// The phone field detects the country from the time zone, then the language.
		locale: 'en-US',
		timezoneId: 'America/New_York',
	} );
	const page = await context.newPage();

	await page.goto( `${ SITE }/wp-login.php` );
	await page.fill( '#user_login', USER );
	await page.fill( '#user_pass', PASS );
	await Promise.all( [ page.waitForNavigation(), page.click( '#wp-submit' ) ] );

	const nonce = await ( await page.request.get( `${ SITE }/wp-admin/admin-ajax.php?action=rest-nonce` ) ).text();
	const response = await page.request.get( `${ SITE }/?rest_route=/sureforms/v1/form-templates`, { headers: { 'X-WP-Nonce': nonce } } );
	if ( ! response.ok() ) {
		throw new Error( `Templates endpoint answered ${ response.status() }; check the login.` );
	}
	const { templates, themes, pro_tier: tier } = await response.json();

	const wanted = 'free' === PLAN ? 'free' : 'business';
	if ( tier !== wanted ) {
		throw new Error( `--plan=${ PLAN } needs a site on the ${ wanted } plan; this one is on ${ tier }. See the header of this file.` );
	}

	const list = templates.filter(
		( template ) => ( 'premium' !== PLAN ) === ( 'free' === template.requires ) && ( ! ONLY || ONLY.includes( template.id ) )
	);
	const tmp = fs.mkdtempSync( path.join( os.tmpdir(), 'srfm-template-images-' ) );
	const failures = [];

	for ( const template of list ) {
		try {
			if ( template.is_locked ) {
				throw new Error( 'locked on this site, so its preview is not the full version' );
			}
			await page.goto( template.preview_url, { waitUntil: 'networkidle' } );
			// A conversational template previews as its own full page (welcome
			// screen first), which paints its own background.
			const conversational = ( await page.locator( '.srfm-cf-ctn' ).count() ) > 0;
			await page.addStyleTag( {
				content: `${ conversational ? '' : `html, body.srfm-template-preview { background: ${ pageColor( template.theme, themes ) } !important; }` }
					*, *::before, *::after { transition: none !important; animation: none !important; caret-color: transparent !important; }
					.srfm-test-mode-notice { display: none !important; }`,
			} );
			await page.evaluate( async () => {
				// Runs in the preview page, which has no node refs to go by.
				// eslint-disable-next-line @wordpress/no-global-active-element
				document.activeElement?.blur?.();
				// Stripe's card fields, as a site that has connected Stripe shows them.
				document
					.querySelectorAll( '.srfm-template-preview__payment-note' )
					.forEach( ( note ) => {
						const muted = 'color: #9CA3AF; font-size: 15px;';
						note.innerHTML = `<span style="${ muted }">Card number</span><span style="${ muted } margin-inline-start: auto;">MM / YY</span><span style="${ muted }">CVC</span>`;
						note.style.cssText += 'display: flex; gap: 24px; align-items: center; margin: 0;';
					} );
				await document.fonts.ready;
				await Promise.all(
					Array.from( document.images )
						.filter( ( image ) => ! image.complete )
						.map(
							( image ) =>
								new Promise( ( resolve ) => {
									image.addEventListener( 'load', resolve );
									image.addEventListener( 'error', resolve );
								} )
						)
				);
			} );
			// Scripts that finish the form's look after load, e.g. the phone field's flag.
			await page.waitForTimeout( 800 );

			const png = path.join( tmp, `${ template.id }.png` );
			if ( conversational ) {
				// Drawn like the other cards: the welcome screen as a white card
				// on the page color, with the first question as a second card
				// under it, so the picture says "one question at a time".
				await page.addStyleTag( {
					content: `html, body, .srfm-cf-inner-ctn { background: ${ pageColor( template.theme, themes ) } !important; }
						.srfm-cf-inner-ctn { justify-content: flex-start !important; align-items: center !important; padding-top: ${ SIDE }px !important; }
						.srfm-cf-progress-bar-ctn, .srfm-error-message { display: none !important; }`,
				} );
				const box = await page.evaluate( ( formWidth ) => {
					const content = document.querySelector( '.srfm-cf-content' );
					const blocks = document.querySelectorAll( '.srfm-cf-block' );
					if ( ! content || ! blocks.length ) {
						return null;
					}
					const [ welcome, first ] = blocks;
					const card = `box-sizing: border-box; width: ${ formWidth }px; background: #fff; border-radius: 12px;`;
					content.style.cssText += `; width: ${ formWidth }px; max-width: ${ formWidth }px; padding: 0; background: none;`;
					welcome.style.cssText += `; ${ card } padding: 40px; text-align: center;`;
					if ( first ) {
						first.classList.add( 'srfm-active' );
						first.style.cssText += `; display: block; opacity: 1; visibility: visible; transform: none; position: static; margin-top: 16px; ${ card } padding: 32px 40px;`;
						welcome.after( first );
					}
					// Runs in the preview page, which has no node refs to go by.
					// eslint-disable-next-line @wordpress/no-global-active-element
					document.activeElement?.blur?.();
					const rect = content.getBoundingClientRect();
					return { left: rect.left + window.scrollX, top: rect.top + window.scrollY, bottom: rect.bottom + window.scrollY };
				}, FORM_WIDTH );
				if ( ! box ) {
					throw new Error( 'no conversational welcome screen on the preview page' );
				}
				await page.waitForTimeout( 300 );
				await page.screenshot( {
					path: png,
					fullPage: true,
					clip: { x: box.left - SIDE, y: Math.max( 0, box.top - SIDE ), width: FORM_WIDTH + ( 2 * SIDE ), height: Math.ceil( box.bottom - box.top + ( 2 * SIDE ) ) },
				} );
				const { quality, bytes } = save( template.id, png );
				console.log( `${ template.id }: ${ Math.round( bytes / 1024 ) } KB (q${ quality }, conversational)` );
				continue;
			}

			const box = await page.evaluate( () => {
				const wrap = document.querySelector( '.srfm-template-preview__wrap' );
				const form = wrap?.querySelector( '.srfm-form-container' );
				if ( ! form || document.querySelector( '#wpadminbar' ) ) {
					return null;
				}
				const outer = wrap.getBoundingClientRect();
				const inner = form.getBoundingClientRect();
				return { left: inner.left + window.scrollX, width: inner.width, bottom: outer.bottom + window.scrollY };
			} );
			if ( ! box ) {
				throw new Error( 'no form on the preview page, or the admin bar shows' );
			}
			if ( Math.round( box.width ) !== FORM_WIDTH ) {
				throw new Error( `form is ${ box.width }px wide, expected ${ FORM_WIDTH }px` );
			}
			await page.screenshot( {
				path: png,
				// The whole page, not a taller viewport: resizing just before the
				// shot can paint stale tiles.
				fullPage: true,
				clip: { x: box.left - SIDE, y: 0, width: FORM_WIDTH + ( 2 * SIDE ), height: Math.ceil( box.bottom ) },
			} );
			const { quality, bytes } = save( template.id, png );
			console.log( `${ template.id }: ${ Math.round( bytes / 1024 ) } KB (q${ quality })` );
		} catch ( error ) {
			failures.push( template.id );
			console.error( `${ template.id }: ${ error.message }` );
		}
	}

	await browser.close();
	fs.rmSync( tmp, { recursive: true, force: true } );
	console.log( `${ list.length - failures.length } of ${ list.length } captured.` );
	process.exit( failures.length ? 1 : 0 );
} )();
