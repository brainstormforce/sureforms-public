import { __ } from '@wordpress/i18n';
import { Search, Calendar } from 'lucide-react';
import { Select, Input } from '@bsf/force-ui';
import DatePicker from '@Admin/components/DatePicker';
import { STATUS_OPTIONS } from '../constants';

/**
 * EntriesFilters Component
 * Displays all filter controls for entries
 *
 * @param {Object}   props                      - Component props
 * @param {string}   props.statusFilter         - Current status filter value
 * @param {Function} props.onStatusFilterChange - Handler for status filter change
 * @param {string}   props.formFilter           - Current form filter value
 * @param {Function} props.onFormFilterChange   - Handler for form filter change
 * @param {string}   props.searchQuery          - Current search query
 * @param {Function} props.onSearchChange       - Handler for search input change
 * @param {Object}   props.dateRange            - Current date range
 * @param {Function} props.onDateRangeChange    - Handler for date range change
 * @param {Array}    props.formOptions          - Array of form options
 */
const EntriesFilters = ( {
	statusFilter,
	onStatusFilterChange,
	formFilter,
	onFormFilterChange,
	searchQuery,
	onSearchChange,
	dateRange,
	onDateRangeChange,
	formOptions = [],
} ) => {
	return (
		<div className="flex items-center gap-4">
			<div className="w-[150px]">
				<Select
					value={ statusFilter }
					onChange={ onStatusFilterChange }
					size="sm"
				>
					<Select.Button placeholder={ __( 'Status', 'sureforms' ) }>
						{
							STATUS_OPTIONS.find(
								( option ) => option.value === statusFilter
							)?.label
						}
					</Select.Button>
					<Select.Options className="z-999999">
						{ STATUS_OPTIONS.map( ( option ) => (
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
					onChange={ onFormFilterChange }
					size="sm"
				>
					<Select.Button
						placeholder={ __( 'All Forms', 'sureforms' ) }
					>
						{
							formOptions.find(
								( option ) => option.value === formFilter
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
				<DatePicker
					value={ dateRange }
					onChange={ onDateRangeChange }
					trigger={ ( { setShow } ) => (
						<Input
							type="text"
							placeholder="mm/dd/yyyy"
							readOnly
							onClick={ () => setShow( ( prev ) => ! prev ) }
							prefix={
								<Calendar className="w-4 h-4 text-icon-secondary" />
							}
						/>
					) }
				/>
			</div>

			<div className="w-[234px]">
				<Input
					type="search"
					placeholder={ __( 'Search your entry.', 'sureforms' ) }
					value={ searchQuery }
					onChange={ onSearchChange }
					prefix={
						<Search className="w-4 h-4 text-icon-secondary" />
					}
				/>
			</div>
		</div>
	);
};

export default EntriesFilters;
