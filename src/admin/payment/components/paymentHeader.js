import { Container, Button, Title } from '@bsf/force-ui';
import { ArrowLeft } from 'lucide-react';
import { __, sprintf } from '@wordpress/i18n';

const PaymentHeader = ( { title, id, onBack } ) => {
	return (
		<Container
			containerType="flex"
			direction="row"
			gap="xs"
			className="w-full justify-between items-center"
		>
			<Title
				icon={null}
				size="sm"
				tag="h2"
				title={ sprintf( __( '%s #%s', 'sureforms' ), title, id ) }
			/>

			<Button
				variant="outline"
				size="s"
				icon={ <ArrowLeft className='!size-4'/> }
				onClick={ onBack }
				className="text-text-secondary hover:text-text-primary rounded-sm p-2.5"
			>
				{ __( 'Back', 'sureforms' ) }
			</Button>
		</Container>
	);
};

export default PaymentHeader;
