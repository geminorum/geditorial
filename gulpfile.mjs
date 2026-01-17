import gulp from 'gulp';
import sass from 'gulp-dart-sass';
import * as compiler from 'sass'; // @REF: https://github.com/sass/dart-sass/issues/2008
// import sourcemaps from 'gulp-sourcemaps';
import postcss from 'gulp-postcss';
import cssnano from 'cssnano';
import autoprefixer from 'autoprefixer';
import rename from 'gulp-rename';
import gulpdebug from 'gulp-debug';
import gulpif from 'gulp-if';
import gulpexec from 'gulp-exec';
import gulptemplate from 'gulp-template';
import header from 'gulp-header';
import uglify from 'gulp-uglify';
import zip from 'gulp-zip';
import svgo from 'gulp-svgo'; // https://github.com/corneliusio/gulp-svgo
import bump from 'gulp-bump';
import changedInPlace from 'gulp-changed-in-place';
import log from 'fancy-log';
import rtlcss from 'rtlcss';
import livereload from 'gulp-livereload';
import parseChangelog from 'parse-changelog';
import extend from 'xtend';
import template from 'lodash.template';
import yaml from 'js-yaml';

import { exec } from 'child_process';
import path from 'path';

import fs from 'fs-extra';
import { deleteSync } from 'del';
import { readFileSync, accessSync } from 'node:fs';
// import { readFile } from 'fs/promises';

// @REF: https://www.stefanjudis.com/snippets/how-to-import-json-files-in-es-modules-node-js/
import { createRequire } from 'module';
const customRequire = createRequire(import.meta.url);

const { src, dest, watch, series, parallel, task } = gulp;

// @REF: https://www.stefanjudis.com/snippets/how-to-import-json-files-in-es-modules-node-js/
// const conf = JSON.parse(await readFile(new URL('./gulp.config.json', import.meta.url))); // eslint-disable-line
// const pkg = JSON.parse(await readFile(new URL('./package.json', import.meta.url))); // eslint-disable-line

const conf = customRequire('./gulp.config.json');
const pkg = customRequire('./package.json');

// @REF: https://www.sitepoint.com/pass-parameters-gulp-tasks/
const devBuild = ((process.env.NODE_ENV || 'development').trim().toLowerCase() === 'development'); // eslint-disable-line

// @REF: https://www.sitepoint.com/pass-parameters-gulp-tasks/
const args=(argList=>{let arg={},a,opt,thisOpt,curOpt;for(a=0;a<argList.length;a++){thisOpt=argList[a].trim();opt=thisOpt.replace(/^\-+/,'');if(opt===thisOpt){if(curOpt)arg[curOpt]=opt;curOpt=null;}else{curOpt=opt;arg[curOpt]=true;}}return arg;})(process.argv); // eslint-disable-line

// @REF: https://stackoverflow.com/a/7224605
const capitalize = s => s && s[0].toUpperCase() + s.slice(1);

// @REF: https://stackoverflow.com/a/74218453
const sanitizeModule = s => s && s.trim().replace(/[^A-Za-z0-9-]/g, '');

// @REF: https://stackoverflow.com/a/49968211
const normalizeEOL = s => s.replace(/^\s*[\r\n]/gm, '\r\n');

// @REF: https://flaviocopes.com/how-to-check-if-file-exists-node/
function fsExists(path){try{if(fs.existsSync(path)){return true;}}catch(err){log.error(err)};return false;} // eslint-disable-line

let env = conf.env;
const banner = conf.banner.join('\n');

const debug = /--debug/.test(process.argv.slice(2));
const patch = /--patch/.test(process.argv.slice(2)); // bump a patch?

try {
  env = extend(conf.env, yaml.load(readFileSync('./environment.yml', { encoding: 'utf-8' }), { json: true })); // eslint-disable-line no-unused-vars
} catch (e) {
  log.warn('no environment.yml loaded!');
}

function githubCommand (command, callback) {
  exec('gh ' + command, function (err, stdout, stderr) {
    if (stdout) log.info('GitHub:', stdout.trim());
    if (stderr) log.error('Errors:', stderr.trim());
    callback(err);
  });
}

