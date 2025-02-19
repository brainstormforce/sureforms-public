import { __ } from '@wordpress/i18n';
import { Button, Container, Label } from '@bsf/force-ui';
import { Check } from 'lucide-react';
import ForceUiIcons from './ForceUiIcons';

export default () => {
	return (
		<Container
			className="p-6 gap-4 bg-background-primary border-0.5 border-solid border-border-subtle rounded-xl shadow-sm-blur-1 w-[50%] ml-auto mr-auto"
			containerType="flex"
			direction="column"
		>
			<Container.Item>
				<Container
					className="items-center gap-1.5"
					containerType="flex"
					direction="column"
				>
					<Container.Item>
						<Label
							tag="h3"
							className="font-semibold text-2xl text-text-primary"
						>
							{ __( 'Almost done…', 'sureforms' ) }
						</Label>
					</Container.Item>
					<Container.Item>
						<Label
							className="font-normal text-base text-text-secondary"
						>
							{ __( 'Let\'s connect AI to your website', 'sureforms' ) }
						</Label>
					</Container.Item>
				</Container>
			</Container.Item>

			<Container.Item>
				<Container
					className="justify-center gap-2 p-1"
					containerType="flex"
					direction="row"
				>
					<Container.Item>
						{ ForceUiIcons.wordpressIcon }
					</Container.Item>
					<Container.Item>
						<Label
							className="mt-2 text-text-tertiary"
						>
							- - - - -
						</Label>
					</Container.Item>
					<Container.Item >
						<Check size={ 20 } className="bg-[#22C55E] p-1 rounded-full text-white mt-2" />
					</Container.Item>
					<Container.Item>
						<Label
							className="mt-2 text-text-tertiary"
						>
							- - - - -
						</Label>
					</Container.Item>
					<Container.Item>
						{ ForceUiIcons.sureFormsIcon }
					</Container.Item>
				</Container>
			</Container.Item>

			<Container.Item className="flex gap-2 p-1 text-center">
				<Label
					className="font-normal text-sm text-text-secondary"
				>
					{ __( 'Click the button bellow to connect your SureForms account johnd@bsf.io with https://different-dalbec-jx4.zipwp.dev', 'sureforms' ) }
				</Label>
			</Container.Item>
			<Container.Item className="flex flex-col gap-4 p-1 items-center">
				<Button
					className="w-[50%] ml-auto mr-auto"
				>
					{ __( 'Continue', 'sureforms' ) }
				</Button>
				<Label
					className="font-semibold text-xs text-link-primary"
				>
					{ __( 'Want to use a different account?', 'sureforms' ) }
				</Label>
			</Container.Item>
		</Container >
	);
};
