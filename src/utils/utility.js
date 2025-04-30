import { appName, baseUrl } from './config.js';

import { generateUrl, generateFilePath, generateOcsUrl } from '@nextcloud/router';

const getT = (textToTranslate) => {
	return t(appName, textToTranslate);
}

// const checkServerUrl = (serverUrlToCheck, apiUrl, loaderElt, messageStatus, messageCheckServerUrl) => {
const checkServerUrl = (serverNumber, apiUrl, objReqServerUrl) => {
	let urlRequest = generateOcsUrl(baseUrl + apiUrl);

	objReqServerUrl.reqServerUrl.enable = true;
	objReqServerUrl.reqServerUrl.request = true;

	$.post(urlRequest, { serverNumber: serverNumber }, function (response) {
		objReqServerUrl.reqServerUrl.enable = true;
		objReqServerUrl.reqServerUrl.request = false;
		objReqServerUrl.reqServerUrl.code = response.code;
		objReqServerUrl.reqServerUrl.message = response.message;
		objReqServerUrl.reqServerUrl.status = response.status;
	});
}

export { getT, checkServerUrl };
// export { getT };

