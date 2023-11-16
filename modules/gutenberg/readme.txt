Components & Control Last Updated: 04-04-2022

=== How to update the spectra/ultimate-addon-for-gutenebrg components and controls in SureForms  ===

SureForms has the Gutenberg blocks and it uses the Spectra's components to render the SureForms blocks settings.
See the SureForms > modules > gutenebrg > src > components
See the SureForms > modules > gutenebrg > src > control
See the SureForms > modules > gutenebrg > src > styles
These are are the folders need to be update in the SureForms.

Below are the steps to update the spectra/ultimate-addon-for-gutenebrg components and controls in SureForms.

1. Get the latest version of the components and controls from https://github.com/brainstormforce/ultimate-addons-for-gutenberg

	Components => ultimate-addons-for-gutenberg/src/components
	Controls => ultimate-addons-for-gutenberg/blocks-config/uagb-controls
	Styles => ultimate-addons-for-gutenberg/src/styles

	Also make sure that, copy the content of common-editor.scss from ultimate-addons-for-gutenberg/src/common-editor.scss and paste it in editor.scss of SureForms

2. Copy the components folder from the Spectra to the SureForms > modules > gutenebrg > src > components
3. Copy the uagb-controls files ( except getBlocksDefaultAttributes.js ) from the Spectra to the SureForms > modules > gutenebrg > src > control
6. Done

