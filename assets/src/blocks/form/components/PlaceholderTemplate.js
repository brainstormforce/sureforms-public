/* eslint-disable react/no-unknown-property */
/** @jsx jsx */
import { css, jsx } from '@emotion/react';

export default ( { header, footerLeft, footerRight, children, maxHeight } ) => {
	return (
		<div
			css={ css`
				background: #fff;
				font-family: var( --sc-font-sans );
				font-size: 14px;
				box-sizing: border-box;
				position: relative;
				min-height: 200px;
				width: 100%;
				text-align: left;
				margin: 0;
				color: #1e1e1e;
				-moz-font-smoothing: subpixel-antialiased;
				-webkit-font-smoothing: subpixel-antialiased;
				border-radius: 2px;
				background-color: #fff;
				border: 1px solid #ddd;
				outline: 1px solid transparent;
			` }
			style={ {
				'--sc-color-primary-500': 'var(--wp-admin-theme-color)',
				'--sc-focus-ring-color-primary': 'var(--wp-admin-theme-color)',
				'--sc-input-border-color-focus': 'var(--wp-admin-theme-color)',
			} }
		>
			<div
				css={ css`
					width: 100%;
					position: relative;
				` }
			>
				<div
					css={ css`
						position: ${ maxHeight ? 'sticky' : 'relative' };
						top: 0;
						left: 0;
						right: 0;
						border-bottom: 1px solid #ddd;
						padding: 0 16px;
						display: flex;
						flex-direction: row;
						justify-content: flex-start;
						align-items: center;
						height: 60px;
						font-size: 1rem;
						font-weight: 600;
						background: white;
    					z-index: 2;
					` }
				>
					{ header }
				</div>
				<div
					css={ css`
						position: relative;
						overflow: ${ maxHeight ? 'auto' : 'visible' };
					` }
				>
					{ children }
				</div>
				<div
					css={ css`
						background: #fff;
						position: ${ maxHeight ? 'sticky' : 'relative' };
						bottom: 0;
						left: 0;
						right: 0;
						border-top: 1px solid #ddd;
						padding: 0 16px;
						display: flex;
						flex-direction: row;
						justify-content: space-between;
						align-items: center;
						height: 60px;
					` }
				>
					<div>{ footerLeft }</div>
					<div>{ footerRight }</div>
				</div>
			</div>
		</div>
	);
};
