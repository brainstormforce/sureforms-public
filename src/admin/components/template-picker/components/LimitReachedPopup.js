import { __ } from '@wordpress/i18n';
import ICONS from './icons.js';
import { Button, Container, Label } from '@bsf/force-ui';
import { applyFilters } from '@wordpress/hooks';
import { Zap, ChevronRight } from 'lucide-react';
import { useEffect } from '@wordpress/element';

const LimitReachedPopup = ( {
	title = '',
	paraOne = '',
	paraTwo = '',
	buttonText = '',
	onclick = () => {},
	deactivatedLicense = false,
	paraTitle = '',
	features = [],
	showFeatures = false,
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

	useEffect( () => {
		if ( showFeatures ) {
			localStorage.setItem( 'srfm_ai_banner_closed', 'true' );
		}
	}, [ showFeatures ] );

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
						<span className="pt-1">
							{ <Zap className="size-4 text-brand-800" /> }
						</span>
						{ finalTitle }
						<span
							className="absolute top-[-10px] right-[-15px] cursor-pointer"
							onClick={ () =>
								( window.location.href =
									srfm_admin.site_url +
									'/wp-admin/admin.php?page=sureforms_menu' )
							}
						>
							{ ICONS.close }
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
						className="text-text-secondary text-sm font-normal"
					>
						{ finalParaOne }
					</Label>
					{ showFeatures ? (
						<Container className="flex flex-col gap-2">
							{ features.map( ( item, index ) => (
								<Container.Item
									key={ index }
									className="flex flex-row gap-1.5 text-sm text-text-primary items-center"
								>
									<ChevronRight className="w-3.5 h-3.5" />
									<Label
										size="sm"
										className="font-normal break-words"
									>
										{ item }
									</Label>
								</Container.Item>
							) ) }

							<Container.Item className="flex flex-row gap-1.5 text-sm text-text-primary items-center">
								<ChevronRight className="w-3.5 h-3.5" />
								<Container.Item className="flex flex-row text-sm gap-1">
									<span>{ __( 'and ', 'sureforms' ) }</span>
									<Label
										as="a"
										size="sm"
										className="font-normal break-words underline cursor-pointer text-brand-800 hover:text-brand-900"
										onClick={ () =>
											window.open(
												'https://sureforms.com',
												'_blank',
												'noreferrer'
											)
										}
									>
										{ __( 'more…', 'sureforms' ) }
									</Label>
								</Container.Item>
							</Container.Item>
						</Container>
					) : (
						<Label
							size="md"
							className="text-text-secondary text-sm font-normal"
						>
							{ finalParaTwo }
						</Label>
					) }
				</Container.Item>

				{ ! is_pro_active && ! deactivatedLicense ? (
					<hr className="border-b-5 border-x-0 border-t-0 w-full border-solid border-border-subtle" />
				) : (
					''
				) }

				<Container.Item className="flex flex-col w-full gap-3 pb-2">
					{ FinalButton }

					{ ! is_pro_active &&
					! deactivatedLicense &&
					! ActivateLicenseButton ? (
							<Button
								size="md"
								variant="ghost"
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

	return <>{ renderLimitReachedContent() }</>;
};

export default LimitReachedPopup;
