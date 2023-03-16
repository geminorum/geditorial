import gulp from 'gulp';
import sass from 'gulp-dart-sass';
import compiler from 'sass';
import sourcemaps from 'gulp-sourcemaps';
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
import bump from 'gulp-bump';
import changedInPlace from 'gulp-changed-in-place';
import githubRelease from 'gulp-github-release';
import log from 'fancy-log';
import rtlcss from 'rtlcss';
import livereload from 'gulp-livereload';
import parseChangelog from 'parse-changelog';
import extend from 'xtend';
import template from 'lodash.template';
import yaml from 'js-yaml'

import { exec } from 'child_process';
import path from 'path';

import { deleteSync } from 'del';
import { readFileSync, accessSync } from 'node:fs';
import { readFile } from 'fs/promises';

const { src, dest, watch, series, parallel, task } = gulp;

// @REF: https://www.stefanjudis.com/snippets/how-to-import-json-files-in-es-modules-node-js/
const conf = JSON.parse(await readFile(new URL('./gulp.config.json', import.meta.url))); // eslint-disable-line
const pkg = JSON.parse(await readFile(new URL('./package.json', import.meta.url))); // eslint-disable-line

// @REF: https://www.sitepoint.com/pass-parameters-gulp-tasks/
const devBuild = ((process.env.NODE_ENV || 'development').trim().toLowerCase() === 'development');

// @REF: https://www.sitepoint.com/pass-parameters-gulp-tasks/
const args=(argList=>{let arg={},a,opt,thisOpt,curOpt;for(a=0;a<argList.length;a++){thisOpt=argList[a].trim();opt=thisOpt.replace(/^\-+/,'');if(opt===thisOpt){if(curOpt)arg[curOpt]=opt;curOpt=null;}else{curOpt=opt;arg[curOpt]=true;}}return arg;})(process.argv);

// @REF: https://stackoverflow.com/a/7224605
const capitalize = s => s && s[0].toUpperCase() + s.slice(1);

let env = conf.env;
const banner = conf.banner.join('\n');

const debug = /--debug/.test(process.argv.slice(2));
const patch = /--patch/.test(process.argv.slice(2)); // bump a patch?

try {
  env = extend(conf.env, yaml.load(readFileSync('./environment.yml', { encoding: 'utf-8' }), { json: true }));
} catch (e) {
  log.warn('no environment.yml loaded!');
}

task('dev:newmodule', function (done) {
	if (!('name' in args)) {
		log.error('Error: missing required name for the module: `--name NewModule`');
		return done();
	}

	const name = capitalize(args.name); // TODO: sanitize this!
	const parts = name.split(/(?=[A-Z])/);

	const data = extend(conf.templates.newmodule.defaults, {
		moduleTitle: parts.join(' '),
		moduleCamelCase: name,
		moduleUnderline: parts.join('_').toLowerCase(),
		moduleTextdomain: conf.templates.newmodule.defaults.pluginTexdomain + '-' + parts.join('-').toLowerCase()
	});

	const file = data.moduleCamelCase + '.' + conf.templates.newmodule.ext;
	const targ = path.join(conf.templates.newmodule.dest, data.moduleCamelCase);

	if (debug) log.info(data);

	try {
		accessSync(path.join(dest, file));
		log.error('Error: the module already exists');
		return done();
	} catch (e) {
		return src(conf.templates.newmodule.src)
			.pipe(gulptemplate(data))
			.pipe(rename(file))
			.pipe(dest(targ));
	}
});

function i18nExtra (i18n) {
	return '--exclude="' + i18n.exclude.toString() + '"' +
				' --file-comment="' + i18n.comment.toString() + '"' +
				' --skip-plugins --skip-themes --skip-packages' +
				(debug ? ' --debug' : '');
}

task('i18n:plugin', function (cb) {
	const command = 'wp i18n make-pot . ' +
		i18nExtra(conf.i18n.plugin) +
		' --headers=\'' + template(JSON.stringify(conf.i18n.plugin.headers), { variable: 'data' })({ bugs: pkg.bugs.url }) + '\'';

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

task('i18n:modules', function () {
	const extra = i18nExtra(conf.i18n.modules);

	return src(conf.input.modules)
		.pipe(gulpexec(function (file) {
			const folder = file.path.split(path.sep).pop();
			const domain = folder.split(/(?=[A-Z])/).join('-').toLowerCase(); // EXAMPLE: `DocumentRevisions` >> `document-revisions`
			const module = folder.toLowerCase();
			log.info('Make pot for Module: ' + folder);
			return 'wp i18n make-pot ' + file.path +
				' ./languages/' + folder + '/' + domain + '.pot' +
				' --domain=' + pkg.name + '-' + domain +
				' --subtract=./languages/' + pkg.name + '.pot' +
				// ' --package-name="' + pkg.productName + ' ' + folder + ' ' + pkg.version + '" ' +
				' --package-name="' + pkg.productName + ' ' + folder + '" ' + // no version for fewer commits!
				' --headers=\'' + template(JSON.stringify(conf.i18n.modules.headers), { variable: 'data' })({ bugs: pkg.bugs.url, folder: folder, domain: domain, module: module }) + '\' ' +
				extra;
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

task('i18n', series('i18n:plugin', 'i18n:modules'));

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
		.pipe(header(banner, { pkg: pkg }))
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
		.pipe(header(banner, { pkg: pkg }))
		.pipe(dest('.'));
});

task('build:copy', function () {
	return src(conf.input.final, { base: '.' })
		.pipe(dest(conf.output.ready + pkg.name));
});

task('build:clean', function (done) {
	deleteSync([conf.output.ready]);
	done();
});

task('build:zip', function () {
	return src(conf.input.ready)
		.pipe(zip(pkg.name + '-' + pkg.version + '.zip'))
		.pipe(dest(conf.output.final));
});

task('build', series(
	parallel('build:styles', 'build:rtl', 'build:scripts'),
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
	if (!env.github) {
		log.error('Error: missing required token for github');
		return done();
	}

	const changes = parseChangelog(readFileSync('CHANGES.md', { encoding: 'utf-8' }), { title: false });
	const options = {
		token: env.github,
		tag: pkg.version,
		notes: changes.versions[0].rawNote,
		manifest: pkg,
		skipIfPublished: true,
		draft: true
	};

	return src(pkg.name + '-' + pkg.version + '.zip')
		.pipe(githubRelease(options));
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
	log.info('Sass is:\n' + compiler.info);
	done();
});
