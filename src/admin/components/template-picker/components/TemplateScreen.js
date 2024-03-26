import { useState, useEffect } from '@wordpress/element';
import TemplateCard from './TemplateCard';
import { __ } from '@wordpress/i18n';
import ICONS from './icons';
import apiFetch from '@wordpress/api-fetch';
import TemplatePreview from './TemplatePreview';
import { useLocation } from 'react-router-dom';
import { randomNiceColor } from '@Utils/Helpers';
import { Spinner } from '@wordpress/components';

const TemplateScreen = () => {
	const [ patterns, setPatterns ] = useState( [] );
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const [ selectedCategory, setSelectedCategory ] = useState( null );
	const [ showSearch, setShowSearch ] = useState( false );
	const [ templateColors, setTemplateColors ] = useState( [] );
	const [ loading, setLoading ] = useState( true );

	const getPatterns = async () => {
		setLoading( true );
		const newPatterns = await apiFetch( {
			path: '/sureforms/v1/form-patterns',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': srfm_admin.template_picker_nonce,
			},
		} );

		// Generate and store colors based on the template title
		const newColors = newPatterns.reduce( ( acc, template ) => {
			acc[ template.id ] = randomNiceColor();
			return acc;
		}, {} );
		setTemplateColors( newColors );

		setPatterns( newPatterns );

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

	function useQuery() {
		return new URLSearchParams( useLocation().search );
	}

	function QueryScreen() {
		const query = useQuery();
		const templateId = query.get( 'template-id' );

		const selectedTemplate = patterns.find(
			( template ) => template.id === templateId
		);

		const { title, content, info } = selectedTemplate || {};

		if ( templateId ) {
			return (
				<>
					<TemplatePreview
						templateName={ title }
						formData={ content }
						info={ info }
						templatePreview={ `${ srfm_admin.preview_images_url }contact-form.png` }
					/>
				</>
			);
		}
		return (
			<div className="srfm-ts-main-container srfm-content-section">
				{ loading ? (
					<Spinner className="srfm-ts-loader" />
				) : (
					<>
						<div className="srfm-ts-sidebar">
							<div className="srfm-ts-sidebar-header">
								{ ! showSearch ? (
									__( 'Form Templates', 'sureforms' )
								) : (
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
								) }
								<span
									className="srfm-ts-sidebar-search-icon"
									onClick={ () => {
										setShowSearch( ! showSearch );
									} }
								>
									{ ICONS.search }
								</span>
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
										'Can’t find it? Let us know what kind of form do you need.',
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
								const filteredTemplateId = template.id;

								let imageName = filteredTemplateTitle
									.toLowerCase()
									.split( ' ' )
									.join( '-' );

								if (
									imageName === 'feedback-form-/-survey-form'
								) {
									imageName = 'survey-form';
								}

								return (
									<TemplateCard
										key={ filteredTemplateTitle }
										templateName={ filteredTemplateTitle }
										templateId={ filteredTemplateId }
										templatePreview={
											srfm_admin.preview_images_url +
											`${ imageName }.png`
										}
										formData={ template.content }
										color={ templateColors[ template.id ] }
										templateMetas={ template?.postMetas }
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
