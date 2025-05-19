import { __ } from '@wordpress/i18n';
import {
	Button,
	Container,
	DatePicker,
	Input,
	Label,
	Loader,
	Title,
	AreaChart,
	Select,
} from '@bsf/force-ui';
import { ChartLine, Calendar, X } from 'lucide-react';
import apiFetch from '@wordpress/api-fetch';
import {
	format,
	getDatePlaceholder,
	getSelectedDate,
	getLastNDays,
	cn,
} from '@Utils/Helpers';
import { useState, useEffect, useRef } from '@wordpress/element';

// Text for chart when data is loading or no data is available
const ChartText = ( { text, color = '', weight = '', size = 'md' } ) => (
	<Label
		tag="p"
		size={ size }
		className={ cn(
			'text-center  max-w-sm mx-auto font-semibold',
			color,
			weight
		) }
	>
		{ text ||
			__( 'This is where your form views will appear', 'sureforms' ) }
	</Label>
);

// Clear Filters Button
const ClearButton = ( { onClick, ariaLabel } ) => (
	<Button
		variant="link"
		size="xs"
		icon={ <X /> }
		iconPosition="left"
		onClick={ onClick }
		aria-label={ ariaLabel }
	>
		{ __( 'Clear Filters', 'sureforms' ) }
	</Button>
);

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
		const label = formData.find( ( option ) => {
			return option.id === value;
		} );
		return label
			? label.name.length > 0
				? label.name
				: __( 'Unnamed Form', 'sureforms' )
			: '';
	};

	useEffect( () => {
		const fetchPosts = async () => {
			try {
				setLoading( true ); // Set loading to true before fetching

				const { from, to } = selectedDates;
				const form = selectedForm || '';

				// Determine the date range
				const afterDate = from
					? new Date( from ).toISOString()
					: new Date(
						new Date().setMonth( new Date().getMonth() - 1 )
					  ).toISOString();
				const beforeDate = to
					? new Date( to ).toISOString()
					: new Date().toISOString();

				// Construct the API endpoint
				const endpoint = `sureforms/v1/entries-chart-data?after=${ afterDate }&before=${ beforeDate }&form=${ form }`;

				// Fetch data from API
				const response = await apiFetch( { path: endpoint } );

				// Process the data to create the chart.
				const postsData = {};

				response.forEach( ( post ) => {
					const postDate = new Date(
						post.created_at
					).toLocaleDateString();

					if ( ! postsData[ postDate ] ) {
						postsData[ postDate ] = 1;
					} else {
						postsData[ postDate ]++;
					}
				} );

				// Convert date strings to Date objects and sort by date
				const sortedDataArray = Object.entries( postsData )
					.sort(
						( [ dateA ], [ dateB ] ) =>
							new Date( dateA ) - new Date( dateB )
					)
					.map( ( [ date, count ] ) => ( {
						month: format( new Date( date ), 'MMM dd, yyyy' ),
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

				const responseData = Object.entries( response );

				// check response data length and set in the state
				if ( responseData.length > 0 ) {
					const formsData = responseData.map( ( post ) => ( {
						name: post[ 1 ],
						id: post[ 0 ],
					} ) );
					setFormData( formsData );
				}
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
	const formatXAxis = () => {
		return '';
	};

	return (
		<Container
			direction="column"
			className="p-4 gap-2 rounded-xl bg-background-primary border-0.5 border-solid border-border-subtle shadow-sm-blur-2"
		>
			<Container.Item className="flex flex-wrap items-center justify-between p-1 gap-6 sm:flex-row sm:gap-2">
				<Title
					title={ __( 'Forms Overview', 'sureforms' ) }
					size="xs"
				/>
				<Container
					align="center"
					className="flex-wrap gap-3 sm:flex-row"
				>
					<Container align="center" className="gap-2">
						{ selectedForm ? (
							<ClearButton
								onClick={ () => handleChange( '' ) }
								ariaLabel={ __(
									'Clear Form Filters',
									'sureforms'
								) }
							/>
						) : null }
						<div className="min-w-[200px]">
							<Select
								value={ selectedForm }
								onChange={ ( value ) => handleChange( value ) }
								size="sm"
							>
								<Select.Button
									placeholder={ __(
										'Select Form',
										'sureforms'
									) }
								>
									{ getFormLabel( selectedForm ) }
								</Select.Button>
								<Select.Options className="z-999999">
									{ formData.map( ( option ) => (
										<Select.Option
											key={ option.id }
											value={ option.id }
										>
											{
												<span>
													{ option.name.length > 0
														? option.name
														: __(
															'Unnamed Form',
															'sureforms'
														  ) }
												</span>
											}
										</Select.Option>
									) ) }
								</Select.Options>
							</Select>
						</div>
					</Container>
					<Container align="center" className="gap-2">
						{ selectedDates.from || selectedDates.to ? (
							<ClearButton
								onClick={ handleClearDateFilters }
								ariaLabel={ __(
									'Clear Date Filters',
									'sureforms'
								) }
							/>
						) : null }
						<div
							className="relative flex gap-1"
							ref={ containerRef }
						>
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
								className="min-w-[200px]"
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
										selected={ getLastNDays(
											selectedDates
										) }
									/>
								</div>
							) }
						</div>
					</Container>
				</Container>
			</Container.Item>
			{ ! loading &&
				( selectedForm !== '' || dataToShow.length !== 0 ) && (
				<Container.Item className="flex justify-between p-1 gap-2">
					<Label
						size="sm"
						variant="help"
						className="font-semibold"
					>
						{ getFormLabel( selectedForm ) || 'All Forms' }
					</Label>
					<Container align="center" className="gap-2 p-1">
						<div className="w-2 h-2 bg-chart-purple-500 rounded-sm"></div>
						<Label
							size="xs"
							variant="help"
							className="font-medium"
						>
							{ __( 'Entries', 'sureforms' ) }
						</Label>
					</Container>
				</Container.Item>
			) }
			<Container.Item
				className={
					'w-full flex flex-col flex-1 items-stretch justify-between rounded-md mt-2'
				}
			>
				{ loading ? (
					<Container
						direction="column"
						justify="center"
						align="center"
						className="min-h-[256px] gap-4"
					>
						<Loader size="xl" variant="primary" />
						<Container
							direction="column"
							align="center"
							className="gap-1"
						>
							<ChartText
								text={ __(
									'Please wait for the data to load',
									'sureforms'
								) }
							/>
							<ChartText
								color="text-text-secondary"
								weight="font-normal"
								size="sm"
							/>
						</Container>
					</Container>
				) : ! loading && dataToShow.length > 0 ? (
					<div className="flex-1 min-h-[248px]">
						<AreaChart
							data={ dataToShow }
							dataKeys={ [ 'entries' ] }
							chartWidth="100%"
							chartHeight="100%"
							variant="gradient"
							showTooltip={ true }
							showYAxis={ false }
							showLegend={ false }
							showCartesianGrid={ true }
							colors={ [
								{
									fill: '#A855F7',
									stroke: '#A855F7',
								},
							] }
							showXAxis={ true }
							tooltipIndicator="dot"
							tickFormatter={ formatXAxis }
							xAxisDataKey="month"
							tooltipLabelKey="month"
						/>
					</div>
				) : (
					<Container
						direction="column"
						align="center"
						justify="center"
						className="min-h-[256px] gap-4"
					>
						<ChartLine />
						<Container
							direction="column"
							align="center"
							className="gap-1"
						>
							<ChartText
								text={ __(
									'No entries to display',
									'sureforms'
								) }
							/>
							<ChartText
								color="text-text-secondary"
								weight="font-normal"
								size="sm"
								text={ __(
									'Once you create a form and start receiving submissions, the data will appear here.',
									'sureforms'
								) }
							/>
						</Container>
					</Container>
				) }
			</Container.Item>
		</Container>
	);
};
