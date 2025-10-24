import { __ } from '@wordpress/i18n';
import { Edit3, Trash2, RotateCcw } from 'lucide-react';
import { Button, Container, Table, Badge, Text } from '@bsf/force-ui';

/**
 * Get status badge variant based on form status
 */
const getStatusBadgeVariant = ( status ) => {
	switch ( status ) {
		case 'publish':
			return 'green';
		case 'draft':
			return 'yellow';
		case 'trash':
			return 'red';
		default:
			return 'neutral';
	}
};

/**
 * Get status label
 */
const getStatusLabel = ( status ) => {
	switch ( status ) {
		case 'publish':
			return __( 'Published', 'sureforms' );
		case 'draft':
			return __( 'Draft', 'sureforms' );
		case 'trash':
			return __( 'Trash', 'sureforms' );
		default:
			return status;
	}
};

/**
 * FormsTableRow Component
 * Displays a single form row in the table
 */
const FormsTableRow = ( {
	form,
	isSelected,
	onChangeSelection,
	onEdit,
	onTrash,
	onRestore,
	onDelete,
} ) => {
	// Format date
	const formatDate = ( dateString ) => {
		const date = new Date( dateString );
		return date.toLocaleDateString( 'en-US', {
			year: 'numeric',
			month: 'short',
			day: 'numeric',
		} );
	};

	// Build action buttons based on form status
	const buttons = [];
	
	if ( form.status !== 'trash' ) {
		buttons.push( {
			label: __( 'Edit', 'sureforms' ),
			icon: <Edit3 className="w-4 h-4" />,
			onClick: () => onEdit( form ),
			variant: 'ghost',
		} );
	}

	if ( form.status === 'trash' ) {
		buttons.push( {
			label: __( 'Restore', 'sureforms' ),
			icon: <RotateCcw className="w-4 h-4" />,
			onClick: () => onRestore( form ),
			variant: 'ghost',
		} );
		buttons.push( {
			label: __( 'Delete Permanently', 'sureforms' ),
			icon: <Trash2 className="w-4 h-4" />,
			onClick: () => onDelete( form ),
			variant: 'ghost',
			destructive: true,
		} );
	} else {
		buttons.push( {
			label: __( 'Move to Trash', 'sureforms' ),
			icon: <Trash2 className="w-4 h-4" />,
			onClick: () => onTrash( form ),
			variant: 'ghost',
			destructive: true,
		} );
	}

	return (
		<Table.Row
			value={ form }
			selected={ isSelected }
			onChangeSelection={ onChangeSelection }
			className="hover:bg-background-primary"
		>
			{/* Title */}
			<Table.Cell className="max-w-xs">
				<div>
					<Text
						size={ 14 }
						weight={ 600 }
						color="primary"
						className="truncate"
					>
						{ form.title || __( '(no title)', 'sureforms' ) }
					</Text>
					{ form.edit_url && (
						<div className="flex items-center gap-3 mt-1">
							<Button
								variant="link"
								size="xs"
								onClick={ () => onEdit( form ) }
								className="text-text-secondary hover:text-text-primary p-0 h-auto"
							>
								{ __( 'Edit', 'sureforms' ) }
							</Button>
							<span className="text-text-tertiary">|</span>
							<Button
								variant="link"
								size="xs"
								onClick={ () => window.open( `admin.php?page=sureforms_entries&form_filter=${ form.id }`, '_self' ) }
								className="text-text-secondary hover:text-text-primary p-0 h-auto"
							>
								{ __( 'Entries', 'sureforms' ) }
							</Button>
						</div>
					) }
				</div>
			</Table.Cell>

			{/* Status */}
			<Table.Cell>
				<Badge
					variant={ getStatusBadgeVariant( form.status ) }
					size="sm"
					label={ getStatusLabel( form.status ) }
				/>
			</Table.Cell>

			{/* Entries Count */}
			<Table.Cell className="text-center">
				<Button
					variant="link"
					size="sm"
					onClick={ () => window.open( `admin.php?page=sureforms_entries&form_filter=${ form.id }`, '_self' ) }
					className="text-text-primary hover:text-text-primary-hover p-0 h-auto font-medium"
				>
					{ form.entries_count || 0 }
				</Button>
			</Table.Cell>

			{/* Author */}
			<Table.Cell>
				<Text size={ 14 } color="secondary">
					{ form.author ? form.author.name : __( 'Unknown', 'sureforms' ) }
				</Text>
			</Table.Cell>

			{/* Date */}
			<Table.Cell>
				<Text size={ 14 } color="secondary">
					{ formatDate( form.date_created ) }
				</Text>
			</Table.Cell>

			{/* Actions */}
			<Table.Cell>
				<Container align="center" className="gap-1" justify="end">
					{ buttons.map( ( button, index ) => (
						<Button
							key={ index }
							variant={ button.variant }
							size="xs"
							icon={ button.icon }
							onClick={ button.onClick }
							destructive={ button.destructive }
							className="p-1.5"
							title={ button.label }
						/>
					) ) }
				</Container>
			</Table.Cell>
		</Table.Row>
	);
};

export default FormsTableRow;