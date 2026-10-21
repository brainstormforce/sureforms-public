import { __, sprintf } from '@wordpress/i18n';
import { Check, RotateCcw } from 'lucide-react';
import { cn } from '@Utils/Helpers';
import { firstHex, isDark } from '../template-colors.js';

// Curated picks; the last swatch in each row opens the full color picker.
const BACKGROUNDS = [
	{ value: '#FFFFFF', name: __( 'White', 'sureforms' ) },
	{ value: '#F8FAFC', name: __( 'Cloud gray', 'sureforms' ) },
	{ value: '#FFF7ED', name: __( 'Warm cream', 'sureforms' ) },
	{ value: '#F0FDF4', name: __( 'Soft mint', 'sureforms' ) },
	{ value: '#EFF6FF', name: __( 'Pale sky', 'sureforms' ) },
	{ value: '#FDF2F8', name: __( 'Blush pink', 'sureforms' ) },
	{ value: '#1F2937', name: __( 'Charcoal', 'sureforms' ) },
	{ value: '#0F172A', name: __( 'Midnight navy', 'sureforms' ) },
];
const ACCENTS = [
	{ value: '#2563EB', name: __( 'Ocean blue', 'sureforms' ) },
	{ value: '#7C3AED', name: __( 'Violet', 'sureforms' ) },
	{ value: '#DB2777', name: __( 'Raspberry', 'sureforms' ) },
	{ value: '#E4572E', name: __( 'Coral', 'sureforms' ) },
	{ value: '#D97706', name: __( 'Amber', 'sureforms' ) },
	{ value: '#059669', name: __( 'Emerald', 'sureforms' ) },
	{ value: '#0891B2', name: __( 'Teal', 'sureforms' ) },
	{ value: '#111827', name: __( 'Ink black', 'sureforms' ) },
];

const ringClass =
	'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-border-interactive';

/**
 * Translated name of a style preset.
 *
 * @param {Object} preset Preset: { id, label? }.
 * @return {string} Name.
 */
const presetName = ( preset ) => {
	const names = {
		ocean: __( 'Ocean', 'sureforms' ),
		midnight: __( 'Midnight', 'sureforms' ),
		mint: __( 'Mint', 'sureforms' ),
		lavender: __( 'Lavender', 'sureforms' ),
		sunset: __( 'Sunset', 'sureforms' ),
		sand: __( 'Sand', 'sureforms' ),
	};
	return (
		names[ preset.id ] ||
		preset.label ||
		preset.id.charAt( 0 ).toUpperCase() + preset.id.slice( 1 )
	);
};

/**
 * One labelled group in the panel.
 *
 * @param {Object} props          Props.
 * @param {string} props.label    Group label.
 * @param {*}      props.children Controls.
 */
const Group = ( { label, children } ) => (
	<div className="flex flex-col gap-2" role="group" aria-label={ label }>
		<span className="text-xs font-medium text-text-secondary">
			{ label }
		</span>
		{ children }
	</div>
);

/**
 * Row of color swatches plus a custom color picker.
 *
 * @param {Object}   props          Props.
 * @param {Object[]} props.colors   Swatches: { value, name }.
 * @param {string}   props.value    Selected color, '' for the preset's own.
 * @param {Function} props.onChange Called with a hex color.
 * @param {string}   props.label    Accessible name of the group.
 */
const Swatches = ( { colors, value, onChange, label } ) => {
	const isCustom =
		'' !== value && ! colors.some( ( color ) => color.value === value );

	return (
		<div className="flex flex-wrap gap-2">
			{ colors.map( ( color ) => (
				<button
					key={ color.value }
					type="button"
					aria-pressed={ value === color.value }
					aria-label={ sprintf(
						/* translators: 1: color group, e.g. "Background", 2: color name, e.g. "Ocean blue". */
						__( '%1$s: %2$s', 'sureforms' ),
						label,
						color.name
					) }
					title={ color.name }
					onClick={ () => onChange( color.value ) }
					className={ cn(
						'relative size-8 p-0 rounded-full border border-solid border-border-subtle cursor-pointer transition-transform hover:scale-110 motion-reduce:transition-none motion-reduce:hover:scale-100',
						ringClass,
						value === color.value &&
							'ring-2 ring-offset-2 ring-border-interactive'
					) }
					style={ { background: color.value } }
				>
					{ value === color.value && (
						<Check
							className="absolute inset-0 m-auto size-4"
							strokeWidth={ 3 }
							style={ {
								color: isDark( color.value )
									? '#FFFFFF'
									: '#0F172A',
							} }
							aria-hidden="true"
						/>
					) }
				</button>
			) ) }
			<label
				className={ cn(
					'relative size-8 rounded-full border border-solid border-border-subtle cursor-pointer overflow-hidden focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-border-interactive',
					isCustom && 'ring-2 ring-offset-2 ring-border-interactive'
				) }
				title={ __( 'Custom color', 'sureforms' ) }
				style={ {
					background: isCustom
						? value
						: 'conic-gradient(#F87171, #FBBF24, #34D399, #60A5FA, #A78BFA, #F472B6, #F87171)',
				} }
			>
				<span className="sr-only">
					{ sprintf(
						/* translators: %s: color group. */
						__( 'Custom %s color', 'sureforms' ),
						label
					) }
				</span>
				<input
					type="color"
					value={ value || '#FFFFFF' }
					onChange={ ( event ) =>
						onChange( event.target.value.toUpperCase() )
					}
					className="absolute inset-0 size-full opacity-0 cursor-pointer"
				/>
			</label>
		</div>
	);
};

