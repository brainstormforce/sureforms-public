const paths = require( './paths' );
const fs = require( 'fs' );
const sass = require( 'node-sass' );

/* Generate common editor */
sass.render(
	{
		file: paths.pluginSrc + '/editor.scss',
		outputStyle: 'compressed',
		outFile: paths.pluginDir + '/dist/editor.css',
		sourceMap: false,
	},
	function ( error, result ) {
		if ( null !== result && ! error ) {
			fs.writeFile(
				paths.pluginDir + '/dist/editor.css',
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
//Generate individual block's css files
fs.readdir( paths.pluginSrc + '/blocks', function ( readError, items ) {
	console.log( "Individual block's css file generation started...\n\n" ); // eslint-disable-line

	for ( const item of items ) {
		sass.render(
			{
				file: paths.pluginSrc + '/blocks/' + item + '/style.scss',
				outputStyle: 'compressed',
				outFile: './assets/css/blocks/' + item + '.css',
				sourceMap: false,
			},
			function ( error, result ) {
				if ( result && ! error ) {
					const file_name = item;

					console.log( `Generating for - ${ file_name }` ); // eslint-disable-line

					fs.writeFile(
						'./assets/css/blocks/' + file_name + '.css',
						result.css,
						function ( err ) {
							if ( err ) {
								throw err;
							}
						}
					);
				}
			}
		);
	}

	if ( readError ) {
		console.error( readError ); // eslint-disable-line
	}
} );

// Copy generated style file content to custom style file
// source to copy content
const src = paths.pluginDir + '/build/style-blocks.css';

// // Deprecated at 1.23.0. Deelte this after 2 updates.

// // destination for copied content
const old_dest = paths.pluginDir + '/build/blocks.style.css';

fs.copyFile( src, old_dest, ( error ) => {
	// 	// incase of any error
	if ( error ) {
		console.error( error ); // eslint-disable-line
		return;
	}

	console.log(
		'\n\nStyle in deprecated file blocks.style.css - Copied Successfully!'
	); // eslint-disable-line
} );
