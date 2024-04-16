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
	const [ showSearch, setShowSearch ] = useState( false );
	const [ templateColors, setTemplateColors ] = useState( [] );
	const [ loading, setLoading ] = useState( true );

	const proTemplatesPreview = [
		{
			name: 'srfm/demo-pro-form',
			title: 'Demo Pro Form',
			categories: [ 'sureforms_form' ],
			templateCategory: 'Pro Templates',
			slug: 'demo-pro-form',
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

			// Add Pro Templates to the list
			! srfm_admin.is_pro_active &&
				newPatterns.push( ...proTemplatesPreview );

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

	// [
	// 	{
	// 		"name": "srfm/blank-form",
	// 		"title": "Blank Form",
	// 		"categories": [
	// 			"sureforms_form"
	// 		],
	// 		"templateCategory": "Basic Forms",
	// 		"slug": "blank-form",
	// 		"isPro": false,
	// 		"content": ""
	// 	},
	// 	{
	// 		"name": "srfm/contact-form",
	// 		"title": "Contact Form",
	// 		"info": "A basic Contact Form",
	// 		"categories": [
	// 			"sureforms_form"
	// 		],
	// 		"templateCategory": "Basic Forms",
	// 		"slug": "contact-form",
	// 		"isPro": false,
	// 		"content": "<!-- wp:srfm/advanced-heading {\"block_id\":\"88614146\",\"headingTitle\":\"Contact us\",\"headingDescToggle\":true,\"headingDesc\":\"Use this form to contact our team. We usually respond within 24 hours but it can take longer on weekends and around public holidays.\",\"headSpace\":16,\"subHeadSpace\":24,\"subHeadFontWeight\":\"400\",\"subHeadFontSize\":18} /--><!-- wp:srfm/input {\"block_id\":\"e8a489f7\",\"required\":true,\"label\":\"Name\",\"formId\":79} /--><!-- wp:srfm/email {\"block_id\":\"a5728450\",\"required\":true,\"formId\":79} /--><!-- wp:srfm/input {\"block_id\":\"9ec2463e\",\"required\":true,\"label\":\"Subject\",\"formId\":79} /--><!-- wp:srfm/textarea {\"block_id\":\"4afb9556\",\"required\":true,\"label\":\"Message\",\"formId\":79} /-->"
	// 	},
	// 	{
	// 		"name": "srfm/newsletter-form",
	// 		"title": "Newsletter Form",
	// 		"info": "Creates a Newsletter Form",
	// 		"categories": [
	// 			"sureforms_form"
	// 		],
	// 		"templateCategory": "Newsletter Forms",
	// 		"postMetas": {
	// 			"_srfm_submit_alignment": [
	// 				"justify"
	// 			],
	// 			"_srfm_submit_width": [
	// 				"100%"
	// 			],
	// 			"_srfm_submit_width_backend": [
	// 				"auto"
	// 			]
	// 		},
	// 		"slug": "newsletter-form",
	// 		"isPro": true,
	// 		"content": "<!-- wp:srfm/advanced-heading {\"block_id\":\"2117a231\",\"headingTitle\":\"Subscribe to Our Newsletter\",\"headingDescToggle\":true,\"headingDesc\":\"Subscribe to our newsletter below and receive exclusive access to new content, including tips, articles, guides, and updates.\",\"subHeadingColor\":\"#545454\",\"headingTag\":\"h3\",\"headSpace\":12,\"subHeadSpace\":20,\"subHeadFontSize\":18} /--><!-- wp:srfm/input {\"block_id\":\"3f513e23\",\"fieldWidth\":50,\"label\":\"First Name\",\"formId\":80} /--><!-- wp:srfm/input {\"block_id\":\"4f84bead\",\"fieldWidth\":50,\"label\":\"Last Name\",\"formId\":80} /--><!-- wp:srfm/email {\"block_id\":\"6ef07308\",\"label\":\"Your Email\",\"formId\":80} /-->"
	// 	},
	// 	{
	// 		"name": "srfm/support-form",
	// 		"title": "Support Form",
	// 		"info": "Form for submitting support query",
	// 		"categories": [
	// 			"sureforms_form"
	// 		],
	// 		"templateCategory": "Support Forms",
	// 		"slug": "support-form",
	// 		"isPro": false,
	// 		"content": "<!-- wp:srfm/input {\"block_id\":\"fd4ef0f9\",\"required\":true,\"fieldWidth\":50,\"label\":\"First Name\",\"formId\":81} /--><!-- wp:srfm/input {\"block_id\":\"667678ef\",\"required\":true,\"fieldWidth\":50,\"label\":\"Last Name\",\"formId\":81} /--><!-- wp:srfm/email {\"block_id\":\"eae1ae54\",\"required\":true,\"formId\":81} /--><!-- wp:srfm/input {\"block_id\":\"5e4d1e8f\",\"required\":true,\"label\":\"Subject\",\"formId\":81} /--><!-- wp:srfm/textarea {\"block_id\":\"f0076110\",\"required\":true,\"label\":\"Please describe your question in detail. Explain the exact steps to replicate the problem.\",\"formId\":81} /--><!-- wp:srfm/url {\"block_id\":\"0e087bac\",\"required\":true,\"label\":\"URL where we can see more details.\",\"formId\":81} /-->"
	// 	},
	// 	{
	// 		"name": "srfm/feedback-form",
	// 		"title": "Feedback Form / Survey Form",
	// 		"info": "Form for conducting surveys",
	// 		"categories": [
	// 			"sureforms_form"
	// 		],
	// 		"templateCategory": "Survey Forms",
	// 		"slug": "survey-form",
	// 		"isPro": false,
	// 		"content": "<!-- wp:srfm/advanced-heading {\"block_id\":\"a9e4f8ad\",\"headingTitle\":\"Customer Survey\",\"headingDescToggle\":true,\"headingDesc\":\"Please take a moment to complete a short survey and let us know how much you like our service.\",\"subHeadingColor\":\"#575757\",\"headingTag\":\"h3\",\"headSpace\":12,\"subHeadSpace\":20,\"subHeadFontSize\":18} /--><!-- wp:srfm/input {\"block_id\":\"c7894ce2\",\"required\":true,\"label\":\"Name\",\"formId\":82} /--><!-- wp:srfm/email {\"block_id\":\"82ea2785\",\"required\":true,\"formId\":82} /--><!-- wp:srfm/multi-choice {\"block_id\":\"3a7ef9dd\",\"required\":true,\"options\":[{\"optionTitle\":\"Food was great\"},{\"optionTitle\":\"Staff service\"},{\"optionTitle\":\"Location was great\"},{\"optionTitle\":\"Somthing else\"}],\"label\":\"What did you like about our lodge?\",\"formId\":82} /--><!-- wp:srfm/textarea {\"block_id\":\"7046569e\",\"label\":\"Any Comment\",\"formId\":82} /-->"
	// 	},
	// 	{
	// 		"name": "srfm/event-rsvp-form",
	// 		"title": "Event RSVP Form",
	// 		"info": "Form for RSVP",
	// 		"categories": [
	// 			"sureforms_form"
	// 		],
	// 		"templateCategory": "RSVP Forms",
	// 		"postMetas": {
	// 			"_srfm_submit_alignment": [
	// 				"justify"
	// 			],
	// 			"_srfm_submit_width": [
	// 				"100%"
	// 			],
	// 			"_srfm_submit_width_backend": [
	// 				"auto"
	// 			]
	// 		},
	// 		"slug": "event-rsvp-form",
	// 		"isPro": false,
	// 		"content": "<!-- wp:srfm/input {\"block_id\":\"c7894ce2\",\"required\":true,\"fieldWidth\":50,\"label\":\"First Name\",\"formId\":85} /--><!-- wp:srfm/input {\"block_id\":\"44759383\",\"required\":true,\"fieldWidth\":50,\"label\":\"Last Name\",\"formId\":85} /--><!-- wp:srfm/email {\"block_id\":\"82ea2785\",\"required\":true,\"formId\":85} /--><!-- wp:srfm/multi-choice {\"block_id\":\"3a7ef9dd\",\"required\":true,\"singleSelection\":true,\"options\":[{\"optionTitle\":\"Yes\"},{\"optionTitle\":\"No\"}],\"label\":\"Will you be attending the Event\",\"formId\":85} /--><!-- wp:srfm/textarea {\"block_id\":\"7046569e\",\"label\":\"Any Comments or Suggestions\",\"formId\":85} /-->"
	// 	},
	// 	{
	// 		"name": "srfm/subscription-form",
	// 		"title": "Subscription Form",
	// 		"info": "Form for Subscription",
	// 		"categories": [
	// 			"sureforms_form"
	// 		],
	// 		"templateCategory": "Subscription Forms",
	// 		"postMetas": {
	// 			"_srfm_submit_alignment": [
	// 				"justify"
	// 			],
	// 			"_srfm_submit_width": [
	// 				"100%"
	// 			],
	// 			"_srfm_submit_width_backend": [
	// 				"auto"
	// 			]
	// 		},
	// 		"slug": "subscription-form",
	// 		"isPro": false,
	// 		"content": "<!-- wp:srfm/advanced-heading {\"block_id\":\"6b9decba\",\"headingTitle\":\"Subscribe to our Blog Post\",\"headingTag\":\"h3\",\"blockTopPadding\":0,\"blockRightPadding\":0,\"blockLeftPadding\":0,\"blockBottomPadding\":0,\"blockTopMargin\":0,\"blockRightMargin\":0,\"blockLeftMargin\":0,\"blockBottomMargin\":20} /--><!-- wp:srfm/email {\"block_id\":\"6ef07308\",\"label\":\"Your Email\",\"formId\":83} /-->"
	// 	}
	// ]

	console.log( patterns );

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
