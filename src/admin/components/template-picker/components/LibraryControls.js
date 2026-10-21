import { __, _n, sprintf } from '@wordpress/i18n';
import {
	createInterpolateElement,
	forwardRef,
	useEffect,
	useRef,
} from '@wordpress/element';
import { Button, ButtonGroup, Input, Select, Text } from '@bsf/force-ui';
import { ChevronDown, Plus, Search, Sparkles, X } from 'lucide-react';
import { cn } from '@Utils/Helpers';

/**
 * Whether AI form generation can run now. Out of generations, or an error
 * from the AI service, means the AI shortcuts are left out.
 *
 * @since x.x.x
 * @return {boolean} AI can generate a form.
 */
export const canGenerateWithAi = () => {
	const usage = window.srfm_admin?.srfm_ai_usage_details;
	return ! ( 0 === usage?.remaining || Boolean( usage?.code ) );
};

/**
 * URL of a new, empty form.
 *
 * @since x.x.x
 * @return {string} URL.
 */
export const getScratchUrl = () =>
	`${ window.srfm_admin?.site_url || '' }/wp-admin/post-new.php?post_type=sureforms_form`;

// Brand focus ring shown only for keyboard focus.
const FOCUS_RING =
	'focus:ring-0 focus:ring-offset-0 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-toggle-on';

/**
 * "Start from scratch" and "Build with AI". Sizes: `header` (36px, icon-only
 * on phones) or `large` (44px).
 *
 * @param {Object}   props           Props.
 * @param {Function} props.onAi      Open the AI builder. Left out when AI can't run.
 * @param {string}   props.size      'header' or 'large'.
 * @param {boolean}  props.aiFirst   Show the AI button first, as the main action.
 * @param {string}   props.aiLabel   AI button text.
 * @param {string}   props.className Extra classes for the row.
 */
export const CreateButtons = ( {
	onAi,
	size = 'large',
	aiFirst = false,
	aiLabel = __( 'Build with AI', 'sureforms' ),
	className,
} ) => {
	const isHeader = 'header' === size;
	const base = cn(
		'shrink-0 font-semibold whitespace-nowrap [&>span]:px-0 gap-1.5',
		isHeader
			? 'h-9 px-3 text-[13.5px] rounded-lg max-[620px]:w-9 max-[620px]:px-0 max-[620px]:gap-0'
			: 'h-11 px-4 text-sm rounded-lg',
		FOCUS_RING
	);
	const label = ( text ) =>
		isHeader ? <span className="max-[620px]:sr-only">{ text }</span> : text;
	const outline = cn(
		base,
		'bg-background-primary text-[#374151] outline-[#D1D5DB] shadow-none hover:bg-background-secondary hover:outline-[#C9CDD4]'
	);

	const scratch = (
		<Button
			key="scratch"
			tag="a"
			size="sm"
			href={ getScratchUrl() }
			variant="outline"
			icon={ <Plus className="size-4" aria-hidden="true" /> }
			className={ cn( outline, 'no-underline hover:text-[#374151]' ) }
		>
			{ label( __( 'Start from scratch', 'sureforms' ) ) }
		</Button>
	);
	const ai = onAi && (
		<Button
			key="ai"
			size="sm"
			variant={ isHeader ? 'outline' : 'primary' }
			icon={ <Sparkles className="size-4" aria-hidden="true" /> }
			onClick={ onAi }
			className={
				isHeader
					? cn(
						outline,
						'text-brand-800 outline-[rgba(213,68,7,0.35)] hover:bg-brand-background-50 hover:outline-[rgba(213,68,7,0.35)]'
					  )
					: cn(
						base,
						'bg-brand-800 outline-brand-800 hover:bg-brand-hover-700 hover:outline-brand-hover-700'
					  )
			}
		>
			{ label( aiLabel ) }
		</Button>
	);

	return (
		<div
			className={ cn(
				'flex flex-wrap items-center',
				isHeader ? 'gap-2 max-[620px]:gap-1.5' : 'gap-2.5',
				className
			) }
		>
			{ aiFirst ? [ ai, scratch ] : [ scratch, ai ] }
		</div>
	);
};

/**
 * Search box with a clear button. `hero` is the big one under the title;
 * `compact` sits in the header once the big one scrolls away.
 */
