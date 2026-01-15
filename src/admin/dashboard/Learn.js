import { __ } from '@wordpress/i18n';
import { Container, Text, Button } from '@bsf/force-ui';
import { Learn as LearnComponent } from '@Admin/lib/learn';
import Header from '../components/Header';
import { Video } from 'lucide-react';
import { useState, useRef } from '@wordpress/element';

const LearnPage = () => {
	const [ showVideo, setShowVideo ] = useState( false );
	const iframeRef = useRef( null );

	const handleToggleVideo = () => {
		setShowVideo( ( prevState ) => ! prevState );
	};

	return (
		<Container className="h-full" direction="column" gap={ 0 }>
			<Header />
			<main className="bg-background-secondary min-h-[calc(100vh_-_8rem)]">
				<h1 className="sr-only">{ __( 'Learn', 'sureforms' ) }</h1>
				<Container
					className="md:p-8 sm:p-6 p-[0.7rem]"
					cols={ 12 }
					containerType="grid"
					gap="2xl"
				>
					<Container.Item
						className="flex flex-col gap-8"
						colSpan={ { lg: 8, md: 10, sm: 12 } }
						colStart={ { lg: 3, md: 2, sm: 0 } }
					>
						<div className="flex flex-col gap-6 sm:gap-8 bg-background-primary rounded-lg shadow-sm p-4 sm:p-6">
							<div className="relative flex flex-col items-start gap-2">
								<Text size={ 16 } weight={ 600 }>
									{ __( 'Learn', 'sureforms' ) }
								</Text>

								<Text size={ 14 }>
									{ __(
										'Customize your website using SureForms with step-by-step guide and launch with confidence.',
										'sureforms'
									) }
								</Text>

								<Button
									className="absolute right-0 whitespace-nowrap"
									variant="ghost"
									size="xs"
									icon={ <Video className="w-4 h-4" /> }
									onClick={ handleToggleVideo }
								>
									<span className="hidden sm:inline-block">
										{ showVideo
											? __( 'Hide Video', 'sureforms' )
											: __( 'Watch Video', 'sureforms' ) }
									</span>
								</Button>
							</div>

							<div>
								<div
									className={ `transition-all duration-700 ease-in-out ${
										showVideo
											? 'max-h-[800px] opacity-100 mb-8'
											: 'max-h-0 opacity-0 mb-0'
									}` }
								>
									{ showVideo && (
										<div className="relative rounded-lg shadow-lg aspect-video overflow-hidden">
											<iframe
												ref={ iframeRef }
												className="w-full h-full"
												src={ `https://www.youtube-nocookie.com/embed/y_tsLWV6QRM?autoplay=1&enablejsapi=1` }
												title="Youtube Video Player"
												allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
												allowFullScreen
											></iframe>
										</div>
									) }
								</div>

								<LearnComponent
									className="bg-background-secondary p-2 rounded-lg [&>div]:space-y-2"
									endpoints={ {
										get: '/sureforms/v1/get-learn-chapters',
										set: '/sureforms/v1/update-learn-progress',
									} }
								/>
							</div>
						</div>
					</Container.Item>
				</Container>
			</main>
		</Container>
	);
};

export default LearnPage;
