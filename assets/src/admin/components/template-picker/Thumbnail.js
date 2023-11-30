/* eslint-disable react/no-unknown-property */
/** @jsx jsx */
import { css, jsx } from '@emotion/react';

export default ( { label, children, selected, onSelect } ) => {
	const base = css`
		flex: 1;
		display: flex;
		align-items: center;
		overflow: hidden;
		border-radius: 2px;
		border: 1px solid #f0f0f0;
		height: inherit;
		min-height: 300px;
		max-height: 700px;
		cursor: pointer;
		&:hover {
			border-color: var( --wp-admin-theme-color );
		}
		&:focus,
		&:active {
			box-shadow: inset 0 0 0 1px #fff,
				0 0 0 var( --wp-admin-border-width-focus )
					var( --wp-admin-theme-color );
			outline: 2px solid transparent;
		}
	`;

	const highlighted = css`
		box-shadow: inset 0 0 0 1px #fff,
			0 0 0 var( --wp-admin-border-width-focus )
				var( --wp-admin-theme-color );
		outline: 2px solid transparent;
	`;

	return (
		<div
			css={ css`
				display: flex;
				flex-direction: column;
			` }
			onClick={ onSelect }
		>
			<div css={ [ base, selected && highlighted ] }>{ children }</div>
			<div
				css={ css`
					padding-top: 8px;
					font-size: 16px;
					text-align: center;
				` }
			>
				{ label }
			</div>
		</div>
	);
};
