/**
 * Generate with AI screen — starter cards.
 *
 * The AI middleware can't verify local/CI domains, so the page would normally
 * open behind a "limit reached / domain verification" popup. Each test forces
 * the localized usage details to a healthy state before the app boots, and
 * stubs the generate-form request so no AI credits are used.
 */
const { test, expect } = require( '@playwright/test' );
const { loginAsAdmin } = require( '../utils/loginAsAdmin' );

const AI_SCREEN = '/wp-admin/admin.php?page=add-new-form';
const GENERATE_FORM_ROUTE = /sureforms(\/|%2F)v1(\/|%2F)generate-form/;

const STARTERS = [
	{ title: 'Contact form', keyword: 'Contact form' },
	{ title: 'Quote request', keyword: 'Quote request form' },
	{ title: 'Event registration', keyword: 'Event registration form' },
	{ title: 'Feedback form', keyword: 'Customer feedback form' },
];

/**
 * Open the AI screen with usable AI credits and a stubbed generate endpoint.
 *
 * @param {import('@playwright/test').Page} page
 * @param {Object}                          options
 * @param {boolean}                         options.hasAiForms Pretend the site already has an AI form.
 * @param {string}                          options.query      Extra query string, e.g. '&source=learn'.
 * @return {Promise<Array<Object>>} Request bodies sent to generate-form.
 */
async function openAiScreen( page, { hasAiForms = false, query = '' } = {} ) {
	await page.addInitScript( ( siteHasAiForms ) => {
		// srfm_admin is printed as `var srfm_admin = {...}`; intercept the
		// assignment so the screen boots without the limit/verification popup.
		let srfmAdmin;
		Object.defineProperty( window, 'srfm_admin', {
			configurable: true,
			get: () => srfmAdmin,
			set: ( value ) => {
				srfmAdmin = {
					...value,
					srfm_ai_usage_details: {
						type: 'registered',
						remaining: 5,
						limit: 10,
					},
					has_ai_generated_forms: siteHasAiForms,
				};
			},
		} );
	}, hasAiForms );

	const generateRequests = [];
	await page.route( GENERATE_FORM_ROUTE, async ( route ) => {
		generateRequests.push( route.request().postDataJSON() );
		await route.fulfill( {
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify( {
				success: false,
				message: 'Stubbed in tests.',
			} ),
		} );
	} );

	await loginAsAdmin( page );
	await page.goto( AI_SCREEN + query );
	await expect(
		page.getByRole( 'heading', { name: 'What form should we build?' } )
	).toBeVisible();

	return generateRequests;
}

const starterCard = ( page, title ) =>
	page.getByRole( 'button', { name: new RegExp( `^${ title }` ) } );

const promptBox = ( page ) => page.locator( '#textarea' );

const generateButton = ( page ) =>
	page.getByRole( 'button', { name: 'Generate', exact: true } );

