const gulp = require('gulp');
const uglify = require('gulp-uglify');
const cleanCSS = require('gulp-clean-css');
const rename = require('gulp-rename');

// Minifier JavaScript
gulp.task('minify-js', function() {
    return gulp.src(['public/js/*.js', '!public/js/*.min.js'])
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('public/js'));
});

// Minifier CSS
gulp.task('minify-css', function() {
    return gulp.src(['public/css/*.css', '!public/css/*.min.css'])
        .pipe(cleanCSS())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('public/css'));
});

// Tâche par défaut
gulp.task('default', gulp.series('minify-js', 'minify-css'));

// Tâche de surveillance
gulp.task('watch', function() {
    gulp.watch(['public/js/*.js', '!public/js/*.min.js'], gulp.series('minify-js'));
    gulp.watch(['public/css/*.css', '!public/css/*.min.css'], gulp.series('minify-css'));
}); 