task('dev:newmodule', function (done) {
  if (!('name' in args)) {
    log.error('Error: missing required name for the module: `--name NewModule`');
    return done();
  }

  const template = 'template' in args ? args.template : 'generalModule';

  if (!(template in conf.templates)) {
    log.error('Error: provided template not exist in configuration: `' + template + '`');
    return done();
  }

  const name = capitalize(sanitizeModule(args.name));

  if (!name) {
    log.error('Error: invalid name for the module: `' + args.name + '`');
    return done();
  }

  const parts = name.split(/(?=[A-Z])/);

  const data = extend(conf.templates[template].defaults, {
    moduleTitle: parts.join(' '),
    moduleCamelCase: name,
    moduleUnderline: parts.join('_').toLowerCase(),
    moduleTextdomain: conf.templates[template].defaults.pluginTexdomain + '-' + parts.join('-').toLowerCase()
  });

  const file = data.moduleCamelCase + '.' + conf.templates[template].ext;
  const targ = path.join(conf.templates[template].dest, data.moduleCamelCase);

  if (debug) log.info(data, path.join(targ, file));

  try {
    accessSync(path.join(targ, file));
    log.error('Error: the module already exists');
    return done();
  } catch (e) {
    return src(conf.templates[template].src)
      .pipe(gulptemplate(data))
      .pipe(rename(file))
      .pipe(dest(targ));
  }
});

function i18nExtra (i18n) {
  return '--exclude="' + i18n.exclude.toString() + '"' +
        ' --file-comment="' + i18n.comment.toString() + '"' +
        ' --skip-plugins --skip-themes --skip-packages' +
        // ' --quiet' +
        (debug ? ' --debug' : '');
}

