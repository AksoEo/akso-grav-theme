const { src, watch, dest } = require('gulp');
const plumber = require('gulp-plumber');
const less = require('gulp-less');

const styleWatchSources = ['less/**/*.less'];
function compileStyles () {
    return src('less/index.less')
        .pipe(plumber())
        .pipe(less())
        .pipe(dest('./css-compiled'))
}

function build () {
    return compileStyles();
}

function watchBuild () {
    return watch(styleWatchSources, build);
}

exports.build = build;
exports.watch = exports.default = watchBuild;
