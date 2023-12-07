import React, { useState, useEffect } from 'react';
import TemplateCard from './TemplateCard';
import { __ } from '@wordpress/i18n';
import templatesMarkup from './templatesMarkup';
import ICONS from './icons';
import apiFetch from '@wordpress/api-fetch';

const TemplateScreen = () => {
	const [ patterns, setPatterns ] = useState( [] );
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const [ selectedCategory, setSelectedCategory ] = useState( null );
	const [ showSearch, setShowSearch ] = useState( false );

	const getPatterns = async () => {
		const newPatterns = await apiFetch( {
			path: '/sureforms/v1/form-patterns',
		} );
		setPatterns( newPatterns );
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

	return (
		<div className="srfm-ts-main-container">
			<div className="srfm-ts-sidebar">
				<div className="srfm-ts-sidebar-header">
					{ ! showSearch ? (
						__( 'Form Templates', 'sureforms' )
					) : (
						<input
							type="text"
							placeholder={ __(
								'Search Templates...',
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
							selectedCategory === null ? 'selected' : ''
						}` }
						onClick={ () => handleCategoryClick( null ) }
					>
						{ __( 'All Forms', 'sureforms' ) }
						<span>{ patterns.length }</span>
					</div>
					{ Array.from(
						new Set(
							patterns.map(
								( template ) => template.templateCategory
							)
						)
					).map( ( uniqueCategory ) => {
						const categoryElements = patterns.filter(
							( template ) => {
								return (
									template.templateCategory === uniqueCategory
								);
							}
						);
						return (
							<div
								className={ `srfm-ts-sidebar-category ${
									selectedCategory === uniqueCategory
										? 'selected'
										: ''
								}` }
								key={ uniqueCategory }
								onClick={ () =>
									handleCategoryClick( uniqueCategory )
								}
							>
								{ uniqueCategory }
								<span>{ categoryElements.length }</span>
							</div>
						);
					} ) }
				</div>
			</div>
			<div className="srfm-ts-cards-container">
				{ filteredTemplates.map( ( template ) => {
					const title = template.title;

					return (
						<TemplateCard
							key={ title }
							templateName={ title }
							templatePreview={
								sureforms_admin.preview_images_url +
								'contact-form.png'
							}
							formData={ template.content }
						/>
					);
				} ) }
			</div>
		</div>
	);
};

export default TemplateScreen;
