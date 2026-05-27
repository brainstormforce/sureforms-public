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
import { Badge, Button, Container, Text, Title } from '@bsf/force-ui';
import { ArrowRight, CheckCircle2, CircleSlash } from 'lucide-react';
import LoadingSkeleton from '@Admin/components/LoadingSkeleton';
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
		return <LoadingSkeleton count={ 3 } height={ 25 } />;
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

	// Column count tracks how many sources we have: 1 → full width, 2 → 50/50,
	// 3 → thirds, 4+ → thirds wrapping onto new rows. Capped per breakpoint so
	// tiles stack on small screens instead of squeezing.
	const count = sources.length;
	const cols = {
		sm: 1,
		md: Math.min( count, 2 ),
		lg: Math.min( count, 3 ),
	};

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
			<Container containerType="grid" cols={ cols } gap="md">
				{ sources.map( ( source ) => (
					<SourceTile
						key={ source.key }
						source={ source }
						onSelect={ onSelect }
					/>
				) ) }
			</Container>
		</Container>
	);
};

const SourceTile = ( { source, onSelect } ) => {
	const isInstalled = !! source.installed;
	const formCount = Number( source.form_count ?? 0 );
	const hasForms = isInstalled && formCount > 0;

	return (
		<Container
			direction="column"
			gap="sm"
			className={ `rounded-lg border border-border-subtle bg-background-primary p-5 ${
				hasForms
					? 'hover:border-border-strong transition-colors'
					: 'opacity-70'
			}` }
		>
			<Container align="start" justify="between" gap="xs">
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
			</Container>
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
		</Container>
	);
};

export default SourcePicker;
