'use strict';

const gulp = require('gulp'),
    sourcemaps = require('gulp-sourcemaps');

const pump = require('pump'),
    rename = require('gulp-rename'),
    babel = require('gulp-babel'),
    uglify = require('gulp-uglify'),
    postcss = require('gulp-postcss'),
    cssnext = require('postcss-cssnext'),
    nested = require('postcss-nested'),
    nano = require('gulp-cssnano'),
    //sass = require('node-sass'),
    less = require('gulp-less'),
    atImport = require("postcss-import");

const paths = {
    scss: ['src/scss/*/*.scss', 'src/scss/*.scss'],
    less: ['src/less/*/*.less', 'src/less/*.less'],
    css: ['src/less/*.css'],
    scripts: ['src/js/*.js', '!src/js/lib/*'],
    images: ['src/img/*']
};

gulp.task('default', [/*'lint',*/ 'bulid']);
gulp.task('build', ['css', 'js'/*, 'images'*/]);
gulp.task('build:release', ['css:release', 'js:release']);

gulp.task('css', () => {
    return gulp.src(paths.less)
        .pipe(sourcemaps.init())
        .pipe(less({
            paths: [ './node_modules/', paths.less[0] ]
        }))
        .pipe(postcss([
            require('autoprefixer')({browsers: ['> 0%']})
        ]))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('./dist/css'));
});
gulp.task('scss:release', () => {
    return gulp.src(paths.less)
        .pipe(sourcemaps.init())
        .pipe(less({
            paths: [ paths.less ]
        }))
        .pipe(postcss([
            require('autoprefixer')({browsers: ['> 0%']})
        ]))
        .nano()
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('./dist/css'));
});

gulp.task('js', () => {
    pump([
             gulp.src(paths.scripts),
             babel({
                presets: ['es2015']
            }),
             gulp.dest('dist/js')
         ]);
});
gulp.task('js:release', ['js'], () => {
    pump([
             gulp.src(paths.scripts),
             uglify(),
             gulp.dest('dist/js')
         ]);
});

gulp.task('watch', function() {
    gulp.watch(paths.scripts, ['js']);
    gulp.watch(paths.less, ['css']);
    //gulp.watch(paths.images, ['images']);
});