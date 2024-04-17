import { useState, useEffect } from '@wordpress/element';
import TemplateCard from './TemplateCard';
import { __ } from '@wordpress/i18n';
import ICONS from './icons';
import apiFetch from '@wordpress/api-fetch';
import { randomNiceColor } from '@Utils/Helpers';
import { Spinner } from '@wordpress/components';

const TemplateScreen = () => {
	const [ patterns, setPatterns ] = useState( [] );
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const [ selectedCategory, setSelectedCategory ] = useState( null );
	const [ templateColors, setTemplateColors ] = useState( [] );
	const [ loading, setLoading ] = useState( true );

	// Pro Templates dummy data for the preview in the free version.
	const proTemplatesPreview = [
		{
			title: 'Job Application Form',
			categories: [ 'sureforms_form' ],
			templateCategory: 'Job Application',
			slug: 'job-application-form',
			isPro: true,
			content: '',
		},
	];

	const getPatterns = async () => {
		setLoading( true );

		try {
			const newPatterns = await apiFetch( {
				path: '/sureforms/v1/form-patterns',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': srfm_admin.template_picker_nonce,
				},
			} );

			// Add Pro Templates preview to the list if the pro version is not active.
			if ( ! srfm_admin.is_pro_active ) {
				newPatterns.push( ...proTemplatesPreview );
			}

			// Generate and store colors based on the template id and set it in the template card background.
			const newColors = newPatterns.reduce( ( acc, template ) => {
				acc[ template.slug ] = randomNiceColor();
				return acc;
			}, {} );
			setTemplateColors( newColors );

			setPatterns( newPatterns );
		} catch ( error ) {
			console.error(
				__( 'Error loading form templates:', 'sureforms' ),
				error
			);
		}
		setLoading( false );
	};

	useEffect( () => {
		getPatterns();
	}, [] );

	const filteredTemplates = patterns.filter( ( template ) => {
		const matchesSearch = template.title
			.toLowerCase()
			.includes( searchQuery.toLowerCase() );
		const matchesCategory =
			! selectedCategory ||
			template.templateCategory === selectedCategory ||
			selectedCategory === 'All Forms';

		return matchesSearch && matchesCategory;
	} );

	const handleCategoryClick = ( category ) => {
		setSelectedCategory( category );
	};

	useEffect( () => {
		const inputElement = document.querySelector(
			'.srfm-ts-sidebar-search-input'
		);
		if ( inputElement ) {
			inputElement.focus();
		}
	}, [ searchQuery ] );

	function QueryScreen() {
		return (
			<div className="srfm-ts-main-container srfm-content-section">
				{ loading ? (
					<Spinner className="srfm-ts-loader" />
				) : (
					<>
						<div className="srfm-ts-sidebar">
							<div className="srfm-ts-sidebar-header">
								<input
									type="text"
									placeholder={ __(
										'Search Templates…',
										'sureforms'
									) }
									className="srfm-ts-sidebar-search-input"
									value={ searchQuery }
									onChange={ ( e ) =>
										setSearchQuery( e.target.value )
									}
								/>
							</div>
							<div className="srfm-ts-sidebar-categories-container">
								<div
									className={ `srfm-ts-sidebar-category ${
										selectedCategory === null
											? 'srfm-ts-category-is-selected'
											: ''
									}` }
									onClick={ () =>
										handleCategoryClick( null )
									}
								>
									{ __( 'All Forms', 'sureforms' ) }
									<span>{ patterns.length }</span>
								</div>
								{ Array.from(
									new Set(
										patterns.map(
											( template ) =>
												template.templateCategory
										)
									)
								).map( ( uniqueCategory ) => {
									const categoryElements = patterns.filter(
										( template ) => {
											return (
												template.templateCategory ===
												uniqueCategory
											);
										}
									);
									return (
										<div
											className={ `srfm-ts-sidebar-category ${
												selectedCategory ===
												uniqueCategory
													? 'srfm-ts-category-is-selected'
													: ''
											}` }
											key={ uniqueCategory }
											onClick={ () =>
												handleCategoryClick(
													uniqueCategory
												)
											}
										>
											{ uniqueCategory }
											<span>
												{ categoryElements.length }
											</span>
										</div>
									);
								} ) }
							</div>
							<div className="srfm-req-template-container">
								<div className="srfm-req-template-title-container">
									{ ICONS.message }
									<div className="srfm-req-template-title">
										{ __(
											'Request template',
											'sureforms'
										) }
									</div>
								</div>
								<div className="srfm-req-template-body">
									{ __(
										"Can't find it? Let us know what kind of form do you need.",
										'sureforms'
									) }
								</div>
								<button className="srfm-req-template-btn">
									{ __( 'Request a Template', 'sureforms' ) }
								</button>
							</div>
						</div>
						<div className="srfm-ts-cards-container">
							{ filteredTemplates.map( ( template ) => {
								const filteredTemplateTitle = template.title;

								return (
									<TemplateCard
										key={ filteredTemplateTitle }
										templateName={ filteredTemplateTitle }
										templateSlug={ template?.slug }
										templatePreview={
											srfm_admin?.preview_images_url +
											`${ template?.slug }.png`
										}
										formData={ template?.content }
										color={
											templateColors[ template?.slug ]
										}
										templateMetas={ template?.postMetas }
										isPro={ template?.isPro }
									/>
								);
							} ) }
						</div>
					</>
				) }
			</div>
		);
	}

	return <QueryScreen />;
};

export default TemplateScreen;
