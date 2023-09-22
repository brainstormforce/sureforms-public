const paths = require( './paths' );
const fs = require( 'fs' );
const sass = require( 'node-sass' );

/* Generate common editor */
sass.render(
	{
		file: paths.pluginSrc + '/common-editor.scss',
		outputStyle: 'compressed',
		outFile: paths.pluginDist + '/common-editor.css',
		sourceMap: false,
	},
	function ( error, result ) {
		if ( null !== result && ! error ) {
			fs.writeFile(
				paths.pluginDist + '/common-editor.css',
				result.css,
				function ( err ) {
					if ( err ) {
						throw err;
					}

					console.log( '\n\nCommon editor generated!' ); // eslint-disable-line
				}
			);
		}
	}
);

sass.render(
	{
		file: paths.pluginSrc + '/admin/admin.scss',
		outputStyle: 'compressed',
		outFile: paths.pluginDist + '/admin.css',
		sourceMap: false,
	},
	function ( error, result ) {
		if ( null !== result && ! error ) {
			fs.writeFile(
				paths.pluginDist + '/admin.css',
				result.css,
				function ( err ) {
					if ( err ) {
						throw err;
					}

					console.log( '\nAdmin CSS generated!' ); // eslint-disable-line
				}
			);
		}
	}
);
