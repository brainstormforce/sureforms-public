import { __ } from '@wordpress/i18n'
import TabContentWrapper from '@Components/tab-content-wrapper'
import { Button, Container, Label } from '@bsf/force-ui'
import { Check, FileText } from 'lucide-react'

const FeaturePreview = ({
    title = __( 'Post Feed', 'sureforms' ),
    subtitle = __( 'Automatically create WordPress posts from form submissions.', 'sureforms' ),
}) => {
  return (
		<TabContentWrapper title={ title }>
			<div className="space-y-6">
				<Container
					direction="column"
					className="bg-white p-6 rounded-xl border border-solid border-border-subtle"
				>
					{/* Icon */}
					<Container.Item className="mb-6">
						<div className="w-12 h-12 flex items-center justify-center rounded-lg bg-orange-50">
							<FileText className="w-6 h-6 text-orange-500" strokeWidth={ 2 } />
						</div>
					</Container.Item>

					{/* Title */}
					<Container.Item className="mb-4">
						<Label
							as="h3"
							size="lg"
							className="text-text-primary font-semibold text-xl"
						>
							{ title }
						</Label>
					</Container.Item>

					{/* Description */}
					<Container.Item className="mb-6">
						<Label
							size="sm"
							className="text-text-tertiary text-base font-normal leading-relaxed"
						>
							{ subtitle }
						</Label>
					</Container.Item>

					{/* Features List */}
					<Container
						direction="column"
						className="gap-3 mb-6"
					>
						<Container.Item className="flex items-start gap-3">
							<Check className="w-5 h-5 text-text-primary mt-0.5 flex-shrink-0" strokeWidth={ 2 } />
							<Label size="sm" className="text-text-primary font-normal">
								{ __( 'Create posts, pages, or CPTs from form entries', 'sureforms' ) }
							</Label>
						</Container.Item>
						<Container.Item className="flex items-start gap-3">
							<Check className="w-5 h-5 text-text-primary mt-0.5 flex-shrink-0" strokeWidth={ 2 } />
							<Label size="sm" className="text-text-primary font-normal">
								{ __( 'Map form fields to post fields easily', 'sureforms' ) }
							</Label>
						</Container.Item>
						<Container.Item className="flex items-start gap-3">
							<Check className="w-5 h-5 text-text-primary mt-0.5 flex-shrink-0" strokeWidth={ 2 } />
							<Label size="sm" className="text-text-primary font-normal">
								{ __( 'Automate content publishing with no extra steps', 'sureforms' ) }
							</Label>
						</Container.Item>
					</Container>

					{/* Upgrade Button */}
					<Container.Item>
						<Button
							className="w-fit px-6"
							size="md"
							variant="primary"
							onClick={ () => {
								// Add upgrade link logic here
								window.open( srfm_admin?.pricing_page_url || '#', '_blank', 'noreferrer' )
							} }
						>
							{ __( 'Upgrade Now', 'sureforms' ) }
						</Button>
					</Container.Item>
				</Container>
			</div>
		</TabContentWrapper>
  )
}

export default FeaturePreview