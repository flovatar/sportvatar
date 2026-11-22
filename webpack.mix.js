const mix = require('laravel-mix');

const NodePolyfillPlugin = require("node-polyfill-webpack-plugin")

mix.setPublicPath('public/');
/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.webpackConfig({
    plugins: [
        new NodePolyfillPlugin()
    ]
});

mix.extend("addWebpackLoaders", (webpackConfig, loaderRules) => {
    loaderRules.forEach((loaderRule) => {
        webpackConfig.module.rules.push(loaderRule);
    });
});

mix.addWebpackLoaders([
    {
        test: /\.glsl$/,
        loader: 'webpack-glsl-loader'
    }
]);

mix.sass('resources/scss/app.scss', 'css/')

mix.js('resources/js/app.js', 'js/');
    // .postCss('resources/css/app.css', 'public/css', [
    //     require('postcss-import'),
    //     require('tailwindcss')('./tailwind.config.js'),
    // ]);

if (mix.inProduction()) {
    mix.version();
}
