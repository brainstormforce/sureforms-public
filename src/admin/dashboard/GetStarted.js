import { __ } from '@wordpress/i18n';
import { Button, Container, Label, Title } from '@bsf/force-ui';
import { Plus, X } from 'lucide-react';
import ExternalLinkIcon from '@Image/external-link-icon.js';
import { useState, useEffect } from '@wordpress/element';

export default () => {
	const siteUrl = srfm_admin.site_url;
	const [ popupVideo, setPopupVideo ] = useState( null );

	const videoUrl = 'https://www.youtube.com/embed/it16jGnZBus?showinfo=0&rel=0&autoplay=1';

	useEffect( () => {
		const handleKeyDown = ( e ) => {
			if ( e.key === 'Escape' ) {
				setPopupVideo( null );
			}
		};
		window.addEventListener( 'keydown', handleKeyDown );
		return () => window.removeEventListener( 'keydown', handleKeyDown );
	}, [] );

	return (
		<Container
			className="w-full bg-background-primary p-4 gap-8 shadow-sm-blur-1 rounded-xl border-0.5 border-solid border-border-subtle"
			containerType="grid"
			cols={ 12 }
			align="center"
		>
			<Container.Item className="flex flex-col gap-6 p-2 col-span-12 md:col-span-7 lg:col-span-7">
				<Container direction="column" className="gap-1">
					<Title
						size="md"
						tag="h3"
						title={ __( 'Welcome to SureForms!', 'sureforms' ) }
					/>
					<Label
						size="md"
						variant="neutral"
						className="font-normal text-text-secondary"
					>
						{ __(
							'SureForms is a WordPress plugin that enables users to create beautiful looking forms through a drag-and-drop interface, without needing to code. It integrates with the WordPress block editor.',
							'sureforms'
						) }
					</Label>
				</Container>
				<Container className="flex-wrap gap-3">
					<Button
						className="shadow-sm-blur-1 gap-1"
						icon={
							<Plus
								aria-label={ __(
									'Create New Form',
									'sureforms'
								) }
							/>
						}
						iconPosition="right"
						size="md"
						variant="primary"
						onClick={ () => {
							window.location.href = `${ siteUrl }/wp-admin/admin.php?page=add-new-form`;
						} }
					>
						{ __( 'Create New Form', 'sureforms' ) }
					</Button>

					<Button
						className="gap-1"
						icon={
							<ExternalLinkIcon
								aria-label={ __(
									'Read Full Guide',
									'sureforms'
								) }
							/>
						}
						iconPosition="right"
						size="md"
						variant="ghost"
						onClick={ () =>
							window.open(
								'https://sureforms.com/docs/',
								'_blank'
							)
						}
					>
						{ __( 'Read Full Guide', 'sureforms' ) }
					</Button>
				</Container>
			</Container.Item>

			<Container.Item className="col-span-12 md:col-span-5 lg:col-span-5 p-2">
				<div
					className="relative aspect-video cursor-pointer group"
					onClick={ () => {
						setPopupVideo( videoUrl );
					} }
				>
					<img
						src="https://img.youtube.com/vi/it16jGnZBus/hqdefault.jpg"
						alt={ __( 'SureForms Video Thumbnail', 'sureforms' ) }
						className="w-full h-full rounded border border-solid border-border-subtle object-cover"
					/>
					<div className="absolute inset-0 flex items-center justify-center">
						<div className="w-14 h-14 flex items-center justify-center bg-black bg-opacity-50 group-hover:bg-opacity-80 transition-all duration-300 rounded-full">
							<svg
								className="w-8 h-8 text-white"
								viewBox="0 0 24 24"
								fill="currentColor"
							>
								<path d="M8 5v14l11-7L8 5z" />
							</svg>
						</div>
					</div>
				</div>
			</Container.Item>

			{ /* Popup Video */ }
			{ popupVideo && (
				<div
					className="fixed inset-0 flex items-center justify-center bg-black bg-opacity-70 cursor-pointer z-[9999] w-full"
					onClick={ () => setPopupVideo( null ) }
				>
					{ /* Close Button */ }
					<div className="absolute top-10 right-8 text-white cursor-pointer">
						<X
							size={ 24 }
							onClick={ ( e ) => {
								e.stopPropagation();
								setPopupVideo( null );
							} }
						/>
					</div>

					<div
						className="relative rounded-lg shadow-lg cursor-default w-full max-w-[1206px] "
						onClick={ ( e ) => e.stopPropagation() }
					>
						<iframe
							className="w-full aspect-video rounded-lg lg:h-188 sm:h-120 h-60"
							src={ popupVideo }
							title={ __(
								'SureForms: Custom WordPress Forms MADE SIMPLE',
								'sureforms'
							) }
							allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
							allowFullScreen
						></iframe>
					</div>
				</div>
			) }
		</Container>
	);
};
