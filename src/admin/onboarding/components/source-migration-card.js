/**
 * SourceMigrationCard — a single radio card the onboarding migration step
 * uses to list each detected source plugin (Contact Form 7 / WPForms /
 * Gravity Forms / Ninja Forms). Clicking anywhere on the card selects it.
 *
 * The card renders one of three states based on counts coming back from
 * `/sureforms/v1/migrator/sources`:
 *
 *   - `pending > 0`              → enabled. Shows "N forms ready" plus an
 *                                  inline "· M already imported" hint when
 *                                  some of the source's forms have already
 *                                  been brought over on a previous run.
 *   - `pending === 0, imported > 0` → disabled with a "All forms imported"
 *                                  caption. The card stays visible so the
 *                                  user understands why nothing's offered
 *                                  for this source.
 *   - `installed: false`         → not rendered (filtered out upstream).
 *
 * @since x.x.x
 */

import { __, _n, sprintf } from '@wordpress/i18n';
import { Text } from '@bsf/force-ui';
import { Check, CheckCircle2 } from 'lucide-react';

const SourceMigrationCard = ( { source, selected, onSelect, disabled } ) => {
	const formCount = Number( source?.form_count ?? 0 );
	const importedCount = Number( source?.imported_count ?? 0 );
	const pendingCount = Math.max( 0, Number( source?.pending_count ?? formCount - importedCount ) );

	const allImported = pendingCount === 0 && importedCount > 0;
	const isDisabled = Boolean( disabled ) || allImported;

	const id = `srfm-onboarding-source-${ source?.key }`;

	const pendingLabel = sprintf(
		/* translators: %d: number of source forms still pending import. */
		_n( '%d form ready', '%d forms ready', pendingCount, 'sureforms' ),
		pendingCount
	);

	const importedLabel =
		importedCount > 0
			? sprintf(
				/* translators: %d: number of source forms already imported into SureForms. */
				_n(
					'%d already imported',
					'%d already imported',
					importedCount,
					'sureforms'
				),
				importedCount
			  )
			: '';

	return (
		<label
			htmlFor={ id }
			className={ `flex items-center justify-between gap-3 rounded-md border border-solid p-4 transition-colors focus-within:ring-2 focus-within:ring-focus focus-within:ring-offset-1 ${
				isDisabled
					? 'cursor-not-allowed bg-background-secondary border-border-subtle'
					: 'cursor-pointer'
			} ${
				selected && ! isDisabled
					? 'border-border-interactive bg-background-interactive-subtle'
					: 'border-border-subtle hover:border-border-strong'
			}`.trim() }
		>
			<div className="flex items-center gap-3">
				{ /*
				 * Native input kept for keyboard + screen-reader access; rendered
				 * off-screen via sr-only because the WordPress admin stylesheet
				 * paints `input[type=radio]:checked { background: var(--wp-admin-theme-color); }`
				 * with admin-theme blue and overrides Tailwind `accent-*` utilities.
				 * The visible selection state is the card border + the brand-orange
				 * dot drawn below.
				 */ }
				<input
					id={ id }
					type="radio"
					name="srfm-onboarding-source"
					value={ source?.key }
					checked={ !! selected && ! isDisabled }
					disabled={ isDisabled }
					onChange={ () => onSelect?.( source ) }
					className="sr-only"
				/>
				{ allImported ? (
					<CheckCircle2
						aria-hidden="true"
						className="size-4 shrink-0 text-icon-interactive"
					/>
				) : (
					<span
						aria-hidden="true"
						className={ `flex size-4 shrink-0 items-center justify-center rounded-full border border-solid transition-colors ${
							selected && ! isDisabled
								? 'border-icon-interactive bg-icon-interactive'
								: 'border-border-strong bg-background-primary'
						}` }
					>
						{ selected && ! isDisabled && (
							<span className="size-1.5 rounded-full bg-background-primary" />
						) }
					</span>
				) }
				<Text
					size={ 14 }
					weight={ 500 }
					color={ isDisabled ? 'secondary' : 'primary' }
				>
					{ source?.title ||
						__( '(unnamed source)', 'sureforms' ) }
				</Text>
			</div>
			<div className="flex items-center gap-1.5">
				{ allImported ? (
					<Text size={ 13 } color="secondary">
						{ __( 'All forms already imported', 'sureforms' ) }
					</Text>
				) : (
					<>
						{ selected && (
							<Check className="size-4 text-icon-interactive" />
						) }
						<Text size={ 13 } color="secondary">
							{ pendingLabel }
							{ importedLabel && (
								<>
									{ ' · ' }
									{ importedLabel }
								</>
							) }
						</Text>
					</>
				) }
			</div>
		</label>
	);
};

export default SourceMigrationCard;
