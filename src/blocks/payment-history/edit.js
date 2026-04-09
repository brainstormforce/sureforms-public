/**
 * Payment History Block — Edit.
 *
 * @since x.x.x
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { perPage, showSubscription } = attributes;

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
							'Show Subscriptions Section',
							'sureforms'
						) }
						checked={ showSubscription }
						onChange={ ( value ) =>
							setAttributes( { showSubscription: value } )
						}
						help={ __(
							'Show a dedicated subscriptions section above payment history.',
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
						{ __( 'Payment Dashboard', 'sureforms' ) }
					</span>
					<span style={ { fontSize: '13px' } }>
						{ __(
							'View your payments and manage subscriptions in a single dashboard.',
							'sureforms'
						) }
					</span>
				</div>
			</div>
		</>
	);
}
