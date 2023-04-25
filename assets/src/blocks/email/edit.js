/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

import Settings from './settings';

export default ( { className, attributes, setAttributes } ) => {
	const { label, placeholder, help, required } = attributes;
	const blockID = useBlockProps().id;
	return (
		<div { ...useBlockProps() }>
			<Settings
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>
			<div
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				<label htmlFor={ 'email-input-' + blockID }>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<input
					id={ 'email-input-' + blockID }
					type="email"
					className={ className }
					placeholder={ placeholder }
					required={ required }
				/>
				{ help !== '' && (
					<label
						htmlFor={ 'email-input-help-' + blockID }
						style={ { color: '#ddd' } }
					>
						{ help }
					</label>
				) }
			</div>
		</div>
	);
};
