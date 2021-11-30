const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .copyFiles({
        from: './src/Ui/Http/Asset/guest/images',
        to: 'images/[path][name].[hash:8].[ext]',
        pattern: /\.(svg|png)$/,
    })
    .addEntry('guest', './src/Ui/Http/Asset/guest')
    .addEntry('day-countdown', './src/Ui/Http/Asset/guest/day-countdown')
    .addEntry('pending-rsvp', './src/Ui/Http/Asset/guest/pending-rsvp')
    .addEntry('admin', './src/Ui/Http/Asset/admin')
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
