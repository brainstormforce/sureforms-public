import renderSVG from '@Controls/renderIcon';
import { __ } from '@wordpress/i18n';
const HeaderContainer = ( props ) => {
	const { searchIconInputValue, onClickRemoveSearch, searchIcon, inputElement } = props;
	const removeTextIcon = () => {
		return '' === searchIconInputValue ? (
			renderSVG( 'sistrix' )
		) : (
			<span onClick={ onClickRemoveSearch } className="dashicons dashicons-no-alt"></span>
		);
	};

	// Search input container.
	return (
		<section className="srfm-ip-header">
			<h2>{ __( 'Icon Library', 'sureforms' ) }</h2>
			<div className="srfm-ip-search-container">
				<div className="srfm-ip-search-bar">
					{ removeTextIcon() }
					<input
						type="text"
						placeholder={ __( 'Search', 'ultimate-addons-for-gutenberg' ) }
						value={ searchIconInputValue }
						onChange={ searchIcon }
						ref={ inputElement }
					/>
				</div>
			</div>
		</section>
	);
};
export default HeaderContainer;
