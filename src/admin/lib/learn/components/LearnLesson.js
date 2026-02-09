import { __, sprintf } from '@wordpress/i18n';
import { Badge, Button, Container, Label } from '@bsf/force-ui';
import { useState, useEffect } from '@wordpress/element';
import {
	Check,
	ChevronDown,
	ChevronUp,
	Circle,
	CircleCheckBig,
	Play,
} from 'lucide-react';

/**
 * Component for rendering a single expandable lesson card
 *
 * @param {Object}   props                    - Component props
 * @param {Object}   props.lesson             - Lesson data object
 * @param {string}   props.moduleId           - ID of the parent module
 * @param {boolean}  props.isExpanded         - Whether this lesson is expanded
 * @param {Function} props.onToggle           - Callback to toggle expansion
 * @param {Function} props.onCompletionChange - Callback when lesson completion status changes
 * @return {JSX.Element|null} - Rendered lesson component
 */
const LearnLesson = ( {
	lesson,
	moduleId,
	isExpanded = false,
	onToggle,
	onCompletionChange,
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
		if ( headerAction?.url ) {
			window.open( headerAction.url, '_blank', 'noopener,noreferrer' );
		}
	};

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
									<div className="flex items-center justify-center w-6 h-6 rounded-full">
										{ getStatusIcon() }
									</div>
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

						{ headerAction?.url && (
							<Container.Item className="hidden sm:block">
								<Button
									variant="outline"
									size="xs"
									onClick={ handleHeaderActionClick }
								>
									{ headerAction?.label }
								</Button>
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
								<Label className="text-sm text-text-secondary font-normal px-2 leading-relaxed">
									{ description }
								</Label>
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
								{ /* For video lessons, show View Documentation button if docsUrl exists */ }
								{ hasVideo && docsUrl && (
									<Container.Item>
										<Button
											variant="primary"
											size="md"
											className="w-full shadow-sm"
											onClick={ handleDocsClick }
										>
											{ __( 'View Documentation', 'sureforms' ) }
										</Button>
									</Container.Item>
								) }
								{ /* For non-video lessons, show the action button */ }
								{ ! hasVideo && action?.url && (
									<Container.Item>
										<Button
											variant="primary"
											size="md"
											className="w-full shadow-sm"
											onClick={ handleActionClick }
										>
											{ action?.label ||
												__( 'Get Started', 'sureforms' ) }
										</Button>
									</Container.Item>
								) }
								<Container.Item>
									<Button
										variant="outline"
										size="md"
										icon={ <Check className="size-5" /> }
										className="w-full shadow-sm"
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
								</Container.Item>
							</Container>
						</Container.Item>
					</Container>
				</Container.Item>
			) }
		</Container>
	);
};

export default LearnLesson;
