/**
 * ImportResult — step 4 of the migration wizard.
 *
 * Renders the summary of a completed import: which forms were created (with
 * "Edit in SureForms" links), which ones failed, and any unsupported-field
 * warnings. Offers a "Import more" action to reset back to the source picker.
 *
 * @since x.x.x
 */

import { __, sprintf, _n } from '@wordpress/i18n';
import {
	Alert,
	Button,
	Container,
	Text,
	Title,
} from '@bsf/force-ui';
import { AlertTriangle, CheckCircle2, ExternalLink, RotateCcw } from 'lucide-react';

const ImportResult = ( { result, onRestart } ) => {
	const imported = Array.isArray( result?.imported ) ? result.imported : [];
	const failed = Array.isArray( result?.failed ) ? result.failed : [];
	const unsupported = Array.isArray( result?.unsupported_fields )
		? result.unsupported_fields
		: [];

	return (
		<Container direction="column" gap="lg">
			<Container direction="column" gap="xs">
				<Title
					size="md"
					tag="h3"
					title={
						imported.length > 0
							? __( 'Migration complete', 'sureforms' )
							: __( 'Nothing was imported', 'sureforms' )
					}
				/>
				<Text size={ 14 } color="secondary">
					{ imported.length > 0
						? sprintf(
							/* translators: %d: number of forms imported. */
							_n(
								'%d form was imported into SureForms.',
								'%d forms were imported into SureForms.',
								imported.length,
								'sureforms'
							),
							imported.length
						  )
						: __(
							'None of the selected forms could be migrated. See the warnings below for details.',
							'sureforms'
						  ) }
				</Text>
			</Container>

			{ imported.length > 0 && (
				<Container
					direction="column"
					className="rounded-lg border border-border-subtle bg-background-primary overflow-hidden"
				>
					<Container
						align="center"
						gap="xs"
						className="px-4 py-3 border-0 border-b border-solid border-border-subtle bg-background-secondary"
					>
						<CheckCircle2 className="size-4 text-text-success" />
						<Text size={ 13 } weight={ 600 }>
							{ __( 'Imported forms', 'sureforms' ) }
						</Text>
					</Container>
					{ imported.map( ( item ) => (
						<Container
							key={ item.srfm_id }
							align="center"
							justify="between"
							gap="sm"
							className="px-4 py-3 border-0 border-t border-solid border-border-subtle first:border-t-0"
						>
							<Text size={ 14 }>{ item.name }</Text>
							<Button
								tag="a"
								href={ item.edit_url }
								target="_blank"
								rel="noopener noreferrer"
								variant="outline"
								size="xs"
								icon={ <ExternalLink /> }
								iconPosition="right"
							>
								{ __( 'Edit in SureForms', 'sureforms' ) }
							</Button>
						</Container>
					) ) }
				</Container>
			) }

			{ unsupported.length > 0 && (
				<Alert
					variant="warning"
					title={ __(
						'Some fields were skipped during import',
						'sureforms'
					) }
					content={
						<Container direction="column" gap="2xs">
							<Text size={ 13 }>
								{ __(
									'Add these manually in the form editor - they have no SureForms equivalent yet:',
									'sureforms'
								) }
							</Text>
							<ul className="list-disc pl-5 mt-1">
								{ unsupported.map( ( label, idx ) => (
									<li key={ idx }>
										<Text size={ 13 }>{ label }</Text>
									</li>
								) ) }
							</ul>
						</Container>
					}
					icon={ <AlertTriangle /> }
				/>
			) }

			{ failed.length > 0 && (
				<Alert
					variant="danger"
					title={ __(
						'Some forms failed to import',
						'sureforms'
					) }
					content={
						<Text size={ 13 }>{ failed.join( ', ' ) }</Text>
					}
					icon={ <AlertTriangle /> }
				/>
			) }

			<Container align="center" justify="end">
				<Button
					variant="primary"
					size="sm"
					onClick={ onRestart }
					icon={ <RotateCcw /> }
					iconPosition="left"
				>
					{ __( 'Import more forms', 'sureforms' ) }
				</Button>
			</Container>
		</Container>
	);
};

export default ImportResult;
