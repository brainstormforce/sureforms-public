/**
 * Payment History Block — Edit.
 *
 * @since 2.6.0
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	ToggleControl,
	SelectControl,
} from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { perPage, showSubscription, showRenewal, gateway } = attributes;

	const blockProps = useBlockProps( {
		className: 'srfm-payment-history-block-preview',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'sureforms' ) }
					initialOpen={ true }
				>
					<RangeControl
						label={ __( 'Payments Per Page', 'sureforms' ) }
						value={ perPage }
						onChange={ ( value ) =>
							setAttributes( { perPage: value } )
						}
						min={ 1 }
						max={ 50 }
					/>
					<ToggleControl
						label={ __(
							'Show Subscriptions',
							'sureforms'
						) }
						checked={ showSubscription }
						onChange={ ( value ) =>
							setAttributes( { showSubscription: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Renewals', 'sureforms' ) }
						checked={ showRenewal }
						onChange={ ( value ) =>
							setAttributes( { showRenewal: value } )
						}
					/>
					<SelectControl
						label={ __( 'Payment Gateway', 'sureforms' ) }
						value={ gateway }
						options={ [
							{
								label: __( 'All Gateways', 'sureforms' ),
								value: '',
							},
							{
								label: __( 'Stripe', 'sureforms' ),
								value: 'stripe',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { gateway: value } )
						}
						help={ __(
							'Filter to show payments from a specific gateway only.',
							'sureforms'
						) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div
					style={ {
						display: 'flex',
						flexDirection: 'column',
						alignItems: 'center',
						justifyContent: 'center',
						padding: '40px 20px',
						background: '#f9fafb',
						border: '1px solid #e5e7eb',
						borderRadius: '8px',
						textAlign: 'center',
						gap: '8px',
						color: '#6b7280',
					} }
				>
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="48"
						height="48"
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						strokeWidth="1.5"
						strokeLinecap="round"
						strokeLinejoin="round"
					>
						<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
						<polyline points="14 2 14 8 20 8" />
						<line x1="16" y1="13" x2="8" y2="13" />
						<line x1="16" y1="17" x2="8" y2="17" />
						<polyline points="10 9 9 9 8 9" />
					</svg>
					<span
						style={ {
							fontSize: '16px',
							fontWeight: '600',
							color: '#111827',
						} }
					>
						{ __( 'Payment History', 'sureforms' ) }
					</span>
					<span style={ { fontSize: '13px' } }>
						{ __(
							'Displays a payment history table for logged-in users. Configure options in the block settings panel.',
							'sureforms'
						) }
					</span>
				</div>
			</div>
		</>
	);
}
