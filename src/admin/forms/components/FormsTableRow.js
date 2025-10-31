import { __ } from '@wordpress/i18n';
import { Edit3, Trash, FileDown, Copy, Eye, Share } from 'lucide-react';
import { Button, Container, Table, Badge, Text } from '@bsf/force-ui';
import { useState } from '@wordpress/element';
import Tooltip from '@Admin/components/Tooltip';
import { exportForms } from '../utils';

// FormsTableRow Component - Displays a single form row in the table
const FormsTableRow = ( {
	form,
	isSelected,
	onChangeSelection,
	onEdit,
	onTrash,
	onRestore,
	onDelete,
} ) => {
	// State for copy button feedback
	const [ hasCopied, setHasCopied ] = useState( false );

	// Format date and time
	const formatDateTime = ( dateString ) => {
		const date = new Date( dateString );
		return date.toLocaleString( 'en-US', {
			year: 'numeric',
			month: 'short',
			day: 'numeric',
			hour: 'numeric',
			minute: '2-digit',
			hour12: true,
		} );
	};

	// Handle copy shortcode
	const handleCopyShortcode = async () => {
		try {
			await navigator.clipboard.writeText(
				`[sureforms id='${ form.id }']`
			);
			setHasCopied( true );
			setTimeout( () => setHasCopied( false ), 2000 );
		} catch ( err ) {
			// Fallback for older browsers
			const textArea = document.createElement( 'textarea' );
			textArea.value = `[sureforms id='${ form.id }']`;
			document.body.appendChild( textArea );
			textArea.select();
			document.execCommand( 'copy' );
			document.body.removeChild( textArea );
			setHasCopied( true );
			setTimeout( () => setHasCopied( false ), 2000 );
		}
	};

	// Handle export action
	const handleExport = async () => {
		try {
			await exportForms( form.id );
		} catch ( error ) {
			console.error( 'Export error:', error );
			// TODO: Show user-friendly error message
		}
	};

	// Handle view action
	const handleView = () => {
		if ( form.frontend_url ) {
			window.open( form.frontend_url, '_blank' );
		}
	};

	// Build action buttons based on form status
	const actions = [];

	if ( form.status !== 'trash' ) {
		// Export action
		actions.push( {
			key: 'export',
			label: __( 'Export', 'sureforms' ),
			icon: <Share className="size-4" />,
			onClick: handleExport,
		} );

		// Edit action
		actions.push( {
			key: 'edit',
			label: __( 'View', 'sureforms' ),
			icon: <Edit3 className="size-4" />,
			onClick: () => onEdit( form ),
		} );

		// View action
		actions.push( {
			key: 'view',
			label: __( 'Preview', 'sureforms' ),
			icon: <Eye className="size-4" />,
			onClick: handleView,
		} );

		// Delete action
		actions.push( {
			key: 'delete',
			label: __( 'Move to Trash', 'sureforms' ),
			icon: <Trash className="size-4" />,
			onClick: () => onTrash( form ),
		} );
	} else {
		// Restore action for trashed items
		actions.push( {
			key: 'restore',
			label: __( 'Restore', 'sureforms' ),
			icon: <FileDown className="size-4" />,
			onClick: () => onRestore( form ),
		} );

		// Delete permanently action for trashed items
		actions.push( {
			key: 'delete-permanent',
			label: __( 'Permanently Delete', 'sureforms' ),
			icon: <Trash className="size-4" />,
			onClick: () => onDelete( form ),
		} );
	}

	return (
		<Table.Row
			value={ form }
			selected={ isSelected }
			onChangeSelection={ onChangeSelection }
			className="hover:bg-background-primary"
		>
			{ /* Title */ }
			<Table.Cell>
				<div>
					<Text size={ 14 } color="secondary">
						{ form.title || __( '(no title)', 'sureforms' ) }{ ' ' }
						<span className="font-semibold">
							{ form.status === 'draft' &&
								__( '- Draft', 'sureforms' ) }
						</span>
					</Text>
				</div>
			</Table.Cell>

			{ /* Shortcode */ }
			<Table.Cell>
				<div
					onClick={ handleCopyShortcode }
					className="cursor-pointer w-fit"
				>
					<Badge
						label={ form.shortcode }
						size="xs"
						variant="neutral"
						icon={ <Copy className="w-3 h-3" /> }
						className="hover:bg-background-secondary rounded-sm"
						title={
							hasCopied
								? __( 'Copied!', 'sureforms' )
								: __( 'Copy Shortcode', 'sureforms' )
						}
					/>
				</div>
			</Table.Cell>

			{ /* Entries Count */ }
			<Table.Cell>
				<Button
					variant="link"
					size="sm"
					onClick={ () =>
						window.open(
							`admin.php?page=sureforms_entries&form_filter=${ form.id }`,
							'_self'
						)
					}
					className="text-text-primary hover:text-text-primary-hover p-0 h-auto font-medium"
				>
					{ String( form.entries_count ?? 0 ) }
				</Button>
			</Table.Cell>

			{ /* Author */ }
			<Table.Cell>
				<Text size={ 14 } color="secondary">
					{ form.author
						? form.author.name
						: __( 'Unknown', 'sureforms' ) }
				</Text>
			</Table.Cell>

			{ /* Date & Time */ }
			<Table.Cell>
				<Text size={ 14 } color="secondary">
					{ formatDateTime( form.date_created ) }
				</Text>
			</Table.Cell>

			{ /* Actions */ }
			<Table.Cell>
				<Container align="center" className="gap-2">
					{ actions.map( ( action ) => (
						<Tooltip
							key={ action.key }
							content={ action.label }
							placement="top"
							arrow={ true }
						>
							<Button
								variant="ghost"
								size="xs"
								icon={ action.icon }
								onClick={ action.onClick }
								className="p-1.5 text-text-primary [&>svg]:size-4"
							/>
						</Tooltip>
					) ) }
				</Container>
			</Table.Cell>
		</Table.Row>
	);
};

export default FormsTableRow;
