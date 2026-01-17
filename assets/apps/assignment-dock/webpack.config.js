const webpack = require('webpack');
const path = require('path');

const { VueLoaderPlugin } = require('vue-loader');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');
const RtlCssPluginWebpack = require('rtl-css-plugin-webpack');

module.exports = (env, argv) => {
  const config = {
    entry: path.resolve(__dirname, 'src', 'main.js'),
    output: {
      path: path.resolve(__dirname, 'build'),
      filename: '[name].js',
      clean: true
    },
    resolve: {
      extensions: [
        '.js',
        '.vue'
      ]
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
            options: {
              presets: [
                '@babel/preset-env',
                '@wordpress/babel-preset-default'
              ]
            }
          }
        },
        {
          test: /\.(sa|sc|c)ss$/,
          use: [
            argv.mode === 'development'
              ? 'vue-style-loader'
              : {
                  loader: MiniCssExtractPlugin.loader,
                  options: {
                    esModule: false
                  }
                },
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
          // test: /\.(svg|ico|png|webp|jpg|gif|jpeg)$/,
          test: /\.(ico|jpg|jpeg|png|gif|eot|otf|webp|svg|ttf|woff|woff2)(\?.*)?$/,
          type: 'asset/resource'
        }
      ]
    },
    plugins: [
      new webpack.DefinePlugin({
        __VUE_OPTIONS_API__: 'true',
        __VUE_PROD_DEVTOOLS__: argv.mode === 'development' ? 'true' : 'false',
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: 'false'
      }),

      new DependencyExtractionWebpackPlugin(),

      new VueLoaderPlugin(),

      // https://webpack.js.org/plugins/mini-css-extract-plugin/
      new MiniCssExtractPlugin({
        filename: '[name].css'
      }),

      new RtlCssPluginWebpack({
        filename: '[name]-rtl.css'
      })
    ]
  };

  if (argv.mode === 'development') {
    config.devtool = 'inline-source-map';
    config.mode = 'development';
    config.watch = true;
    config.watchOptions = {
      ignored: '**/node_modules',
      aggregateTimeout: 600,
      poll: 1000,
      stdin: true
    };
  }

  if (argv.mode === 'production') {
    // ...
  }

  return config;
};
