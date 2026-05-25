import { appName, baseUrl } from './config.js';

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

	$.post(urlRequest, requestData, function (response) {
		objReqServerUrl.reqServerUrl.enable = true;
		objReqServerUrl.reqServerUrl.request = false;
		objReqServerUrl.reqServerUrl.code = response.code;
		objReqServerUrl.reqServerUrl.message = response.message;
		objReqServerUrl.reqServerUrl.status = response.status;
	});
};

export { getT, checkServerUrl };
// export { getT };