export const TemplateSearch = forwardRef(
	(
		{
			value,
			onChange,
			placeholder,
			variant = 'hero',
			className,
			...rest
		},
		ref
	) => {
		const inputRef = useRef( null );
		const isHero = 'hero' === variant;
		const setRefs = ( node ) => {
			inputRef.current = node;
			if ( 'function' === typeof ref ) {
				ref( node );
			} else if ( ref ) {
				ref.current = node;
			}
		};

		return (
			<div className={ cn( 'relative w-full', className ) }>
				<Input
					ref={ setRefs }
					type="search"
					size={ isHero ? 'lg' : 'sm' }
					value={ value }
					onChange={ ( next ) => onChange( next ?? '' ) }
					placeholder={ placeholder }
					aria-label={ __( 'Search templates', 'sureforms' ) }
					autoComplete="off"
					prefix={ <Search aria-hidden="true" /> }
					className={ cn(
						'[&>input]:m-0 [&>input]:border-0 [&>input]:text-text-primary [&>input::-webkit-search-cancel-button]:hidden [&>input:focus]:ring-0 [&>input:focus]:ring-offset-0 [&>input:focus]:outline-brand-800 [&>input:focus]:shadow-[0_0_0_3px_rgba(213,68,7,0.10)]',
						'[&>div:first-child>svg]:text-icon-secondary',
						// force-ui pins the icon to the physical left; move it to the start in RTL.
						'rtl:[&>div:first-child]:left-auto rtl:[&>div:first-child]:right-0 rtl:[&>div:first-child]:pl-0',
						isHero
							? '[&>input]:h-[50px] [&>input]:rounded-[10px] [&>input]:text-[15px] [&>input]:ps-[46px] [&>input]:pe-12 [&>input]:outline-border-subtle [&>input]:shadow-[0_1px_2px_rgba(17,24,39,0.04),0_8px_22px_-8px_rgba(17,24,39,0.12)] [&>input:hover]:outline-[#D1D5DB] [&>input:hover]:shadow-[0_1px_2px_rgba(17,24,39,0.06)] [&>div:first-child]:ps-4 rtl:[&>div:first-child]:pr-4 [&>div:first-child>svg]:size-[18px]'
							: '[&>input]:h-[38px] [&>input]:rounded-[10px] [&>input]:text-sm [&>input]:ps-9 [&>input]:pe-9 [&>input]:bg-background-tertiary [&>input]:outline-transparent [&>input:hover]:bg-[#ECEEF1] [&>input:hover]:outline-transparent [&>input:focus]:bg-background-primary [&>input]:placeholder:text-[#8A9099] rtl:[&>div:first-child]:pr-3'
					) }
					{ ...rest }
				/>
				{ value && (
					<Button
						variant="ghost"
						size="xs"
						aria-label={ __( 'Clear search', 'sureforms' ) }
						icon={
							<X
								className={ isHero ? 'size-4' : 'size-3.5' }
								aria-hidden="true"
							/>
						}
						onClick={ () => {
							onChange( '' );
							inputRef.current?.focus();
						} }
						className={ cn(
							'absolute z-20 top-1/2 -translate-y-1/2 flex items-center justify-center p-0 rounded-full text-text-tertiary hover:text-text-primary hover:bg-background-tertiary',
							FOCUS_RING,
							isHero ? 'end-[9px] size-8' : 'end-1.5 size-6'
						) }
					/>
				) }
			</div>
		);
	}
);

/**
 * Dropdown filter with a count on each option. Options can be chosen even
 * when they would show nothing.
 *
 * @param {Object}   props          Props.
 * @param {string}   props.name     Filter name, e.g. "Category".
 * @param {string}   props.label    Filter name as shown, e.g. "Category:".
 * @param {string}   props.value    Chosen option id; `all` for none.
 * @param {Array}    props.options  { id, label, count, short } — `short` is shown on the button.
 * @param {Function} props.onChange Called with an option id.
 */
