import { __ } from '@wordpress/i18n';
import { Link, useLocation } from 'react-router-dom';

// settings icons.
import GeneralIcon from './settingsIcon.js';

function useQuery() {
	return new URLSearchParams( useLocation().search );
}

const Navigation = () => {
	const activatedTab = useQuery();
	const navigation = [
		{
			name: __( 'General', 'sureforms' ),
			slug: 'general-settings',
			icon: <GeneralIcon />,
		},
	];
	return (
		<nav
			className="srfm-flex srfm-flex-col srfm-max-w-[300px] srfm-min-h-full srfm-p-[20px] srfm-gap-[2px] srfm-w-[20%] srfm-bg-[#FBFBFC] srfm-shadow-md"
			style={ { borderRight: '1px solid #E4E7EB' } }
		>
			{ navigation.map( ( item ) => (
				<Link
					to={ {
						pathname: 'wp-admin/admin.php',
						search: `?page=sureforms_form_settings&tab=${ item.slug }`,
					} }
					key={ item.name }
					className={ `srfm-no-underline srfm-group srfm-p-2 srfm-cursor-pointer srfm-rounded-md srfm-transition-colors srfm-duration-300 srfm-ease-in-out hover:srfm-bg-wpcolor focus:srfm-bg-wpcolor focus:srfm-text-[#FBFBFC] focus:srfm-ring-0 ${
						activatedTab.get( 'tab' ) === item.slug
							? 'srfm-bg-wpcolor srfm-text-[#FBFBFC]'
							: 'srfm-text-[#111827]'
					}` }
				>
					<div className="srfm-flex srfm-justify-start srfm-gap-2">
						{ item.icon }
						<span className="truncate srfm-text-[16px] group-hover:srfm-text-[#FBFBFC] srfm-transition-colors srfm-duration-300 srfm-ease-in-out">
							{ item.name }
						</span>
					</div>
				</Link>
			) ) }
		</nav>
	);
};

export default Navigation;
