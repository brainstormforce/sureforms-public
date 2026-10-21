import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Button, Switch, Tabs, toast } from '@bsf/force-ui';
import { useGlobalSettingsTabSaveAdapter } from '../tab-save-adapter';

const GROUP_LABELS = {
	core: __( 'Core', 'sureforms' ),
	payments: __( 'Payments', 'sureforms' ),
	pro: __( 'SureForms Pro', 'sureforms' ),
};

const BLOCK_GROUP_LABELS = {
	free: __( 'SureForms', 'sureforms' ),
	pro: __( 'SureForms Pro', 'sureforms' ),
	spectra: __( 'Layout blocks', 'sureforms' ),
};

/**
 * Each row's own saved switch, keyed by feature key or block name.
 *
 * @param {Object} rows Rows from Features::for_js() or Features::blocks_for_js().
 * @return {Object} Map of key to its switch.
 */
const switchesOf = ( rows ) =>
	Object.fromEntries(
		Object.entries( rows ).map( ( [ key, row ] ) => [ key, row.switch ] )
	);

/**
 * Rows grouped by their `group` key, in first-seen order.
 *
 * @param {Object} rows Rows keyed by feature key or block name.
 * @return {Array} Pairs of group and its [ key, row ] entries.
 */
const groupRows = ( rows ) => {
	const groups = {};
	Object.entries( rows ).forEach( ( [ key, row ] ) => {
		( groups[ row.group ] = groups[ row.group ] || [] ).push( [
			key,
			row,
		] );
	} );
	return Object.entries( groups );
};

/**
 * Settings → Features: switch whole features, and single blocks, off and on.
 *
 * Rows come from `window.srfmFeatures` and `window.srfmBlocks` (Features::for_js()
 * and Features::blocks_for_js()), so Pro rows show up without any Pro JS. The
 * header Save button saves both tabs in place; the WordPress admin menus and the
 * editor pick up the change on their next load, because a feature or block that
 * is off is not loaded at all.
 */
