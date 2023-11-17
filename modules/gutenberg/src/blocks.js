/**
 * Gutenberg Blocks
 *
 * All blocks related JavaScript files should be imported here.
 * You can create a new block folder in this dir and include code
 * for that block here as well.
 *
 * All blocks should be included here since this is the file that
 * Webpack is compiling as the input file.
 */

import getUAGEditorStateLocalStorage from '@Controls/getUAGEditorStateLocalStorage';

// Delete the local storage on every refresh.
const uagLocalStorage = getUAGEditorStateLocalStorage();
if ( uagLocalStorage ) {
	uagLocalStorage.removeItem( 'uagSettingState' );
	uagLocalStorage.removeItem( 'isSpectraFontAwesomeAPILoading' );
}

import fontAwesomePollyfiller from './font-awesome-pollyfiller';

__webpack_public_path__ = uagb_blocks_info.uagb_url + 'dist/';

// Merge all icon chunks.
uagb_blocks_info.uagb_svg_icons = {};
if( uagb_blocks_info?.number_of_icon_chunks ){
	for ( let iconChunk = 0; iconChunk < uagb_blocks_info.number_of_icon_chunks; iconChunk++ ) {
		uagb_blocks_info.uagb_svg_icons = { ...uagb_blocks_info.uagb_svg_icons, ...window[ 'uagb_svg_icons_'+iconChunk ]}
	}
}

// Add Font Awesome Polyfiller to localized variable.
uagb_blocks_info.font_awesome_5_polyfill = fontAwesomePollyfiller;

// Setting local storage key for svg Confirmation data.
uagLocalStorage.setItem( 'uagSvgConfirmation', JSON.stringify( uagb_blocks_info?.svg_confirmation || false ) );

import './editor.scss';
import './blocks/separator/block.js';
import './blocks/advanced-heading/block.js';
import './blocks/image/block.js';
import './blocks/icon/block.js';

import CF_Block_Icons from './block-icons';


// Keep category list in separate variable and remove category list from icons list.
if ( uagb_blocks_info.uagb_svg_icons?.uagb_category_list ) {

	wp.uagb_icon_category_list = [ ...uagb_blocks_info.uagb_svg_icons.uagb_category_list ];
	delete uagb_blocks_info.uagb_svg_icons.uagb_category_list;
}

wp.UAGBSvgIcons = Object.keys( uagb_blocks_info.uagb_svg_icons );

const { updateCategory } = wp.blocks;

// wp.WCFSvgIcons = Object.keys( cf_blocks_info.wcf_svg_icons );
wp.UAGBSvgIcons = Object.keys( uagb_blocks_info.uagb_svg_icons );

updateCategory( 'cartflows', {
	icon: CF_Block_Icons.logo,
} );

const addCfResponsiveCondtionBlocks = function ( blocks ) {
	blocks.push( 'wcfb/' );
	return blocks;
};

wp.hooks.addFilter(
	'uag_reponsive_conditions_compatible_blocks',
	'enable_reponsive_condition_for_cf_blocks',
	addCfResponsiveCondtionBlocks
);
