/** @jsx jsx */
import { jsx } from '@emotion/react';
import {
	ScFlex,
} from '@surecart/components-react';
import { Fragment } from '@wordpress/element';
import GetStarted from './GetStarted';
import Overview from './Overview';
import DashboardModel from './templates/DashboardModel';

export default () => {
	return (
		<>
			<DashboardModel>
				<Fragment>
					<h1 className='border-x flex justify-between items-center'>Hello</h1>
					
					<GetStarted />
					<ScFlex
						style={ { '--sc-flex-column-gap': '2em' } }
						stack="tablet"
					>
						<Overview />
					</ScFlex>
				</Fragment>
			</DashboardModel>
		</>
	);
};
