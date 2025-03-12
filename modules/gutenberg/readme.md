**5th Aug, 2024 Update:**
### Note: If you are having trouble with npm package dependencies and this is your first time running npm install in this folder then follow below steps:

* Clear `node_modules` folder and `package-lock.json` file if exists.
* **[Recommended]** Have volta installed in your system. See: https://volta.sh/
* Run these below commands step-by-step:
	1. `npm install --legacy-peer-deps`,
	2. `npm install --force`,
	3. `npm install`,
	4. `npm run build`
* On 4th step, if it builds successfully, then you can start your dev work with `npm start` command.
* If that didn't work then try to find a better solution and update here. ( Please ).

## Components & Control Last Updated: 04-04-2022

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


=== How to update SureForms with new options in spectra/ultimate-addon-for-gutenebrg === updated on 13-06-2024

- From the next-release branch of UAGB, verify the final changes that needs to be added for the feature you are adding. Start by adding changes in the attributes.js file. Try to copy the changes and paste them in the exact places.
- We do not need to add deprecated save.js changes as we render the blocks using PHP.
- Start by adding the new attributes in attributes.js - just the object of setting.
- Next, add the changes in render.js & settings.js.
- To see the changes in JavaScript, switch to Node version 14.15.0, then run `npm i` and then run `npm run start`.
- For the frontend, you will need to make the appropriate changes in PHP:
modules/gutenberg/dist/blocks/nameofblock.php
- You will need to take reference from the save.js of that block in Spectra and write the equivalent code in the PHP file.