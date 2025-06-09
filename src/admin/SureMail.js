/**
 * SureMail promotional page component.
 *
 * @since 1.7.1
 */

import { __ } from '@wordpress/i18n';
import { useState, createRoot } from '@wordpress/element';
import { Button, Text } from '@bsf/force-ui';
import {
	Shield,
	RotateCcw,
	Heart,
	ClipboardList,
	DatabaseBackup,
	MailCheck,
	Mails,
} from 'lucide-react';
import './tw-base.scss';

// Reusable components using available Force UI components and HTML
const FeatureCard = ( { icon: IconComponent, title, description } ) => (
	<div className="flex flex-col items-start text-left gap-2">
		<div className="text-[#0D7EE8]">
			<IconComponent size={ 24 } strokeWidth={ 1 } />
			<Text size={ 18 } lineHeight={ 28 } weight={ 600 } color="primary">
				{ title }
			</Text>
		</div>
		<Text size={ 14 } lineHeight={ 20 } weight={ 400 } color="secondary">
			{ description }
		</Text>
	</div>
);

const YouTubeVideo = () => (
	<div className="relative bg-gray-900 rounded-lg overflow-hidden shadow-2xl aspect-video w-full">
		<iframe
			src="https://www.youtube.com/embed/fFKJfbWLif4"
			title={ __( 'SureMail Introduction Video', 'sureforms' ) }
			className="w-full h-full"
			frameBorder="0"
			allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
			allowFullScreen
		/>
	</div>
);

const InstallSureMailButton = ( { pluginStatus, onClick } ) => {
	const getButtonText = () => {
		switch ( pluginStatus ) {
			case 'installing':
				return __( 'Installing SureMail Now…', 'sureforms' );
			case 'activating':
				return __( 'Activating SureMail Now…', 'sureforms' );
			case 'redirecting':
				return __( 'Redirecting to SureMail Dashboard…', 'sureforms' );
			case 'installed':
				return __( 'Activate SureMail Now', 'sureforms' );
			default:
				return __( 'Install SureMail Now', 'sureforms' );
		}
	};

	return (
		<Button
			variant="primary"
			onClick={ onClick }
			disabled={ [ 'installing', 'activating', 'redirecting' ].includes(
				pluginStatus
			) }
			loading={ [ 'installing', 'activating', 'redirecting' ].includes(
				pluginStatus
			) }
			size="lg"
		>
			{ getButtonText() }
		</Button>
	);
};

