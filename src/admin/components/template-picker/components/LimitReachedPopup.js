import { __ } from '@wordpress/i18n';
import Header from './Header.js';
import AiFormBuilder from './AiFormBuilder.js';
import { Button, Container, Label } from '@bsf/force-ui';
import { applyFilters } from '@wordpress/hooks';
import { Zap, X } from 'lucide-react';

const LimitReachedPopup = ( {
	title = '',
	paraOne,
	paraTwo,
	buttonText,
	onclick,
	deactivatedLicense = false,
	paraTitle = '',
} ) => {
	// Get filter values
	const {
		paraOne: filteredParaOne,
		paraTwo: filteredParaTwo,
		title: filteredTitle,
		activateLicenseButton: ActivateLicenseButton,
		paraTitle: filteredParaTitle,
	} = applyFilters( 'srfm.aiFormScreen.freeCreds.expired', null ) || {};

	const finalTitle = deactivatedLicense ? filteredTitle : title;
	const finalParaOne = deactivatedLicense ? filteredParaOne : paraOne;
	const finalParaTwo = deactivatedLicense ? filteredParaTwo : paraTwo;
	const finalParaTitle = deactivatedLicense ? filteredParaTitle : paraTitle;
	const FinalButton =
		deactivatedLicense && ActivateLicenseButton ? (
			<ActivateLicenseButton />
		) : (
			<Button size="md" variant="primary" onClick={ onclick }>
				{ buttonText ?? __( 'Connect Now', 'sureforms' ) }
			</Button>
		);

	const is_pro_active =
		srfm_admin?.is_pro_active && srfm_admin?.is_pro_license_active;

	const renderLimitReachedContent = () => (
		<Container
			direction="column"
			justify="center"
			align="center"
			className="fixed inset-0 bg-overlay-background z-[99999999]"
		>
			<Container
				direction="column"
				className="bg-background-primary gap-4 p-5 rounded-lg max-w-md shadow-lg"
			>
				<Container.Item className="relative pt-2">
					<Label
						variant="neutral"
						className="text-xs font-semibold flex gap-3 text-brand-800"
					>
						<span className="pt-1">{ <Zap className="size-4 text-brand-800" /> }</span>
						{ finalTitle }
						<span
							className="absolute -top-2 -right-1 cursor-pointer"
							onClick={ () =>
								( window.location.href =
									srfm_admin.site_url +
									'/wp-admin/post-new.php?post_type=sureforms_form' )
							}
						>
							<X className="size-4" />
						</span>
					</Label>
				</Container.Item>

				<Container.Item className="flex flex-col gap-1">
					<Label
						size="md"
						className="text-text-primary font-semibold text-lg"
					>
						{ finalParaTitle }
					</Label>
					<Label
						size="md"
						className="text-text-secondary font-normal"
					>
						{ finalParaOne }
					</Label>
					<Label
						size="md"
						className="text-text-secondary font-normal"
					>
						{ finalParaTwo }
					</Label>
				</Container.Item>

				<Container.Item className="flex flex-col w-full gap-3 pb-2">
					{ FinalButton }

					{ ! is_pro_active ? (
						<hr className="border-b-5 border-x-0 border-t-0 w-full border-solid border-border-subtle" />
					) : ( ''
					) }

					{ ! is_pro_active && ! deactivatedLicense && ! ActivateLicenseButton ? (
						<Button
							size="md"
							variant="outline"
							onClick={ () => {
								window.location.href = `${ srfm_admin.site_url }/wp-admin/post-new.php?post_type=sureforms_form`;
							} }
						>
							{ __( 'Or Build It Yourself', 'sureforms' ) }
						</Button>
					) : (
						''
					) }
				</Container.Item>
			</Container>
		</Container>
	);

	return (
		<>
			<Header />
			{ renderLimitReachedContent() }
			<AiFormBuilder />
		</>
	);
};

export default LimitReachedPopup;
