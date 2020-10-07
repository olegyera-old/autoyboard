const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

<<<<<<< HEAD
mix.browserSync('http://yboard:8000/');


=======
mix.browserSync('http://yboard.loc');
>>>>>>> origin/frontArtur

// mix.js('resources/js/auth/auth.js', 'public/js')
//     .sass('resources/sass/auth.scss', 'public/css/');

// mix.js('resources/js/admin/admin.js', 'public/js')
//     .sass('resources/sass/admin/admin.scss', 'public/css/');


mix.js('resources/js/site/site.js', 'public/js')
    .sass('resources/sass/auto/site.scss', 'public/css/');
