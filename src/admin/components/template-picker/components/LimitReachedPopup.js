import { __ } from '@wordpress/i18n';
import Header from './Header.js';
import ICONS from './icons.js';
import AiFormBuilder from './AiFormBuilder.js';
import { Button, Container, Label } from '@bsf/force-ui';
import { applyFilters } from '@wordpress/hooks';

const LimitReachedPopup = ( {
	title = __( 'Limit Reached', 'sureforms' ),
	paraOne,
	paraTwo,
	buttonText,
	onclick,
	deactivatedLicense = false,
} ) => {
	// Get filter values
	const {
		paraOne: filteredParaOne,
		paraTwo: filteredParaTwo,
		title: filteredTitle,
		activateLicenseButton: ActivateLicenseButton,
	} = applyFilters( 'srfm.aiFormScreen.freeCreds.expired', null ) || {};

	const finalTitle = deactivatedLicense ? filteredTitle : title;
	const finalParaOne = deactivatedLicense ? filteredParaOne : paraOne;
	const finalParaTwo = deactivatedLicense ? filteredParaTwo : paraTwo;
	const FinalButton =
		deactivatedLicense && ActivateLicenseButton ? (
			<ActivateLicenseButton />
		) : (
			<Button size="md" variant="primary" onClick={ onclick }>
				{ buttonText ?? __( 'Connect Now', 'sureforms' ) }
			</Button>
		);

	const renderLimitReachedContent = () => (
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
						className="text-lg font-bold flex gap-3"
					>
						{ ! deactivatedLicense && (
							<span className="pt-1">{ ICONS.warning }</span>
						) }
						{ finalTitle }
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
						{ finalParaOne }
					</Label>
					<Label
						size="md"
						className="text-text-secondary font-normal"
					>
						{ finalParaTwo }
					</Label>
				</Container.Item>

				<Container.Item className="flex flex-col w-full gap-4 pb-2">
					{ FinalButton }
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
