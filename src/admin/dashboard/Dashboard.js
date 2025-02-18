import { cn } from '@Utils/Helpers';
import {
	Container,
	Title,
	Button,
} from '@bsf/force-ui';
import {
	CirclePlay,
} from 'lucide-react';

import Header from '../components/Header';

export default () => {
	const nav = <Header />;

	const leftSidebar = <div
		className={ cn( 'h-full border-l border-solid', 'p-[32px]' ) }
	>
		<Container
			className="p-8 max-w-[82rem] mx-auto"
			cols={ 12 }
			containerType="grid"
			gap="2xl"
		>
			<Container.Item
				className="flex flex-col gap-8"
				colSpan={ 8 }
			>
				<Container
					className="bg-background-primary p-6 shadow-sm rounded-xl"
					cols={ 8 }
					containerType="grid"
					gap="2xl"
				>
					<Container.Item
						className="flex flex-col gap-6"
						colSpan={ 5 }
					>
						<div>
							<Title
								className="text-text-primary mb-1"
								size="lg"
								tag="h3"
								title="Welcome to Astra"
							/>
							<p className="text-sm text-text-secondary m-0">
								Astra is fast, fully customizable & beautiful WordPress theme suitable for blog, personal portfolio, business website and WooCommerce storefront. It is very lightweight and offers unparalleled speed.
							</p>
						</div>
						<div className="flex gap-3">
							<Button>
								Start Customizing
							</Button>
							<Button
								icon={ <CirclePlay /> }
								variant="ghost"
							>
								Watch a Quick Guide
							</Button>
						</div>
					</Container.Item>
					<Container.Item colSpan={ 3 }>
						<img
							alt="Astra video"
							className="w-full h-full object-cover rounded"
							src="https://placehold.co/272x154"
						/>
					</Container.Item>
				</Container>
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
					<div>
						<h1>This is right sidebar</h1>
					</div>
				</Container.Item>
			</Container>

		</Container.Item>
	</Container>;
};
