(function () {
  const gulp = require('gulp');
  const plugins = require('gulp-load-plugins')();
  const sass = require('gulp-dart-sass');
  const cssnano = require('cssnano');
  const autoprefixer = require('autoprefixer');
  const rtlcss = require('rtlcss');
  const parseChangelog = require('parse-changelog');
  const extend = require('xtend');
  const template = require('lodash.template');
  const yaml = require('js-yaml');
  const log = require('fancy-log');
  const del = require('del');
  const fs = require('fs');
  const exec = require('child_process').exec;
  const path = require('path');

  const pkg = require('./package.json');
  const config = require('./gulp.config.json');

  let env = config.env;
  const banner = config.banner.join('\n');

  const debug = /--debug/.test(process.argv.slice(2));
  const patch = /--patch/.test(process.argv.slice(2)); // bump a patch?

  try {
    env = extend(config.env, yaml.load(fs.readFileSync('./environment.yml', { encoding: 'utf-8' }), { json: true }));
  } catch (e) {
    log.warn('no environment.yml loaded!');
  }

  gulp.task('dev:tinify', function () {
    return gulp.src(config.input.images)
      .pipe(plugins.newer(config.output.images))
      .pipe(plugins.tinypngUnlimited({
        key: env.tinypng,
        sigFile: config.logs.tinypng,
        summarize: true,
        keepMetadata: false,
        keepOriginal: true,
        log: true
      }))
      .pipe(gulp.dest(config.output.images));
  });

  gulp.task('svgmin', function () {
    return gulp.src(config.input.svg)
      .pipe(plugins.newer(config.output.images))
      .pipe(plugins.svgmin()) // SEE: http://dbushell.com/2016/03/01/be-careful-with-your-viewbox/
      .pipe(gulp.dest(config.output.images));
  });

  gulp.task('smushit', function () {
    return gulp.src(config.input.images)
      .pipe(plugins.newer(config.output.images))
      .pipe(plugins.smushit())
      .pipe(gulp.dest(config.output.images));
  });

  function i18nExtra (config) {
    return '--exclude="' + config.exclude.toString() + '"' +
          ' --file-comment="' + config.comment.toString() + '"' +
          ' --skip-plugins --skip-themes --skip-packages' +
          (debug ? ' --debug' : '');
  }

  gulp.task('i18n:plugin', function (cb) {
    const command = 'wp i18n make-pot . ' +
      i18nExtra(config.i18n.plugin) +
      ' --headers=\'' + template(JSON.stringify(config.i18n.plugin.headers), { variable: 'data' })({ bugs: pkg.bugs.url }) + '\'';

    exec(command, function (err, stdout, stderr) {
      if (stdout) {
        log('WP-CLI:', stdout.trim());
      }
      if (stderr) {
        log.error('Errors:', stderr.trim());
      }
      cb(err);
    });
  });

  gulp.task('i18n:modules', function () {
    const extra = i18nExtra(config.i18n.modules);

    return gulp.src(config.input.modules)
      .pipe(plugins.exec(function (file) {
        const folder = file.path.split(path.sep).pop();
        const module = folder.toLowerCase();
        log.info('Make pot for Module: ' + folder);
        return 'wp i18n make-pot ' + file.path +
          ' ./languages/' + folder + '/' + module + '.pot' +
          ' --domain=' + pkg.name + '-' + module +
          ' --subtract=./languages/' + pkg.name + '.pot' +
          ' --package-name="' + pkg.productName + ' ' + folder + ' ' + pkg.version + '" ' +
          ' --headers=\'' + template(JSON.stringify(config.i18n.modules.headers), { variable: 'data' })({ bugs: pkg.bugs.url, folder: folder, module: module }) + '\' ' +
          extra;
      }), {
        continueOnError: false,
        pipeStdout: false
      })
      .pipe(plugins.exec.reporter({
        err: true,
        stderr: true,
        stdout: true
      }));
  });

  gulp.task('i18n', gulp.series('i18n:plugin', 'i18n:modules'));

  gulp.task('pot', function () {
    return gulp.src(config.input.php)
      .pipe(plugins.excludeGitignore())
      .pipe(plugins.wpPot(config.pot))
      .pipe(gulp.dest(config.output.languages));
  });

  gulp.task('textdomain', function () {
    return gulp.src(config.input.php)
      .pipe(plugins.excludeGitignore())
      .pipe(plugins.checktextdomain(config.textdomain));
  });

  gulp.task('dev:sass', function () {
    return gulp.src(config.input.sass)
      // .pipe(plugins.sourcemaps.init())
      .pipe(sass.sync(config.sass).on('error', sass.logError))
      .pipe(plugins.postcss([
        cssnano(config.cssnano.dev),
        autoprefixer(config.autoprefixer.dev)
      ]))
      // .pipe(plugins.sourcemaps.write(config.output.sourcemaps))
      .pipe(gulp.dest(config.output.css)).on('error', log.error)
      .pipe(plugins.postcss([rtlcss()]))
      .pipe(plugins.rename({ suffix: '-rtl' }))
      .pipe(gulp.dest(config.output.css)).on('error', log.error)
      .pipe(plugins.changedInPlace())
      .pipe(plugins.debug({ title: 'Changed' }))
      .pipe(plugins.if(function (file) {
        if (file.extname !== '.map') return true;
      }, plugins.livereload()));
  });

  gulp.task('watch:styles', function () {
    plugins.livereload.listen();
    gulp.watch(config.input.sass, gulp.series('dev:sass'));
  });

  // all styles / without livereload
  gulp.task('dev:styles', function () {
    return gulp.src(config.input.sass)
      // .pipe(plugins.sourcemaps.init())
      .pipe(sass.sync(config.sass).on('error', sass.logError))
      .pipe(plugins.postcss([
        cssnano(config.cssnano.dev),
        autoprefixer(config.autoprefixer.dev)
      ]))
      .pipe(plugins.header(banner, { pkg: pkg }))
      // .pipe(plugins.sourcemaps.write(config.output.sourcemaps))
      .pipe(plugins.debug({ title: 'Changed' }))
      .pipe(gulp.dest(config.output.css)).on('error', log.error)
      .pipe(plugins.postcss([rtlcss()]))
      .pipe(plugins.rename({ suffix: '-rtl' }))
      .pipe(plugins.debug({ title: 'RTLed' }))
      .pipe(gulp.dest(config.output.css)).on('error', log.error);
  });

  gulp.task('dev:scripts', function () {
    return gulp.src(config.input.js, { base: '.' })
      .pipe(plugins.rename({ suffix: '.min' }))
      .pipe(plugins.uglify())
      .pipe(gulp.dest('.'));
  });

  gulp.task('build:styles', function () {
    return gulp.src(config.input.sass)
      .pipe(sass(config.sass).on('error', sass.logError))
      .pipe(plugins.postcss([
        cssnano(config.cssnano.build),
        autoprefixer(config.autoprefixer.build)
      ]))
      .pipe(gulp.dest(config.output.css)).on('error', log.error);
  });

  // seperated because of stripping rtl directives in compression
  gulp.task('build:rtl', function () {
    return gulp.src(config.input.sass)
      .pipe(sass.sync(config.sass).on('error', sass.logError))
      .pipe(plugins.postcss([rtlcss()])) // divided to avoid cssnano messing with rtl directives
      .pipe(plugins.postcss([
        cssnano(config.cssnano.build),
        autoprefixer(config.autoprefixer.build)
      ]))
      .pipe(plugins.rename({ suffix: '-rtl' }))
      .pipe(gulp.dest(config.output.css)).on('error', log.error);
  });

  gulp.task('build:scripts', function () {
    return gulp.src(config.input.js, { base: '.' })
      .pipe(plugins.rename({ suffix: '.min' }))
      .pipe(plugins.uglify())
      .pipe(gulp.dest('.'));
  });

  gulp.task('build:banner', function () {
    return gulp.src(config.input.banner, { base: '.' })
      .pipe(plugins.header(banner, { pkg: pkg }))
      .pipe(gulp.dest('.'));
  });

  gulp.task('build:copy', function () {
    return gulp.src(config.input.final, { base: '.' })
      .pipe(gulp.dest(config.output.ready + pkg.name));
  });

  gulp.task('build:clean', function (done) {
    del.sync([config.output.ready]);
    done();
  });

  gulp.task('build:zip', function () {
    return gulp.src(config.input.ready)
      .pipe(plugins.zip(pkg.name + '-' + pkg.version + '.zip'))
      .pipe(gulp.dest(config.output.final));
  });

  gulp.task('build', gulp.series(
    gulp.parallel('build:styles', 'build:rtl', 'build:scripts'),
    'build:banner',
    'build:clean',
    'build:copy',
    'build:zip',
    function (done) {
      log('Done!');
      done();
    }
  ));

  gulp.task('github:package', function (done) {
    if (!env.github) {
      log.error('Error: missing required token for github');
      return done();
    }

    const changes = parseChangelog(fs.readFileSync('CHANGES.md', { encoding: 'utf-8' }), { title: false });
    const options = {
      token: env.github,
      tag: pkg.version,
      notes: changes.versions[0].rawNote,
      manifest: pkg,
      skipIfPublished: true,
      draft: true
    };

    return gulp.src(pkg.name + '-' + pkg.version + '.zip')
      .pipe(plugins.githubRelease(options));
  });

  gulp.task('bump:package', function () {
    return gulp.src('./package.json')
      .pipe(plugins.bump({
        type: patch ? 'patch' : 'minor' // `major|minor|patch|prerelease`
      }).on('error', log.error))
      .pipe(gulp.dest('.'));
  });

  gulp.task('bump:plugin', function () {
    return gulp.src(config.pot.metadataFile)
      .pipe(plugins.bump({
        type: patch ? 'patch' : 'minor' // `major|minor|patch|prerelease`
      }).on('error', log.error))
      .pipe(gulp.dest('.'));
  });

  gulp.task('bump:constant', function () {
    return gulp.src(config.pot.metadataFile)
      .pipe(plugins.bump({
        type: patch ? 'patch' : 'minor', // `major|minor|patch|prerelease`
        key: config.constants.version, // for error reference
        regex: new RegExp('([<|\'|"]?(' + config.constants.version + ')[>|\'|"]?[ ]*[:=,]?[ ]*[\'|"]?[a-z]?)(\\d+.\\d+.\\d+)(-[0-9A-Za-z.-]+)?(\\+[0-9A-Za-z\\.-]+)?([\'|"|<]?)', 'i')
      }).on('error', log.error))
      .pipe(gulp.dest('.'));
  });

  gulp.task('bump', gulp.series(
    'bump:package',
    'bump:plugin',
    'bump:constant',
    function (done) {
      log(patch ? 'Bumped to a Patched Version!' : 'Bumped to a Minor Version!');
      done();
    }
  ));

  gulp.task('ready', function (done) {
    log.info('Must build the release!');
    done();
  });

  gulp.task('default', function (done) {
    log.info('Hi, I\'m Gulp!');
    log.info('Sass is:\n' + require('sass').info);
    done();
  });
}());
