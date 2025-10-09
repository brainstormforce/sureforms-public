import { __ } from '@wordpress/i18n';
import { Edit3, Trash2 } from 'lucide-react';
import { Button, Container, Table, Badge } from '@bsf/force-ui';
import { getStatusBadgeVariant } from '../utils/entryHelpers';

/**
 * EntriesTableRow Component
 * Displays a single entry row in the table
 *
 * @param {Object}   props                   - Component props
 * @param {Object}   props.entry             - Entry object
 * @param {boolean}  props.isSelected        - Whether the row is selected
 * @param {Function} props.onChangeSelection - Handler for selection change
 * @param {Function} props.onEdit            - Handler for edit action
 * @param {Function} props.onDelete          - Handler for delete action
 */
const EntriesTableRow = ( {
	entry,
	isSelected,
	onChangeSelection,
	onEdit,
	onDelete,
} ) => {
	return (
		<Table.Row
			value={ entry }
			selected={ isSelected }
			onChangeSelection={ onChangeSelection }
			className="hover:bg-background-primary"
		>
			<Table.Cell>{ entry.entryId }</Table.Cell>
			<Table.Cell>{ entry.formName }</Table.Cell>
			<Table.Cell>
				<Badge
					className="w-fit"
					variant={ getStatusBadgeVariant( entry.status ) }
					size="md"
					label={ entry.statusLabel }
				/>
			</Table.Cell>
			<Table.Cell>{ entry.firstField }</Table.Cell>
			<Table.Cell>{ entry.dateTime }</Table.Cell>
			<Table.Cell>
				<Container align="center" className="gap-2" justify="end">
					<Button
						variant="ghost"
						aria-label={ __( 'Edit', 'sureforms' ) }
						size="xs"
						icon={ <Edit3 /> }
						onClick={ () => onEdit( entry ) }
					/>
					<Button
						variant="ghost"
						aria-label={ __( 'Delete', 'sureforms' ) }
						size="xs"
						icon={ <Trash2 /> }
						onClick={ () => onDelete( entry ) }
					/>
				</Container>
			</Table.Cell>
		</Table.Row>
	);
};

export default EntriesTableRow;
