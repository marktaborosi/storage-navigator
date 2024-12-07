const gulp = require('gulp');
const sass = require('gulp-dart-sass');
const rename = require('gulp-rename');
const cleanCSS = require('gulp-clean-css');
const uglify = require('gulp-uglify');  // Import gulp-uglify for JS minification

// Directory containing theme SCSS files
const themeFiles = 'assets/scss/theme-*.scss';

// JavaScript files to minify
const jsFiles = [
    'assets/js/**/*.js'  // All JS files in the assets/js directory
];

// Task to compile, minify, and rename each theme CSS file with `.min` suffix
gulp.task('sass', function () {
    return gulp.src(themeFiles)
        .pipe(sass().on('error', sass.logError))  // Compile SCSS to CSS
        .pipe(cleanCSS())  // Minify the CSS
        .pipe(rename(function (path) {
            // Remove the "theme-" prefix and add ".min" suffix to the file name
            path.basename = path.basename.replace('theme-', '') + '.min';
        }))
        .pipe(gulp.dest('public/css/'));  // Output minified CSS files to the public/css/ directory
});

// Task to minify JavaScript files
gulp.task('minify-js', function () {
    return gulp.src(jsFiles)
        .pipe(uglify())  // Minify the JavaScript files
        .pipe(rename({ suffix: '.min' }))  // Add ".min" suffix to the file name
        .pipe(gulp.dest('public/js/'));  // Output minified JS files to the public/js/ directory
});

// Combined build task to run both 'sass' and 'minify-js'
gulp.task('build', gulp.series('sass', 'minify-js'));

// Watch task to recompile SCSS and minify JS on changes
gulp.task('watch', function () {
    gulp.watch('assets/scss/**/*.scss', gulp.series('sass'));
    gulp.watch(jsFiles, gulp.series('minify-js'));
});

// Default task
gulp.task('default', gulp.series('build', 'watch'));
