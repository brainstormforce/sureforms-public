import { __ } from '@wordpress/i18n';
import { Button, Container, DatePicker, Input, Label, Loader, Title, AreaChart, Select } from '@bsf/force-ui';
import { FileChartColumnIncreasing, Calendar, X } from 'lucide-react';
import apiFetch from '@wordpress/api-fetch';
import {
	cn,
	format,
	getDatePlaceholder,
	getSelectedDate,
	getLastNDays,
} from '@Utils/Helpers';
import { useState, useEffect, useRef } from '@wordpress/element';

export default () => {
	const [ selectedDates, setSelectedDates ] = useState( {
		from: null,
		to: null,
	} );
	const [ isDatePickerOpen, setIsDatePickerOpen ] = useState( false );
	const [ dataToShow, setDataToShow ] = useState( [] );
	const [ formData, setFormData ] = useState( [] );
	const containerRef = useRef( null );
	const [ selectedForm, setSelectedForm ] = useState( '' );
	const [ loading, setLoading ] = useState( false );

	const handleChange = ( value ) => {
		setSelectedForm( value );
	};

	const getFormLabel = ( value ) => {
		const label = formData.find(
			( option ) => {
				return option.id === value;
			}
		);
		return label ? label.name : '';
	};

	useEffect( () => {
		const fetchPosts = async () => {
			try {
				setLoading( true ); // Set loading to true before fetching

				const { from, to } = selectedDates;
				const form = selectedForm || '';

				// Determine the date range
				const afterDate = from ? new Date( from ).toISOString() : new Date( new Date().setMonth( new Date().getMonth() - 1 ) ).toISOString();
				const beforeDate = to ? new Date( to ).toISOString() : new Date().toISOString();

				// Construct the API endpoint
				const endpoint = `sureforms/v1/entries-chart-data?after=${ afterDate }&before=${ beforeDate }&form=${ form }`;

				// Fetch data from API
				const response = await apiFetch( { path: endpoint } );

				// Process the data to create the chart.
				const postsData = {};

				response.forEach( ( post ) => {
					const postDate = new Date( post.created_at ).toLocaleDateString();

					if ( ! postsData[ postDate ] ) {
						postsData[ postDate ] = 1;
					} else {
						postsData[ postDate ]++;
					}
				} );

				// Convert date strings to Date objects and sort by date
				const sortedDataArray = Object.entries( postsData )
					.sort( ( [ dateA ], [ dateB ] ) => new Date( dateA ) - new Date( dateB ) )
					.map( ( [ date, count ] ) => ( {
						month: date,
						entries: count,
					} ) );

				// Prepare the data for the chart.
				setDataToShow( sortedDataArray );
			} catch ( error ) {
				console.error( 'Error fetching data:', error );
			} finally {
				setLoading( false ); // Set loading to false after fetching (whether success or error)
			}
		};

		fetchPosts();
	}, [ selectedDates, selectedForm ] );

	useEffect( () => {
		const fetchPosts = async () => {
			try {
				const endpoint = `sureforms/v1/form-data`;

				// Fetch data from API
				const response = await apiFetch( { path: endpoint } );

				const formsData = response.map( ( post ) => ( {
					name: post.title,
					id: post.id,
				} ) );

				// Prepare the forms data.
				setFormData( formsData );
			} catch ( error ) {
				console.error( 'Error fetching data:', error );
			}
		};

		fetchPosts();
	}, [ selectedDates ] );

	// Handler to clear filters and reset to default data from Query Client
	const handleClearDateFilters = () => {
		setSelectedDates( { from: null, to: null } );
	};

	// Handlers for DatePicker
	const handleDateApply = ( dates ) => {
		const { from, to } = dates;

		if ( from && to ) {
			const fromDate = new Date( from );
			const toDate = new Date( to );

			if ( fromDate > toDate ) {
				// Swap the dates to ensure 'from' is earlier than 'to'
				setSelectedDates( { from: to, to: from } );
			} else {
				setSelectedDates( dates );
			}
		} else if ( from && ! to ) {
			setSelectedDates( { from, to: from } );
		} else {
			setSelectedDates( { from: null, to: null } );
		}
		setIsDatePickerOpen( false );
	};

	const handleDateCancel = () => {
		setIsDatePickerOpen( false );
	};

	// Click Outside Handler using useEffect
	useEffect( () => {
		function handleClickOutside( event ) {
			if (
				isDatePickerOpen &&
				containerRef.current &&
				! containerRef.current.contains( event.target )
			) {
				setIsDatePickerOpen( false );
			}
		}

		// Bind the event listener
		document.addEventListener( 'mousedown', handleClickOutside );
		return () => {
			// Unbind the event listener on cleanup
			document.removeEventListener( 'mousedown', handleClickOutside );
		};
	}, [ isDatePickerOpen ] );

	// Formatter for X-Axis
	const formatXAxis = ( tickItem ) => {
		return format( new Date( tickItem ), 'MMM dd, yyyy' );
	};

	return (
		<Container
			containerType="flex"
			direction="column"
			gap="xs"
			className="w-full h-full p-4 rounded-xl bg-background-primary border-border-subtle border-0.5"
		>
			{ /* Header */ }
			<Container.Item className="flex items-center justify-between w-full p-1">
				<Title title={ __( 'Overview', 'sureforms' ) } tag="h5" />

				<div className="flex items-center gap-2 focus:[box-shadow:none]">

					{ selectedForm ? (
						<Button
							variant="link"
							size="xs"
							icon={ <X /> }
							onClick={ () => handleChange( '' ) }
							className="text-button-danger no-underline focus:ring-0 [box-shadow:none] focus:[box-shadow:none] hover:no-underline hover:text-button-danger"
							aria-label={ __( 'Clear Filters', 'sureforms' ) }
						>
							{ '' }
						</Button>
					) : null }

					{ /* {select form} */ }
					<div
						className="w-auto min-w-[200px] cursor-pointer [&>input]:min-h-2 rounded-sm shadow-sm border border-border-subtle"
					>
						<Select
							value={ selectedForm }
							onChange={ ( value ) =>
								handleChange( value )
							}
							size="sm"
							className="focus:[box-shadow:none]"
						>
							<Select.Button
								className="focus:[box-shadow:none] text-text-tertiary text-xs"
							>
								{ getFormLabel(
									selectedForm
								) }
							</Select.Button>
							<Select.Options
								className="z-999999 focus:[box-shadow:none]"
							>
								{ formData.map( ( option ) => (
									<Select.Option
										key={ option.id }
										value={ option.id }
									>
										{ <span>
											{ option.name }
										</span> }
									</Select.Option>
								) ) }
							</Select.Options>
						</Select>
					</div>

					{ /* {date picker} */ }
					{ selectedDates.from || selectedDates.to ? (
						<Button
							variant="link"
							size="xs"
							icon={ <X /> }
							onClick={ handleClearDateFilters }
							className="text-button-danger no-underline focus:ring-0 [box-shadow:none] focus:[box-shadow:none] hover:no-underline hover:text-button-danger"
							aria-label={ __( 'Clear Filters', 'sureforms' ) }
						>
							{ '' }
						</Button>
					) : null }

					<div className="relative focus:[box-shadow:none]" ref={ containerRef }>
						<Input
							type="text"
							size="sm"
							value={ getSelectedDate( selectedDates ) }
							suffix={
								<Calendar className="text-icon-secondary" />
							}
							onClick={ () =>
								setIsDatePickerOpen( ( prev ) => ! prev )
							}
							placeholder={ getDatePlaceholder() }
							className="w-auto min-w-[200px] focus:[box-shadow:none] cursor-pointer [&>input]:min-h-9 rounded-sm shadow-sm border border-border-subtle"
							readOnly
							aria-label={ __(
								'Select Date Range',
								'sureforms'
							) }
						/>

						{ isDatePickerOpen && (
							<div className="absolute z-10 mt-2 rounded-lg shadow-lg right-0 bg-background-primary">
								<DatePicker
									applyButtonText={ __(
										'Apply',
										'sureforms'
									) }
									cancelButtonText={ __(
										'Cancel',
										'sureforms'
									) }
									selectionType="range"
									showOutsideDays={ false }
									variant="presets"
									onApply={ handleDateApply }
									onCancel={ handleDateCancel }
									selected={ getLastNDays( selectedDates ) }
								/>
							</div>
						) }
					</div>
				</div>
			</Container.Item>

			<Container.Item
				className={ cn(
					'w-full flex items-stretch justify-between gap-1 bg-background-secondary rounded-lg',
					dataToShow.length > 0 ? 'p-1' : 'p-0'
				) }
			>
				{ /* Chart Container */ }
				<Container
					className={ cn(
						'w-full flex flex-col flex-1 p-2 overflow-hidden bg-background-primary',
						dataToShow.length > 0 ? 'rounded-md shadow-sm' : ''
					) }
					containerType="flex"
					direction="column"
				>
					{ loading ? (
						<div className="flex flex-col items-center justify-center h-full  min-[1427px]:min-h-[236px] min-h-[256px] gap-3">
							<div className="flex flex-col items-center justify-center w-[29.375rem]">
								<Loader
									className="mb-3"
									size="xl"
									variant="primary"
								/>
								<div className="flex flex-col items-center space-y-1">
									<Label
										tag="p"
										className="text-sm font-medium text-center text-text-primary"
									>
										{ __(
											'Please wait for the data to load',
											'sureforms'
										) }
									</Label>
									<Label
										tag="p"
										className="text-sm font-normal text-center text-text-secondary"
									>
										{ __(
											'This is where your form views will appear',
											'sureforms'
										) }
									</Label>
								</div>
							</div>
						</div>
					)
						: ! loading && dataToShow.length > 0 ? (
							<div className="flex-1 w-full">
								<div className="w-full h-full min-h-[248px] min-[1427px]:min-h-[228px]">
									<AreaChart
										chartWidth="100%"
										chartHeight="100%"
										colors={ [
											{
												fill: '#BFDBFE',
												stroke: '#2563EB',
											},
											{
												fill: '#BAE6FD',
												stroke: '#38BDF8',
											},
										] }
										data={ dataToShow }
										dataKeys={ [ 'entries' ] }
										showXAxis={ true }
										showYAxis={ false }
										variant="gradient"
										xAxisDataKey="month"
										tickFormatter={ formatXAxis }
									/>
								</div>
							</div>
						) : (
							<div className="flex flex-col items-center justify-center h-full  min-[1427px]:min-h-[236px] min-h-[256px] gap-3">
								<div className="flex flex-col items-center justify-center w-[29.375rem]">
									<FileChartColumnIncreasing className="mb-3" />
									<div className="flex flex-col items-center space-y-1">
										<Label
											tag="p"
											className="text-sm font-medium text-center text-text-primary"
										>
											{ __(
												'There is no data on this view',
												'sureforms'
											) }
										</Label>
										<Label
											tag="p"
											className="text-sm font-normal text-center text-text-secondary"
										>
											{ __(
												'This is where your form views will appear',
												'sureforms'
											) }
										</Label>
									</div>
								</div>
							</div>
						) }
				</Container>
			</Container.Item>
		</Container >
	);
};
