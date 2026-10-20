import {
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import {
	getFormStyleBlocks,
	getProbePreset,
	restyleDocument,
} from './template-colors.js';

// When a preview can't be restyled in place, wait this long after the last
// change before rendering it again.
export const RESTYLE_DELAY = 250;

// A change made while the other render (see getProbePreset()) is still on its
// way waits this long for it before the preview is rendered again instead.
export const PROBE_GRACE = 1500;

// The other render is given up on after this long.
export const PROBE_TIMEOUT = 8000;

// A preview that hasn't loaded after this long is treated as blocked.
export const PREVIEW_TIMEOUT = 10000;

/**
 * Preview URL for a template with a chosen style.
 *
 * @since x.x.x
 * @param {Object} template Template.
 * @param {Object} style    Chosen style.
 * @return {string} URL.
 */
export const styledPreviewUrl = ( template, style ) =>
	Object.keys( style ).length
		? `${ template.preview_url }&srfm_style=${ encodeURIComponent(
			JSON.stringify( style )
		  ) }`
		: template.preview_url;

/**
 * A frame's document, or null when it can't be read.
 *
 * @param {HTMLIFrameElement|null} frame Frame.
 * @return {Document|null} Document.
 */
const readDocument = ( frame ) => {
	try {
		return frame?.contentDocument || null;
	} catch ( error ) {
		return null;
	}
};

/**
 * Whether a loaded preview frame shows something other than the preview page,
 * e.g. a PHP error, a login screen, or the browser's "refused to connect"
 * page. A frame from another origin can't be read, so it is trusted.
 *
 * @since x.x.x
 * @param {HTMLIFrameElement} iframe Frame that fired `load`.
 * @return {boolean} The preview is broken.
 */
export const isPreviewBroken = ( iframe ) => {
	let sameOrigin = false;
	try {
		sameOrigin =
			new URL( iframe.src, window.location.href ).origin ===
			window.location.origin;
	} catch ( error ) {
		return false;
	}
	const doc = readDocument( iframe );
	if ( ! doc ) {
		// Same-origin pages are readable, so no document means the browser
		// replaced it with its own error page. Not in a cross-origin isolated
		// page though (the block editor sends Document-Isolation-Policy):
		// there even a working same-origin frame can't be read.
		return sameOrigin && ! window.crossOriginIsolated;
	}
	if ( 'about:blank' === doc.location?.href ) {
		return false;
	}
	return ! doc.querySelector( '.srfm-template-preview' );
};

/**
 * Replace one of the two frame URLs.
 *
 * @param {string[]} frames Frame URLs.
 * @param {number}   index  Slot.
 * @param {string}   src    URL, '' for no frame.
 * @return {string[]} Frame URLs, the same array when nothing changes.
 */
const withFrame = ( frames, index, src ) => {
	if ( frames[ index ] === src ) {
		return frames;
	}
	const next = [ ...frames ];
	next[ index ] = src;
	return next;
};

/**
 * The template preview's frames.
 *
 * The page is rendered once, in the template's own look, and restyled in
 * place when a style is chosen (restyleDocument()). That needs the form's CSS
 * from one more render in another preset, which is only fetched once colors
 * can change and only when it can be used: a live preview whose page can be
 * read. When the page can't be restyled in place it is rendered again in a
 * second frame behind the shown one, which swaps in once it has loaded, so
 * the preview never goes blank.
 *
 * @since x.x.x
 * @param {Object}  args              Arguments.
 * @param {Object}  args.template     Template.
 * @param {Object}  args.style        Chosen style.
 * @param {Object}  args.look         Look of the chosen style, from resolveLook().
 * @param {Object}  args.renderedLook Look of the first render (no style).
 * @param {Array}   args.presets      Presets.
 * @param {boolean} args.active       A live preview is shown, not a picture.
 * @param {boolean} args.canRestyle   Colors can change: the Customize panel has been open.
 * @return {{frames: string[], shown: number, status: string, isUpdating: boolean, setFrameNode: Function, onFrameLoad: Function}}
 *         Frame URLs by slot ('' for none), the shown slot, 'loading', 'ready'
 *         or 'failed', whether a change is still on its way, a ref callback
 *         and a `load` handler per slot.
 */
const usePreviewRestyle = ( {
	template,
	style,
	look,
	renderedLook,
	presets,
	active,
	canRestyle,
} ) => {
	const [ frames, setFrames ] = useState( [ template.preview_url, '' ] );
	const [ shown, setShown ] = useState( 0 );
	const [ status, setStatus ] = useState( 'loading' );
	// The form's CSS rendered in another preset: null until it's needed and
	// while it loads, false when it can't be had.
	const [ probe, setProbe ] = useState( null );
	// Whether the first render's page can be read; null until it loads.
	const [ readable, setReadable ] = useState( null );
	// The slot rendering a change, and whether a change is waiting to start one.
	const [ loadingSlot, setLoadingSlot ] = useState( null );
	const [ waiting, setWaiting ] = useState( false );
	const nodes = useRef( [ null, null ] );

	const isOriginal = frames[ shown ] === template.preview_url;
	const wantsProbe =
		active &&
		canRestyle &&
		isOriginal &&
		false !== readable &&
		! window.crossOriginIsolated;
	const needsProbe = wantsProbe && null === probe;

	useEffect( () => {
		if ( ! needsProbe ) {
			return;
		}
		const preset = getProbePreset( renderedLook, presets );
		if ( ! preset ) {
			setProbe( false );
			return;
		}
		const controller = new window.AbortController();
		let cancelled = false;
		const timer = setTimeout( () => controller.abort(), PROBE_TIMEOUT );
		window
			.fetch( styledPreviewUrl( template, { preset } ), {
				credentials: 'same-origin',
				signal: controller.signal,
			} )
			.then( ( response ) => ( response.ok ? response.text() : '' ) )
			.then( ( html ) => {
				const doc = new window.DOMParser().parseFromString(
					html,
					'text/html'
				);
				const blocks = getFormStyleBlocks( doc ).map(
					( node ) => node.textContent
				);
				if ( ! cancelled ) {
					setProbe( blocks.length ? blocks : false );
				}
			} )
			.catch( () => {
				// Also reached when the timer aborts the request.
				if ( ! cancelled ) {
					setProbe( false );
				}
			} )
			.finally( () => clearTimeout( timer ) );
		return () => {
			cancelled = true;
			clearTimeout( timer );
			controller.abort();
		};
	}, [ needsProbe, template, renderedLook, presets ] );

	const url = styledPreviewUrl( template, style );
	const hidden = 1 - shown;

	useEffect( () => {
		if ( ! active || 'ready' !== status ) {
			return;
		}
		if (
			isOriginal &&
			probe &&
			restyleDocument(
				readDocument( nodes.current[ shown ] ),
				renderedLook,
				look,
				probe
			)
		) {
			// Shown as asked, so a render still on its way isn't needed.
			setFrames( ( current ) => withFrame( current, hidden, '' ) );
			setLoadingSlot( null );
			setWaiting( false );
			return;
		}
		if ( frames[ shown ] === url || frames[ hidden ] === url ) {
			setWaiting( false );
			return;
		}
		setWaiting( true );
		const timer = setTimeout(
			() => {
				setFrames( ( current ) => withFrame( current, hidden, url ) );
				setLoadingSlot( hidden );
				setWaiting( false );
			},
			// The other render may still make a page render unnecessary.
			needsProbe ? PROBE_GRACE : RESTYLE_DELAY
		);
		return () => clearTimeout( timer );
	}, [
		active,
		status,
		isOriginal,
		probe,
		needsProbe,
		renderedLook,
		look,
		url,
		frames,
		shown,
		hidden,
	] );

	useEffect( () => {
		if ( ! active ) {
			return;
		}
		const timer = setTimeout(
			() =>
				setStatus( ( current ) =>
					'loading' === current ? 'failed' : current
				),
			PREVIEW_TIMEOUT
		);
		return () => clearTimeout( timer );
	}, [ active ] );

	// One stable ref callback per slot, so React doesn't detach and reattach
	// the frames on every render.
	const frameRefs = useMemo(
		() =>
			[ 0, 1 ].map( ( index ) => ( node ) => {
				nodes.current[ index ] = node;
			} ),
		[]
	);
	const setFrameNode = useCallback(
		( index ) => frameRefs[ index ],
		[ frameRefs ]
	);

	const onFrameLoad = useCallback(
		( index, event ) => {
			const frame = event.currentTarget;
			if ( isPreviewBroken( frame ) ) {
				setStatus( 'failed' );
				return;
			}
			if ( frames[ index ] === template.preview_url ) {
				setReadable( !! readDocument( frame ) );
			}
			setLoadingSlot( ( current ) =>
				current === index ? null : current
			);
			setShown( index );
			setStatus( 'ready' );
		},
		[ frames, template.preview_url ]
	);

	return {
		frames,
		shown,
		status,
		isUpdating: waiting || null !== loadingSlot,
		setFrameNode,
		onFrameLoad,
	};
};

export default usePreviewRestyle;
