import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button, Container } from '@bsf/force-ui';
import { XIcon } from 'lucide-react';
import { addQueryParam } from '@Utils/Helpers';

const UpgradeNotice = ( { onClose } ) => {
	const [ isVisible, setIsVisible ] = useState( true );

	const handleClose = () => {
		setIsVisible( false );

		if ( typeof onClose !== 'function' ) {
			return;
		}
		onClose();
	};

	if ( ! isVisible ) {
		return null;
	}

	return (
		<Container
			className="relative bg-brand-background-hover-100 p-2 text-xs text-center w-full gap-1"
			align="center"
		>
			<p className="text-text-primary w-full mx-7 lg:mx-0">
				<span className=" font-semibold">
					{ __( 'Ready to go beyond free plan?', 'sureforms' ) }
				</span>{ ' ' }
				<Button
					tag="a"
					variant="link"
					className="p-0 [&>span]:p-0 underline underline-offset-2"
					onClick={ () =>
						window.open(
							addQueryParam(
								srfm_admin?.pricing_page_url ||
									srfm_admin?.sureforms_pricing_page,
								'dashboard-cta'
							),
							'_blank',
							'noreferrer'
						)
					}
				>
					{ __( 'Upgrade now', 'sureforms' ) }
				</Button>{ ' ' }
				<span className="font-normal">
					{ __(
						'and unlock the full power of SureForms!',
						'sureforms'
					) }
				</span>
			</p>
			<Container.Item className="absolute right-2 inset-y-0 inline-flex items-center">
				<Button
					variant="ghost"
					size="xs"
					className="p-1 hover:bg-transparent"
					icon={ <XIcon /> }
					onClick={ handleClose }
				/>
			</Container.Item>
		</Container>
	);
};

export default UpgradeNotice;
