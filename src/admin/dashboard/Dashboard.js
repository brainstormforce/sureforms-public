import GetStarted from './GetStarted';

import { Container } from '@bsf/force-ui';
import ExtendTab from './ExtendTab';

import Header from '../components/Header';
import QuickAccessTab from './QuickAccessTab';
import UpgradeToPro from './UpgradeToPro';
import FormsOverview from './FormsOverview';

export default () => {
	const nav = <Header />;

	const isFirstFormCreated = srfm_admin?.is_first_form_created || false;
	const isProActive = srfm_admin?.is_pro_active || false;

	const leftSidebar = (
		<>
			<GetStarted />
			<FormsOverview />
			{ ! isProActive && isFirstFormCreated && <ExtendTab /> }
		</>
	);

	const rightSidebar = (
		<>
			{ isProActive || ! isFirstFormCreated ? <ExtendTab /> : null }
			{ ! isProActive && isFirstFormCreated && <UpgradeToPro /> }
			<QuickAccessTab />
		</>
	);

	return (
		<Container className="h-full" direction="column" gap={ 0 }>
			{ /* top banner */ }
			{ /* nav */ }
			{ nav }
			<Container.Item>
				<Container
					className="p-5 pb-8 xl:p-8 w-full bg-background-secondary"
					containerType="grid"
					cols={ 12 }
					gap="2xl"
				>
					<Container.Item className="flex flex-col gap-8 col-span-12 xl:col-span-8">
						{ leftSidebar }
					</Container.Item>
					<Container.Item className="flex flex-col gap-8 col-span-12 xl:col-span-4">
						{ rightSidebar }
					</Container.Item>
				</Container>
			</Container.Item>
		</Container>
	);
};
