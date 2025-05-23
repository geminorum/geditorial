const path = require('path');

const { VueLoaderPlugin } = require('vue-loader');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');
const RtlCssPluginWebpack = require('rtl-css-plugin-webpack');

module.exports = (env, argv) => {
  const config = {
    entry: {
      main: path.resolve(__dirname, 'src', 'main.js')
    },
    output: {
      path: path.resolve(__dirname, 'build'),
      filename: '[name].js',
      clean: true
    },
    module: {
      rules: [
        {
          // @REF: https://morioh.com/p/92604a9fa314
          test: /\.vue$/,
          use: 'vue-loader'
        },
        {
          test: /\.js$/,
          exclude: file => (
            /node_modules/.test(file) &&
            !/\.vue\.js/.test(file)
          ),
          use: {
            loader: 'babel-loader',
            options: { presets: ['@babel/preset-env', '@wordpress/babel-preset-default'] }
          }
        },
        {
          test: /\.(sa|sc|c)ss$/,
          use: [
            argv.mode === 'development' ? 'vue-style-loader' : { loader: MiniCssExtractPlugin.loader, options: { esModule: false } },
            // 'vue-style-loader',
            {
              loader: 'css-loader',
              options: {
                // modules: true,
                importLoaders: 1
              }
            },
            {
              loader: 'sass-loader',
              options: {
                sourceMap: argv.mode === 'development',
                sassOptions: {
                  silenceDeprecations: [
                    'import',
                    'legacy-js-api'
                  ],
                  // includePaths: [
                  //   './../../gnetwork/assets/sass',
                  //   './../gnetwork/assets/sass'
                  // ]
                  sourceComments: true,
                  errLogToConsole: true
                }
              }
            },
            {
              loader: 'postcss-loader',
              options: {
                postcssOptions: {
                  plugins: [
                    'autoprefixer'
                  ]
                }
              }
            }
          ]
        },
        {
          // @SEE: https://webpack.js.org/guides/asset-modules/
          test: /\.(svg|ico|png|webp|jpg|gif|jpeg)$/,
          type: 'asset/resource'
        }
      ]
    },
    plugins: [
      new DependencyExtractionWebpackPlugin(),
      new VueLoaderPlugin(),
      new MiniCssExtractPlugin({
        filename: '[name].css'
      }),

      new RtlCssPluginWebpack({
        filename: '[name]-rtl.css'
      })
    ],

    watch: true,
    watchOptions: {
      ignored: '**/node_modules',
      aggregateTimeout: 600,
      poll: 1000,
      stdin: true
    }
  };

  if (argv.mode === 'development') {
    config.devtool = 'inline-source-map';
  }

  if (argv.mode === 'production') {
    // ...
  }

  return config;
};
