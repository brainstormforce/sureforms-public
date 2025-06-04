import { __ } from '@wordpress/i18n';
import ICONS from './icons.js';
import AiFormBuilder from './AiFormBuilder.js';
import { Button, Container, Label, Input, Loader } from '@bsf/force-ui';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const ActivateLicensePopup = ( {
	title = __( 'License Not Activated', 'sureforms' ),
	paraOne,
	paraTwo,
	buttonText = __( 'Activate License', 'sureforms' ),
} ) => {
	const [ licenseKey, setLicenseKey ] = useState( '' );
	const [ isActivated, setIsActivated ] = useState(
		window.srfm_admin?.is_license_active || false
	);
	const [ activating, setActivating ] = useState( false );
	const [ showError, setShowError ] = useState( false );
	const [ errorMessage, setErrorMessage ] = useState( '' );

	const activateLicense = () => {
		if ( ! licenseKey.trim() ) {
			setErrorMessage( __( 'Please enter a license key', 'sureforms' ) );
			setShowError( true );
			return;
		}

		if ( activating ) {
			return;
		}
		setShowError( false );
		setActivating( true );

		const formData = new window.FormData();
		formData.append( 'action', 'sureforms_activate_license' );
		formData.append( 'license_key', licenseKey );
		formData.append( 'nonce', srfm_admin.licensing_nonce );

		apiFetch( {
			url: ajaxurl,
			method: 'POST',
			body: formData,
		} ).then( ( data ) => {
			if ( data.success ) {
				setIsActivated( true );
				setLicenseKey( '' );
				checkLicense( true );
				window.location.reload();
			} else {
				setErrorMessage( data.data.message );
				setShowError( true );
				console.error( data.data.message );
			}
			setActivating( false );
		} );
	};

	const checkLicense = ( status ) => {
		const event = new CustomEvent( 'srfm_license_status_updated', {
			detail: {
				is_license_active: status,
			},
		} );
		window.dispatchEvent( event );
	};

	return (
		<>
			<Container
				direction="column"
				justify="center"
				align="center"
				className="fixed inset-0 bg-overlay-background z-[99999999]"
			>
				<Container
					direction="column"
					className="bg-background-primary gap-6 py-4 px-5 rounded-lg max-w-md shadow-lg"
				>
					<Container.Item className="relative pt-2">
						<Label
							variant="neutral"
							className="text-base font-semibold flex gap-3"
						>
							{ title }
							<span
								className="absolute top-[-10px] right-[-15px] cursor-pointer"
								onClick={ () =>
									( window.location.href =
										srfm_admin.site_url +
										'/wp-admin/admin.php?page=add-new-form' )
								}
							>
								{ ICONS.close }
							</span>
						</Label>
					</Container.Item>
					<Container.Item className="flex flex-col gap-4">
						<Label
							size="md"
							className="text-text-secondary font-normal"
						>
							{ paraOne }
						</Label>
						<Label
							size="md"
							className="text-text-secondary font-normal"
						>
							{ paraTwo }
						</Label>
					</Container.Item>
					<Container.Item className="flex flex-col w-full gap-5 pb-2">
						{ ! isActivated && (
							<div>
								<Input
									aria-label="License Key"
									size="md"
									type="text"
									value={ licenseKey }
									error={ showError }
									placeholder={ __(
										'Paste your license key here',
										'sureforms'
									) }
									onChange={ ( value ) => {
										setShowError( false );
										setLicenseKey( value.trim() );
									} }
								/>
								{ showError && (
									<Label
										size="sm"
										variant="error"
										className="font-semibold"
									>
										{ errorMessage ||
											__(
												'An error occurred. Please try again.',
												'sureforms'
											) }
									</Label>
								) }
							</div>
						) }
						<Button
							size="md"
							variant="primary"
							onClick={ () => {
								activateLicense();
							} }
							icon={
								activating ? (
									<Loader className="text-text-inverse" />
								) : null
							}
							iconPosition="left"
						>
							{ isActivated
								? __( 'Activated', 'sureforms' )
								: buttonText }
						</Button>
					</Container.Item>
				</Container>
			</Container>
			<AiFormBuilder />
		</>
	);
};

export default ActivateLicensePopup;
