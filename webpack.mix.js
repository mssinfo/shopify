const mix = require('laravel-mix');

mix.setPublicPath('src/resources/public');

mix.js('src/resources/assets/js/app.js', 'js')
    .js('src/resources/assets/js/shopify.js', 'js')
    .sass('src/resources/assets/scss/app.scss', 'css')
    .options({ processCssUrls: false });

mix.copyDirectory('src/resources/assets/fonts', 'src/resources/public/fonts');
mix.copyDirectory('src/resources/assets/images', 'src/resources/public/images');
mix.copyDirectory('src/resources/assets/scss/pages', 'src/resources/public/css/pages');

if (mix.inProduction()) {
  mix.version();
}