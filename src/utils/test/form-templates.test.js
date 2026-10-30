/* eslint-env jest */
import { pickTemplateSettings } from '../form-templates';

const MESSAGE_KEYS = [
	'_srfm_submit_button_text',
	'_srfm_email_notification',
	'_srfm_form_confirmation',
	'_srfm_page_break_settings',
];

const settings = {
	_srfm_submit_button_text: 'Request my quote',
	_srfm_email_notification: [ { id: 1, subject: 'New quote request' } ],
	_srfm_form_confirmation: [ { id: 1, message: '<p>Thanks</p>' } ],
	_srfm_page_break_settings: { first_page_label: 'Your details' },
	_srfm_is_inline_button: false,
	_srfm_unknown_to_the_editor: 'x',
};

const meta = {
	_srfm_submit_button_text: 'Send',
	_srfm_email_notification: [ { id: 1, subject: 'Sales team copy' } ],
	_srfm_form_confirmation: [ { id: 1, message: '<p>Custom</p>' } ],
	_srfm_page_break_settings: {},
	_srfm_is_inline_button: true,
};

describe( 'pickTemplateSettings', () => {
	it( 'applies every known setting when messages are replaced too', () => {
		expect(
			pickTemplateSettings( settings, meta, MESSAGE_KEYS, true )
		).toEqual( {
			_srfm_submit_button_text: 'Request my quote',
			_srfm_email_notification: settings._srfm_email_notification,
			_srfm_form_confirmation: settings._srfm_form_confirmation,
			_srfm_page_break_settings: settings._srfm_page_break_settings,
			_srfm_is_inline_button: false,
		} );
	} );

	it( 'keeps the form emails, thank-you message and button text when unticked', () => {
		const picked = pickTemplateSettings(
			settings,
			meta,
			MESSAGE_KEYS,
			false
		);
		expect( picked ).toEqual( { _srfm_is_inline_button: false } );
		MESSAGE_KEYS.forEach( ( key ) =>
			expect( picked ).not.toHaveProperty( key )
		);
	} );

	it( 'honours message keys added by other plugins', () => {
		const picked = pickTemplateSettings(
			{ ...settings, _srfm_conditional_confirmations: [ 1 ] },
			{ ...meta, _srfm_conditional_confirmations: [] },
			[ ...MESSAGE_KEYS, '_srfm_conditional_confirmations' ],
			false
		);
		expect( picked ).not.toHaveProperty(
			'_srfm_conditional_confirmations'
		);
	} );

	it( 'never applies settings the editor does not know', () => {
		expect(
			pickTemplateSettings( settings, meta, MESSAGE_KEYS, true )
		).not.toHaveProperty( '_srfm_unknown_to_the_editor' );
		expect( pickTemplateSettings( undefined, meta, [], true ) ).toEqual(
			{}
		);
	} );
} );