const SureMail = () => {
	const [ pluginStatus, setPluginStatus ] = useState(
		window.srfm_admin?.suremail_status || 'not_installed'
	);

	const mainFeatures = [
		{
			icon: DatabaseBackup,
			title: __( 'Multiple Backup Connections', 'sureforms' ),
			description: __(
				'Never worry about email failures. SureMail automatically reroutes through backup SMTP providers.',
				'sureforms'
			),
		},
		{
			icon: ClipboardList,
			title: __( 'Email Logs & Resending', 'sureforms' ),
			description: __(
				'Keep a complete record of every email sent from your WordPress site, and resend any email message with ease.',
				'sureforms'
			),
		},
		{
			icon: Shield,
			title: __( 'Reputation Shield', 'sureforms' ),
			description: __(
				"Reputation Shield protects your sender reputation by identifying and blocking potentially problematic emails before they're sent.",
				'sureforms'
			),
		},
		{
			icon: RotateCcw,
			title: __( 'Advanced Retry Logic', 'sureforms' ),
			description: __(
				'Failed emails are automatically retried through all of the configured connections to ensure seamless delivery.',
				'sureforms'
			),
		},
		{
			icon: MailCheck,
			title: __( 'Built-In Email Testing Tool', 'sureforms' ),
			description: __(
				'Easily send test emails for verifying your SMTP configuration before sending live emails to your customers and subscribers.',
				'sureforms'
			),
		},
		{
			icon: Mails,
			title: __( 'Multiple SMTP Providers', 'sureforms' ),
			description: __(
				'Integrate and switch between leading SMTP services like Amazon SES, Gmail, SendGrid, SMTP2GO, and many more.',
				'sureforms'
			),
		},
	];

	const activateSureMail = async () => {
		const activateFormData = new FormData();
		activateFormData.append(
			'action',
			'sureforms_recommended_plugin_activate'
		);
		activateFormData.append( 'security', srfm_admin.sfPluginManagerNonce );
		activateFormData.append( 'init', 'suremails/suremails.php' );

		const activateResponse = await wp.apiFetch( {
			url: srfm_admin.ajax_url,
			method: 'POST',
			body: activateFormData,
		} );

		if ( ! activateResponse.success ) {
			throw new Error( 'Activation failed' );
		}

		return activateResponse;
	};

	const installSureMail = async () => {
		console.log( 'working' );
		console.log( pluginStatus );
		try {
			// Check if plugin is already installed
			const isPluginInstalled = pluginStatus === 'installed';
			console.log( pluginStatus );
			if ( ! isPluginInstalled ) {
				setPluginStatus( 'installing' );
				// Install plugin
				const formData = new FormData();
				formData.append(
					'action',
					'sureforms_recommended_plugin_install'
				);
				formData.append(
					'_ajax_nonce',
					srfm_admin?.plugin_installer_nonce
				);
				formData.append( 'slug', 'suremails' );

				const response = await wp.apiFetch( {
					url: srfm_admin.ajax_url,
					method: 'POST',
					body: formData,
				} );

				if ( ! response.success ) {
					throw new Error( 'Installation failed' );
				}
			}

			// Activate plugin
			setPluginStatus( 'activating' );
			const activateResponse = await activateSureMail();

			if ( activateResponse.success ) {
				setPluginStatus( 'redirecting' );
				setTimeout( () => {
					window.location.href =
						srfm_admin.admin_url +
						'options-general.php?page=suremail#/dashboard';
				}, 1000 );
			}
		} catch ( error ) {
			console.error( 'Error installing/activating SureMail:', error );
			alert(
				__(
					'Plugin installation/activation failed. Please try again later.',
					'sureforms'
				)
			);
			setPluginStatus(
				pluginStatus ? 'installed' : 'not_installed'
			);
		}
	};

	return (
		<div className="min-h-screen mt-14">
			<style>{ `
				.notice, .update-nag, #wpfooter { display: none !important; }
				.wrap { margin: 0 !important; }
			` }</style>
			<div className="flex flex-col max-w-4xl mx-auto bg-white rounded-xl shadow-sm p-10 gap-6">
				{ /* Header Section */ }
				<div className="text-center gap-3">
					<div className="flex justify-center gap-6 p-2">
						<div className="flex items-center gap-8">
							<svg
								width="48"
								height="48"
								viewBox="0 0 24 24"
								fill="none"
								xmlns="http://www.w3.org/2000/svg"
							>
								<path d="M24 0H0V24H24V0Z" fill="#D54407" />
								<path
									d="M6.8501 5.14209H17.1358V8.57068H8.56439L6.8501 10.285V8.57068V5.14209Z"
									fill="white"
								/>
								<path
									d="M6.8501 10.2866H15.4215V13.7152H8.56439L6.8501 15.4294V13.7152V10.2866Z"
									fill="white"
								/>
								<path
									d="M6.8501 15.4272H11.9929V18.8558H6.8501V15.4272Z"
									fill="white"
								/>
							</svg>
							<Text
								size={ 30 }
								lineHeight={ 38 }
								weight={ 600 }
								color="primary"
							>
								+
							</Text>
							<svg
								width="48"
								height="48"
								viewBox="0 0 48 48"
								fill="none"
								xmlns="http://www.w3.org/2000/svg"
							>
								<path
									d="M46.5 0H1.5C0.671573 0 0 0.671573 0 1.5V46.5C0 47.3284 0.671573 48 1.5 48H46.5C47.3284 48 48 47.3284 48 46.5V1.5C48 0.671573 47.3284 0 46.5 0Z"
									fill="#0D7EE8"
								/>
								<path
									d="M12.8058 23.327C13.2233 23.6289 13.816 23.5298 14.0891 23.1236C14.391 22.7062 14.2918 22.1134 13.8857 21.8403L9.86562 18.9603C9.72275 18.8501 9.7352 18.7122 9.74142 18.6433C9.74772 18.5744 9.8003 18.4541 9.97325 18.3863L36.7218 11.5667C36.8772 11.5391 36.9687 11.603 37.0314 11.6781C37.0941 11.7533 37.1568 11.8285 37.0866 11.9889L26.952 37.6323C26.8818 37.7928 26.7552 37.8091 26.6976 37.8317C26.6286 37.8254 26.4908 37.8129 26.4055 37.6802L23.3421 32.0652C23.2455 31.9036 23.1666 31.7018 23.07 31.5402C21.871 28.8185 21.5684 26.8103 23.8186 24.832L29.6121 19.4706C30.0054 19.1171 30.0441 18.5368 29.7018 18.1722C29.3484 17.7788 28.7681 17.7401 28.4034 18.0824L22.3186 23.327C19.2527 26.0237 19.7632 29.3781 21.7032 32.9731L24.7665 38.588C25.1578 39.3323 25.9537 39.7519 26.8047 39.7178C27.029 39.6965 27.2709 39.635 27.4726 39.5559C28.0202 39.3415 28.4487 38.9077 28.6941 38.3462L38.8289 12.7028C39.1332 11.9519 38.9901 11.0772 38.4485 10.4583C37.9068 9.83934 37.0809 9.59769 36.2864 9.77589L9.509 16.6068C8.69697 16.8251 8.08302 17.4645 7.91037 18.2967C7.73765 19.1289 8.09382 19.9535 8.78577 20.447L12.8058 23.327Z"
									fill="white"
								/>
								<path
									d="M11.5395 31.0511C11.6148 31.0216 11.7053 30.9572 11.7708 30.9027L15.8196 27.349C16.1621 27.0412 16.1958 26.536 15.8978 26.2186C15.59 25.8761 15.0849 25.8424 14.7675 26.1404L10.7186 29.6941C10.3761 30.0019 10.3425 30.5071 10.6404 30.8245C10.8685 31.1113 11.2133 31.1788 11.5395 31.0511Z"
									fill="white"
								/>
								<path
									d="M11.0218 36.7353C11.0971 36.706 11.1879 36.6419 11.2534 36.5875L18.1346 30.5614C18.478 30.2546 18.5131 29.7496 18.216 29.4313C17.9092 29.0879 17.4041 29.0528 17.0859 29.3499L10.2047 35.376C9.86136 35.6828 9.82626 36.1879 10.1233 36.5061C10.3409 36.7685 10.7203 36.8524 11.0218 36.7353Z"
									fill="white"
								/>
							</svg>
							<Text
								size={ 30 }
								lineHeight={ 38 }
								weight={ 600 }
								color="primary"
							>
								=
							</Text>
							<Heart
								size={ 32 }
								className="text-black fill-current"
							/>
						</div>
					</div>
					<div className="flex justify-center gap-6 p-2">
						<div className="flex flex-col">
							<Text
								size={ 20 }
								lineHeight={ 30 }
								letterSpacing={ -0.5 }
								weight={ 600 }
							>
								{ __(
									'Ensure Every Form Submission Reaches the Inbox with SureMail',
									'sureforms'
								) }
							</Text>
							<Text
								size={ 16 }
								lineHeight={ 24 }
								weight={ 400 }
								color="secondary"
							>
								{ __(
									'SureForms and SureMail are the perfect pair! SureMail ensures that every form submission you receive is reliably delivered to your inbox. You\'ll never lose a lead, miss a support request, or overlook a customer inquiry again.',
									'sureforms'
								) }
							</Text>
						</div>
					</div>
					<div className="flex justify-center gap-6 p-2">
						<InstallSureMailButton
							pluginStatus={ pluginStatus }
							onClick={ installSureMail }
						/>
					</div>
				</div>

				{ /* Video Section */ }
				<div className="flex justify-center p-2 gap-2">
					<YouTubeVideo />
				</div>

				{ /* Features Section */ }
				<div className="flex flex-col p-2 gap-3">
					{ /* Group features into pairs */ }
					{ Array.from( {
						length: Math.ceil( mainFeatures.length / 2 ),
					} ).map( ( _, groupIndex ) => (
						<div key={ groupIndex } className="flex gap-6">
							{ mainFeatures
								.slice( ( groupIndex * 2 ), ( ( groupIndex * 2 ) + 2 ) )
								.map( ( feature, index ) => (
									<div
										key={ index }
										className="flex-1 p-4 gap-2"
									>
										<FeatureCard
											icon={ feature.icon }
											title={ feature.title }
											description={ feature.description }
										/>
									</div>
								) ) }
						</div>
					) ) }
				</div>

				{ /* Final CTA Section */ }
				<div className="flex flex-col justify-center gap-3 text-center">
					<div className="p-2 gap-6">
						<Text
							size={ 24 }
							lineHeight={ 32 }
							weight={ 600 }
							letterSpacing={ -0.6 }
						>
							{ __(
								'Forms Submitted. Emails Delivered. Every Time.',
								'sureforms'
							) }
						</Text>
					</div>
					<div className="p-2 gap-6">
						<InstallSureMailButton
							pluginStatus={ pluginStatus }
							onClick={ installSureMail }
						/>
					</div>
				</div>
			</div>
		</div>
	);
};

// Render the SureMail component when the DOM is ready
document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'srfm-suremail-container' );
	if ( container ) {
		const root = createRoot( container );
		root.render( <SureMail /> );
	}
} );

export default SureMail;
