import { cn } from '@Utils/Helpers';
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

	const leftSidebar = <div
		className={ cn( 'h-full border-l border-solid border-red-400', 'p-[32px]' ) }
	>
		<Container
			className="p-8 max-w-[82rem] mx-auto"
			cols={ 12 }
			containerType="grid"
			gap="2xl"
		>
			<Container.Item
				className="flex flex-col gap-8"
			>
				<GetStartedNew />
				<UpgradeToPro />
			</Container.Item>
		</Container>
	</div>;

	const rightSidebar = <div
		className={ cn( 'h-full border-l border-solid border-red-400', 'p-[32px]' ) }
	>
		<Container
			className="p-8"
			gap="2xl"
		>
			<Container.Item
				className="flex flex-col gap-8"
			>
				<ExtendTab />
				<QuickAccessTab />
			</Container.Item>
		</Container>
	</div>;

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
			<Container
				className="h-full grid-cols-[minmax(864px,63%)_minmax(416px,37%)] w-full mx-auto gap-[32px]"
				containerType="grid"
				gap={ 0 }
			>
				<Container.Item>
					{ /* left sidebar */ }
					{ leftSidebar }
				</Container.Item>
				<Container.Item>
					{ /* right sidebar */ }
					{ rightSidebar }
				</Container.Item>
			</Container>

		</Container.Item>
	</Container>;
};
