import { __ } from '@wordpress/i18n';
import { Container, Label, Title } from '@bsf/force-ui';
import { Learn as LearnComponent } from '@Admin/lib/learn';
import Header from '../components/Header';
import AdminNotice from '../components/AdminNotice';
import { cn } from '@Utils/Helpers';

const LearnPage = () => {
	return (
		<Container className="h-full" direction="column" gap={ 0 }>
			<Header />
			<main className="bg-background-secondary min-h-[calc(100vh_-_8rem)]">
				<h1 className="sr-only">{ __( 'Learn', 'sureforms' ) }</h1>
				{ /* Admin Notices - only render if notices exist */ }
				{ window.srfm_admin?.notices?.length > 0 && (
					<div className="px-5 xl:px-8 py-4">
						<AdminNotice currentPage="sureforms_menu" />
					</div>
				) }
				<Container
					className={ cn(
						'md:p-8 sm:p-6 p-4',
						window.srfm_admin?.notices?.length > 0 && '!pt-0'
					) }
					cols={ 12 }
					containerType="grid"
					gap="2xl"
				>
					<Container.Item
						className="flex flex-col gap-6"
						colSpan={ { lg: 8, md: 10, sm: 12 } }
						colStart={ { lg: 3, md: 2, sm: 0 } }
					>
						{ /* Top Title and Description Section */ }
						<Container
							containerType="flex"
							direction="column"
							className="gap-2"
						>
							<Container.Item>
								<Title
									size="lg"
									tag="h2"
									title={ __( 'Learn', 'sureforms' ) }
									className="text-text-primary"
								/>
							</Container.Item>
							<Container.Item>
								<Label
									size="sm"
									variant="help"
									className="text-text-secondary"
								>
									{ __(
										'To help you take full control and ensure success of your forms, we have outlined a few tasks and prepared a set of instructional videos. These will help you learn how to setup, customize and grow your forms.',
										'sureforms'
									) }
								</Label>
							</Container.Item>
						</Container>

						{ /* Main Learn Component */ }
						<LearnComponent
							endpoints={ {
								get: '/sureforms/v1/get-learn-chapters',
								set: '/sureforms/v1/update-learn-progress',
							} }
						/>
					</Container.Item>
				</Container>
			</main>
		</Container>
	);
};

export default LearnPage;