const FeaturesPage = () => {
	const [ features, setFeatures ] = useState( window.srfmFeatures || {} );
	const [ blocks, setBlocks ] = useState( window.srfmBlocks || {} );
	const [ switches, setSwitches ] = useState( () => switchesOf( features ) );
	const [ blockSwitches, setBlockSwitches ] = useState( () =>
		switchesOf( blocks )
	);
	const [ activeTab, setActiveTab ] = useState( 'features' );
	const [ inUse, setInUse ] = useState( null );
	const [ saving, setSaving ] = useState( false );

	const isDirty =
		Object.keys( switches ).some(
			( key ) => switches[ key ] !== features[ key ].switch
		) ||
		Object.keys( blockSwitches ).some(
			( name ) => blockSwitches[ name ] !== blocks[ name ].switch
		);

	// Whether every feature this one needs is switched on, using unsaved state.
	const parentsOn = ( key, seen = [] ) =>
		( features[ key ]?.depends_on || [] ).every(
			( parent ) =>
				seen.includes( parent ) ||
				( switches[ parent ] !== false &&
					parentsOn( parent, [ ...seen, key ] ) )
		);

	const save = ( confirm = false ) => {
		setSaving( true );
		apiFetch( {
			path: '/sureforms/v1/srfm-global-settings',
			method: 'POST',
			data: {
				srfm_tab: 'features-settings',
				features: switches,
				blocks: blockSwitches,
				confirm,
			},
		} )
			.then( ( res ) => {
				if ( res?.needs_confirmation ) {
					setInUse( res.in_use );
					setSaving( false );
					return;
				}
				// Settings tabs, isFeatureEnabled() and the editor read these globals.
				window.srfmFeatures = res.features;
				window.srfmBlocks = res.blocks;
				setFeatures( res.features );
				setBlocks( res.blocks );
				setSwitches( switchesOf( res.features ) );
				setBlockSwitches( switchesOf( res.blocks ) );
				setInUse( null );
				setSaving( false );
				toast.success( __( 'Settings saved.', 'sureforms' ) );
			} )
			.catch( () => {
				setSaving( false );
				toast.error( __( 'Could not save the features.', 'sureforms' ) );
			} );
	};

	// Saved from the page header's Save button, like the other settings tabs.
	useGlobalSettingsTabSaveAdapter( 'features-settings', {
		isDirty: isDirty && ! inUse,
		isSaving: saving && ! inUse,
		onSave: () => save(),
	} );

	const featureRows = groupRows( features ).map( ( [ group, rows ] ) => (
		<div key={ group } className="space-y-4">
			<h3 className="text-base font-semibold text-text-primary m-0">
				{ GROUP_LABELS[ group ] || group }
			</h3>
			{ rows.map( ( [ key, row ] ) => {
				const available = parentsOn( key );
				const needs = row.depends_on
					.map( ( parent ) => features[ parent ]?.label )
					.filter( Boolean )
					.join( ', ' );
				return (
					<div key={ key }>
						<Switch
							disabled={ ! available }
							value={ available && switches[ key ] }
							onChange={ ( value ) => {
								setInUse( null );
								setSwitches( { ...switches, [ key ]: value } );
							} }
							label={ {
								heading: row.label,
								description: available
									? row.description
									: sprintf(
										/* translators: %s: names of the features this one needs. */
										__(
											'Needs %s. Turn that on to use this feature.',
											'sureforms'
										),
										needs
									),
							} }
						/>
					</div>
				);
			} ) }
		</div>
	) );

	const blockRows = groupRows( blocks ).map( ( [ group, rows ] ) => (
		<div key={ group } className="space-y-4">
			<h3 className="text-base font-semibold text-text-primary m-0">
				{ BLOCK_GROUP_LABELS[ group ] || group }
			</h3>
			<div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
				{ rows.map( ( [ name, row ] ) => {
					// A block owned by a feature follows that feature's unsaved switch.
					const owner = row.feature ? features[ row.feature ] : null;
					return (
						<div key={ name }>
							<Switch
								disabled={ !! row.feature }
								value={
									row.feature
										? false !== switches[ row.feature ] &&
										  parentsOn( row.feature )
										: blockSwitches[ name ]
								}
								onChange={ ( value ) => {
									setInUse( null );
									setBlockSwitches( {
										...blockSwitches,
										[ name ]: value,
									} );
								} }
								label={ {
									heading: row.label,
									description: owner
										? sprintf(
											/* translators: %s: feature name. */
											__(
												'Follows %s on the Features tab.',
												'sureforms'
											),
											owner.label
										)
										: undefined,
								} }
							/>
						</div>
					);
				} ) }
			</div>
		</div>
	) );

	return (
		<div className="space-y-6">
			<p className="text-sm text-text-secondary m-0">
				{ __(
					'Turning a feature or block off never deletes its data. Turn it back on and everything is where you left it.',
					'sureforms'
				) }
			</p>

			{ inUse && (
				<div
					role="alert"
					className="p-4 rounded-md border border-solid border-alert-border-warning bg-alert-background-warning space-y-2"
				>
					<p className="text-sm font-semibold m-0">
						{ __(
							'These published forms use something you are turning off:',
							'sureforms'
						) }
					</p>
					<ul className="m-0 pl-5 list-disc text-sm">
						{ Object.entries( inUse ).map( ( [ key, forms ] ) =>
							forms.map( ( form ) => (
								<li key={ `${ key }-${ form.id }` }>
									{ sprintf(
										/* translators: 1: form title, 2: feature or block name. */
										__( '%1$s (%2$s)', 'sureforms' ),
										form.title ||
											__( '(no title)', 'sureforms' ),
										features[ key ]?.label ||
											blocks[ key ]?.label ||
											key
									) }
								</li>
							) )
						) }
					</ul>
					<p className="text-sm m-0">
						{ __(
							'They keep working without it, but a switched-off block is no longer shown in them. A form that takes payments stops accepting submissions until Payments is back on.',
							'sureforms'
						) }
					</p>
					<div className="flex gap-2">
						<Button
							variant="primary"
							size="sm"
							loading={ saving }
							onClick={ () => save( true ) }
						>
							{ __( 'Turn off anyway', 'sureforms' ) }
						</Button>
						<Button
							variant="outline"
							size="sm"
							onClick={ () => setInUse( null ) }
						>
							{ __( 'Cancel', 'sureforms' ) }
						</Button>
					</div>
				</div>
			) }

			<Tabs activeItem={ activeTab }>
				<Tabs.Group
					variant="rounded"
					onChange={ ( { value: { slug } } ) => setActiveTab( slug ) }
				>
					<Tabs.Tab
						slug="features"
						text={ __( 'Features', 'sureforms' ) }
					/>
					<Tabs.Tab slug="blocks" text={ __( 'Blocks', 'sureforms' ) } />
				</Tabs.Group>
				<Tabs.Panel slug="features">
					<div className="space-y-6 pt-4">{ featureRows }</div>
				</Tabs.Panel>
				<Tabs.Panel slug="blocks">
					<div className="space-y-6 pt-4">
						<p className="text-sm text-text-secondary m-0">
							{ __(
								'A block that is off disappears from the block inserter and is not loaded in the editor or on your site.',
								'sureforms'
							) }
						</p>
						{ blockRows }
					</div>
				</Tabs.Panel>
			</Tabs>
		</div>
	);
};

export default FeaturesPage;
