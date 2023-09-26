const paths = require( './paths' );
const fs = require( 'fs' );
const sass = require( 'node-sass' );

function generateAndWriteCSS( inputFile, outputFile, message ) {
	sass.render(
		{
			file: inputFile,
			outputStyle: 'compressed',
			outFile: outputFile,
			sourceMap: false,
		},
		function ( error, result ) {
			if ( null !== result && ! error ) {
				fs.writeFile( outputFile, result.css, function ( err ) {
					if ( err ) {
						throw err;
					}

					console.log( message ); // eslint-disable-line
				} );
			}
		}
	);
}

generateAndWriteCSS(
	paths.pluginSrc + '/common-editor.scss',
	paths.pluginDist + '/common-editor.css',
	'\n\nCommon editor generated!'
);

generateAndWriteCSS(
	paths.pluginSrc + '/admin/admin.scss',
	paths.pluginDist + '/admin.css',
	'\nAdmin CSS generated!'
);
