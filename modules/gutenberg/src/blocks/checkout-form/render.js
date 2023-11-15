/**
 * BLOCK: Buttons Child - Edit Class
 */

// Import classes
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import React, { useLayoutEffect } from 'react';
import { useDeviceType } from '@Controls/getPreviewType';
import { Placeholder, Spinner } from '@wordpress/components';
import styles from './editor.lazy.scss';

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
				'wcf-gb-checkout-form cartflows-gutenberg__checkout-form',
				`cf-editor-preview-mode-${ deviceType }`,
				`cf-block-${ props.clientId.substr( 0, 8 ) }`
			) }
		>
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
	);
};

export default React.memo( Render );
