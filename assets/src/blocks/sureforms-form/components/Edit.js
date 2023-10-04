/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import {
	Placeholder,
	TextControl,
	PanelBody,
	PanelRow,
	Spinner,
} from '@wordpress/components';
import {
	useEntityBlockEditor,
	useEntityProp,
	store as coreStore,
} from '@wordpress/core-data';
import {
	useInnerBlocksProps as __stableUseInnerBlocksProps,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUseInnerBlocksProps,
	// __experimentalBlockContentOverlay as BlockContentOverlay, // TODO when gutenberg releases it: https://github.com/WordPress/gutenberg/blob/afee31ee020b8965e811f5d68a5ca8001780af9d/packages/block-editor/src/components/block-content-overlay/index.js#L17
	InspectorControls,
	useBlockProps,
	Warning,
} from '@wordpress/block-editor';

export default ( { attributes } ) => {
	const useInnerBlocksProps = __stableUseInnerBlocksProps
		? __stableUseInnerBlocksProps
		: __experimentalUseInnerBlocksProps;

	// TODO: Let's store a unique hash in both meta and attribute to find.
	const { id } = attributes;

	const blockProps = useBlockProps();

	const [ blocks, onInput, onChange ] = useEntityBlockEditor(
		'postType',
		'sureforms_form',
		{ id }
	);

	const [ title, setTitle ] = useEntityProp(
		'postType',
		'sureforms_form',
		'title',
		id
	);

	const innerBlocksProps = useInnerBlocksProps(
		{},
		{
			value: blocks,
			onInput,
			onChange,
			template: [ [ 'sureforms/form', {} ] ],
			templateLock: 'all',
		}
	);

	const { isMissing, hasResolved } = useSelect( ( select ) => {
		const hasResolvedValue = select( coreStore ).hasFinishedResolution(
			'getEntityRecord',
			[ 'postType', 'sureforms_form', id ]
		);
		const form = select( coreStore ).getEntityRecord(
			'postType',
			'sureforms_form',
			id
		);
		const canEdit =
			select( coreStore ).canUserEditEntityRecord( 'sureforms_form' );
		return {
			canEdit,
			isMissing: hasResolvedValue && ! form,
			hasResolved: hasResolvedValue,
			form,
		};
	} );

	// form has resolved
	if ( ! hasResolved ) {
		return (
			<div { ...blockProps }>
				<Placeholder>
					<Spinner />
				</Placeholder>
			</div>
		);
	}

	// form is missing
	if ( isMissing ) {
		return (
			<div { ...blockProps }>
				<Warning>
					{ __(
						'This form has been deleted or is unavailable.',
						'sureforms'
					) }
				</Warning>
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Form Settings', 'sureforms' ) }>
					<PanelRow>
						<TextControl
							label={ __( 'Form Title', 'sureforms' ) }
							value={ title }
							onChange={ ( value ) => {
								setTitle( value );
							} }
						/>
					</PanelRow>
					<PanelRow>
						<p className="sureform-form-notice">
							{ __(
								'Note: For Editing the stylings, please check the SureForms styling - ',
								'sureforms'
							) }
							<a
								href={ `${ sfBlockData.post_url }?post=${ id }&action=edit` }
								target="_blank"
								rel="noreferrer"
							>
								{ __( 'Edit Form Settings', 'sureforms' ) }
							</a>
						</p>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>{ <div { ...innerBlocksProps } /> }</div>
		</>
	);
};