export const FilterSelect = ( { name, label, value, options, onChange } ) => {
	const chosen = options.find( ( option ) => option.id === value );
	const isSet = 'all' !== value;

	return (
		<div className="shrink-0 min-w-0 max-[560px]:flex-1 max-[560px]:basis-[140px]">
			<Select value={ value } onChange={ onChange } size="md">
				<Select.Button
					// A combobox doesn't take its name from its text.
					aria-label={ name }
					icon={ <ChevronDown aria-hidden="true" /> }
					className={ cn(
						'h-11 min-h-0 w-auto max-[560px]:w-full ps-3.5 pe-3 py-0 gap-1.5 [&>div:last-child]:ms-1.5 rounded-[10px] outline-border-subtle [&:hover:not(:focus):not(:disabled):not([aria-expanded=true])]:outline-[#D1D5DB]',
						FOCUS_RING,
						'aria-expanded:outline-brand-800 aria-expanded:shadow-[0_0_0_3px_rgba(213,68,7,0.10)]',
						'[&>div:last-child>svg]:size-4 [&>div:last-child>svg]:text-text-tertiary [&>div:last-child>svg]:transition-transform aria-expanded:[&>div:last-child>svg]:rotate-180',
						isSet &&
							'bg-brand-background-50 outline-[rgba(213,68,7,0.4)] [&:hover:not(:focus):not(:disabled):not([aria-expanded=true])]:outline-[rgba(213,68,7,0.4)] [&>div:last-child>svg]:text-brand-800'
					) }
				>
					<span className="inline-flex items-center gap-1.5 text-sm">
						<span
							className={
								isSet ? 'text-[#8A3A12]' : 'text-icon-secondary'
							}
						>
							{ label }
						</span>
						<span
							className={ cn(
								'font-semibold truncate',
								isSet ? 'text-brand-800' : 'text-text-primary'
							) }
						>
							{ chosen?.short || chosen?.label }
						</span>
					</span>
				</Select.Button>
				<Select.Options className="!z-30 !w-max !min-w-[240px] !max-w-[min(320px,calc(100vw-32px))] !max-h-[min(460px,60vh)] rounded-xl outline-border-subtle shadow-[0_18px_40px_-14px_rgba(17,24,39,0.22),0_2px_6px_rgba(17,24,39,0.05)] [&>div]:p-1.5">
					{ options.map( ( option ) => (
						<Select.Option
							key={ option.id }
							value={ option.id }
							selected={ option.id === value }
							className={ cn(
								'h-[38px] px-3 gap-3 rounded-lg text-sm font-medium text-[#374151] hover:bg-[#F6F7F9] [&>svg]:size-[15px] [&>svg]:shrink-0 [&>svg]:text-brand-800',
								option.id === value &&
									'bg-brand-background-50 hover:bg-brand-background-50 text-[#8A3A12] font-bold'
							) }
						>
							<span className="flex items-center justify-between gap-3">
								<span className="truncate">
									{ option.label }
								</span>
								<span className="text-xs font-normal text-text-tertiary tabular-nums">
									{ option.count }
								</span>
							</span>
						</Select.Option>
					) ) }
				</Select.Options>
			</Select>
		</div>
	);
};

/**
 * All / Free / Premium switch.
 *
 * @param {Object}   props          Props.
 * @param {string}   props.value    'all', 'free' or 'premium'.
 * @param {Function} props.onChange Called with the chosen value.
 */
export const PlanSwitch = ( { value, onChange } ) => {
	const plans = [
		{ slug: 'all', text: __( 'All', 'sureforms' ) },
		{ slug: 'free', text: __( 'Free', 'sureforms' ) },
		{ slug: 'premium', text: __( 'Premium', 'sureforms' ) },
	];
	return (
		<div
			role="group"
			aria-label={ __( 'Plan', 'sureforms' ) }
			className="order-2"
		>
			<ButtonGroup.Group
				activeItem={ value }
				onChange={ ( { value: chosen } ) => onChange( chosen.slug ) }
				size="sm"
				className="gap-0.5 p-1 max-[760px]:p-[3px] bg-background-primary rounded-[10px]"
			>
				{ plans.map( ( plan ) => (
					<ButtonGroup.Button
						key={ plan.slug }
						slug={ plan.slug }
						text={ plan.text }
						aria-pressed={ value === plan.slug }
						className={ cn(
							'h-[34px] py-0 px-3.5 max-[760px]:px-3 border-0 rounded-[7px] text-sm transition-colors',
							FOCUS_RING,
							value === plan.slug
								? 'bg-background-tertiary hover:bg-background-tertiary font-semibold text-text-primary'
								: 'bg-transparent font-medium text-text-secondary hover:bg-transparent hover:text-text-primary'
						) }
					/>
				) ) }
			</ButtonGroup.Group>
		</div>
	);
};

