const HtmlWebPackPlugin = require('html-webpack-plugin')
const path = require('path')

const IS_DEV = process.env.NODE_ENV === 'development'

const OUTPUT_FOLDER = IS_DEV ? '/dist_dev' : '/dist'

module.exports = {
    entry: {
        "index": "./builds/index.jsx"
    },
    output: {
        filename: '[name].js',
        path: path.join( __dirname, OUTPUT_FOLDER)
    },
    optimization: {
        minimize: IS_DEV ? false : true
    },
    module: {
        rules: [
            {
                test: /\.(js|jsx)$/,
                exclude: /node_modules/,
                loader: 'babel-loader',
                options: {
                    presets: ['@babel/preset-react']
                }
            },
            {
                test: /\.(sa|sc|c)ss$/,
                use: [
                    { loader: 'style-loader', options: { injectType: 'styleTag'} },
                    'css-loader',
                    {
                        loader: 'sass-loader',
                        options: {
                            implementation: require('sass')
                        }
                    }
                ]
            },
            {
                test: /\.(png|woff|woff2|eot|ttf|svg|jpg)$/,
                type: 'asset/resource'
            }
        ]
    },
    plugins: [
        new HtmlWebPackPlugin( {
            template: path.resolve( __dirname, 'index.html'),
            filename: 'index.html'
        })
    ]

}