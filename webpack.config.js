/**
 * @copyright Copyright (c) 2024, RCDevs (info@rcdevs.com)
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

const path = require('path')
const webpack = require('webpack')
const webpackConfig = require('@nextcloud/webpack-vue-config')

// Supprimer l’entrée par défaut
delete webpackConfig.entry['main']
webpackConfig.entry['challenge'] = path.join(__dirname, 'src', 'main-challenge.js')
webpackConfig.entry['settings'] = path.join(__dirname, 'src', 'main-settings.js')
webpackConfig.entry['login-setup'] = path.join(__dirname, 'src', 'main-login-setup.js')
webpackConfig.entry['admin-settings'] = path.join(__dirname, 'src', 'admin-settings.js')

// 🔧 Ajouter l'extension .js comme fallback pour les imports ESM fully-specified
webpackConfig.resolve = {
	...webpackConfig.resolve,
	extensions: ['.js', '.vue', '.json'],
	alias: {
		...(webpackConfig.resolve?.alias || {}),
		'process/browser': require.resolve('process/browser.js')
	},
	fallback: {
		...webpackConfig.resolve?.fallback,
		process: require.resolve('process/browser.js'),
		buffer: require.resolve('buffer/')
	}
}

// 🧩 Fournir les polyfills Node.js
webpackConfig.plugins = [
	...(webpackConfig.plugins || []),
	new webpack.ProvidePlugin({
		process: 'process/browser',
		Buffer: ['buffer', 'Buffer']
	})
]

// ✅ Important : désactiver la concaténation des modules pour éviter les bugs avec ESM
webpackConfig.optimization = {
	...webpackConfig.optimization,
	concatenateModules: false
}

module.exports = webpackConfig
