(function() {

  var
    gulp = require('gulp'),
    gutil = require('gulp-util'),
    plugins = require('gulp-load-plugins')(),
    yaml = require('js-yaml'),
    del = require('del'),
    fs = require('fs'),

    pkg = JSON.parse(fs.readFileSync('./package.json'), 'utf8'),
    env = {
      tinypng: '',
    },

    banner = ['/**',
      ' * <%= pkg.name %> - <%= pkg.description %>',
      ' * @version v<%= pkg.version %>',
      ' * @link <%= pkg.homepage %>',
      ' * @license <%= pkg.license %>',
      ' */',
      ''
    ].join('\n'),

    input = {
      'php': [
        './**/*.php',
        '!./assets/libs/**',
      ],
      'sass': './assets/sass/**/*.scss',
      'js': [
        './assets/js/*.js',
        '!./assets/js/*.min.js',
        './assets/js/tinymce/*.js',
        '!./assets/js/tinymce/*.min.js',
      ],
      'svg': './assets/images/raw/**/*.svg',
      'images': './assets/images/raw/**/*.{png,jpg,jpeg}',
      'banner': [
        './assets/css/**/*.css',
        '!./assets/css/**/*.raw.css',
        './assets/js/*.js',
        './assets/js/tinymce/*.js',
      ],
      'ready': './ready/**/*',
      'final': [
        './assets/css/**/*.css',
        './assets/css/**/*.html',
        './assets/images/**/*',
        './assets/js/**/*.js',
        './assets/js/**/*.html',
        './assets/libs/**/*',
        './assets/packages/**/*',
        './assets/vendor/**/*.php',
        '!./assets/vendor/**/test/*',
        '!./assets/vendor/**/Tests/*',
        '!./assets/vendor/**/tests/*',
        '!./assets/vendor/**/scripts/*',
        '!./assets/vendor/**/examples/*',
        '!./assets/vendor/**/.git',
        './assets/index.html',
        './includes/**/*',
        './modules/**/*',
        './languages/**/*',
        '!./languages/**/*.pot',
        '!./languages/**/*.po',
        './*.php',
        './*.md',
        './LICENSE',
        './index.html',
      ],
    },

    output = {
      'css': './assets/css',
      'js': './assets/js',
      'sourcemaps': './maps',
      'images': './assets/images',
      'languages': './languages/'+pkg.name+'.pot',
      'ready': './ready/',
      'final': '..',
    },

    logs = {
      'tinypng': './assets/images/raw/.tinypng-sigs'
    };

  try {
    env = yaml.safeLoad(fs.readFileSync('./environment.yml', 'utf8'), {
      'json': true
    });
  } catch (e) {
    gutil.log('no environment.yml loaded!');
  }

  gulp.task('pot', function() {
    return gulp.src(input.php)
      .pipe(plugins.excludeGitignore())
      .pipe(plugins.wpPot(pkg._pot))
      .pipe(gulp.dest(output.languages));
  });

  gulp.task('dev:sass', function() {
    return gulp.src(input.sass)
    .pipe(plugins.newer({
      dest: output.css,
      ext: '.css',
    }))
    .pipe(plugins.sourcemaps.init())
    .pipe(plugins.sass().on('error', plugins.sass.logError))
    .pipe(plugins.cssnano({
      core: false,
      zindex: false,
      discardComments: false,
    }))
    .pipe(plugins.sourcemaps.write(output.sourcemaps))
    .pipe(gulp.dest(output.css)).on('error', gutil.log)
    .pipe(plugins.livereload());
  });

  gulp.task('dev:watch', function() {
    plugins.livereload.listen();
    gulp.watch(input.sass, gulp.series('dev:sass'));
  });

  // without livereload
  gulp.task('dev:styles', function() {
    return gulp.src(input.sass)
    .pipe(plugins.sourcemaps.init())
    .pipe(plugins.sass().on('error', plugins.sass.logError))
    .pipe(plugins.cssnano({
      core: false,
      zindex: false,
      discardComments: false,
    }))
    // .pipe(plugins.header(banner, {
    //   pkg: pkg
    // }))
    .pipe(plugins.sourcemaps.write(output.sourcemaps))
    .pipe(gulp.dest(output.css)).on('error', gutil.log);
  });

  gulp.task('build:styles', function() {
    return gulp.src(input.sass)
    .pipe(plugins.sass().on('error', plugins.sass.logError))
    .pipe(plugins.cssnano({
      zindex: false,
      discardComments: {
        removeAll: true
      }
    }))
    .pipe(gulp.dest(output.css));
  });

  gulp.task('build:scripts', function() {
    return gulp.src(input.js, {base: '.'})
    .pipe(plugins.rename({
      suffix: '.min',
    }))
    .pipe(plugins.uglify());
  });

  gulp.task('build:banner', function() {
    return gulp.src(input.banner, {
      'base': '.'
    })
    .pipe(plugins.header(banner, {
      pkg: pkg
    }))
    .pipe(gulp.dest('.'));
  });

  gulp.task('build:copy', function() {
    return gulp.src(input.final, {
      'base': '.'
    })
    .pipe(gulp.dest(output.ready + pkg.name));
  });

  gulp.task('build:clean', function(done) {
    del.sync([output.ready]);
    done();
  });

  gulp.task('build:zip', function() {
    return gulp.src(input.ready)
    .pipe(plugins.zip(pkg.name + '-' + pkg.version + '.zip'))
    .pipe(gulp.dest(output.final));
  });

  gulp.task('build', gulp.series(
    gulp.parallel('build:styles', 'build:scripts'),
    'build:banner', 'build:clean', 'build:copy', 'build:zip',
    function(done) {
      gutil.log('Done!');
      done();
  }));

  gulp.task('sass', function() {
    return gulp.src('./assets/sass/**/*.scss')
      // .pipe(sourcemaps.init())
      .pipe(plugins.sass().on('error', plugins.sass.logError)).pipe(plugins.cssnano({
        zindex: false,
        discardComments: {
          removeAll: true
        }
      }))
      // .pipe(sourcemaps.write('./maps'))
      .pipe(gulp.dest('./assets/css'));
  });

  gulp.task('watch', function() {
    gulp.watch('./assets/sass/**/*.scss', gulp.series('sass'));
  });

  gulp.task('default', function() {
    gutil.log('Hi, I\'m Gulp!');
    gutil.log("Sass is:\n"+require('node-sass').info);
  });
}());
