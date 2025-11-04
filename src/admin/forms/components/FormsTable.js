import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Edit3, Trash, RotateCcw, Eye, Share, Copy, Check } from 'lucide-react';
import { Button, Container, Badge, Text } from '@bsf/force-ui';
import Tooltip from '@Admin/components/Tooltip';
import Table from '@Admin/components/Table';
import { exportForms } from '../utils';

/**
 * FormsTable Component
 * Displays the forms table with headers and rows
 *
 * @param {Object}   props
 * @param {Array}    props.data                 - Array of data objects (forms)
 * @param {Array}    props.columns              - Array of column definitions
 * @param {Array}    props.selectedItems        - Array of selected item IDs
 * @param {Function} props.onToggleAll          - Handler for toggle all checkbox
 * @param {Function} props.onChangeRowSelection - Handler for row selection change
 * @param {boolean}  props.indeterminate        - Whether the header checkbox is indeterminate
 * @param {boolean}  props.isLoading            - Whether data is loading
 * @param {Function} props.onSort               - Handler for sort column change
 * @param {Function} props.getSortDirection     - Function to get sort direction for a column
 * @param {string}   props.emptyMessage         - Message to display when no data
 * @param {Function} props.onEdit               - Handler for edit action
 * @param {Function} props.onTrash              - Handler for trash action
 * @param {Function} props.onRestore            - Handler for restore action
 * @param {Function} props.onDelete             - Handler for delete action
 * @param {Node}     props.children             - Child components (typically pagination)
 */

const FormsTable = ( {
	data = [],
	selectedItems = [],
	onToggleAll,
	onChangeRowSelection,
	indeterminate = false,
	isLoading = false,
	onSort,
	getSortDirection,
	emptyMessage = __( 'No forms found', 'sureforms' ),
	onEdit,
	onTrash,
	onRestore,
	onDelete,
	children,
} ) => {
	// State to track which shortcode was recently copied
	const [ copiedFormId, setCopiedFormId ] = useState( null );
	// Format date and time function
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
	const handleCopyShortcode = async ( form ) => {
		try {
			await navigator.clipboard.writeText(
				`[sureforms id='${ form.id }']`
			);
			// Show copied feedback
			setCopiedFormId( form.id );
			// Reset after 2 seconds
			setTimeout( () => {
				setCopiedFormId( null );
			}, 1000 );
		} catch ( err ) {
			// Fallback for older browsers
			const textArea = document.createElement( 'textarea' );
			textArea.value = `[sureforms id='${ form.id }']`;
			document.body.appendChild( textArea );
			textArea.select();
			document.execCommand( 'copy' );
			document.body.removeChild( textArea );
			// Show copied feedback for fallback too
			setCopiedFormId( form.id );
			setTimeout( () => {
				setCopiedFormId( null );
			}, 1000 );
		}
	};

	// Handle export action
	const handleExport = async ( form ) => {
		try {
			await exportForms( form.id );
		} catch ( error ) {
			console.error( 'Export error:', error );
		}
	};

	// Handle view action
	const handleView = ( form ) => {
		if ( form.frontend_url ) {
			window.open( form.frontend_url, '_blank' );
		}
	};

	const defaultColumns = [
		{
			label: __( 'Title', 'sureforms' ),
			key: 'title',
			sortable: true,
			headerClassName: 'w-auto',
			render: ( form ) => (
				<div>
					<Text 
						size={ 14 } 
						color="secondary"
						onClick={ () => onEdit( form ) }
						className="cursor-pointer hover:text-link-primary hover:underline"
					>
						{ form.title || __( '(no title)', 'sureforms' ) }{ ' ' }
						<span className="font-semibold">
							{ form.status === 'draft' &&
								__( '- Draft', 'sureforms' ) }
						</span>
					</Text>
				</div>
			),
		},
		{
			label: __( 'Shortcode', 'sureforms' ),
			key: 'shortcode',
			headerClassName: 'w-[15%]',
			render: ( form ) => (
				<div
					onClick={ () => handleCopyShortcode( form ) }
					className="cursor-pointer w-fit"
				>
					<Badge
						label={ form.shortcode }
						size="xs"
						variant="neutral"
						icon={ 
							copiedFormId === form.id ? 
								<Check className="w-3 h-3 text-green-600" /> : 
								<Copy className="w-3 h-3" /> 
						}
						className="hover:bg-background-secondary rounded-sm"
						title={ 
							copiedFormId === form.id ? 
								__( 'Copied!', 'sureforms' ) : 
								__( 'Copy Shortcode', 'sureforms' ) 
						}
					/>
				</div>
			),
		},
		{
			label: __( 'Entries', 'sureforms' ),
			key: 'entries_count',
			headerClassName: 'w-[8%]',
			render: ( form ) => (
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
			),
		},
		{
			label: __( 'Author', 'sureforms' ),
			key: 'author',
			headerClassName: 'w-[15%]',
			render: ( form ) => (
				<Text size={ 14 } color="secondary">
					{ form.author
						? form.author.name
						: __( 'Unknown', 'sureforms' ) }
				</Text>
			),
		},
		{
			label: __( 'Date & Time', 'sureforms' ),
			key: 'date',
			sortable: true,
			headerClassName: 'w-[18%]',
			render: ( form ) => (
				<Text size={ 14 } color="secondary">
					{ formatDateTime( form.date_created ) }
				</Text>
			),
		},
		{
			label: __( 'Actions', 'sureforms' ),
			key: 'actions',
			align: 'right',
			headerClassName: 'w-[160px]',
			render: ( form ) => {
				// Build action buttons based on form status
				const actions = [];

				if ( form.status !== 'trash' ) {
					// Export action
					actions.push( {
						key: 'export',
						label: __( 'Export', 'sureforms' ),
						icon: <Share className="size-4" />,
						onClick: () => handleExport( form ),
					} );

					// Edit action
					actions.push( {
						key: 'edit',
						label: __( 'Edit', 'sureforms' ),
						icon: <Edit3 className="size-4" />,
						onClick: () => onEdit?.( form ),
					} );

					// View action
					actions.push( {
						key: 'view',
						label: __( 'Preview', 'sureforms' ),
						icon: <Eye className="size-4" />,
						onClick: () => handleView( form ),
					} );

					// Delete action
					actions.push( {
						key: 'delete',
						label: __( 'Move to Trash', 'sureforms' ),
						icon: <Trash className="size-4" />,
						onClick: () => onTrash?.( form ),
					} );
				} else {
					// Restore action for trashed items
					actions.push( {
						key: 'restore',
						label: __( 'Restore', 'sureforms' ),
						icon: <RotateCcw className="size-4" />,
						onClick: () => onRestore?.( form ),
					} );

					// Delete permanently action for trashed items
					actions.push( {
						key: 'delete-permanent',
						label: __( 'Permanently Delete', 'sureforms' ),
						icon: <Trash className="size-4" />,
						onClick: () => onDelete?.( form ),
					} );
				}

				return (
					<Container className="gap-2 justify-end">
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
				);
			},
		},
	];

	return (
		<Table
			data={ data }
			columns={ defaultColumns }
			selectedItems={ selectedItems }
			onToggleAll={ onToggleAll }
			onChangeRowSelection={ onChangeRowSelection }
			indeterminate={ indeterminate }
			isLoading={ isLoading }
			onSort={ onSort }
			getSortDirection={ getSortDirection }
			emptyMessage={ emptyMessage }
		>
			{ children }
		</Table>
	);
};

export default FormsTable;
