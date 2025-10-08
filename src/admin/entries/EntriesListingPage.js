import { useState, forwardRef, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Search, Calendar, ChevronsUpDown, Edit3, Trash2 } from 'lucide-react';
import {
	Button,
	Container,
	Table,
	Badge,
	Select,
	Input,
	Pagination,
} from '@bsf/force-ui';

const CustomButton = forwardRef(
	(
		{
			ariaLabel,
			icon,
			onClick,
			variant = 'ghost',
			classNames = 'text-icon-secondary',
			label = '',
			...props
		},
		ref
	) => {
		return (
			<Button
				aria-label={ ariaLabel }
				className={ classNames }
				variant={ variant }
				size="xs"
				onClick={ onClick }
				icon={ icon }
				ref={ ref }
				{ ...props }
			>
				{ label || '' }
			</Button>
		);
	}
);

const EntriesListingPage = () => {
	const [ selectedEntries, setSelectedEntries ] = useState( [] );
	const [ currentPage, setCurrentPage ] = useState( 2 );
	const [ entriesPerPage, setEntriesPerPage ] = useState( 10 );
	const [ statusFilter, setStatusFilter ] = useState( 'all' );
	const [ formFilter, setFormFilter ] = useState( 'all' );
	const [ searchQuery, setSearchQuery ] = useState( '' );

	const statusOptions = [
		{ value: 'all', label: __( 'All Status', 'sureforms' ) },
		{ value: 'read', label: __( 'Read', 'sureforms' ) },
		{ value: 'unread', label: __( 'Unread', 'sureforms' ) },
		{ value: 'trash', label: __( 'Trash', 'sureforms' ) },
	];

	const formOptions = [
		{ value: 'all', label: __( 'All Forms', 'sureforms' ) },
		{ value: 'contact-us', label: __( 'Contact Us', 'sureforms' ) },
		{ value: 'newsletter', label: __( 'Newsletter', 'sureforms' ) },
		{
			value: 'job-application',
			label: __( 'Job Application Form', 'sureforms' ),
		},
		{
			value: 'support-request',
			label: __( 'Support Request', 'sureforms' ),
		},
	];

	const entriesPerPageOptions = [
		{ value: 10, label: '10' },
		{ value: 25, label: '25' },
		{ value: 50, label: '50' },
		{ value: 100, label: '100' },
	];

	const entries = [
		{
			id: 1,
			entryId: 'Entry #4',
			formName: 'Contact Us',
			status: 'Unread',
			firstField: 'Name',
			dateTime: 'Jul 3, 2023 10:30 am',
		},
		{
			id: 2,
			entryId: 'Entry #4',
			formName: 'Newsletter',
			status: 'Read',
			firstField: 'Name',
			dateTime: 'Jul 3, 2023 10:30 am',
		},
		{
			id: 3,
			entryId: 'Entry #4',
			formName: 'Job Application Form',
			status: 'Read',
			firstField: 'Name',
			dateTime: 'Jun 23, 2023 10:30 am',
		},
		{
			id: 4,
			entryId: 'Entry #4',
			formName: 'Support Request',
			status: 'Read',
			firstField: 'Name',
			dateTime: 'Aug 3, 2023 10:30 am',
		},
		{
			id: 5,
			entryId: 'Entry #4',
			formName: 'Calculation Form',
			status: 'Read',
			firstField: 'Name',
			dateTime: 'Jul 3, 2023 10:30 am',
		},
		{
			id: 6,
			entryId: 'Entry #4',
			formName: 'Login Form',
			status: 'Trash',
			firstField: 'Name',
			dateTime: 'Jul 3, 2023 10:30 am',
		},
		{
			id: 7,
			entryId: 'Entry #4',
			formName: 'Registration form',
			status: 'Read',
			firstField: 'Name',
			dateTime: 'Oct 3, 2024 10:30 am',
		},
		{
			id: 8,
			entryId: 'Entry #4',
			formName: 'Contact Us',
			status: 'Unread',
			firstField: 'Name',
			dateTime: 'Jul 3, 2023 10:30 am',
		},
		{
			id: 9,
			entryId: 'Entry #4',
			formName: 'Contact Us',
			status: 'Unread',
			firstField: 'Name',
			dateTime: 'Jul 3, 2023 10:30 am',
		},
		{
			id: 10,
			entryId: 'Entry #4',
			formName: 'Contact Us',
			status: 'Unread',
			firstField: 'Name',
			dateTime: 'Jul 3, 2023 10:30 am',
		},
	];

	const getStatusBadgeVariant = ( status ) => {
		switch ( status ) {
			case 'Unread':
				return 'yellow';
			case 'Read':
				return 'green';
			case 'Trash':
				return 'red';
			default:
				return 'neutral';
		}
	};

	const headerContent = [
		{
			label: __( 'Form ID', 'sureforms' ),
			key: 'formId',
			sortable: true,
		},
		{
			label: __( 'Form Name', 'sureforms' ),
			key: 'formName',
		},
		{
			label: __( 'Status', 'sureforms' ),
			key: 'status',
			sortable: true,
		},
		{
			label: __( 'First Field', 'sureforms' ),
			key: 'firstField',
		},
		{
			label: __( 'Date & Time', 'sureforms' ),
			key: 'dateTime',
			sortable: true,
		},
		{
			label: __( 'Actions', 'sureforms' ),
			key: 'actions',
			align: 'right',
		},
	];

	const handleChangeRowCheckbox = ( checked, item ) => {
		if ( checked ) {
			setSelectedEntries( ( prev ) => [ ...prev, item.id ] );
		} else {
			setSelectedEntries( ( prev ) =>
				prev.filter( ( entryId ) => entryId !== item.id )
			);
		}
	};

	const handleToggleAll = ( checked ) => {
		if ( checked ) {
			setSelectedEntries( entries.map( ( entry ) => entry.id ) );
		} else {
			setSelectedEntries( [] );
		}
	};

	const indeterminate = useMemo( () => {
		return (
			selectedEntries.length > 0 &&
			selectedEntries.length < entries.length
		);
	}, [ selectedEntries ] );

	const totalPages = 10;

	const getPaginationItems = () => {
		const items = [];
		const pagesToShow = [ 1, 2, 3, 7, 8, 9 ];

		pagesToShow.forEach( ( page, index ) => {
			if ( index === 3 ) {
				items.push( <Pagination.Ellipsis key="ellipsis" /> );
			}
			items.push(
				<Pagination.Item
					key={ page }
					isActive={ currentPage === page }
					onClick={ () => setCurrentPage( page ) }
				>
					{ page }
				</Pagination.Item>
			);
		} );

		return items;
	};

	return (
		<div className="p-8 bg-gray-50 min-h-screen">
			<div className="max-w-[1374px] mx-auto">
				<div className="bg-white rounded-xl border-0.5 border-border-subtle shadow-sm p-4 space-y-2">
					<div className="p-1">
						<div className="flex justify-between items-center gap-5">
							<div>
								<h1 className="text-xl font-semibold text-text-primary leading-[30px] tracking-[-0.005em]">
									All form Entries
								</h1>
							</div>

							<div className="flex items-center gap-4">
								<div className="w-[150px]">
									<Select
										value={ statusFilter }
										onChange={ setStatusFilter }
										size="sm"
									>
										<Select.Button
											placeholder={ __(
												'Status',
												'sureforms'
											) }
										>
											{
												statusOptions.find(
													( option ) =>
														option.value ===
														statusFilter
												)?.label
											}
										</Select.Button>
										<Select.Options className="z-999999">
											{ statusOptions.map( ( option ) => (
												<Select.Option
													key={ option.value }
													value={ option.value }
												>
													{ option.label }
												</Select.Option>
											) ) }
										</Select.Options>
									</Select>
								</div>

								<div className="w-[160px]">
									<Select
										value={ formFilter }
										onChange={ setFormFilter }
										size="sm"
									>
										<Select.Button
											placeholder={ __(
												'All Forms',
												'sureforms'
											) }
										>
											{
												formOptions.find(
													( option ) =>
														option.value ===
														formFilter
												)?.label
											}
										</Select.Button>
										<Select.Options className="z-999999">
											{ formOptions.map( ( option ) => (
												<Select.Option
													key={ option.value }
													value={ option.value }
												>
													{ option.label }
												</Select.Option>
											) ) }
										</Select.Options>
									</Select>
								</div>

								<div className="w-[200px]">
									<Input
										type="text"
										placeholder="mm/dd/yyyy"
										prefix={
											<Calendar className="w-4 h-4 text-icon-secondary" />
										}
									/>
								</div>

								<div className="w-[234px]">
									<Input
										type="search"
										placeholder="Search your entry."
										value={ searchQuery }
										onChange={ ( value ) =>
											setSearchQuery( value )
										}
										prefix={
											<Search className="w-4 h-4 text-icon-secondary" />
										}
									/>
								</div>
							</div>
						</div>
					</div>

					<Table className="rounded-md" checkboxSelection>
						<Table.Head
							selected={ !! selectedEntries.length }
							onChangeSelection={ handleToggleAll }
							indeterminate={ indeterminate }
						>
							{ headerContent.map( ( header, index ) => (
								<Table.HeadCell key={ index }>
									<Container
										align="center"
										className="gap-2"
										justify={
											header.align === 'right' && 'end'
										}
									>
										{ header.label }
										{ header.sortable && (
											<ChevronsUpDown className="w-4 h-4 text-text-primary" />
										) }
									</Container>
								</Table.HeadCell>
							) ) }
						</Table.Head>
						<Table.Body>
							{ entries.map( ( entry ) => (
								<Table.Row
									key={ entry.id }
									value={ entry }
									selected={ selectedEntries.includes(
										entry.id
									) }
									onChangeSelection={
										handleChangeRowCheckbox
									}
									className="hover:bg-background-primary"
								>
									<Table.Cell>{ entry.entryId }</Table.Cell>
									<Table.Cell>{ entry.formName }</Table.Cell>
									<Table.Cell>
										<Badge
											className="w-fit"
											variant={ getStatusBadgeVariant(
												entry.status
											) }
											size="md"
											label={ entry.status }
										/>
									</Table.Cell>
									<Table.Cell>
										{ entry.firstField }
									</Table.Cell>
									<Table.Cell>{ entry.dateTime }</Table.Cell>
									<Table.Cell>
										<Container
											align="center"
											className="gap-2"
											justify="end"
										>
											<CustomButton
												ariaLabel={ __(
													'Edit',
													'sureforms'
												) }
												icon={
													<Edit3 className="size-4" />
												}
												onClick={ () => {
													// Handle edit
												} }
											/>
											<CustomButton
												ariaLabel={ __(
													'Delete',
													'sureforms'
												) }
												icon={
													<Trash2 className="text-icon-secondary size-4" />
												}
												onClick={ () => {
													// Handle delete
												} }
											/>
										</Container>
									</Table.Cell>
								</Table.Row>
							) ) }
						</Table.Body>
						<Table.Footer className="flex flex-col lg:flex-row lg:justify-between">
							<div className="flex items-center gap-3">
								<span className="text-sm text-text-secondary">
									Page { currentPage } out of { totalPages }
								</span>
								<div className="w-[60px]">
									<Select
										value={ entriesPerPage }
										onChange={ setEntriesPerPage }
										size="sm"
									>
										<Select.Button
											render={ ( value ) => value }
										/>
										<Select.Options className="z-999999">
											{ entriesPerPageOptions.map(
												( option ) => (
													<Select.Option
														key={ option.value }
														value={ option.value }
													>
														{ option.label }
													</Select.Option>
												)
											) }
										</Select.Options>
									</Select>
								</div>
							</div>

							<Pagination className="w-fit">
								<Pagination.Content>
									<Pagination.Previous
										onClick={ () =>
											setCurrentPage(
												Math.max( 1, currentPage - 1 )
											)
										}
										disabled={ currentPage === 1 }
									/>
									{ getPaginationItems() }
									<Pagination.Next
										onClick={ () =>
											setCurrentPage(
												Math.min(
													totalPages,
													currentPage + 1
												)
											)
										}
										disabled={ currentPage === totalPages }
									/>
								</Pagination.Content>
							</Pagination>
						</Table.Footer>
					</Table>
				</div>
			</div>
		</div>
	);
};

export default EntriesListingPage;
