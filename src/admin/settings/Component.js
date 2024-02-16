import { __ } from '@wordpress/i18n';
// settings icons.
import parse from 'html-react-parser';
import svgIcons from '@Svg/svgs.json';
import { BaseControl, TabPanel } from '@wordpress/components';
import { useState, useEffect, Fragment } from '@wordpress/element';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import { navigation } from './Navigation';
import apiFetch from '@wordpress/api-fetch';
import GeneralPage from './pages/General'
import EmailPage from './pages/Email'
import SecurityPage from './pages/Security'

const Component = ( { path } ) => {
	const [ sureformsV2CheckboxSite, setSureformsV2CheckboxSite ] =
		useState( '' );
	const [ sureformsV2CheckboxSecret, setSureformsV2CheckboxSecret ] =
		useState( '' );
	const [ sureformsV2InvisibleSite, setSureformsV2InvisibleSite ] =
		useState( '' );
	const [ sureformsV2InvisibleSecret, setSureformsV2InvisibleSecret ] =
		useState( '' );
	const [ sureformsV3Site, setSureformsV3Site ] = useState( '' );
	const [ sureformsV3Secret, setSureformsV3Secret ] = useState( '' );

	const [ formData, setFormData ] = useState( {} );
	const [ honeyPot, setHoneyPot ] = useState( false );

	const [ pageTitle, setPageTitle ] = useState( '' );
	const [ pageIcon, setPageIcon ] = useState( '' );

	const onSelect = () => {};

	const handleChange = ( e ) => {
		const { name, value, type, checked } = e.target;
		const newValue = type === 'checkbox' ? checked : value;

		if ( name === 'sureforms_v2_checkbox_secret' ) {
			setSureformsV2CheckboxSecret( newValue );
			setFormData( () => ( {
				sureforms_v2_checkbox_site: sureformsV2CheckboxSite,
				sureforms_v2_invisible_site: sureformsV2InvisibleSite,
				sureforms_v2_invisible_secret: sureformsV2InvisibleSecret,
				sureforms_v3_site: sureformsV3Site,
				sureforms_v3_secret: sureformsV3Secret,
				honeypot_toggle: honeyPot,
				[ name ]: newValue,
			} ) );
		} else if ( name === 'sureforms_v2_checkbox_site' ) {
			setSureformsV2CheckboxSite( newValue );
			setFormData( () => ( {
				sureforms_v2_checkbox_secret: sureformsV2CheckboxSecret,
				sureforms_v2_invisible_site: sureformsV2InvisibleSite,
				sureforms_v2_invisible_secret: sureformsV2InvisibleSecret,
				sureforms_v3_site: sureformsV3Site,
				sureforms_v3_secret: sureformsV3Secret,
				honeypot_toggle: honeyPot,
				[ name ]: newValue,
			} ) );
		} else if ( name === 'sureforms_v2_invisible_secret' ) {
			setSureformsV2InvisibleSecret( newValue );
			setFormData( () => ( {
				sureforms_v2_invisible_site: sureformsV2InvisibleSite,
				sureforms_v2_checkbox_site: sureformsV2CheckboxSite,
				sureforms_v2_checkbox_secret: sureformsV2CheckboxSecret,
				sureforms_v3_site: sureformsV3Site,
				sureforms_v3_secret: sureformsV3Secret,
				honeypot_toggle: honeyPot,
				[ name ]: newValue,
			} ) );
		} else if ( name === 'sureforms_v2_invisible_site' ) {
			setSureformsV2InvisibleSite( newValue );
			setFormData( () => ( {
				sureforms_v2_invisible_secret: sureformsV2InvisibleSecret,
				sureforms_v2_checkbox_site: sureformsV2CheckboxSite,
				sureforms_v2_checkbox_secret: sureformsV2CheckboxSecret,
				sureforms_v3_site: sureformsV3Site,
				sureforms_v3_secret: sureformsV3Secret,
				honeypot_toggle: honeyPot,
				[ name ]: newValue,
			} ) );
		} else if ( name === 'sureforms_v3_secret' ) {
			setSureformsV3Secret( newValue );
			setFormData( () => ( {
				sureforms_v2_invisible_secret: sureformsV2InvisibleSecret,
				sureforms_v2_checkbox_site: sureformsV2CheckboxSite,
				sureforms_v2_checkbox_secret: sureformsV2CheckboxSecret,
				sureforms_v3_site: sureformsV3Site,
				sureforms_v2_invisible_site: sureformsV2InvisibleSite,
				honeypot_toggle: honeyPot,
				[ name ]: newValue,
			} ) );
		} else if ( name === 'sureforms_v3_site' ) {
			setSureformsV3Site( newValue );
			setFormData( () => ( {
				sureforms_v2_invisible_secret: sureformsV2InvisibleSecret,
				sureforms_v2_checkbox_site: sureformsV2CheckboxSite,
				sureforms_v2_checkbox_secret: sureformsV2CheckboxSecret,
				sureforms_v3_secret: sureformsV3Secret,
				sureforms_v2_invisible_site: sureformsV2InvisibleSite,
				honeypot_toggle: honeyPot,
				[ name ]: newValue,
			} ) );
		} else if ( name === 'honeypot_toggle' ) {
			setHoneyPot( ! honeyPot );
			setFormData( () => ( {
				sureforms_v2_invisible_site: sureformsV2InvisibleSite,
				sureforms_v2_invisible_secret: sureformsV2InvisibleSecret,
				sureforms_v2_checkbox_site: sureformsV2CheckboxSite,
				sureforms_v2_checkbox_secret: sureformsV2CheckboxSecret,
				sureforms_v3_site: sureformsV3Site,
				sureforms_v3_secret: sureformsV3Secret,
				[ name ]: newValue,
			} ) );
		}
	};

	const handleSubmit = async ( e ) => {
		e.preventDefault();
		try {
			await apiFetch( {
				path: 'sureforms/v1/srfm-settings',
				method: 'POST',
				body: JSON.stringify( formData ),
				headers: {
					'content-type': 'application/json',
				},
			} );
			toast.success( __( 'Settings Saved Successfully!', 'sureforms' ), {
				position: 'bottom-right',
				hideProgressBar: true,
			} );
		} catch ( error ) {
			toast.error( __( 'Error Saving Settings!', 'sureforms' ), {
				position: 'bottom-right',
				hideProgressBar: true,
			} );
			console.error( error );
		}
	};

	useEffect( () => {
		const fetchData = async () => {
			try {
				const data = await apiFetch( {
					path: 'sureforms/v1/srfm-settings',
					method: 'GET',
					headers: {
						'content-type': 'application/json',
					},
				} );

				if ( data ) {
					setSureformsV2CheckboxSecret(
						data.sureforms_v2_checkbox_secret &&
							data.sureforms_v2_checkbox_secret
					);
					setSureformsV2CheckboxSite(
						data.sureforms_v2_checkbox_site &&
							data.sureforms_v2_checkbox_site
					);
					setSureformsV2InvisibleSecret(
						data.sureforms_v2_invisible_secret &&
							data.sureforms_v2_invisible_secret
					);
					setSureformsV2InvisibleSite(
						data.sureforms_v2_invisible_site &&
							data.sureforms_v2_invisible_site
					);
					setSureformsV3Secret(
						data.sureforms_v3_secret && data.sureforms_v3_secret
					);
					setSureformsV3Site(
						data.sureforms_v3_site && data.sureforms_v3_site
					);
					setHoneyPot( data.honeypot && data.honeypot );
				}
			} catch ( error ) {
				console.error( 'Error fetching datates:', error );
			}
		};

		fetchData();
	}, [] );


	useEffect( () => {
		if( path ) {
			navigation.map( ( single ) => {
				const slug = single?.slug && single.slug ? single.slug : ''
				const title = single?.name && single.name ? single.name : ''
				const icon = single?.icon && single.icon ? single.icon : ''
				if( slug ) {
					if( slug === path ) {
						setPageTitle(title);
						setPageIcon(icon);
					}
				}
			})
		}
	
	}, [path] );
	
	return (
		<>
		<div class="srfm-page-heading">
			<div class="srfm-page-icon">
				{pageIcon}
			</div>
			<span>{pageTitle}</span>
		</div>
		<div class="srfm-page-content">
			{ 'general-settings' === path && <GeneralPage/> }
			{ 'email-settings' === path && <EmailPage/> }
			{ 'security-settings' === path && <SecurityPage/> }
		</div>
		</>
	);
};

export default Component;
