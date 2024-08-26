import { useSelect } from '@wordpress/data';

export const useDeviceType = () => {
	const { deviceType } = useSelect( ( select ) => {
		return {
			deviceType:
				select(
					'core/editor'
				)?.getDeviceType() || 'Desktop',
		};
	}, [] );

	return deviceType || '';
};
