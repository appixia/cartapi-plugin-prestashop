function _appixiaBridge_execute(url)
{
	var iframe = document.createElement("IFRAME");
	iframe.setAttribute("src", url);
	document.documentElement.appendChild(iframe);
	iframe.parentNode.removeChild(iframe);
	iframe = null;
}

function _appixiaBridge_argToQuery(arg)
{
	if (typeof(arg)=='string') return encodeURIComponent(arg);
	else return $.param(arg);
}

function appixiaBridge_SendMessageToParent(message, arg)
{
	var query = _appixiaBridge_argToQuery(arg);
	_appixiaBridge_execute("bridge://SendMessageToParent/" + encodeURIComponent(message) + "/?" + query);
}

function appixiaBridge_SendMessageToModuleByConf(moduleConf, message, arg)
{
	var query = _appixiaBridge_argToQuery(arg);
	_appixiaBridge_execute("bridge://SendMessageToModuleByConf/" + encodeURIComponent(moduleConf) + "/" + encodeURIComponent(message) + "/?" + query);
}

function appixiaBridge_DisplayActivityModuleByConf(moduleConf, params)
{
	_appixiaBridge_execute("bridge://DisplayActivityModuleByConf/" + encodeURIComponent(moduleConf) + "/?" + $.param(params));
}