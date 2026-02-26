import { Text, Title } from '@bsf/force-ui';

const Header = ( { title, description } ) => {
	return (
		<div className="space-y-1">
			<Title title={ title } size="lg" />
			<Text as="p" size={ 14 } color="secondary">
				{ description }
			</Text>
		</div>
	);
};

export default Header;
