import { __ } from '@wordpress/i18n';
import { Edit3, Trash2, RotateCcw } from 'lucide-react';
import { Button, Container, Table, Badge } from '@bsf/force-ui';
import Tooltip from '@Admin/components/Tooltip';
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
 * @param {Function} props.onRestore         - Handler for restore action
 */
const EntriesTableRow = ( {
	entry,
	isSelected,
	onChangeSelection,
	onEdit,
	onDelete,
	onRestore,
} ) => {
	const buttons = [];
	if ( entry.status !== 'trash' ) {
		buttons.push( {
			content: __( 'Edit', 'sureforms' ),
			ariaLabel: __( 'Edit', 'sureforms' ),
			icon: <Edit3 />,
			onClick: () => onEdit( entry ),
		} );
	}
	if ( entry.status === 'trash' ) {
		buttons.push( {
			content: __( 'Restore', 'sureforms' ),
			ariaLabel: __( 'Restore', 'sureforms' ),
			icon: <RotateCcw />,
			onClick: () => onRestore( entry ),
		} );
		buttons.push( {
			content: __( 'Delete Permanently', 'sureforms' ),
			ariaLabel: __( 'Delete Permanently', 'sureforms' ),
			icon: <Trash2 />,
			onClick: () => onDelete( entry ),
		} );
	} else {
		buttons.push( {
			content: __( 'Move to Trash', 'sureforms' ),
			ariaLabel: __( 'Move to Trash', 'sureforms' ),
			icon: <Trash2 />,
			onClick: () => onDelete( entry ),
		} );
	}
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
			<Table.Cell>
				{ typeof entry?.firstField === 'string'
					? entry.firstField
					: __( 'Repeater', 'sureforms' ) }
			</Table.Cell>
			<Table.Cell>{ entry.dateTime }</Table.Cell>
			<Table.Cell>
				<Container align="center" className="gap-2" justify="end">
					{ buttons.map( ( btn, index ) => (
						<Tooltip
							key={ index }
							placement="top"
							content={ btn.content }
						>
							<Button
								variant="ghost"
								aria-label={ btn.ariaLabel }
								size="xs"
								icon={ btn.icon }
								onClick={ btn.onClick }
							/>
						</Tooltip>
					) ) }
				</Container>
			</Table.Cell>
		</Table.Row>
	);
};

export default EntriesTableRow;
