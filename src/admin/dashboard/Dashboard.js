/** @jsx jsx */
import { jsx } from '@emotion/react';
import {
	ScFlex,
} from '@surecart/components-react';
import { Fragment } from '@wordpress/element';
import GetStarted from './GetStarted';
import Overview from './Overview';
import DashboardModel from './templates/DashboardModel';
import { Container, Topbar, Badge, Title } from '@bsf/force-ui';

export default () => {
	return (
		<>
			<DashboardModel>
				<Fragment>
					<h1 className='border-x flex justify-between items-center'>Hello</h1>
					<Title
						description=""
						icon={null}
						iconPosition="right"
						size="sm"
						tag="h2"
						title="Basic Title"
						className="srfm-dashboard-title border-b"
					/>

					<Badge label="-- badge force ui Pro" size="xs" variant="neutral" closable={false} />
					
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
