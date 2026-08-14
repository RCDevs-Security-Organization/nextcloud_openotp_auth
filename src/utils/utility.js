/**
 *
 * @copyright Copyright (c) 2026, RCDevs (info@rcdevs.com)
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
 *
 */

import { appName, baseUrl } from './config.js';

import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';

const getT = (textToTranslate) => {
	const translated = t(appName, textToTranslate);
	if (typeof translated === 'string' && translated.trim() !== '') {
		return translated;
	}
	return textToTranslate;
};

const checkServerUrl = (serverNumber, apiUrl, objReqServerUrl) => {
	let urlRequest = generateUrl(baseUrl + apiUrl);

	objReqServerUrl.reqServerUrl.enable = true;
	objReqServerUrl.reqServerUrl.request = true;

	const requestData = { serverNumber: serverNumber };
	if (Object.prototype.hasOwnProperty.call(objReqServerUrl, 'serverUrl')) {
		requestData.serverUrl = objReqServerUrl.serverUrl || '';
	}

	axios.post(urlRequest, requestData).then(({ data: response }) => {
		objReqServerUrl.reqServerUrl.enable = true;
		objReqServerUrl.reqServerUrl.request = false;
		objReqServerUrl.reqServerUrl.code = response.code;
		objReqServerUrl.reqServerUrl.message = response.message;
		objReqServerUrl.reqServerUrl.status = response.status;
	}).catch((error) => {
		objReqServerUrl.reqServerUrl.enable = true;
		objReqServerUrl.reqServerUrl.request = false;
		objReqServerUrl.reqServerUrl.code = 0;
		objReqServerUrl.reqServerUrl.message = error.response?.data?.message || getT('Could not connect to host');
		objReqServerUrl.reqServerUrl.status = false;
	});
};

export { getT, checkServerUrl };
// export { getT };
