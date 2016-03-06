////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// gEditorial: GulpFile.js
////////////////////////////////////////////////////////////////////////////////

// FIXME: add sass watch
// FIXME: add build gulp task to generate the package
// FIXME: add gulp task to minify again each js

// TODO: [Using Gulp for WordPress Theme Development - Matt Banks](http://mattbanks.me/gulp-wordpress-development/)

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

'use strict';

var
	gulp       = require('gulp'),

	compass    = require('gulp-compass'),
	plumber    = require('gulp-plumber'),
	notify     = require('gulp-notify'),
	tinyPNG    = require('gulp-tinypng'), // https://github.com/creativeaura/gulp-tinypng
	minifyCSS  = require('gulp-minify-css'),
	sourcemaps = require('gulp-sourcemaps'),

	wpPot      = require('gulp-wp-pot'), // https://github.com/rasmusbe/gulp-wp-pot
	sort       = require('gulp-sort'),

	plumberErrorHandler = {
		errorHandler: notify.onError({
			title: 'Gulp',
			message: 'Error: <%= error.message %>'
		})
	};

function getRelativePath(absPath) {
	absPath = absPath.replace(/\\/g, '/');
	var curDir = __dirname.replace(/\\/g, '/');
	return absPath.replace(curDir, '');
}

gulp.task('default', function(){
	console.log( 'Hi, I\'m Gulp!' );
});

gulp.task('tinypng', function () {
	gulp.src('./assets/images/raw/*.png')
		.pipe(tinyPNG(''))
		.pipe(gulp.dest('./assets/images'));
});

gulp.task('makepot', function () {
	gulp.src('./**/*.php')

		.pipe(plumber(plumberErrorHandler))

		.pipe(sort())

		.pipe(wpPot( {
			domain: 'geditorial',
			destFile:'languages/geditorial.pot',
			package: 'geditorial',
			bugReport: 'https://github.com/geminorum/geditorial/issues',
			lastTranslator: 'Nasser Rafie <contact@geminorum.ir>',
			team: 'geminorum <contact@geminorum.ir>'
		} ))

		.pipe(gulp.dest('dist'));
});
