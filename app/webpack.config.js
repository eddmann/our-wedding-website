const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .configureImageRule({
      type: 'asset',
      maxSize: 8 * 1024, // 8kb
    })
    .configureBabelPresetEnv(config => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })
    .enableSassLoader()
    .enablePostCssLoader(options => {
        options.postcssOptions = {
            plugins: { autoprefixer: {} },
        };
    })
    .enableTypeScriptLoader()
    .enableForkedTypeScriptTypesChecking()
    .enableSourceMaps(!Encore.isProduction())
    .enableIntegrityHashes(Encore.isProduction())
    .enableVersioning(Encore.isProduction());

module.exports = Encore.getWebpackConfig();
