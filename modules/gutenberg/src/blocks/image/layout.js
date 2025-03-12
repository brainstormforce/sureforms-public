import { useEffect, useRef } from '@wordpress/element';
import { RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export default function Layout( {
	attributes,
	isSelected,
	setAttributes,
	captionRef,
} ) {
	const {
		caption,
		enableCaption,
		heading,
		headingTag,
		layout,
		overlayContentPosition,
		separatorStyle,
		separatorPosition,
	} = attributes;
	const headingRef = useRef();
	useEffect( () => {
		headingRef.current = heading;
	}, [ caption ] );

	const imageHeading = (
		<>
			{ ( ! RichText.isEmpty( heading ) || isSelected ) && (
				<RichText
					ref={ headingRef }
					tagName={ headingTag }
					className="uagb-image-heading"
					aria-label={ __(
						'Image overlay heading text',
						'sureforms'
					) }
					placeholder={ __( 'Add Heading', 'sureforms' ) }
					value={ heading }
					onChange={ ( value ) =>
						setAttributes( { heading: value } )
					}
				/>
			) }
		</>
	);

	const imageCaption = (
		<>
			{ ( ! RichText.isEmpty( caption ) || isSelected ) && (
				<RichText
					ref={ captionRef }
					tagName="figcaption"
					className="uagb-image-caption"
					aria-label={ __( 'Image caption text', 'sureforms' ) }
					placeholder={ __( 'Add caption', 'sureforms' ) }
					value={ caption }
					onChange={ ( value ) =>
						setAttributes( { caption: value } )
					}
				/>
			) }
		</>
	);

	const separator = 'none' !== separatorStyle && (
		<div className="uagb-image-separator"></div>
	);

	return (
		<>
			{ layout === 'overlay' ? (
				<>
					<div className="wp-block-uagb-image--layout-overlay__color-wrapper"></div>
					<div
						className={ `wp-block-uagb-image--layout-overlay__inner ${ overlayContentPosition.replace(
							' ',
							'-'
						) }` }
					>
						{ 'before_title' === separatorPosition && separator }
						{ imageHeading }
						{ 'after_title' === separatorPosition && separator }
						{ imageCaption }
						{ 'after_sub_title' === separatorPosition && separator }
					</div>
				</>
			) : (
				<>{ enableCaption && imageCaption }</>
			) }
		</>
	);
}
