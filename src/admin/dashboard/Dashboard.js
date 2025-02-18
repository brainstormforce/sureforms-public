import GetStartedNew from './GetStartedNew';

import {
	Container,
} from '@bsf/force-ui';
import ExtendTab from './ExtendTab';

import Header from '../components/Header';
import QuickAccessTab from './QuickAccessTab';
import UpgradeToPro from './UpgradeToPro';

export default () => {
	const nav = <Header />;

	const leftSidebar = <>
		<GetStartedNew />
		<UpgradeToPro />
	</>;

	const rightSidebar = <>
		<ExtendTab />
		<QuickAccessTab />
	</>;

	return <Container
		className="h-full"
		containerType="flex"
		direction="column"
		gap={ 0 }
	>
		{ /* top banner */ }
		{ /* nav */ }
		{ nav }
		<Container.Item>
			<Container.Item>
				<br /><br /><br />
			</Container.Item>
			<Container.Item>
				<ConnectWithAI />
			</Container.Item>
			<Container
				className="p-5 pb-8 xl:p-8 max-[1920px]:max-w-full max-w-[92rem] mx-auto box-content bg-background-secondary"
				containerType="grid"
				cols={ 12 }
				gap="2xl"
			>
				<Container.Item className="flex flex-col gap-8 col-span-12 xl:col-span-8 ---sf-left-sidebar">
					{ /* left sidebar */ }
					{ leftSidebar }
				</Container.Item>
				<Container.Item className="flex flex-col gap-8 col-span-12 xl:col-span-4 ---sf-right-sidebar">
					{ /* right sidebar */ }
					{ rightSidebar }
				</Container.Item>
			</Container>

		</Container.Item>
	</Container>;
};
