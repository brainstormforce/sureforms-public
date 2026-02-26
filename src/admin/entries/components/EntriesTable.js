import { __ } from '@wordpress/i18n';
import { Trash, RotateCcw, Eye } from 'lucide-react';
import { Button, Container, Badge, Tooltip as FUITooltip } from '@bsf/force-ui';
import Tooltip from '@Admin/components/Tooltip';
import { getStatusBadgeVariant } from '../utils/entryHelpers';
import Table from '@Admin/common/listing/components/Table';
import { formatDateTime } from '@Utils/Helpers';

/**
 * EntriesTable Component
 * Displays the entries table with headers and rows
 *
 * @param {Object}   props
 * @param {Array}    props.data                 - Array of data objects
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
 * @param {Function} props.onDelete             - Handler for delete action
 * @param {Function} props.onRestore            - Handler for restore action
 * @param {Object}   props.paginationProps      - Props for pagination component
 */
const EntriesTable = ( {
	data = [],
	columns,
	selectedItems = [],
	onToggleAll,
	onChangeRowSelection,
	indeterminate = false,
	isLoading = false,
	onSort,
	getSortDirection,
	emptyMessage = __( 'No entries found', 'sureforms' ),
	onEdit,
	onDelete,
	onRestore,
	paginationProps,
} ) => {
	const defaultColumns = [
		{
			label: __( 'Entry ID', 'sureforms' ),
			key: 'entryId',
			sortable: true,
			sortBy: 'id',
			headerClassName: 'w-[13%]',
			render: ( entry ) => (
				<Button
					tag="a"
					variant="link"
					size="md"
					className="[&>span]:p-0 text-text-secondary font-normal hover:underline no-underline"
					href={ `#/entry/${ entry.id }${
						entry.status === 'unread' ? '?read=true' : ''
					}` }
					onClick={ ( e ) => {
						if ( e.ctrlKey || e.metaKey || e.which === 2 ) {
							return;
						}
						e.preventDefault();
						onEdit?.( entry );
					} }
				>
					{ entry.entryId }
				</Button>
			),
		},
		{
			label: __( 'Form Name', 'sureforms' ),
			key: 'formName',
			headerClassName: 'w-[27%]',
			render: ( entry ) =>
				!! entry.formPermalink ? (
					<Button
						className="line-clamp-1 text-text-secondary font-normal no-underline hover:underline"
						tag="a"
						variant="link"
						size="md"
						href={ entry.formPermalink }
						target="_blank"
					>
						{ entry.formName }
					</Button>
				) : (
					<span className="line-clamp-1">{ entry.formName }</span>
				),
		},
		{
			label: __( 'Status', 'sureforms' ),
			key: 'status',
			sortable: true,
			headerClassName: 'w-[10%]',
			render: ( entry ) => (
				<Badge
					className="w-fit"
					variant={ getStatusBadgeVariant( entry.status ) }
					size="xs"
					label={ entry.statusLabel }
				/>
			),
		},
		{
			label: __( 'First Field', 'sureforms' ),
			key: 'firstField',
			headerClassName: 'w-[23%]',
			render: ( entry ) => {
				if ( typeof entry?.firstField === 'string' ) {
					return (
						<span className="line-clamp-1 break-all overflow-hidden">
							{ entry.firstField }
						</span>
					);
				}

				if (
					Array.isArray( entry?.firstField ) &&
					typeof entry?.firstField[ 0 ] === 'string'
				) {
					return (
						<span className="line-clamp-1 break-all overflow-hidden">
							{ __( 'Upload', 'sureforms' ) }
						</span>
					);
				}

				return (
					<span className="line-clamp-1 break-all overflow-hidden">
						{ ' ' }
						{ __( 'Repeater', 'sureforms' ) }{ ' ' }
					</span>
				);
			},
		},
		{
			label: __( 'Date & Time', 'sureforms' ),
			key: 'dateTime',
			sortable: true,
			headerClassName: 'w-[15%]',
			render: ( entry ) => {
				const { shortFormat, fullFormat } = formatDateTime(
					entry.dateTime
				);
				return (
					<FUITooltip
						content={ <span>{ fullFormat }</span> }
						placement="top"
						variant="dark"
						arrow
						interactive
						tooltipPortalId="srfm-settings-container"
						className="z-999999"
					>
						<span className="text-sm font-normal text-text-secondary">
							{ shortFormat }
						</span>
					</FUITooltip>
				);
			},
		},
		{
			label: __( 'Actions', 'sureforms' ),
			key: 'actions',
			align: 'right',
			headerClassName: 'w-[12%]',
			render: ( entry ) => {
				const buttons = [];
				if ( entry.status !== 'trash' ) {
					buttons.push( {
						content: __( 'Preview', 'sureforms' ),
						ariaLabel: __( 'Preview', 'sureforms' ),
						icon: <Eye />,
						onClick: () => onEdit?.( entry ),
						isPreview: true,
					} );
				}
				if ( entry.status === 'trash' ) {
					buttons.push( {
						content: __( 'Restore', 'sureforms' ),
						ariaLabel: __( 'Restore', 'sureforms' ),
						icon: <RotateCcw />,
						onClick: () => onRestore?.( entry ),
					} );
					buttons.push( {
						content: __( 'Delete Permanently', 'sureforms' ),
						ariaLabel: __( 'Delete Permanently', 'sureforms' ),
						icon: <Trash />,
						onClick: () => onDelete?.( entry ),
					} );
				} else {
					buttons.push( {
						content: __( 'Move to Trash', 'sureforms' ),
						ariaLabel: __( 'Move to Trash', 'sureforms' ),
						icon: <Trash />,
						onClick: () => onDelete?.( entry ),
					} );
				}
				return (
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
									{ ...( btn.isPreview
										? {
											tag: 'a',
											href: `#/entry/${ entry.id }${
												entry.status === 'unread'
													? '?read=true'
													: ''
											}`,
										  }
										: {} ) }
									onClick={ ( e ) => {
										if (
											btn.isPreview &&
											( e.ctrlKey ||
												e.metaKey ||
												e.which === 2 )
										) {
											return;
										}
										btn.onClick?.( e );
									} }
								/>
							</Tooltip>
						) ) }
					</Container>
				);
			},
		},
	];

	const tableColumns = columns || defaultColumns;

	return (
		<Table
			data={ data }
			columns={ tableColumns }
			selectedItems={ selectedItems }
			onToggleAll={ onToggleAll }
			onChangeRowSelection={ onChangeRowSelection }
			indeterminate={ indeterminate }
			isLoading={ isLoading }
			onSort={ onSort }
			getSortDirection={ getSortDirection }
			emptyMessage={ emptyMessage }
			paginationProps={ paginationProps }
		/>
	);
};

export default EntriesTable;
