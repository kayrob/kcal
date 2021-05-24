var gulp = require('gulp'),
	sass = require('gulp-sass'),
	wpPot = require('gulp-wp-pot');
var sourcemaps = require('gulp-sourcemaps');
var pxToRem = require('gulp-px2rem-converter');


var paths = {
	groups: {
		sass: [{
			in: ['css/*.scss'],
			out: 'dist/css'
		}]
	}
};

var sassConfig = {
	inputFile: 'css/*.scss',
	outputDirectory: 'dist/css',
	options: {
		outputStyle: 'compressed'
	},
	includePaths: []
}

function process_scss(inputstream, group) {

	return (
		gulp
		// .pipe(group.in)
		// .pipe(debug({ title: '#1 css' }))
		.src(group.in)
		.pipe(sourcemaps.init())
		.pipe(sass(sassConfig.options).on('error', sass.logError))
		.pipe(pxToRem())
		.pipe(sourcemaps.write('.'))
		.pipe(gulp.dest(group.out))
	);
}

function process_css_promise() {
	return new Promise(function (resolve, reject) {
		paths.groups.sass.forEach(function (group, key) {
			//  gutil.log('READING SASS IN: ' + key);
			// gutil.log('READING SASS IN: ' + group.in);
			// gutil.log('WRITING SASS OUT: ' + group.out);
			var stream = process_scss(gulp.src(group.in), group);
			if (Object.keys(paths.groups.sass).length == key + 1) {
				stream.on('end', resolve);
			}
		});
	});
}

gulp.task('build-css', function() {
	/*return gulp
		.src(sassConfig.inputFile)
		.pipe(sourcemaps.init())
		.pipe(sass(sassConfig.options).on('error', sass.logError))
		.pipe(sourcemaps.write('.'))
		.pipe(gulp.dest(sassConfig.outputDirectory));*/

	return process_css_promise();
});

gulp.task('watch', function() {
	gulp.watch('css/*.scss', gulp.series('build-css'));
});

gulp.task('default', function(){
    gulp.watch('css/*.scss', gulp.series('build-css'));
});

gulp.task('build-pot', function () {
    return gulp.src('k-cal/*.php')
	.pipe(wpPot( {
		domain: 'kcal,',
		package: 'kCal: Events Plugin'
	} ))
	.pipe(gulp.dest('file.pot'));
});