task('i18n:plugin', function (cb) {
  const command = 'wp i18n make-pot .' +
  ' --headers=\'' + template(JSON.stringify(conf.i18n.plugin.headers), { variable: 'data' })({ bugs: pkg.bugs.url }) + '\' ' +
  i18nExtra(conf.i18n.plugin);

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

task('i18n:admin', function (cb) {
  const command = 'wp i18n make-pot . ' +
    ' ./languages/admin.pot' +
    ' --domain=' + pkg.name + '-admin' +
    // ' --subtract=./languages/' + pkg.name + '.pot' + // NOTE: The only duplicates are the plugin info strings.
    ' --headers=\'' + template(JSON.stringify(conf.i18n.admin.headers), { variable: 'data' })({ bugs: pkg.bugs.url }) + '\' ' +
    i18nExtra(conf.i18n.admin);

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

function i18nModule (file) {
  const folder = file.path.split(path.sep).pop();
  const domain = folder.split(/(?=[A-Z])/).join('-').toLowerCase(); // EXAMPLE: `DocumentRevisions` >> `document-revisions`
  // const module = folder.toLowerCase();
  const module = folder.split(/(?=[A-Z])/).join('_').toLowerCase(); // EXAMPLE: `DocumentRevisions` >> `document_revisions`
  return { folder, domain, module };
}

function i18nHeaders (module, tmpl) {
  return template(
    JSON.stringify(tmpl), {
      variable: 'data'
    })({
    bugs: pkg.bugs.url,
    folder: module.folder,
    domain: module.domain,
    module: module.module
  });
}

task('i18n:module', function (done) {
  const extra = i18nExtra(conf.i18n.modules);

  if (!('name' in args)) {
    log.error('Error: missing required name for i18n: `--name ModuleName`');
    return done();
  }

  return src(conf.input.module + args.name)
    .pipe(gulpexec(function (file) {
      const module = i18nModule(file);
      log.info('Make pot for Module: ' + module.folder);
      return 'wp i18n make-pot ' + file.path +
        ' ./languages/' + module.folder + '/' + module.domain + '.pot' +
        ' --domain=' + pkg.name + '-' + module.domain +
        ' --package-name="' + pkg.productName + ' ' + module.folder + '" ' + // no version for fewer commits!
        ' --headers=\'' + i18nHeaders(module, conf.i18n.modules.headers) + '\' ' + extra;
    }), {
      continueOnError: false,
      pipeStdout: false
    })
    .pipe(gulpexec.reporter({
      err: true,
      stderr: true,
      stdout: true
    }));
});

task('i18n:modules', function () {
  const extra = i18nExtra(conf.i18n.modules);

  return src(conf.input.modules)
    .pipe(gulpexec(function (file) {
      const module = i18nModule(file);
      log.info('Make pot for Module: ' + module.folder);
      return 'wp i18n make-pot ' + file.path +
        ' ./languages/' + module.folder + '/' + module.domain + '.pot' +
        ' --domain=' + pkg.name + '-' + module.domain +
        ' --package-name="' + pkg.productName + ' ' + module.folder + '" ' + // no version for fewer commits!
        ' --headers=\'' + i18nHeaders(module, conf.i18n.modules.headers) + '\' ' + extra;
    }), {
      continueOnError: false,
      pipeStdout: false
    })
    .pipe(gulpexec.reporter({
      err: true,
      stderr: true,
      stdout: true
    }));
});

task('i18n:php', function () {
  // const extra = i18nExtra(conf.i18n.langs);

  return src(conf.input.langs)
    .pipe(gulpexec(function (file) {
      const folder = file.path.split(path.sep).slice(-2, -1).pop();
      log.info('Make php for Module: ' + folder);

      // https://developer.wordpress.org/cli/commands/i18n/make-php/
      return 'wp i18n make-php ' + file.path +
      ' --skip-plugins --skip-themes --skip-packages';
      // extra;
    }), {
      continueOnError: false,
      pipeStdout: false
    })
    .pipe(gulpexec.reporter({
      err: true,
      stderr: true,
      stdout: true
    }));
});

task('i18n', series('i18n:admin', 'i18n:plugin', 'i18n:modules'));

task('dev:sass', function () {
  return src(conf.input.sass)
    // .pipe(sourcemaps.init())
    .pipe(sass.sync(conf.sass).on('error', sass.logError))
    .pipe(postcss([
      cssnano(conf.cssnano.dev),
      autoprefixer(conf.autoprefixer.dev)
    ]))
    // .pipe(sourcemaps.write(conf.output.sourcemaps))
    .pipe(dest(conf.output.css)).on('error', log.error)
    .pipe(postcss([rtlcss()]))
    .pipe(rename({ suffix: '-rtl' }))
    .pipe(dest(conf.output.css)).on('error', log.error)
    .pipe(changedInPlace())
    .pipe(gulpdebug({ title: 'Changed' }))
    .pipe(gulpif(function (file) {
      if (file.extname !== '.map') return true;
    }, livereload()));
});

task('watch:styles', function () {
  livereload.listen();
  watch(conf.input.sass, series('dev:sass'));
});

// all styles / without livereload
task('dev:styles', function () {
  return src(conf.input.sass)
    // .pipe(sourcemaps.init())
    .pipe(sass.sync(conf.sass).on('error', sass.logError))
    .pipe(postcss([
      cssnano(conf.cssnano.dev),
      autoprefixer(conf.autoprefixer.dev)
    ]))
    .pipe(header(banner, { pkg }))
    // .pipe(sourcemaps.write(conf.output.sourcemaps))
    .pipe(gulpdebug({ title: 'Changed' }))
    .pipe(dest(conf.output.css)).on('error', log.error)
    .pipe(postcss([rtlcss()]))
    .pipe(rename({ suffix: '-rtl' }))
    .pipe(gulpdebug({ title: 'RTLed' }))
    .pipe(dest(conf.output.css)).on('error', log.error);
});

task('dev:scripts', function () {
  return src(conf.input.js, { base: '.' })
    .pipe(rename({ suffix: '.min' }))
    .pipe(uglify())
    .pipe(dest('.'));
});

gulp.task('dev:banklogos', function () {
  return src(conf.input.banklogos, { base: '.' })
    .pipe(rename({ suffix: '.min' }))
    .pipe(svgo())
    .pipe(dest('.'));
});

gulp.task('dev:svg', function () {
  return src(conf.input.svg, { base: '.' })
    .pipe(rename({ suffix: '.min' }))
    .pipe(svgo())
    .pipe(dest('.'));
});

task('build:styles', function () {
  return src(conf.input.sass)
    .pipe(sass(conf.sass).on('error', sass.logError))
    .pipe(postcss([
      cssnano(conf.cssnano.build),
      autoprefixer(conf.autoprefixer.build)
    ]))
    .pipe(dest(conf.output.css)).on('error', log.error);
});

// seperated because of stripping rtl directives in compression
task('build:rtl', function () {
  return src(conf.input.sass)
    .pipe(sass.sync(conf.sass).on('error', sass.logError))
    // .pipe(postcss([rtlcss()])) // divided to avoid cssnano messing with rtl directives
    .pipe(postcss([
      rtlcss(),
      cssnano(conf.cssnano.build),
      autoprefixer(conf.autoprefixer.build)
    ]))
    .pipe(rename({ suffix: '-rtl' }))
    .pipe(dest(conf.output.css)).on('error', log.error);
});

task('build:scripts', function () {
  return src(conf.input.js, { base: '.' })
    .pipe(rename({ suffix: '.min' }))
    .pipe(uglify())
    .pipe(dest('.'));
});

task('build:banner', function () {
  return src(conf.input.banner, { base: '.' })
    .pipe(header(banner, { pkg }))
    .pipe(dest('.'));
});

task('build:copy', function () {
  return src(conf.input.final, {
    base: '.',
    allowEmpty: true,
    buffer: true,
    encoding: false,
    removeBOM: false
  })
    .pipe(dest(conf.output.ready + pkg.name));
});

task('build:clean', function (done) {
  deleteSync([conf.output.ready]);
  done();
});

task('build:zip', function () {
  return src(conf.input.ready, {
    allowEmpty: true,
    buffer: true,
    encoding: false,
    removeBOM: false
  })
    .pipe(zip(pkg.name + '-' + pkg.version + '.zip'))
    .pipe(dest(conf.output.final));
});

task('build', series(
  parallel(
    'build:styles',
    'build:rtl',
    'build:scripts'
    // 'i18n:php' // just in case forgotten!
  ),
  'build:banner',
  'build:clean',
  'build:copy',
  'build:zip',
  function (done) {
    log('Done!');
    done();
  }
));

task('github:package', function (done) {
  const filename = pkg.name + '-' + pkg.version + '.zip';

  if (!fsExists('./' + filename)) {
    log.error('Error: missing required package for github');
    return done();
  }

  const changes = parseChangelog(fs.readFileSync(conf.root.changelog, { encoding: 'utf-8' }), { title: false });

  // @REF: https://cli.github.com/manual/gh_release_create
  githubCommand('release create ' +
    pkg.version + ' ' +
    filename + ' ' +
    '--draft' + ' ' +
    '--latest' + ' ' + // default: automatic based on date and version
    '--title ' + pkg.version + ' ' +
    '--notes "' + normalizeEOL(changes.versions[0].rawNote.toString()) + '"' +
    '',
  done);
});

task('bump:package', function () {
  return src('./package.json')
    .pipe(bump({
      type: patch ? 'patch' : 'minor' // `major|minor|patch|prerelease`
    }).on('error', log.error))
    .pipe(dest('.'));
});

task('bump:plugin', function () {
  return src(conf.pot.metadataFile)
    .pipe(bump({
      type: patch ? 'patch' : 'minor' // `major|minor|patch|prerelease`
    }).on('error', log.error))
    .pipe(dest('.'));
});

task('bump:constant', function () {
  return src(conf.pot.metadataFile)
    .pipe(bump({
      type: patch ? 'patch' : 'minor', // `major|minor|patch|prerelease`
      key: conf.constants.version, // for error reference
      regex: new RegExp('([<|\'|"]?(' + conf.constants.version + ')[>|\'|"]?[ ]*[:=,]?[ ]*[\'|"]?[a-z]?)(\\d+.\\d+.\\d+)(-[0-9A-Za-z.-]+)?(\\+[0-9A-Za-z\\.-]+)?([\'|"|<]?)', 'i')
    }).on('error', log.error))
    .pipe(dest('.'));
});

task('bump', series(
  'bump:package',
  'bump:plugin',
  'bump:constant',
  function (done) {
    log(patch ? 'Bumped to a Patched Version!' : 'Bumped to a Minor Version!');
    done();
  }
));

task('ready', function (done) {
  log.info('Must build the release!');
  done();
});

task('default', function (done) {
  log.info('Hi, I\'m Gulp!');
  log.info('Sass is:\n' + compiler.default.info);
  done();
});
