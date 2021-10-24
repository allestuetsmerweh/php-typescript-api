/* eslint-env node */

const path = require('path');

module.exports = {
    entry: './example/web/index.ts',
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
    output: {
        filename: 'example.js',
        path: path.resolve(__dirname, 'example', 'web', 'dist'),
        library: 'example',
    },
};
