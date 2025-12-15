const mix = require('laravel-mix');

mix.setPublicPath('public');

mix.js('src/resources/assets/js/app.js', 'js')
    .js('src/resources/assets/js/shopify.js', 'js')
    .sass('src/resources/assets/scss/app.scss', 'css')
    .sass('src/resources/assets/scss/shopify.scss', 'css')
    .options({ processCssUrls: false });

mix.copyDirectory('src/resources/assets/fonts', 'public/fonts');
mix.copyDirectory('src/resources/assets/images', 'public/images');
mix.copyDirectory('src/resources/assets/scss/pages', 'public/css/pages');

if (mix.inProduction()) {
  mix.version();
}