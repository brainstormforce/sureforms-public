/**
 * MigrationPage — top-level component for the Migration tab.
 *
 * Owns the 4-step wizard state machine: SourcePicker → FormSelector →
 * DryRunPreview → ImportResult. Each sub-component is a pure step view;
 * this component is the only place that mutates `step`.
 *
 * Mounted from `src/admin/settings/Component.js` when the URL `tab` query
 * param equals `migration-settings`.
 *
 * @since x.x.x
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Container, ProgressSteps } from '@bsf/force-ui';
import SourcePicker from './SourcePicker';
import FormSelector from './FormSelector';
import DryRunPreview from './DryRunPreview';
import ImportResult from './ImportResult';

const STEPS = [ 'source', 'forms', 'preview', 'result' ];

const MigrationPage = () => {
	const [ step, setStep ] = useState( 'source' );
	const [ source, setSource ] = useState( null );
	const [ formIds, setFormIds ] = useState( [] );
	const [ result, setResult ] = useState( null );

	const handleSourceSelect = ( picked ) => {
		setSource( picked );
		setStep( 'forms' );
	};

	const handleFormsContinue = ( ids ) => {
		setFormIds( ids );
		setStep( 'preview' );
	};

	const handleImportConfirmed = ( importResult ) => {
		setResult( importResult );
		setStep( 'result' );
	};

	const handleRestart = () => {
		setSource( null );
		setFormIds( [] );
		setResult( null );
		setStep( 'source' );
	};

	const handleBackToForms = () => {
		setStep( 'forms' );
	};

	const handleBackToSource = () => {
		setSource( null );
		setFormIds( [] );
		setStep( 'source' );
	};

	return (
		<Container direction="column" gap="xl" className="py-2">
			<ProgressSteps
				currentStep={ STEPS.indexOf( step ) + 1 }
				size="sm"
				variant="dot"
				steps={ [
					{
						label: __( 'Choose source', 'sureforms' ),
					},
					{
						label: __( 'Select forms', 'sureforms' ),
					},
					{
						label: __( 'Preview', 'sureforms' ),
					},
					{
						label: __( 'Done', 'sureforms' ),
					},
				] }
			/>

			{ 'source' === step && (
				<SourcePicker onSelect={ handleSourceSelect } />
			) }

			{ 'forms' === step && source && (
				<FormSelector
					source={ source }
					onBack={ handleBackToSource }
					onContinue={ handleFormsContinue }
				/>
			) }

			{ 'preview' === step && source && (
				<DryRunPreview
					source={ source }
					formIds={ formIds }
					onBack={ handleBackToForms }
					onConfirm={ handleImportConfirmed }
				/>
			) }

			{ 'result' === step && (
				<ImportResult
					result={ result }
					onRestart={ handleRestart }
				/>
			) }
		</Container>
	);
};

export default MigrationPage;
