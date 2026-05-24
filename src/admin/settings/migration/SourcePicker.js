/**
 * SourcePicker — step 1 of the migration wizard.
 *
 * Renders a tile grid of importable source plugins (Contact Form 7, WPForms,
 * Gravity Forms, …). Each tile shows whether the source plugin is active and
 * how many forms it has. Clicking an installed tile moves the wizard forward.
 *
 * @since x.x.x
 */

import { useEffect, useState } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import { Badge, Button, Container, Loader, Text, Title } from '@bsf/force-ui';
import { ArrowRight, CheckCircle2, CircleSlash } from 'lucide-react';
import { listSources } from './api';

const SourcePicker = ( { onSelect } ) => {
	const [ sources, setSources ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( '' );

	useEffect( () => {
		let cancelled = false;
		setLoading( true );
		listSources()
			.then( ( res ) => {
				if ( cancelled ) {
					return;
				}
				setSources( Array.isArray( res?.sources ) ? res.sources : [] );
				setLoading( false );
			} )
			.catch( () => {
				if ( cancelled ) {
					return;
				}
				setError(
					__(
						'Could not load the list of importable plugins.',
						'sureforms'
					)
				);
				setLoading( false );
			} );
		return () => {
			cancelled = true;
		};
	}, [] );

	if ( loading ) {
		return (
			<div className="flex items-center justify-center p-12">
				<Loader />
			</div>
		);
	}

	if ( error ) {
		return (
			<Text size={ 14 } className="text-text-error">
				{ error }
			</Text>
		);
	}

	if ( sources.length === 0 ) {
		return (
			<Text size={ 14 }>
				{ __( 'No importable form plugins detected.', 'sureforms' ) }
			</Text>
		);
	}

	return (
		<Container direction="column" gap="lg">
			<Container direction="column" gap="xs">
				<Title
					size="md"
					tag="h3"
					title={ __( 'Choose a source plugin', 'sureforms' ) }
				/>
				<Text size={ 14 } color="secondary">
					{ __(
						'Select the plugin you want to import forms from.',
						'sureforms'
					) }
				</Text>
			</Container>
			<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
				{ sources.map( ( source ) => (
					<SourceTile
						key={ source.key }
						source={ source }
						onSelect={ onSelect }
					/>
				) ) }
			</div>
		</Container>
	);
};

const SourceTile = ( { source, onSelect } ) => {
	const isInstalled = !! source.installed;
	const formCount = Number( source.form_count ?? 0 );
	const hasForms = isInstalled && formCount > 0;

	return (
		<div
			className={ `rounded-lg border border-border-subtle bg-background-primary p-5 flex flex-col gap-3 ${
				hasForms
					? 'hover:border-border-strong transition-colors'
					: 'opacity-70'
			}` }
		>
			<div className="flex items-start justify-between gap-2">
				<Title size="sm" tag="h4" title={ source.title } />
				{ isInstalled ? (
					<Badge
						variant="green"
						size="xs"
						icon={ <CheckCircle2 className="size-3" /> }
						label={ __( 'Active', 'sureforms' ) }
					/>
				) : (
					<Badge
						variant="neutral"
						size="xs"
						icon={ <CircleSlash className="size-3" /> }
						label={ __( 'Not installed', 'sureforms' ) }
					/>
				) }
			</div>
			<Text size={ 13 } color="secondary">
				{ isInstalled
					? sprintf(
						/* translators: %d: number of forms detected. */
						_n(
							'%d form available to import.',
							'%d forms available to import.',
							formCount,
							'sureforms'
						),
						formCount
					  )
					: __(
						'Activate this plugin to migrate its forms.',
						'sureforms'
					  ) }
			</Text>
			<Button
				variant="primary"
				size="sm"
				disabled={ ! hasForms }
				onClick={ () => onSelect( source ) }
				icon={ <ArrowRight /> }
				iconPosition="right"
			>
				{ __( 'Select', 'sureforms' ) }
			</Button>
		</div>
	);
};

export default SourcePicker;
