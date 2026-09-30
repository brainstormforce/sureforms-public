import { __ } from '@wordpress/i18n';
import { Text } from '@bsf/force-ui';
import { Plus, X } from 'lucide-react';
import { cn } from '@Utils/Helpers';

// Icon tile colors per starter accent (full class names so Tailwind keeps them).
const ACCENT_CLASSES = {
	blue: 'bg-indigo-50 text-indigo-600',
	orange: 'bg-orange-50 text-orange-600',
	purple: 'bg-violet-50 text-violet-600',
	green: 'bg-emerald-50 text-emerald-600',
};
const DEFAULT_ACCENT_CLASSES = 'bg-background-secondary text-icon-secondary';

/**
 * Starter prompt cards shown above the AI prompt box.
 *
 * @since x.x.x
 * @param {Object}   props
 * @param {Array}    props.starters          Starter prompts to render.
 * @param {string}   props.selectedStarterId Currently selected starter id, or null.
 * @param {Function} props.onToggle          Called with a starter when its card is clicked.
 */
const StarterPrompts = ( { starters, selectedStarterId, onToggle } ) => {
	if ( ! starters.length ) {
		return null;
	}

	return (
		<div
			role="group"
			aria-labelledby="srfm-starter-prompts-label"
			className="flex flex-col gap-2"
		>
			<Text
				as="span"
				id="srfm-starter-prompts-label"
				size={ 12 }
				weight={ 500 }
				color="secondary"
			>
				{ __( 'Start with an example', 'sureforms' ) }
			</Text>
			<div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
				{ starters.map( ( starter ) => {
					const isSelected = selectedStarterId === starter.id;
					const Icon = starter.icon;
					const TrailingIcon = isSelected ? X : Plus;

					return (
						<button
							key={ starter.id }
							type="button"
							aria-pressed={ isSelected }
							onClick={ () => onToggle( starter ) }
							className={ cn(
								'group flex w-full items-center gap-3 rounded-lg border border-solid p-3 text-left cursor-pointer transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-1',
								isSelected
									? 'border-border-ai-button bg-brand-background-50'
									: 'border-border-subtle bg-background-primary shadow-sm hover:border-border-strong'
							) }
						>
							{ Icon && (
								<span
									aria-hidden="true"
									className={ cn(
										'flex size-8 shrink-0 items-center justify-center rounded-md',
										ACCENT_CLASSES[ starter.accent ] ??
											DEFAULT_ACCENT_CLASSES
									) }
								>
									<Icon className="size-4" />
								</span>
							) }
							<span className="flex min-w-0 flex-1 flex-col gap-0.5">
								<Text
									as="span"
									size={ 14 }
									weight={ 600 }
									color="primary"
								>
									{ starter.title }
								</Text>
								{ starter.description && (
									<Text
										as="span"
										size={ 12 }
										color="secondary"
										className="hidden sm:block truncate"
									>
										{ starter.description }
									</Text>
								) }
							</span>
							<TrailingIcon
								aria-hidden="true"
								className={ cn(
									'size-4 shrink-0',
									isSelected
										? 'text-icon-interactive'
										: 'text-icon-secondary group-hover:text-icon-primary'
								) }
							/>
						</button>
					);
				} ) }
			</div>
		</div>
	);
};

export default StarterPrompts;
