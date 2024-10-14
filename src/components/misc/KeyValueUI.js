import { useSelect } from '@wordpress/data';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';
import SmartTagList from '@Components/misc/SmartTagList';

const KeyValueUI = ( {
	editorData,
	dataId,
	header,
	label,
	helpText,
	withSmartTags,
	handleOnChange,
} ) => {
	const deleteIcons = parse( svgIcons.delete );
	if (
		! editorData.current[ dataId ] ||
		0 === editorData.current[ dataId ].length
	) {
		editorData.current[ dataId ] = [ { '': '' } ];
	}
	const [ data, setData ] = useState( editorData.current[ dataId ] );
	const [ enabled, setEnabled ] = useState(
		editorData.current[ 'has_' + dataId ] ?? false
	);

	const addNew = ( index ) => {
		handleOnChange( () => {
			editorData.current = {
				...editorData.current,
				...{
					[ dataId ]: [
						...data.slice( 0, index + 1 ),
						{ '': '' },
						...data.slice( index + 1 ),
					],
				},
			};
		}, null, false );

		setData( editorData.current[ dataId ] );
	};

	const deleteItem = ( index ) => {
		handleOnChange( () => {
			editorData.current = {
				...editorData.current,
				...{
					[ dataId ]: [ ...data.slice( 0, index ), ...data.slice( index + 1 ) ],
				},
			};
			if (
				! editorData.current[ dataId ] ||
				0 === editorData.current[ dataId ].length
			) {
				editorData.current[ dataId ] = [ { '': '' } ];
			}
		}, null, false );

		setData( editorData.current[ dataId ] );
	};

	return (
		<div className="srfm-key-value-pairs">
			<CheckboxControl
				label={ label ?? '' }
				help={ helpText ?? '' }
				checked={ enabled }
				onChange={ ( checked ) => {
					handleOnChange( 'has_' + dataId, checked, false );
					setEnabled( checked );
				} }
			/>
			<div className="srfm-modal-input-box">
				{ editorData.current[ 'has_' + dataId ] && (
					<>
						<div className="srfm-modal-label">
							<label> { header }</label>
						</div>
						{ data.map( ( dataItem, index ) => (
							<div
								key={ dataId + '_' + index }
								className="srfm-flex srfm-flex-row srfm-gap-normal"
							>
								<div className="srfm-col-2 srfm-flex srfm-flex-row srfm-gap-normal">
									<div className="srfm-modal-input-box">
										<WebhookTextControl
											type="text"
											placeholder={ __(
												'Add Key',
												'sureforms-pro'
											) }
											defaultValue={
												Object.keys( dataItem )[ 0 ] ?? ''
											}
											onChange={ ( newKey ) => {
												const currentValue =
													Object.values(
														editorData.current[
															dataId
														][ index ]
													)[ 0 ];

												handleOnChange( () => {
													editorData.current[ dataId ][ index ] = {
														[ newKey ]: currentValue ?? '',
													};
												}, null, false );
											} }
										/>
									</div>
									<div className="srfm-modal-input-box">
										<WebhookTextControl
											type="text"
											placeholder={ __(
												'Add Value',
												'sureforms-pro'
											) }
											defaultValue={
												Object.values( dataItem )[ 0 ] ?? ''
											}
											withSmartTags={ withSmartTags }
											onChange={ ( newValue ) => {
												const currentKey = Object.keys(
													editorData.current[ dataId ][
														index
													]
												)[ 0 ];

												handleOnChange( () => {
													editorData.current[ dataId ][ index ] = {
														[ currentKey ?? '' ]: newValue,
													};
												}, null, false );
											} }
										/>
									</div>
								</div>
								<div className="srfm-flex srfm-flex-row srfm-gap-normal">
									<button
										className="srfm-button-secondary srfm-button-xs"
										onClick={ () => {
											addNew( index );
										} }
									>
										{ __( 'Add', 'sureforms-pro' ) }
									</button>

									<button
										onClick={
											0 !== data.length
												? () => {
													deleteItem( index );
												}
												: () => {}
										}
										className="srfm-modal-action"
									>
										{ 0 !== data.length ? deleteIcons : '' }
									</button>
								</div>
							</div>
						) ) }
					</>
				) }
			</div>
		</div>
	);
};

export default KeyValueUI;
