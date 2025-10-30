import { isValidElement } from '@wordpress/element';
import { sprintf, _n, __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import EntryEdit from './EntryEdit';
import { decodeHTMLEntities } from '../utils/entryHelpers';

/**
 * Render field value - handles both regular and repeater fields
 *
 * @param {Field} field - Field value (can be string, array for repeater, etc.)
 * @return {string} Rendered value
 */
const formatField = ( field ) => {
	const renderProFields = applyFilters(
		'srfm-pro.entry-details.render-pro-fields'
	);

	// Handle repeater fields and other PRO fields
	if ( typeof renderProFields === 'function' ) {
		return renderProFields( field );
	}
	const { value, label } = field;

	// Handle repeater fields (array of objects)
	if ( Array.isArray( field?.value ) ) {
		// For repeater fields, show a count or summary
		return {
			label,
			value: sprintf(
				// translators: %d: number of items
				_n( '%d item', '%d items', value.length, 'sureforms' ),
				value.length
			),
		};
	}

	let decodedValue = value;
	if ( typeof value === 'string' && value.match( /&[a-zA-Z0-9#]+;/ ) ) {
		// If field value contains encoded html entities, decode them
		decodedValue = decodeHTMLEntities( value );
	}

	// Handle regular fields
	return {
		...field,
		label,
		value: decodedValue || '-',
	};
};

/**
 * Render a single field
 *
 * @typedef {Object} Field
 * @property {string}                           label       - Field label
 * @property {string | Array | Object | number} value       - Field value
 *
 * @param    {Object}                           props
 * @param    {Field}                            props.field - Field object with label and value
 * @return {JSX.Element} RenderField component
 */
export const RenderField = ( props ) => {
	const { field } = props;

	if ( Array.isArray( field ) ) {
		return field.map( ( item, idx ) => (
			<RenderField key={ `${ field.label }-${ idx }` } field={ item } />
		) );
	}

	return (
		<>
			<div className="p-3 relative bg-background-primary rounded-md shadow-sm">
				<div className="flex gap-4">
					{ field?.label && (
						<div className="w-40 flex-shrink-0">
							<span className="text-sm font-semibold text-text-primary">
								{ field.label }
							</span>
						</div>
					) }
					{ Array.isArray( field.value ) &&
						isValidElement( field.value[ 0 ] ) && (
						<div className="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 place-content-center gap-2">
							{ field.value }
						</div>
					) }
					{ ! Array.isArray( field.value ) && (
						<div className="flex-1">
							<span className="text-sm font-medium text-text-secondary [overflow-wrap:anywhere]">
								{ field?.value ?? '-' }
							</span>
						</div>
					) }
				</div>
			</div>
			{ Array.isArray( field.value ) &&
				! isValidElement( field.value[ 0 ] ) &&
				field.value.map( ( item, idx ) => (
					<RenderField
						key={ `${ field.label }-${ idx }` }
						field={ item }
					/>
				) ) }
		</>
	);
};

/**
 * EntryDataSection Component
 * Displays the form fields and their values for an entry
 *
 * @param {Object} props
 * @param {Object} props.entryData - The entry data object
 */
const EntryDataSection = ( { entryData } ) => {
	// Extract and transform form data fields from entry data
	const fields = ( entryData?.formData || [] ).map( formatField );

	return (
		<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
			<div className="pb-0 px-4 pt-4">
				<div className="flex items-center justify-between">
					<h3 className="text-base font-semibold text-text-primary">
						{ __( 'Entry Data', 'sureforms' ) }
					</h3>
					<EntryEdit entry={ entryData } />
				</div>
			</div>
			<div className="p-4 space-y-1 relative before:content-[''] before:block before:absolute before:inset-3 before:bg-background-secondary before:rounded-lg">
				{ fields.map( ( field, index ) => (
					<RenderField key={ index } field={ field } />
				) ) }
			</div>
		</div>
	);
};

export default EntryDataSection;