/**
 * Minimal style controls beside the template preview. Every change is
 * rendered by the real form renderer, and the form is created with exactly
 * this style.
 *
 * @param {Object}   props           Props.
 * @param {Object}   props.style     { preset, background, accent }.
 * @param {Function} props.onChange  Called with the next style.
 * @param {string}   props.theme     The template's own preset id.
 * @param {Array}    props.presets   Style presets; defaults to the admin page's.
 * @param {string}   props.className Classes for the panel, e.g. to show it as a bottom sheet.
 * @param {Function} props.onClose   When given, shows a "Done" button that calls it.
 * @param {string}   props.id        Element id, for the button that toggles the panel.
 */
const TemplateStylePanel = ( {
	style,
	onChange,
	theme,
	presets: givenPresets,
	className,
	onClose,
	id,
} ) => {
	const presets = givenPresets || srfm_admin?.form_template_themes || [];
	// A template on a dark preset starts with its light look, which is none
	// of the presets.
	const activePreset =
		style.preset ||
		( presets.find( ( preset ) => preset.id === theme )?.default
			? ''
			: theme );
	const isCustomized = Object.keys( style ).length > 0;

	return (
		<aside
			id={ id }
			className={ cn(
				'w-72 shrink-0 flex flex-col gap-6 p-5 overflow-y-auto bg-background-primary border-0 border-l border-solid border-border-subtle',
				className
			) }
			aria-label={ __( 'Customize the template', 'sureforms' ) }
		>
			<div className="flex items-center justify-between gap-3">
				<span className="text-sm font-semibold text-text-primary">
					{ __( 'Customize', 'sureforms' ) }
				</span>
				<span className="flex-1" />
				{ isCustomized && (
					<button
						type="button"
						onClick={ () => onChange( {} ) }
						className={ cn(
							'flex items-center gap-1 p-0 text-xs text-text-secondary bg-transparent border-0 cursor-pointer hover:text-text-primary',
							ringClass
						) }
					>
						<RotateCcw className="size-3" aria-hidden="true" />
						{ __( 'Reset', 'sureforms' ) }
					</button>
				) }
				{ onClose && (
					<button
						type="button"
						onClick={ onClose }
						className={ cn(
							'px-2.5 py-1 text-xs font-semibold rounded-md border-0 cursor-pointer bg-button-primary text-text-on-color',
							ringClass
						) }
					>
						{ __( 'Done', 'sureforms' ) }
					</button>
				) }
			</div>

			<Group label={ __( 'Style', 'sureforms' ) }>
				<div className="grid grid-cols-3 gap-2">
					{ presets.map( ( preset ) => {
						// A light preset shows its page color, a dark one its form.
						const tile = isDark( firstHex( preset.background ) )
							? preset.background
							: preset.page || preset.background;
						const active =
							activePreset === preset.id &&
							! style.background &&
							! style.accent;
						return (
							<button
								key={ preset.id }
								type="button"
								aria-pressed={ active }
								onClick={ () =>
									// A preset is a whole look: it replaces the picked colors.
									onChange( { preset: preset.id } )
								}
								className={ cn(
									'relative flex flex-col items-start gap-1 p-0 bg-transparent border-0 cursor-pointer',
									ringClass
								) }
							>
								<span
									className={ cn(
										'relative flex flex-col justify-center gap-1 w-full h-14 px-2 rounded-md border border-solid transition-shadow motion-reduce:transition-none',
										active
											? 'border-border-interactive ring-2 ring-border-interactive'
											: 'border-border-subtle hover:shadow-md'
									) }
									style={ { background: tile } }
									aria-hidden="true"
								>
									{ /* A tiny form: two fields and the button. */ }
									{ [ 0, 1 ].map( ( row ) => (
										<span
											key={ row }
											className="block h-1.5 w-4/5 rounded-full opacity-25"
											style={ {
												background: isDark(
													firstHex( tile )
												)
													? '#FFFFFF'
													: '#0F172A',
											} }
										/>
									) ) }
									<span
										className="block h-2.5 w-full mt-0.5 rounded-sm"
										style={ { background: preset.accent } }
									/>
									{ active && (
										<span className="absolute -top-1.5 -right-1.5 flex items-center justify-center size-4 rounded-full bg-button-primary text-text-on-color shadow-sm">
											<Check
												className="size-3"
												strokeWidth={ 3 }
											/>
										</span>
									) }
								</span>
								<span className="text-[11px] text-text-secondary">
									{ presetName( preset ) }
								</span>
							</button>
						);
					} ) }
				</div>
			</Group>

			<Group label={ __( 'Background', 'sureforms' ) }>
				<Swatches
					colors={ BACKGROUNDS }
					value={ style.background || '' }
					label={ __( 'Background', 'sureforms' ) }
					onChange={ ( background ) =>
						onChange( { ...style, background } )
					}
				/>
			</Group>

			<Group label={ __( 'Primary Color', 'sureforms' ) }>
				<Swatches
					colors={ ACCENTS }
					value={ style.accent || '' }
					label={ __( 'Primary Color', 'sureforms' ) }
					onChange={ ( accent ) => onChange( { ...style, accent } ) }
				/>
			</Group>

			<p className="m-0 mt-auto text-xs text-text-tertiary">
				{ __(
					"Text colors adjust automatically so the form stays readable. You can change everything later in the form's Style tab.",
					'sureforms'
				) }
			</p>
		</aside>
	);
};

export default TemplateStylePanel;
