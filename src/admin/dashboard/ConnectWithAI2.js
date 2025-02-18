/** @jsx jsx */
import { __ } from '@wordpress/i18n';
import { jsx } from '@emotion/react';
import { Button, Container, Label } from '@bsf/force-ui';
import { ArrowLeft, ArrowRight } from 'lucide-react';

export default () => {
	return (
		<Container
			className="p-8 gap-6 bg-background-secondary w-[90%] ml-auto mr-auto"
			containerType="flex"
			direction="column"
		>
			<Container.Item className="gap-8"
				containerType="flex"
				direction="column"
			>
				Test MIc test
			</Container.Item>
		</Container >
	);
};
