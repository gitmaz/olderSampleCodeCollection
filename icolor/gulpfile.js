var gulp = require('gulp');
var sass = require('gulp-sass');
var minifycss = require('gulp-minify-css');
var rename = require('gulp-rename');
var cachebust = require('gulp-cache-bust');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var pump = require('pump');
var obfuscate = require('gulp-obfuscate');
//var js_obfuscator = require('gulp-js-obfuscator');
var htmlreplace = require('gulp-html-replace');

gulp.task('compile', function() {

    //console.log("Sass compile started");
    
	//gulp.src('sass/**/*.scss')
    //    .pipe(sass().on('error', sass.logError))
    //    .pipe(minifycss())
    //    .pipe(rename('site.min.css'))
    //    .pipe(gulp.dest('./dist/css/'));
		
    //console.log("Sass compile completed");

	//console.log("Cache busting started");
    //gulp.src('./php/View/Layouts/base.tpl.ctp')
    //    .pipe(cachebust({
    //        type: 'timestamp'
    //    }))
    //    .pipe(rename('base.ctp'))
    //    .pipe(gulp.dest('./php/View/Layouts/'));
	//console.log("Cache busting completed");
	
});

gulp.task('concat', function() {
	console.log("Js compile started");
    	
	gulp.src('./js/*.js')
		.pipe(concat('all.js'))
		.pipe(gulp.dest('./dist/conca/'));

	gulp.src('mcolor.html')
		.pipe(htmlreplace({
			'css': 'styles.min.css',
			'js': './js/all.js'
		}))
		.pipe(gulp.dest('dist/'));

	console.log("Js compile completed");
	
});

gulp.task('compress', function (cb) {
	
	console.log("Js compress started");
	pump([
				gulp.src('./dist/conca/all.js'),
				uglify({ mangle: true}),
				gulp.dest('./dist/js/')
			],
			cb
		);
		
	//gulp.src('./js/*.js')
		//.pipe(js_obfuscator({}, ["**/*.js"]))
        //.pipe(obfuscate({exclude: ['animate'], replaceMethod: obfuscate.ZALGO}))
	//	.pipe(gulp.dest('./dist/obfus/')); 



	console.log("Js compress completed");
});

gulp.task('nocompress', function () {
	gulp.src('./dist/conca/*.js')
		.pipe(gulp.dest('./dist/js/')); 
});

gulp.task('all', ['compile', 'concat','compress']);
gulp.task('nocomp', ['compile', 'concat','nocompress']);

//Watch task
gulp.task('default',function() {
    gulp.watch('sass/**/*.scss',['compile']);
});
