import { Breadcrumb as FUIBreadcrumb } from '@bsf/force-ui';

/**
 * Breadcrumb Component
 *
 * @typedef {Object} BreadcrumbItem 	   - Breadcrumb item object
 * @property {string}                        text          - The display text of the breadcrumb item
 * @property {Object}                        [linkProps]   - The URL the breadcrumb item links to (optional)
 * @property {'separator'|'ellipsis'|'page'} [type]        - Type of breadcrumb item (optional)
 *
 * @param    {Object}                        props         Component props
 * @param    {Array<BreadcrumbItem>}         props.options Breadcrumb items
 * @return
 */

const Breadcrumb = ( { options } ) => {
	return (
		<FUIBreadcrumb>
			<FUIBreadcrumb.List>
				{ options.map( ( item, index ) => {
					switch ( item.type ) {
						case 'separator':
							return (
								<FUIBreadcrumb.Separator
									key={ index }
								/>
							);
						case 'ellipsis':
							return (
								<FUIBreadcrumb.Item
									key={ index }
								>
									<FUIBreadcrumb.Ellipsis />
								</FUIBreadcrumb.Item>
							);
						case 'page':
							return (
								<FUIBreadcrumb.Item
									key={ index }
								>
									<FUIBreadcrumb.Page>
										{ item.text }
									</FUIBreadcrumb.Page>
								</FUIBreadcrumb.Item>
							);
						default:
							return (
								<FUIBreadcrumb.Item
									key={ index }
								>
									<FUIBreadcrumb.Link
										{ ...item?.linkProps }
									>
										{ item.text }
									</FUIBreadcrumb.Link>
								</FUIBreadcrumb.Item>
							);
					}
				} ) }
			</FUIBreadcrumb.List>
		</FUIBreadcrumb>
	);
};

export default Breadcrumb;
