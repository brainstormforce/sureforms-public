/**
 * BLOCK: Buttons Child - Edit Class
 */

// Import classes
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import React, { useLayoutEffect } from 'react';
import { useDeviceType } from '@Controls/getPreviewType';
import styles from './editor.lazy.scss';
import { Placeholder, Spinner } from '@wordpress/components';

const Render = ( props ) => {
	useLayoutEffect( () => {
		styles.use();
		return () => {
			styles.unuse();
		};
	}, [] );
	props = props.parentProps;

	const { attributes } = props;

	let deviceType = useDeviceType();

	deviceType = deviceType.toLowerCase();

	const { className, formJson, isHtml } = attributes;

	let html = '';

	if ( formJson && formJson.data.html ) {
		html = formJson.data.html;
	}

	return (
		<div
			className={ classnames(
				className,
				'wp-block-wcfb-optin-form',
				`cf-editor-preview-mode-${ deviceType }`,
				`cf-block-${ props.clientId.substr( 0, 8 ) }`
			) }
		>
			<div className="wpcf__optin-form">
				{ isHtml === true && (
					<div dangerouslySetInnerHTML={ { __html: html } } />
				) }
				{ isHtml === false && (
					<Placeholder
						icon="admin-post"
						label={ __( 'Loading', 'cartflows' ) }
					>
						<Spinner />
					</Placeholder>
				) }
			</div>
		</div>
	);
};

export default React.memo( Render );
