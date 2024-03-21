/** @jsx jsx */
import { css, jsx } from '@emotion/react';
import { ScBlockUi, ScEmpty } from '@surecart/components-react';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Chart from 'react-apexcharts';
export default ( { loading, className } ) => {
	const [ postCounts, setPostCounts ] = useState( {} );

	useEffect( () => {
		const fetchPosts = async () => {
			try {
				// Calculate the date one month ago from the current date.
				const oneMonthAgo = new Date();
				oneMonthAgo.setMonth( oneMonthAgo.getMonth() - 1 );
				const formattedDate = oneMonthAgo.toISOString();

				// Define the REST API endpoint to fetch posts.
				const endpoint = `/wp/v2/sureforms_entry?per_page=-1&after=${ formattedDate }`;

				const response = await apiFetch( { path: endpoint } );

				// Process the data to create the chart.
				const postsData = {};

				response.forEach( ( post ) => {
					const postDate = new Date( post.date ).toLocaleDateString();

					if ( ! postsData[ postDate ] ) {
						postsData[ postDate ] = 1;
					} else {
						postsData[ postDate ]++;
					}
				} );

				// Convert date strings to Date objects and sort by date
				const sortedData = Object.fromEntries(
					Object.entries( postsData ).sort(
						( [ dateA ], [ dateB ] ) =>
							new Date( dateA ) - new Date( dateB )
					)
				);

				// Prepare the data for the chart.
				setPostCounts( sortedData );
			} catch ( error ) {
				console.error( 'Error fetching data:', error );
			}
		};

		fetchPosts();
	}, [] );
	if ( loading ) {
		return (
			<div
				css={ css`
					height: 295px;
				` }
			>
				<ScBlockUi spinner />
			</div>
		);
	}

	const formatYAxis = ( value ) => {
		return parseInt( value );
	};

	return (
		<>
			{ Object.keys( postCounts ).length !== 0 ? (
				<Chart
					style={ { minHeight: '295px' } }
					className={ className }
					options={ {
						chart: {
							toolbar: {
								show: false,
							},
							width: '100%',
							type: 'area',
							events: {
								mounted: ( chart ) => {
									chart.windowResizeHandler();
								},
							},
						},
						dataLabels: {
							enabled: false,
						},
						stroke: {
							curve: 'smooth',
						},
						xaxis: {
							categories: Object.keys( postCounts ),
						},
						yaxis: {
							labels: {
								formatter: formatYAxis,
							},
						},
						colors: [ '#F06434', '#999999' ],
						tooltip: {
							x: {
								format: 'dd/MM/yy HH:mm',
							},
						},
						fill: {
							type: 'gradient',
							gradient: {
								shadeIntensity: 1,
								opacityFrom: 0.7,
								opacityTo: 0.9,
								stops: [ 0, 90, 100 ],
							},
						},
						markers: {
							size: [ 4, 7 ],
						},
					} }
					series={ [
						{
							name: __( 'Entries', 'sureforms' ),
							data: Object.values( postCounts ),
						},
					] }
					type="area"
					height={ 295 }
				/>
			) : (
				<ScEmpty>
					<span>
						<svg
							xmlns="http://www.w3.org/2000/svg"
							width="30"
							height="30"
							viewBox="0 0 20 20"
							fill="none"
						>
							<path
								d="M16.25 11.875V9.6875C16.25 8.1342 14.9908 6.875 13.4375 6.875H12.1875C11.6697 6.875 11.25 6.45527 11.25 5.9375V4.6875C11.25 3.1342 9.9908 1.875 8.4375 1.875H6.875M7.5 13.75V14.375M10 11.875V14.375M12.5 10V14.375M8.75 1.875H4.6875C4.16973 1.875 3.75 2.29473 3.75 2.8125V17.1875C3.75 17.7053 4.16973 18.125 4.6875 18.125H15.3125C15.8303 18.125 16.25 17.7053 16.25 17.1875V9.375C16.25 5.23286 12.8921 1.875 8.75 1.875Z"
								stroke="#475569"
								strokeWidth="1.5"
								strokeLinecap="round"
								strokeLinejoin="round"
							/>
						</svg>
					</span>
					<span
						css={ css`
							color: #1e293b;
							text-align: center;
							font-size: 14px;
							font-weight: 500;
							line-height: 20px;
						` }
					>
						{ __( 'There is no data on this view', 'sureforms' ) }
					</span>
					<span
						css={ css`
							color: #64748b;
							text-align: center;
							font-size: 14px;
							line-height: 20px;
						` }
					>
						{ __(
							'This is where your form views will appear',
							'sureforms'
						) }
					</span>
				</ScEmpty>
			) }
		</>
	);
};
