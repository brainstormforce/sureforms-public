import { __ } from '@wordpress/i18n';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch } from '@wordpress/data';
import { Info } from 'lucide-react';
import { Input, Switch, Label, Container } from '@bsf/force-ui';
import TabContentWrapper from '@Components/tab-content-wrapper';

const Compliance = ( { complianceData } ) => {
	const { editPost } = useDispatch( editorStore );

	const handleToggle = ( id, status ) => {
		const updatedData = complianceData.map( ( item ) => ( {
			...item,
			[ id ]: status,
		} ) );

		editPost( {
			meta: {
				_srfm_compliance: updatedData,
			},
		} );
	};

	const ComplianceSwitch = ( { id, label, value, onChange, key } ) => {
		return (
			<Switch
				key={ key }
				label={ label }
				value={ value }
				onChange={ ( val ) => onChange( id, val ) }
			/>
		);
	};

	const switches = [
		{
			id: 'gdpr',
			label: {
				heading: __( 'Enable GDPR Compliance', 'sureforms' ),
				description: __(
					'When enabled this form will not store User IP, Browser Name and the Device Name in the Entries.',
					'sureforms'
				),
			},
		},
		{
			id: 'do_not_store_entries',
			label: {
				heading: __(
					'Never store entry data after form submission',
					'sureforms'
				),
				description: __(
					'When enabled this form will never store Entries.',
					'sureforms'
				),
			},
			condition: complianceData[ 0 ]?.gdpr,
		},
		{
			id: 'auto_delete_entries',
			label: {
				heading: __( 'Automatically delete entries', 'sureforms' ),
				description: __(
					'When enabled this form will automatically delete entries after a certain period of time.',
					'sureforms'
				),
			},
			condition:
				complianceData[ 0 ]?.gdpr &&
				! complianceData[ 0 ]?.do_not_store_entries,
		},
	];

	return (
		<TabContentWrapper title={ __( 'Compliance Settings', 'sureforms' ) }>
			<Container direction="column" className="gap-6">
				{ switches.map(
					( { id, label, condition = true } ) =>
						condition &&
						ComplianceSwitch( {
							id,
							label,
							value: complianceData[ 0 ]?.[ id ],
							onChange: handleToggle,
							key: id,
						} )
				) }
				{ complianceData[ 0 ]?.auto_delete_entries &&
					! complianceData[ 0 ]?.do_not_store_entries &&
					complianceData[ 0 ]?.gdpr && (
					<Container direction="column" className="gap-1.5">
						<Input
							aria-label={ __(
								'Entries older than the selected days will be deleted.',
								'sureforms'
							) }
							size="md"
							type="number"
							value={ complianceData[ 0 ]?.auto_delete_days }
							label={ __(
								'Entries Time Period',
								'sureforms'
							) }
							onChange={ ( value ) => {
								value = parseInt( value );

								if ( value < 0 ) {
									value = 1;
								}

								value = value.toString();

								handleToggle( 'auto_delete_days', value );
							} }
						/>
						<Container gap="0" align="center">
							<Info className="w-4 h-4 mr-1 cursor-pointer text-icon-secondary" />
							<Label tag="p" size="sm" variant="help">
								{ __(
									'Entries older than the days set will be deleted automatically.',
									'sureforms'
								) }
							</Label>
						</Container>
					</Container>
				) }
			</Container>
		</TabContentWrapper>
	);
};

export default Compliance;
