import Editor from '../QuillEditor';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { Select, Label, Input } from '@bsf/force-ui';
import RadioGroup from '@Admin/components/RadioGroup';

const AFTER_SUBMISSION_OPTIONS = [
	{
		label: __( 'Hide Form', 'sureforms' ),
		value: 'hide form',
	},
	{
		label: __( 'Reset Form', 'sureforms' ),
		value: 'reset form',
	},
];

const DefaultConfirmationTypes = ( { data, setData, pageOptions, setErrorMessage, errorMessage, keyValueComponent } ) => {
	const [ canDisplayError, setCanDisplayError ] = useState( false );
	const handleEditorChange = ( newContent ) => {
		setData( { ...data, message: newContent } );
	};
	useEffect( () => {
		// Do not display pre-validation message right after changing tabs or confirmation type.
		setCanDisplayError( false );
	}, [ data?.confirmation_type ] );
	return (
		<>
			{ data?.confirmation_type === 'same page' && (
				<>
					<div>
						<Editor
							handleContentChange={ handleEditorChange }
							content={ data?.message }
						/>
					</div>
					<div className="space-y-2">
						<Label>
							{ __( 'After Form Submission', 'sureforms' ) }
						</Label>
						<RadioGroup cols={ 2 }>
							{ AFTER_SUBMISSION_OPTIONS.map(
								( option, index ) => (
									<RadioGroup.Option
										key={ index }
										label={ option.label }
										value={ option.value }
										checked={
											data?.submission_action ===
											option.value
										}
										onChange={ () =>
											setData( {
												...data,
												submission_action: option.value,
											} )
										}
									/>
								)
							) }
						</RadioGroup>
					</div>
				</>
			) }

			{ data?.confirmation_type === 'different page' && (
				<>
					<div className="space-y-6">
						<div className="space-y-1.5">
							<Label htmlFor="select-page">
								{ __(
									'Select Page to redirect',
									'sureforms'
								) }
							</Label>
							<Select
								options={ pageOptions }
								value={ data?.page_url }
								onChange={ ( value ) => {
									setCanDisplayError( true );
									setErrorMessage( null );
									setData( {
										...data,
										page_url: value,
									} );
								} }
								combobox
								searchPlaceholder={ __(
									'Search for a page',
									'sureforms'
								) }
							>
								<Select.Button
									id="select-page"
									placeholder={ __(
										'Select a page',
										'sureforms'
									) }
								>
									{ pageOptions?.find(
										( option ) =>
											option.value === data?.page_url
									)?.label }
								</Select.Button>
								<Select.Options>
									{ pageOptions?.map( ( option ) => (
										<Select.Option
											key={ option.value }
											value={ option.value }
											selected={
												option.value === data?.page_url
											}
										>
											{ option.label }
										</Select.Option>
									) ) }
								</Select.Options>
							</Select>
						</div>
					</div>
					{ keyValueComponent() }
				</>
			) }
			{ data?.confirmation_type === 'custom url' && (
				<>
					<div className="space-y-1.5">
						<Label htmlFor="custom-url-input" required>
							{ __( 'Custom URL', 'sureforms' ) }
						</Label>
						<Input
							id="custom-url-input"
							value={ data?.custom_url }
							onChange={ ( value ) => {
								setCanDisplayError( true );
								setData( { ...data, custom_url: value } );
							} }
							size="md"
						/>
						{ canDisplayError && errorMessage && (
							<Label variant="error" size="sm">{ errorMessage }</Label>
						) }
					</div>
					{ keyValueComponent() }
				</>
			) }
		</>
	);
};

export default DefaultConfirmationTypes;
