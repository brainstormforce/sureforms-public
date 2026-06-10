/**
 * SourcePicker — the landing view of the Migration tab.
 *
 * Lists the form plugins you can import from in a native force-ui Table (the
 * same tabular idiom as the Integrations tab). Only sources that are actually
 * available on this site are shown; clicking "Import" on a ready source drills
 * into its forms.
 *
 * @since x.x.x
 */

import { __, sprintf, _n } from '@wordpress/i18n';
import { Button, Container, Table, Text } from '@bsf/force-ui';
import { ArrowRight } from 'lucide-react';

const SourcePicker = ( { sources, onSelect } ) => {
	// Show only sources available on this site (an active plugin we support).
	const available = sources.filter( ( s ) => s.installed );

	if ( available.length === 0 ) {
		return (
			<Text size={ 14 } color="secondary">
				{ __(
					'No supported form plugins are active on this site. Activate one (such as Contact Form 7) with at least one form to import it into SureForms.',
					'sureforms'
				) }
			</Text>
		);
	}

	return (
		<Table className="rounded-md">
			<Table.Head>
				<Table.HeadCell>{ __( 'Plugin', 'sureforms' ) }</Table.HeadCell>
				<Table.HeadCell>{ __( 'Forms', 'sureforms' ) }</Table.HeadCell>
				<Table.HeadCell className="text-right">
					<span className="sr-only">
						{ __( 'Action', 'sureforms' ) }
					</span>
				</Table.HeadCell>
			</Table.Head>
			<Table.Body>
				{ available.map( ( source ) => {
					const formCount = Number( source.form_count ?? 0 );
					const hasForms = formCount > 0;
					return (
						<Table.Row key={ source.key }>
							<Table.Cell>
								<Text size={ 14 } weight={ 500 }>
									{ source.title }
								</Text>
							</Table.Cell>
							<Table.Cell>
								<Text size={ 14 } color="secondary">
									{ hasForms
										? sprintf(
											/* translators: %d: number of forms. */
											_n(
												'%d form',
												'%d forms',
												formCount,
												'sureforms'
											),
											formCount
										  )
										: __( 'No forms', 'sureforms' ) }
								</Text>
							</Table.Cell>
							<Table.Cell>
								<Container justify="end">
									<Button
										variant="primary"
										size="sm"
										disabled={ ! hasForms }
										onClick={ () => onSelect( source ) }
										icon={ <ArrowRight /> }
										iconPosition="right"
									>
										{ __( 'Import', 'sureforms' ) }
									</Button>
								</Container>
							</Table.Cell>
						</Table.Row>
					);
				} ) }
			</Table.Body>
		</Table>
	);
};

export default SourcePicker;
