import { __ } from '@wordpress/i18n';
import { cn } from '@Utils/Helpers';
import ICONS from './icons';

// Decorative drawings on the start screen cards (the cards hide them from
// assistive tech), so they are plain elements rather than form controls.

const Bar = ( { className } ) => (
	<div className={ cn( 'h-2.5 rounded bg-border-subtle', className ) } />
);

const Chip = ( { icon, label } ) => (
	<span className="inline-flex items-center h-[22px] px-2 gap-1 rounded-[5px] border border-solid border-field-color-disabled text-[11px] leading-none font-medium text-text-secondary whitespace-nowrap">
		<span className="inline-flex text-icon-secondary">{ icon }</span>
		{ label }
	</span>
);

/**
 * A prompt box with form type chips and a Generate button.
 *
 * @since x.x.x
 */
export const AiArt = () => (
	<div className="w-[84%] max-w-[300px]">
		<div className="flex flex-col min-h-[128px] gap-3.5 pt-3.5 px-3.5 pb-3 bg-background-primary border border-solid border-border-subtle rounded-[10px] shadow-md-blur-32">
			<div className="flex flex-col gap-[9px] pt-0.5">
				<Bar />
				<Bar className="w-[70%]" />
			</div>
			<div className="flex items-center mt-auto gap-1.5">
				<Chip
					icon={ ICONS.startPayment }
					label={ __( 'Payment', 'sureforms' ) }
				/>
				<Chip icon={ ICONS.startQuiz } label={ __( 'Quiz', 'sureforms' ) } />
				<span className="ms-auto h-[26px] px-2.5 inline-flex items-center gap-[5px] rounded-[5px] bg-brand-800/[0.16] text-brand-800/90 text-[11.5px] font-semibold whitespace-nowrap">
					{ ICONS.startSparkle }
					{ __( 'Generate', 'sureforms' ) }
				</span>
			</div>
		</div>
	</div>
);

// Parts of the small forms in the template drawing.
const MiniTitle = () => (
	<div className="h-[7px] w-[58%] mb-[3px] rounded-[3px] bg-field-color-disabled" />
);
const MiniLabel = ( { width = '30%' } ) => (
	<div
		className="h-1 rounded-sm bg-field-color-disabled"
		style={ { width } }
	/>
);
const MiniInput = ( { height = 15 } ) => (
	<div
		className="mb-[3px] rounded border border-solid border-field-color-disabled bg-background-primary"
		style={ { height } }
	/>
);
const MiniSubmit = () => (
	<div className="relative mt-auto h-[15px] w-[46%] rounded bg-brand-800/[0.26] after:content-[''] after:absolute after:inset-x-[14%] after:top-1/2 after:h-[3px] after:-translate-y-1/2 after:rounded-sm after:bg-background-primary/85" />
);

const MiniForm = ( { className, children } ) => (
	<div
		className={ cn(
			'absolute flex flex-col gap-[5px] pt-[11px] px-[11px] pb-3 bg-background-primary rounded-lg shadow-md-blur-32',
			className
		) }
	>
		<MiniTitle />
		{ children }
		<MiniSubmit />
	</div>
);

/**
 * Three small forms fanned out, mirrored in right-to-left languages.
 *
 * @since x.x.x
 */
export const TemplateArt = () => (
	<div className="relative w-[82%] max-w-[300px] h-40">
		<MiniForm className="start-0 top-4 w-[44%] h-[132px] -rotate-[4deg] rtl:rotate-[4deg]">
			<MiniLabel />
			<MiniInput />
			<MiniLabel width="38%" />
			<MiniInput />
			<MiniLabel />
			<MiniInput />
		</MiniForm>
		<MiniForm className="end-0 top-0.5 w-[44%] h-[132px] rotate-[3deg] rtl:-rotate-[3deg]">
			<MiniLabel />
			<MiniInput />
			<MiniLabel width="42%" />
			<MiniInput height={ 26 } />
		</MiniForm>
		<MiniForm className="start-1/4 top-[22px] w-1/2 h-[134px] z-[2]">
			<div className="flex gap-1.5">
				{ [ 0, 1 ].map( ( i ) => (
					<div key={ i } className="flex flex-1 flex-col gap-[5px]">
						<MiniLabel />
						<MiniInput />
					</div>
				) ) }
			</div>
			<MiniLabel width="26%" />
			<MiniInput />
			<MiniLabel width="34%" />
			<MiniInput height={ 20 } />
		</MiniForm>
	</div>
);
