import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { Checkbox, Input, Text, Title } from '@bsf/force-ui';
import apiFetch from '@wordpress/api-fetch';
import { Divider } from '../components';
import NavigationButtons from '../components/navigation-buttons';
import { useOnboardingNavigation } from '../hooks';
import { useOnboardingState } from '../onboarding-state';

const DEFAULT_FORM_DATA = {
	firstName: '',
	lastName: '',
	email: '',
};

const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const UserDetails = () => {
	const [ onboardingState, actions ] = useOnboardingState();
	const { navigateToNextRoute, navigateToPreviousRoute } =
		useOnboardingNavigation();
	const [ formData, setFormData ] = useState( {
		...DEFAULT_FORM_DATA,
		...onboardingState?.userDetails,
	} );
	const [ errors, setErrors ] = useState( {} );

	const privacyPolicyURL =
		srfm_admin?.privacy_policy_url || 'https://sureforms.com/privacy-policy/';

	const handleFieldChange = ( field ) => ( value ) => {
		setFormData( ( prev ) => ( {
			...prev,
			[ field ]: value,
		} ) );
		setErrors( ( prev ) => ( {
			...prev,
			[ field ]: '',
		} ) );
	};

	const validateForm = () => {
		const validationErrors = {};
		const firstName = formData.firstName?.trim() || '';
		const email = formData.email?.trim() || '';

		if ( ! firstName ) {
			validationErrors.firstName = __(
				'First name is required.',
				'sureforms'
			);
		}

		if ( ! email ) {
			validationErrors.email = __(
				'Email address is required.',
				'sureforms'
			);
		} else if ( ! emailRegex.test( email ) ) {
			validationErrors.email = __(
				'Please enter a valid email address.',
				'sureforms'
			);
		}

		if ( ! formData.consent ) {
			validationErrors.consent = __( 'This is required.', 'sureforms' );
		}

		setErrors( validationErrors );
		return Object.keys( validationErrors ).length === 0;
	};

	const handleContinue = async () => {
		if ( ! validateForm() ) {
			return;
		}

		const payload = {
			first_name: formData.firstName.trim(),
			last_name: formData.lastName?.trim() || '',
			email: formData.email.trim(),
		};

		actions.setUserDetails( formData );
		actions.unmarkStepSkipped( 'userDetails' );

		try {
			await apiFetch( {
				path: '/sureforms/v1/onboarding/user-details',
				method: 'POST',
				data: payload,
			} );
		} catch ( error ) {
			// Silent error handling: lead submit failure should not block onboarding completion.
		}

		navigateToNextRoute();
	};

	const handleSkip = () => {
		actions.setUserDetails( formData );
		actions.markStepSkipped( 'userDetails' );
		navigateToNextRoute();
	};

	return (
		<div className="space-y-6">
			<div className="space-y-2">
				<Title
					tag="h3"
					title={ __( 'Okay, just one last step…', 'sureforms' ) }
					size="lg"
				/>
				<Text size={ 14 } weight={ 400 } color="secondary">
					{ __(
						'Help us tailor your SureForms experience by sharing a bit about yourself.',
						'sureforms'
					) }
				</Text>
			</div>

			<div className="space-y-4">
				<div className="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div className="space-y-1.5">
						<Input
							id="srfm-onboarding-first-name"
							size="md"
							label={ __( 'First Name', 'sureforms' ) }
							placeholder={ __( 'Enter your first name', 'sureforms' ) }
							value={ formData.firstName }
							onChange={ handleFieldChange( 'firstName' ) }
							error={ errors.firstName }
						/>
						{ errors.firstName && (
							<Text size={ 14 } color="error">
								{ errors.firstName }
							</Text>
						) }
					</div>

					<div className="space-y-1.5">
						<Input
							id="srfm-onboarding-last-name"
							size="md"
							label={ __( 'Last Name', 'sureforms' ) }
							placeholder={ __( 'Enter your last name', 'sureforms' ) }
							value={ formData.lastName }
							onChange={ handleFieldChange( 'lastName' ) }
						/>
					</div>
				</div>

				<div className="space-y-1.5">
					<Input
						id="srfm-onboarding-email"
						size="md"
						type="email"
						label={ __( 'Email Address', 'sureforms' ) }
						placeholder={ __( 'Enter your email address', 'sureforms' ) }
						value={ formData.email }
						onChange={ handleFieldChange( 'email' ) }
						error={ errors.email }
					/>
					{ errors.email && (
						<Text size={ 14 } color="error">
							{ errors.email }
						</Text>
					) }
				</div>

				<div className="space-y-1.5">
					<Checkbox
						checked={ formData.consent }
						onChange={ handleFieldChange( 'consent' ) }
						size="sm"
						label={ {
							heading: (
								<span>
									{ createInterpolateElement(
										__(
											'Stay in the loop and help shape SureForms! Get feature updates, and help us in betterment of SureForms by sharing how you use the plugin. <a>Privacy Policy</a>.',
											'sureforms'
										),
										{
											a: (
												<a
													href={ privacyPolicyURL }
													target="_blank"
													rel="noopener noreferrer"
													className="underline text-button-primary hover:text-button-primary-hover"
												>
													{ __(
														'Privacy Policy',
														'sureforms'
													) }
												</a>
											),
										}
									) }
								</span>
							),
						} }
					/>
					{ errors.consent && (
						<Text size={ 14 } color="error">
							{ errors.consent }
						</Text>
					) }
				</div>
			</div>

			<Divider />

			<NavigationButtons
				backProps={ {
					onClick: navigateToPreviousRoute,
				} }
				continueProps={ {
					onClick: handleContinue,
					text: __( 'Finish', 'sureforms' ),
				} }
				skipProps={ {
					onClick: handleSkip,
					text: __( 'Skip', 'sureforms' ),
				} }
			/>
		</div>
	);
};

export default UserDetails;
