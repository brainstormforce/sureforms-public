import { __ } from '@wordpress/i18n';
import { Button, Container, Label, Title } from '@bsf/force-ui';
import { Check, Zap } from 'lucide-react';

export default () => {
	return (
		<Container
			className="w-864 bg-background-primary p-4 gap-2 border-0.5 border-solid border-border-subtle shadow-sm-blur-1 rounded-xl"
			containerType="grid"
			cols={ 12 }
		>
			<Container.Item
				className="flex flex-col gap-8 p-2"
				colSpan={ 8 }
			>
				<Container className="flex flex-col">
					<Container.Item className="flex flex-row gap-1 items-center">
						<Zap className="w-12 text-brand-primary border-1.25" />
						<Label className="font-semibold cursor-pointer text-xs text-brand-primary">
							{ __( 'Upgrade to Pro', 'sureforms' ) }
						</Label>
					</Container.Item>
					<Container.Item className="flex flex-col gap-1 py-1">
						<Title
							className="text-text-primary font-semibold text-lg"
							tag="h5"
							title={ __( 'Upgrade to Unlock SureForms Premium Features!', 'sureforms' ) }
						/>
						<Label className="text-sm text-text-secondary font-normal">
							{ __( 'Upgrade to SureForms Premium and access advanced fields and features that enhance your form-building experience:', 'sureforms' ) }
						</Label>
					</Container.Item>
					<Container.Item>
						<Container className="grid grid-cols-1 sm:grid-cols-2 py-1 gap-3 rounded-lg">
							<Container.Item className="flex flex-row items-center gap-2">
								<Check className="w-12 text-brand-primary border" />
								<Label className="text-field-label font-medium text-sm">
									{ __( 'File Upload', 'sureforms' ) }
								</Label>
							</Container.Item>
							<Container.Item className="flex flex-row items-center gap-2">
								<Check className="w-12 text-brand-primary border" />
								<Label className="text-field-label font-medium text-sm">
									{ __( 'Page Break', 'sureforms' ) }
								</Label>
							</Container.Item>
							<Container.Item className="flex flex-row items-center gap-2">
								<Check className="w-12 text-brand-primary border" />
								<Label className="text-field-label font-medium text-sm">
									{ __( 'Rating Fields', 'sureforms' ) }
								</Label>
							</Container.Item>
							<Container.Item className="flex flex-row items-center gap-2">
								<Check className="w-12 text-brand-primary border" />
								<Label className="text-field-label font-medium text-sm">
									{ __( 'Date & Time Pickers', 'sureforms' ) }
								</Label>
							</Container.Item>
							<Container.Item className="flex flex-row items-center gap-2">
								<Check className="w-12 text-brand-primary border" />
								<Label className="text-field-label font-medium text-sm">
									{ __( 'Conditional Logic', 'sureforms' ) }
								</Label>
							</Container.Item>
							<Container.Item className="flex flex-row items-center gap-2">
								<Check className="w-12 text-brand-primary border" />
								<Label className="text-field-label font-medium text-sm">
									{ __( 'And much more…', 'sureforms' ) }
								</Label>
							</Container.Item>
						</Container>
					</Container.Item>
					<Container.Item className="p-2 gap-3">
						<Button variant="secondary" size="md" className="border border-solid border-button-secondary bg-button-secondary hover:bg-button-secondary gap-1 shadow-sm-blur-2 text-sm">
							{ __( 'Upgrade now', 'sureforms' ) }
						</Button>
					</Container.Item>
				</Container>
			</Container.Item>
			<Container.Item className="p-2 gap-1.5 bg-gray-500" colSpan={ 4 }>
				image
			</Container.Item>
		</Container>
	);
};
