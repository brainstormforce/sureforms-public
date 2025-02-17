/* eslint-disable no-unused-vars */
// Load the default @wordpress/scripts config object
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const webpack = require('webpack');

module.exports = {
    ...defaultConfig,
    optimization: {
        usedExports: true,
    },
    mode: 'production',
    plugins: [
        ...defaultConfig.plugins,
        new webpack.optimize.LimitChunkCountPlugin( {
        	maxChunks: 1,
        } ),
        // new MiniCssExtractPlugin({
        //     filename: [name].css,
        // }),
    ],
    entry: {
        dashboard: path.resolve(__dirname, 'src/admin/dashboard/index.js'),
    },
    resolve: {
        alias: {
            ...defaultConfig.resolve.alias,
            '@Admin': path.resolve(__dirname, 'src/admin/'),
            '@Blocks': path.resolve(__dirname, 'src/blocks/'),
            '@Controls': path.resolve(__dirname, 'src/srfm-controls/'),
            '@Components': path.resolve(__dirname, 'src/components/'),
            '@Utils': path.resolve(__dirname, 'src/utils/'),
            '@Svg': path.resolve(__dirname, 'assets/svg/'),
            '@Attributes': path.resolve(__dirname, 'src/blocks-attributes/'),
            '@Image': path.resolve(__dirname, 'images/'),
            '@IncBlocks': path.resolve(__dirname, 'inc/blocks/'),
        },
    },
    output: {
        ...defaultConfig.output,
        path: path.resolve(__dirname, 'assets/settings-build'),
    },
};