/**
 * "46 available", announced as it changes.
 *
 * @param {Object} props       Props.
 * @param {number} props.count Templates shown.
 */
export const ResultCount = ( { count } ) => (
	<Text
		as="span"
		role="status"
		className="text-sm text-icon-secondary whitespace-nowrap max-[760px]:order-3 max-[760px]:basis-full"
	>
		{ count
			? createInterpolateElement(
				sprintf(
					/* translators: %d: number of templates. */
					_n(
						'<strong>%d</strong> available',
						'<strong>%d</strong> available',
						count,
						'sureforms'
					),
					count
				),
				{
					strong: (
						<strong className="font-semibold text-text-primary" />
					),
				}
			  )
			: __( 'No templates', 'sureforms' ) }
	</Text>
);

/**
 * Shown when nothing matches the search and filters.
 *
 * @param {Object}   props           Props.
 * @param {boolean}  props.canCreate Offer to start from scratch or with AI.
 * @param {Function} props.onAi      Open the AI builder; left out when AI can't run.
 */
export const NoTemplates = ( { canCreate, onAi } ) => {
	let hint = __( 'Try other words, or change the filters.', 'sureforms' );
	if ( canCreate ) {
		hint = onAi
			? __(
				'Describe what you need and AI will build it, or lay one out yourself.',
				'sureforms'
			  )
			: __( 'Lay one out yourself from an empty form.', 'sureforms' );
	}
	return (
		<div className="flex flex-col items-center px-6 py-14 mb-[72px] text-center bg-background-primary border border-dashed border-[#D1D5DB] rounded-[10px]">
			<Text className="mb-1.5 text-[15px] font-semibold text-text-primary">
				{ __( 'No template matches that', 'sureforms' ) }
			</Text>
			<Text className="text-[13px] text-icon-secondary">{ hint }</Text>
			{ canCreate && (
				<CreateButtons
					onAi={ onAi }
					aiFirst
					aiLabel={ __( 'Build it with AI', 'sureforms' ) }
					className="justify-center mt-4"
				/>
			) }
		</div>
	);
};

/**
 * Under a short list of results: offers to start from scratch or with AI.
 *
 * @param {Object}   props      Props.
 * @param {Function} props.onAi Open the AI builder; left out when AI can't run.
 */
export const NotQuiteCard = ( { onAi } ) => (
	<div className="pt-1 pb-9">
		<div className="flex flex-wrap items-center justify-between gap-[18px] px-7 py-[26px] bg-background-primary border border-solid border-border-subtle rounded-xl">
			<div className="flex flex-col gap-1">
				<Text
					as="h2"
					className="text-lg font-semibold tracking-[-0.01em] text-text-primary"
				>
					{ __( 'Not quite what you wanted?', 'sureforms' ) }
				</Text>
				<Text className="text-sm text-icon-secondary">
					{ onAi
						? __(
							'Describe your form and let AI build it, or lay it out yourself from an empty form.',
							'sureforms'
						  )
						: __(
							'Lay it out yourself from an empty form.',
							'sureforms'
						  ) }
				</Text>
			</div>
			<CreateButtons
				onAi={ onAi }
				aiLabel={ __( 'Generate with AI', 'sureforms' ) }
			/>
		</div>
	</div>
);

/**
 * Calls `onChange` with whether an element is in view below the header.
 *
 * @since x.x.x
 * @param {Object}   ref      Ref of the element.
 * @param {Function} onChange Called with true or false.
 * @param {number}   offset   Height of the fixed header.
 */
export const useInView = ( ref, onChange, offset = 56 ) => {
	useEffect( () => {
		const node = ref.current;
		if ( ! node || ! onChange || ! window.IntersectionObserver ) {
			return;
		}
		const observer = new window.IntersectionObserver(
			( [ entry ] ) =>
				onChange(
					entry.isIntersecting || entry.boundingClientRect.top > 0
				),
			{ rootMargin: `-${ offset }px 0px 0px 0px` }
		);
		observer.observe( node );
		return () => observer.disconnect();
	}, [ ref, onChange, offset ] );
};
