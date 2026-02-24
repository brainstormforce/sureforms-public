import { __, sprintf } from '@wordpress/i18n';
import { Badge, Button, Container, Dialog, Label, Skeleton, Tooltip } from '@bsf/force-ui';
import { useState, useEffect } from '@wordpress/element';
import {
	ChevronDown,
	ChevronUp,
	Circle,
	CircleAlert,
	CircleCheckBig,
	Play,
} from 'lucide-react';
import { addQueryParam } from '@Utils/Helpers';

/**
 * Component for rendering a single expandable lesson card
 *
 * @param {Object}                props                    - Component props
 * @param {Object}                props.lesson             - Lesson data object
 * @param {string}                props.moduleId           - ID of the parent module
 * @param {boolean}               props.isExpanded         - Whether this lesson is expanded
 * @param {Function}              props.onToggle           - Callback to toggle expansion
 * @param {Function}              props.onCompletionChange - Callback when lesson completion status changes
 * @param {number|null|undefined} props.latestFormId
 * @return {JSX.Element|null} - Rendered lesson component
 */
const LearnLesson = ( {
	lesson,
	moduleId,
	isExpanded = false,
	onToggle,
	onCompletionChange,
	latestFormId,
} ) => {
	const {
		id,
		title,
		description,
		learn,
		action,
		headerAction,
		docsUrl,
		completed = false,
		timeEstimate,
	} = lesson;

	const [ isCompleted, setIsCompleted ] = useState( completed );
	const [ isVideoPlaying, setIsVideoPlaying ] = useState( false );
	const [ isImageDialogOpen, setIsImageDialogOpen ] = useState( false );
	const [ isImageLoaded, setIsImageLoaded ] = useState( false );

	// Update local state when completed prop changes
	useEffect( () => {
		setIsCompleted( completed );
	}, [ completed ] );

	// Reset video playing state when lesson is collapsed
	useEffect( () => {
		if ( ! isExpanded ) {
			setIsVideoPlaying( false );
		}
	}, [ isExpanded ] );

	if ( ! id || ! title ) {
		return null;
	}

	// Determine status based on completion and expansion
	const getStatus = () => {
		if ( isCompleted ) {
			return 'completed';
		}
		if ( isExpanded ) {
			return 'in-progress';
		}
		return 'incomplete';
	};

	const status = getStatus();

	const handleMarkAsCompleted = ( e ) => {
		e?.stopPropagation();
		const newStatus = ! isCompleted;
		setIsCompleted( newStatus );

		// Call parent callback to update state
		if ( onCompletionChange ) {
			onCompletionChange( moduleId, id, newStatus );
		}
	};

	const handleActionClick = ( e ) => {
		e?.stopPropagation();
		if ( action?.url ) {
			if ( action?.isExternal ) {
				window.open( action?.url, '_blank', 'noopener,noreferrer' );
			} else {
				window.location.href = action?.url;
			}
		}
	};

	const handleHeaderActionClick = ( e ) => {
		e?.stopPropagation();
		if ( ! headerAction?.url ) {
			return;
		}

		if ( headerAction.dynamic === 'latest-form' ) {
			// Prefer REST-fetched ID; fall back to localStorage for resilience
			const resolvedId =
				latestFormId !== undefined && latestFormId !== null
					? latestFormId
					: localStorage.getItem( 'srfmLearnFormId' );
			const adminBase = `${ srfm_admin?.site_url }/wp-admin/`;
			const source = headerAction.source || 'learn-setup-fields';
			const url = resolvedId
				? `${ adminBase }post.php?post=${ resolvedId }&action=edit&source=${ source }`
				: `${ adminBase }${ headerAction.url }`;
			window.open( url, '_blank', 'noopener,noreferrer' );
			return;
		}

		window.open( headerAction.url, '_blank', 'noopener,noreferrer' );
	};

	// Disabled when we've confirmed no forms exist on the site
	const isHeaderActionDisabled =
		headerAction?.dynamic === 'latest-form' && latestFormId === null;

	const handlePlayVideo = ( e ) => {
		e?.stopPropagation();
		setIsVideoPlaying( true );
	};

	// Handler for opening documentation.
	const handleDocsClick = ( e ) => {
		e?.stopPropagation();
		if ( docsUrl ) {
			window.open( docsUrl, '_blank', 'noopener,noreferrer' );
		}
	};

	// Handler for opening the image dialog.
	const handleOpenImageDialog = ( e ) => {
		e?.stopPropagation();
		setIsImageLoaded( false );
		setIsImageDialogOpen( true );
	};

	const getStatusIcon = () => {
		switch ( status ) {
			case 'completed':
				return (
					<CircleCheckBig className="size-4 text-support-success" />
				);
			case 'in-progress':
				return (
					<Circle className="size-4 text-support-info" />
				);
			default:
				return (
					<Circle className="size-4 text-text-primary" />
				);
		}
	};

	const getStatusText = () => {
		switch ( status ) {
			case 'completed':
				return __( 'Completed', 'sureforms' );
			case 'in-progress':
				return __( 'In Progress', 'sureforms' );
			default:
				return __( 'Incomplete', 'sureforms' );
		}
	};

	// Get video URL from learn content
	const getVideoUrl = () => {
		if ( learn?.type === 'dialog' && learn?.content?.type === 'video' ) {
			return learn?.content?.data?.url || '';
		}
		return '';
	};

	// Build a GIF-like YouTube iframe URL: autoplay, muted, looped, no controls/branding.
	const getGifVideoUrl = () => {
		const baseUrl = learn?.content?.data?.url || '';
		const videoIdMatch = baseUrl.match( /embed\/([^?&]+)/ );
		const videoId = videoIdMatch ? videoIdMatch[ 1 ] : '';
		const params = new URLSearchParams( {
			autoplay: 1,
			mute: 1,
			loop: 1,
			playlist: videoId,
			controls: 0,
			modestbranding: 1,
			rel: 0,
			playsinline: 1,
			disablekb: 1,
		} );
		return `${ baseUrl }?${ params.toString() }`;
	};

	// Get video thumbnail from learn content
	const getVideoThumbnail = () => {
		const videoUrl = getVideoUrl();
		if ( videoUrl ) {
			// Extract YouTube video ID and create thumbnail URL
			const youtubeMatch = videoUrl.match(
				/(?:youtube\.com\/embed\/|youtu\.be\/)([^?&]+)/
			);
			if ( youtubeMatch ) {
				return `https://img.youtube.com/vi/${ youtubeMatch[ 1 ] }/maxresdefault.jpg`;
			}
		}
		return null;
	};

	// Get video duration from learn content (in seconds) and format as "X minutes"
	const getVideoDuration = () => {
		if ( learn?.type === 'dialog' && learn?.content?.type === 'video' ) {
			const durationInSeconds = learn?.content?.data?.duration;
			if ( durationInSeconds && typeof durationInSeconds === 'number' ) {
				const minutes = Math.round( durationInSeconds / 60 );
				return minutes === 1
					? __( '1 minute', 'sureforms' )
					: sprintf(
						// translators: %d is the number of minutes.
						__( '%d minutes', 'sureforms' ),
						minutes
					  );
			}
		}
		return null;
	};

	const videoUrl = getVideoUrl();
	const videoThumbnail = getVideoThumbnail();
	const videoDuration = getVideoDuration();
	const hasVideo =
		learn?.type === 'dialog' && learn?.content?.type === 'video';
	const hasImage =
		learn?.type === 'dialog' && learn?.content?.type === 'image';
	const hasGifVideo =
		learn?.type === 'dialog' && learn?.content?.type === 'gif-video';

	return (
		<Container
			containerType="flex"
			direction="column"
			className={ `border border-solid rounded-lg transition-all duration-200 ${
				isExpanded
					? 'bg-background-secondary border-text-tertiary'
					: 'border-border-subtle'
			}` }
		>
			{ /* Lesson Header - Clickable */ }
			<Container.Item>
				<div
					className="p-4 cursor-pointer"
					onClick={ onToggle }
					onKeyDown={ ( e ) => {
						if ( e.key === 'Enter' || e.key === ' ' ) {
							e.preventDefault();
							onToggle();
						}
					} }
					role="button"
					tabIndex={ 0 }
					aria-expanded={ isExpanded }
				>
					<Container
						containerType="flex"
						direction="row"
						align="center"
						gap="md"
					>
						<Container.Item className="flex-1 min-w-0">
							<Container
								containerType="flex"
								direction="row"
								align="center"
								gap="sm"
							>
								<Container.Item>
									<Tooltip
										content={
											isCompleted
												? __( 'Mark as incomplete', 'sureforms' )
												: __( 'Mark as complete', 'sureforms' )
										}
										arrow={ true }
										placement="top"
										tooltipPortalId="srfm-learn-root"
									>
										<button
											type="button"
											className={ `flex items-center justify-center w-6 h-6 rounded-full cursor-pointer bg-transparent border-0 p-0 transition-all duration-200 focus:outline-none ${ isCompleted ? 'hover:opacity-60' : 'hover:scale-110' }` }
											onClick={ handleMarkAsCompleted }
											onKeyDown={ ( e ) => {
												if (
													e.key === 'Enter' ||
													e.key === ' '
												) {
													e.stopPropagation();
												}
											} }
											aria-label={
												isCompleted
													? __(
														'Mark as incomplete',
														'sureforms'
													  )
													: __(
														'Mark as complete',
														'sureforms'
													  )
											}
										>
											{ getStatusIcon() }
										</button>
									</Tooltip>
								</Container.Item>
								<Container.Item className="flex-1 min-w-0">
									<Label
										className={ `text-base font-normal truncate block ${
											isCompleted
												? 'text-misc-skeleton-element'
												: 'text-text-primary'
										}` }
									>
										{ title }
									</Label>
								</Container.Item>
							</Container>
						</Container.Item>

						{ /* Time indicator - show for non-completed lessons, and for completed lessons only when expanded */ }
						{ ( ! isCompleted || isExpanded ) && ( videoDuration || timeEstimate ) && (
							<Container.Item className="hidden sm:block">
								<Badge
									label={ videoDuration || timeEstimate }
									size="sm"
									variant="neutral"
									type="pill"
								/>
							</Container.Item>
						) }

						{ hasVideo && (
							<Container.Item className="hidden sm:block">
								<Label
									className={ `text-sm font-normal whitespace-nowrap ${
										status === 'completed'
											? 'text-support-success'
											: 'text-text-tertiary'
									}` }
								>
									{ getStatusText() }
								</Label>
							</Container.Item>
						) }

						<Container.Item>
							<div className="flex items-center justify-center w-6 h-6">
								{ isExpanded ? (
									<ChevronUp className="size-4 text-text-tertiary" />
								) : (
									<ChevronDown className="size-4 text-text-tertiary" />
								) }
							</div>
						</Container.Item>
					</Container>
				</div>
			</Container.Item>

			{ /* Expanded Content */ }
			{ isExpanded && (
				<Container.Item>
					<Container
						containerType="flex"
						direction="column"
						gap="md"
						className="p-4 pt-0"
					>
						{ /* Video Section */ }
						{ hasVideo && (
							<Container.Item>
								<div className="relative rounded-lg border border-solid border-border-subtle overflow-hidden aspect-video">
									{ isVideoPlaying ? (
										// Video Player
										<iframe
											src={ `${ videoUrl }?autoplay=1&rel=0` }
											title={ title }
											className="absolute inset-0 w-full h-full border-none"
											allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
											allowFullScreen
										/>
									) : (
										// Thumbnail with Play Button
										<>
											<img
												src={ videoThumbnail }
												alt={ title }
												className="absolute inset-0 w-full h-full object-cover"
											/>
											<div className="absolute inset-0 flex items-center justify-center bg-black/20">
												<Button
													variant="white"
													size="lg"
													icon={
														<Play className="size-5 text-black fill-black" />
													}
													className="rounded-full px-6 py-6 shadow-2xl border-0 outline-none"
													aria-label={ __(
														'Play video',
														'sureforms'
													) }
													onClick={ handlePlayVideo }
												/>
											</div>
										</>
									) }
								</div>
							</Container.Item>
						) }

						{ /* Description */ }
						{ description && (
							<Container.Item>
								<div className="flex items-start justify-between gap-12 px-2">
									<Label className="text-sm text-text-secondary font-normal leading-relaxed text-justify">
										{ description }
									</Label>
									<div className="flex items-center gap-1 flex-shrink-0">
										{ ! hasVideo && ( hasImage || hasGifVideo ) && (
											<Tooltip content={ __( 'View Steps', 'sureforms' ) } arrow={ true } tooltipPortalId="srfm-learn-root">
												<Button
													variant="ghost"
													size="xs"
													icon={ <CircleAlert className="size-5" /> }
													onClick={ handleOpenImageDialog }
													aria-label={ __( 'View Steps', 'sureforms' ) }
												/>
											</Tooltip>
										) }
										{ ! hasVideo && ! hasImage && ! hasGifVideo && action?.url && (
											<Tooltip content={ __( 'View Steps', 'sureforms' ) } arrow={ true } tooltipPortalId="srfm-learn-root">
												<Button
													variant="ghost"
													size="xs"
													icon={ <CircleAlert className="size-5" /> }
													onClick={ handleActionClick }
													aria-label={ __( 'View Steps', 'sureforms' ) }
												/>
											</Tooltip>
										) }
										{ isExpanded && headerAction?.url && (
											isHeaderActionDisabled ? (
												<Tooltip
													content={ __( 'Please create a form first', 'sureforms' ) }
													arrow={ true }
													placement="top"
													tooltipPortalId="srfm-learn-root"
												>
													<span className="inline-block cursor-not-allowed">
														<Button
															variant="outline"
															size="sm"
															className="text-link-primary pointer-events-none opacity-50"
															disabled={ true }
														>
															{ headerAction?.label }
														</Button>
													</span>
												</Tooltip>
											) : (
												<Button
													variant="outline"
													size="sm"
													className="text-link-primary"
													onClick={ handleHeaderActionClick }
												>
													{ headerAction?.label }
												</Button>
											)
										) }
									</div>
								</div>
							</Container.Item>
						) }

						{ /* Action Buttons */ }
						<Container.Item>
							<Container
								containerType="flex"
								direction="column"
								gap="md"
								className="px-2"
							>
								{ hasVideo && (
									<Container.Item>
										<div className="flex gap-3">
											{ srfm_admin?.is_pro_active ? (
												docsUrl && (
													<Button
														variant="ghost"
														size="md"
														className="flex-1 shadow-sm text-link-primary bg-background-secondary outline-none shadow-none"
														onClick={ handleDocsClick }
													>
														{ __( 'Documentation', 'sureforms' ) }
													</Button>
												)
											) : (
												<Button
													variant="ghost"
													size="md"
													className="flex-1 shadow-sm text-link-primary bg-background-secondary outline-none shadow-none"
													onClick={ ( e ) => {
														e?.stopPropagation();
														window.open(
															addQueryParam(
																srfm_admin?.sureforms_pricing_page || 'https://sureforms.com/pricing/',
																'learn-section-upgrade-cta'
															),
															'_blank',
															'noopener,noreferrer'
														);
													} }
												>
													{ __( 'Upgrade to Pro', 'sureforms' ) }
												</Button>
											) }
											<Button
												variant="ghost"
												size="md"
												className="flex-1 shadow-sm bg-background-secondary outline-none shadow-none"
												onClick={ handleMarkAsCompleted }
											>
												{ isCompleted
													? __(
														'Mark as Incomplete',
														'sureforms'
													  )
													: __(
														'Mark as Completed',
														'sureforms'
													  ) }
											</Button>
										</div>
									</Container.Item>
								) }
							</Container>
						</Container.Item>
					</Container>
				</Container.Item>
			) }

			{ /* Gif Video Dialog - YouTube video playing muted, autoplayed, looped like a GIF */ }
			{ hasGifVideo && (
				<Dialog
					open={ isImageDialogOpen }
					setOpen={ setIsImageDialogOpen }
					exitOnClickOutside={ true }
				>
					<Dialog.Backdrop />
					<Dialog.Panel className="!w-full !max-w-4xl">
						<Dialog.Header className="flex flex-row items-center justify-between">
							<Dialog.Title>{ title }</Dialog.Title>
							<Dialog.CloseButton />
						</Dialog.Header>
						<Dialog.Body>
							<div className="relative w-full rounded-lg overflow-hidden aspect-video">
								<iframe
									src={ isImageDialogOpen ? getGifVideoUrl() : '' }
									title={ learn?.content?.data?.alt || title }
									className="absolute inset-0 w-full h-full border-none"
									allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
									allowFullScreen
								/>
							</div>
						</Dialog.Body>
						{ docsUrl && (
							<Dialog.Footer>
								<Button
									variant="outline"
									size="sm"
									onClick={ () => window.open( docsUrl, '_blank', 'noopener,noreferrer' ) }
								>
									{ __( 'View Documentation', 'sureforms' ) }
								</Button>
							</Dialog.Footer>
						) }
					</Dialog.Panel>
				</Dialog>
			) }

			{ /* Image Dialog */ }
			{ hasImage && (
				<Dialog
					open={ isImageDialogOpen }
					setOpen={ setIsImageDialogOpen }
					exitOnClickOutside={ true }
				>
					<Dialog.Backdrop />
					<Dialog.Panel className="!w-auto !max-w-4xl">
						<Dialog.Header className="flex flex-row items-center justify-between">
							<Dialog.Title>{ title }</Dialog.Title>
							<Dialog.CloseButton />
						</Dialog.Header>
						<Dialog.Body>
							<div className="relative w-full rounded-lg overflow-hidden">
								{ ! isImageLoaded && (
									<Skeleton className="w-full h-64" />
								) }
								<img
									src={ learn?.content?.data?.src }
									alt={ learn?.content?.data?.alt || title }
									className={ `w-full h-auto border border-solid border-border-subtle rounded-lg ${ isImageLoaded ? 'block' : 'hidden' }` }
									onLoad={ () => setIsImageLoaded( true ) }
								/>
							</div>
						</Dialog.Body>
						{ docsUrl && (
							<Dialog.Footer>
								<Button
									variant="outline"
									size="sm"
									onClick={ () => window.open( docsUrl, '_blank', 'noopener,noreferrer' ) }
								>
									{ __( 'View Documentation', 'sureforms' ) }
								</Button>
							</Dialog.Footer>
						) }
					</Dialog.Panel>
				</Dialog>
			) }
		</Container>
	);
};

export default LearnLesson;