test.describe( 'Generate with AI — starter cards', () => {
	test( 'shows the starter cards and keeps Generate inactive while empty', async ( {
		page,
	} ) => {
		const generateRequests = await openAiScreen( page );

		for ( const { title } of STARTERS ) {
			await expect( starterCard( page, title ) ).toHaveAttribute(
				'aria-pressed',
				'false'
			);
		}
		await expect( page.locator( '#srfm-ai-prompt-counter' ) ).toHaveText(
			'0/2000'
		);
		await expect( generateButton( page ) ).toHaveAttribute(
			'aria-disabled',
			'true'
		);

		// Generate uses aria-disabled (not the disabled attribute) so it stays
		// clickable and can explain itself; Playwright treats aria-disabled as
		// not actionable, hence force.
		await generateButton( page ).click( { force: true } );

		// The same text is also announced via wp.a11y.speak(), so target the hint by id.
		await expect( page.locator( '#srfm-ai-empty-prompt-hint' ) ).toHaveText(
			'Describe your form or pick an example to continue.'
		);
		await expect( promptBox( page ) ).toBeFocused();
		expect( generateRequests ).toHaveLength( 0 );
	} );

	for ( const { title, keyword } of STARTERS ) {
		test( `"${ title }" card sends its prompt to the AI`, async ( {
			page,
		} ) => {
			const generateRequests = await openAiScreen( page );

			await starterCard( page, title ).click();

			await expect( starterCard( page, title ) ).toHaveAttribute(
				'aria-pressed',
				'true'
			);
			const prompt = await promptBox( page ).inputValue();
			expect( prompt.startsWith( keyword ) ).toBe( true );
			await expect(
				page.locator( '#srfm-ai-prompt-counter' )
			).toHaveText( `${ prompt.length }/2000` );
			await expect( generateButton( page ) ).toHaveAttribute(
				'aria-disabled',
				'false'
			);

			await generateButton( page ).click();

			await expect.poll( () => generateRequests.length ).toBe( 1 );
			const body = generateRequests[ 0 ];
			const lastMessage =
				body.message_array[ body.message_array.length - 1 ];
			expect( lastMessage ).toEqual( {
				role: 'user',
				content: prompt,
			} );
			expect( body.form_type ).toBe( 'simple' );
			expect( body.is_conversional ).toBeFalsy();
		} );
	}

	test( 'clicking a selected card again clears its untouched prompt', async ( {
		page,
	} ) => {
		await openAiScreen( page );

		await starterCard( page, 'Feedback form' ).click();
		await expect( promptBox( page ) ).not.toHaveValue( '' );

		await starterCard( page, 'Feedback form' ).click();

		await expect( promptBox( page ) ).toHaveValue( '' );
		await expect(
			starterCard( page, 'Feedback form' )
		).toHaveAttribute( 'aria-pressed', 'false' );
	} );

	test( 'a card replacing typed text can be undone', async ( { page } ) => {
		await openAiScreen( page );

		await promptBox( page ).fill( 'Booking form for my yoga studio' );
		await starterCard( page, 'Contact form' ).click();

		await expect( promptBox( page ) ).toHaveValue( /^Contact form/ );
		await page.getByRole( 'button', { name: 'Undo' } ).click();

		await expect( promptBox( page ) ).toHaveValue(
			'Booking form for my yoga studio'
		);
		await expect(
			starterCard( page, 'Contact form' )
		).toHaveAttribute( 'aria-pressed', 'false' );
	} );

	test( 'switching cards keeps the typed text for Undo', async ( { page } ) => {
		await openAiScreen( page );

		await promptBox( page ).fill( 'Booking form for my salon' );
		await starterCard( page, 'Contact form' ).click();
		await starterCard( page, 'Quote request' ).click();

		await expect( promptBox( page ) ).toHaveValue( /^Quote request form/ );
		await page.getByRole( 'button', { name: 'Undo' } ).click();

		await expect( promptBox( page ) ).toHaveValue( 'Booking form for my salon' );
		await expect(
			starterCard( page, 'Quote request' )
		).toHaveAttribute( 'aria-pressed', 'false' );
	} );

	test( 'switching cards then deselecting gives back the typed text', async ( {
		page,
	} ) => {
		await openAiScreen( page );

		await promptBox( page ).fill( 'Booking form for my salon' );
		await starterCard( page, 'Contact form' ).click();
		await starterCard( page, 'Quote request' ).click();
		await starterCard( page, 'Quote request' ).click();

		await expect( promptBox( page ) ).toHaveValue( 'Booking form for my salon' );
	} );

	test( 'examples are hidden for a non-simple form type', async ( { page } ) => {
		await openAiScreen( page, { query: '&form_type=quiz' } );

		await expect( starterCard( page, 'Contact form' ) ).toHaveCount( 0 );
		await expect(
			page.getByRole( 'button', { name: 'Show examples' } )
		).toHaveCount( 0 );
	} );

	test( 'examples collapse once the site has an AI form and can be expanded', async ( {
		page,
	} ) => {
		await openAiScreen( page, { hasAiForms: true } );

		await expect( starterCard( page, 'Contact form' ) ).toHaveCount( 0 );
		await page.getByRole( 'button', { name: 'Show examples' } ).click();

		for ( const { title } of STARTERS ) {
			await expect( starterCard( page, title ) ).toBeVisible();
		}
		await expect( starterCard( page, 'Contact form' ) ).toBeFocused();
	} );

	test( 'the Learn flow always shows the examples', async ( { page } ) => {
		await openAiScreen( page, { hasAiForms: true, query: '&source=learn' } );

		await expect( starterCard( page, 'Contact form' ) ).toBeVisible();
		await expect(
			page.getByRole( 'button', { name: 'Show examples' } )
		).toHaveCount( 0 );
	} );

	test( 'deselecting an untouched card gives back the text it replaced', async ( {
		page,
	} ) => {
		await openAiScreen( page );

		await promptBox( page ).fill( 'Booking form for my yoga studio' );
		await starterCard( page, 'Contact form' ).click();
		await starterCard( page, 'Contact form' ).click();

		await expect( promptBox( page ) ).toHaveValue(
			'Booking form for my yoga studio'
		);
	} );

	test( 'deselecting an edited card keeps the edits', async ( { page } ) => {
		await openAiScreen( page );

		await starterCard( page, 'Event registration' ).click();
		const edited = ( await promptBox( page ).inputValue() ) + ' Add a T-shirt size dropdown.';
		await promptBox( page ).fill( edited );
		await starterCard( page, 'Event registration' ).click();

		await expect( promptBox( page ) ).toHaveValue( edited );
		await expect(
			starterCard( page, 'Event registration' )
		).toHaveAttribute( 'aria-pressed', 'false' );
	} );

	test( 'the empty-prompt hint opens the collapsed examples it points to', async ( {
		page,
	} ) => {
		await openAiScreen( page, { hasAiForms: true } );

		await expect( starterCard( page, 'Contact form' ) ).toHaveCount( 0 );
		await expect(
			page.getByText( 'Describe the form you want to create.' )
		).toBeVisible();

		await generateButton( page ).click( { force: true } );

		await expect( page.locator( '#srfm-ai-empty-prompt-hint' ) ).toBeVisible();
		await expect( starterCard( page, 'Contact form' ) ).toBeVisible();
	} );

	/**
	 * Let generation succeed (stubbed) and capture the create-new-form request.
	 *
	 * Routes added after openAiScreen() take precedence over its failing stub.
	 *
	 * @param {import('@playwright/test').Page} page
	 * @return {Promise<Array<Object>>} Request bodies sent to create-new-form.
	 */
	async function captureCreateForm( page ) {
		await page.route( GENERATE_FORM_ROUTE, ( route ) =>
			route.fulfill( {
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify( {
					success: true,
					data: { form: { formTitle: 'Stubbed form', formFields: [] } },
				} ),
			} )
		);
		await page.route( /sureforms(\/|%2F)v1(\/|%2F)map-fields/, ( route ) =>
			route.fulfill( {
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify( '<!-- wp:srfm/input {"block_id":"a1"} /-->' ),
			} )
		);
		const createRequests = [];
		await page.route(
			/sureforms(\/|%2F)v1(\/|%2F)create-new-form/,
			async ( route ) => {
				createRequests.push( JSON.parse( route.request().postData() ) );
				// No id in the response, so the screen stays put instead of redirecting.
				await route.fulfill( {
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify( { message: 'Stubbed in tests.' } ),
				} );
			}
		);
		return createRequests;
	}

	test( 'create-new-form receives the untouched starter card', async ( {
		page,
	} ) => {
		await openAiScreen( page );
		const createRequests = await captureCreateForm( page );

		await starterCard( page, 'Contact form' ).click();
		await generateButton( page ).click();

		await expect.poll( () => createRequests.length ).toBe( 1 );
		expect( createRequests[ 0 ] ).toMatchObject( {
			ai_starter: 'contact',
			ai_starter_edited: false,
		} );
	} );

	test( 'create-new-form marks an edited starter prompt', async ( { page } ) => {
		await openAiScreen( page );
		const createRequests = await captureCreateForm( page );

		await starterCard( page, 'Feedback form' ).click();
		await promptBox( page ).fill(
			( await promptBox( page ).inputValue() ) + ' Add a star rating.'
		);
		await generateButton( page ).click();

		await expect.poll( () => createRequests.length ).toBe( 1 );
		expect( createRequests[ 0 ] ).toMatchObject( {
			ai_starter: 'feedback',
			ai_starter_edited: true,
		} );
	} );

	test( 'create-new-form gets no starter for a free-text prompt', async ( {
		page,
	} ) => {
		await openAiScreen( page );
		const createRequests = await captureCreateForm( page );

		await promptBox( page ).fill( 'Booking form for my yoga studio' );
		await generateButton( page ).click();

		await expect.poll( () => createRequests.length ).toBe( 1 );
		expect( createRequests[ 0 ] ).toMatchObject( {
			ai_starter: '',
			ai_starter_edited: false,
		} );
	} );
} );
