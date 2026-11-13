/**
 * QuickCart theme RequireJS configuration.
 *
 * Motion (https://motion.dev, MIT) is the framework-agnostic build of Framer Motion.
 * Its UMD bundle registers as an anonymous AMD module, so no shim is needed.
 */
var config = {
    paths: {
        motion: 'js/vendor/motion'
    },
    deps: [
        'js/quickcart/app'
    ]
};
