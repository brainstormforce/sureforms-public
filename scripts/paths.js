/**
 * Paths
 *
 * Project related paths.
 */

const path = require( 'path' );
const fs = require( 'fs' );

// Make sure any symlinks in the project folder are resolved:
const pluginDir = fs.realpathSync( process.cwd() );
const resolvePlugin = ( relativePath ) =>
	path.resolve( pluginDir, relativePath );

// Config after eject: we're in ./config/
module.exports = {
	dotenv: resolvePlugin( '.env' ),
	pluginDir,
	pluginSrc: resolvePlugin( 'src' ), // Plugin src folder path.
	yarnLockFile: resolvePlugin( 'yarn.lock' ),
	pluginDist: resolvePlugin( 'assets/build' ), // We are in ./dist folder already so the path '.' resolves to ./dist/.
};
