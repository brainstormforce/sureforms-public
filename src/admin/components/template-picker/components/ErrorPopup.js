import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useId } from '@wordpress/element';
import ICONS from './icons.js';
import { Button, Container, Label } from '@bsf/force-ui';

const ErrorPopup = ( { errorMessage = '', onRetry } ) => {
	const dialogRef = useRef( null );
	const titleId = useId();
	const descId = useId();

	const handleRetry =
		typeof onRetry === 'function'
			? onRetry
			: () => window.location.reload();

	const bodyText =
		errorMessage ||
		__( 'Something went wrong. Please try again.', 'sureforms' );

	// Move focus into the dialog on mount so screen readers announce it and
	// keyboard users can act on it without first tabbing through the page.
	useEffect( () => {
		const node = dialogRef.current;
		if ( ! node ) {
			return;
		}
		// Prefer focusing the primary action button if present; fall back to
		// the dialog wrapper itself (it's tabIndex=-1 so it's focusable).
		const primaryButton = node.querySelector( 'button' );
		if ( primaryButton ) {
			primaryButton.focus();
		} else {
			node.focus();
		}
	}, [] );

	// Escape closes the dialog by invoking the retry/close handler — there's
	// only one action, so Esc and Try Again converge to the same behavior.
	useEffect( () => {
		const onKeyDown = ( event ) => {
			if ( event.key === 'Escape' ) {
				event.stopPropagation();
				handleRetry();
			}
		};
		document.addEventListener( 'keydown', onKeyDown );
		return () => document.removeEventListener( 'keydown', onKeyDown );
	}, [ handleRetry ] );

	return (
		<div
			ref={ dialogRef }
			role="alertdialog"
			aria-modal="true"
			aria-labelledby={ titleId }
			aria-describedby={ descId }
			tabIndex={ -1 }
		>
			<Container
				direction="column"
				justify="center"
				align="center"
				className="fixed inset-0 bg-overlay-background z-[99999999]"
			>
				<Container
					direction="column"
					className="bg-background-primary gap-5 px-5 py-4 rounded-lg max-w-sm shadow-lg"
				>
					<Container.Item className="pt-2">
						<Label
							id={ titleId }
							variant="neutral"
							className="text-lg font-bold flex gap-3"
						>
							<span className="pt-1">{ ICONS.warning }</span>
							{ __( 'Unable to create form', 'sureforms' ) }
						</Label>
					</Container.Item>
					<Container.Item className="flex flex-col gap-4">
						<Label
							id={ descId }
							size="sm"
							className="text-text-secondary font-normal"
						>
							{ bodyText }
						</Label>
					</Container.Item>
					<Container.Item className="flex flex-col w-full gap-4 pb-2">
						<Button
							size="md"
							variant="primary"
							onClick={ handleRetry }
						>
							{ __( 'Try Again', 'sureforms' ) }
						</Button>
					</Container.Item>
				</Container>
			</Container>
		</div>
	);
};

export default ErrorPopup;
