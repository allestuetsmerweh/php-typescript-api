/* eslint-env node */
/* global module */

const path = require('path');
const WebpackShellPluginNext = require('webpack-shell-plugin-next');

module.exports = {
    entry: './web/index.ts',
    devtool: 'inline-source-map',
    module: {
        rules: [
            {
                test: /\.tsx?$/,
                use: 'ts-loader',
                exclude: /node_modules/,
            },
        ],
    },
    resolve: {
        extensions: ['.tsx', '.ts', '.js'],
    },
    plugins: [
        new WebpackShellPluginNext({
            onBuildStart: {
                scripts: ['php ./api/generate.php'],
                blocking: true,
                parallel: false,
            },
        }),
    ],
    output: {
        filename: 'example.js',
        path: path.resolve(__dirname, 'web', 'dist'),
        library: 'example',
    },
};
