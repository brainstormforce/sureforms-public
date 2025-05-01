import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Button, Container } from '@bsf/force-ui';
import { XIcon } from 'lucide-react';
import { addQueryParam, setCookie, getCookie } from '@Utils/Helpers';

const COOKIE_NAME = 'srfm_upgrade_notice_seen';

const UpgradeNotice = ( { onClose } ) => {
	const [ isVisible, setIsVisible ] = useState( true );
	const [ showNotice, setShowNotice ] = useState( false );

	useEffect( () => {
		const hasSeenNotice = getCookie( COOKIE_NAME );
		if ( ! hasSeenNotice ) {
			setShowNotice( true );
			setCookie( COOKIE_NAME, 'true', 10 ); // Expires in 10 days
		}
	}, [] );

	if ( ! showNotice ) {
		return null;
	}

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
				<span className=" font-semibold text-xs">
					{ __( 'Ready to go beyond free plan?', 'sureforms' ) }
				</span>{ ' ' }
				<Button
					tag="a"
					variant="link"
					className="p-0 [&>span]:p-0 underline underline-offset-2 font-normal"
					size="xs"
					onClick={ () =>
						window.open(
							addQueryParam(
								srfm_admin?.pricing_page_url ||
									srfm_admin?.sureforms_pricing_page,
								'dashboard-upgrade-notice-cta'
							),
							'_blank',
							'noreferrer'
						)
					}
				>
					{ __( 'Upgrade now', 'sureforms' ) }
				</Button>{ ' ' }
				<span className="font-normal text-xs">
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
					icon={ <XIcon className="size-4" /> }
					onClick={ handleClose }
				/>
			</Container.Item>
		</Container>
	);
};

export default UpgradeNotice;
