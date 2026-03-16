/**
 * E2E tests — Form restrictions (P1).
 *
 * Verifies that the form restrictions feature prevents submissions under
 * two distinct conditions:
 *
 *   Entry limit
 *     The `_srfm_form_restriction` post meta key controls access.  When
 *     `status: true` and `maxEntries` is set, SureForms counts existing
 *     entries for that form at render time (PHP).  Once the limit is reached
 *     the frontend renders `<div class="srfm-form-restriction-message">` with
 *     the configured message inside `<p class="srfm-form-restriction-text">`
 *     instead of the form fields.
 *
 *   Scheduling (end date in the past)
 *     When `schedulingStatus: true` and the end date/time has already passed,
 *     SureForms treats the form as closed at render time — no submission is
 *     required to trigger the restriction message.
 *
 * Both checks happen during PHP rendering, so the restriction is visible
 * immediately on page load once the condition is met.
 *
 * Prerequisites:
 *   - A running wp-env instance (`npm run play:up`)
 *   - Admin credentials available (defaults: admin / password)
 *   - SureForms plugin active
 *   - `_srfm_form_restriction` meta registered with `show_in_rest: true`
 *     (present by default in SureForms)
 */

const { test, expect } = require( '@playwright/test' );
const { loginAsAdmin } = require( '../utils/loginAsAdmin' );
const {
	createBlankForm,
	publishFormAndGetURL,
	addFieldBlock,
} = require( '../utils/formHelpers' );

test.describe( 'Form restrictions', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// ── 11.1 Entry limit — restriction message appears after limit is reached ──
	test( 'entry limit — form shows restriction message after limit reached', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		// Set the entry limit to 1 via the Gutenberg editor data store BEFORE
		// publishing so the meta is saved with the form.
		await page.evaluate( () => {
			window.wp.data.dispatch( 'core/editor' ).editPost( {
				meta: {
					_srfm_form_restriction: JSON.stringify( {
						status: true,
						maxEntries: 1,
						message: 'Submission limit reached.',
						date: '',
						hours: '12',
						minutes: '00',
						meridiem: 'AM',
						schedulingStatus: false,
						startDate: '',
						startHours: '12',
						startMinutes: '00',
						startMeridiem: 'AM',
						schedulingNotStartedMessage: '',
						schedulingEndedMessage: '',
					} ),
				},
			} );
		} );

		const formURL = await publishFormAndGetURL( page );

		// First visit — fill and submit the form to consume the single allowed entry.
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );
		await page.locator( 'input.srfm-input-input' ).first().fill( 'first submission' );
		await page.locator( '#srfm-submit-btn' ).click();
		await expect( page.locator( '.srfm-success-box' ) ).toBeVisible( { timeout: 15000 } );

		// Second visit — the limit has been reached; the form should now be closed.
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await expect(
			page.locator( '.srfm-form-restriction-message' )
		).toBeVisible( { timeout: 10000 } );

		await expect(
			page.locator( '.srfm-form-restriction-text' )
		).toContainText( 'Submission limit reached.' );
	} );

	// ── 11.2 Scheduling — restriction message when schedule has ended ──────────
	test( 'scheduling — form shows restriction message when schedule has ended', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		// Set a scheduling window that ended in the past (2020-01-01 → 2020-01-02).
		// The restriction check is performed at render time, so no submission is
		// needed — the form loads already closed.
		await page.evaluate( () => {
			window.wp.data.dispatch( 'core/editor' ).editPost( {
				meta: {
					_srfm_form_restriction: JSON.stringify( {
						status: false,
						maxEntries: 0,
						message: '',
						date: '2020-01-02',
						hours: '12',
						minutes: '00',
						meridiem: 'AM',
						schedulingStatus: true,
						startDate: '2020-01-01',
						startHours: '12',
						startMinutes: '00',
						startMeridiem: 'AM',
						schedulingNotStartedMessage: 'Not started yet.',
						schedulingEndedMessage: 'This form is closed. The submission period has ended.',
					} ),
				},
			} );
		} );

		const formURL = await publishFormAndGetURL( page );

		// Navigate to the form — it should immediately show the restriction message
		// because the schedule end date is in the past.
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await expect(
			page.locator( '.srfm-form-restriction-message' )
		).toBeVisible( { timeout: 10000 } );

		await expect(
			page.locator( '.srfm-form-restriction-text' )
		).toContainText( 'The submission period has ended' );
	} );

	// ── 11.3 Scheduling — restriction message when schedule has not started ───
	test( 'scheduling — form shows restriction message when schedule has not started yet', async ( { page } ) => {
		await createBlankForm( page );
		await addFieldBlock( page, 'input' );

		// Set a scheduling window with a start date far in the future.
		// The restriction check is performed at render time — the form loads
		// already closed with the "not started" message.
		await page.evaluate( () => {
			window.wp.data.dispatch( 'core/editor' ).editPost( {
				meta: {
					_srfm_form_restriction: JSON.stringify( {
						status: false,
						maxEntries: 0,
						message: '',
						date: '2099-12-31',
						hours: '11',
						minutes: '59',
						meridiem: 'PM',
						schedulingStatus: true,
						startDate: '2099-12-30',
						startHours: '12',
						startMinutes: '00',
						startMeridiem: 'AM',
						schedulingNotStartedMessage: 'This form is not yet available.',
						schedulingEndedMessage: 'This form is closed.',
					} ),
				},
			} );
		} );

		const formURL = await publishFormAndGetURL( page );

		// Navigate to the form — it should immediately show the "not started"
		// restriction message because the start date is in the future.
		await page.goto( formURL );
		await page.waitForLoadState( 'load' );

		await expect(
			page.locator( '.srfm-form-restriction-message' )
		).toBeVisible( { timeout: 10000 } );

		await expect(
			page.locator( '.srfm-form-restriction-text' )
		).toContainText( 'not yet available' );
	} );
} );
