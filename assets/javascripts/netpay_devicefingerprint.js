function doProfile(org_id) {
	var length = 30;
	var chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!';
	var sessionID = '';
	for (var i = length; i > 0; --i) sessionID += chars[Math.floor(Math.random() * chars.length)];

	//JavaScript Code
	var tmscript = document.createElement("script");

	tmscript.src = "https://h.online-metrix.net/fp/tags.js?org_id=" + org_id + "&session_id=" + sessionID;

	tmscript.type = "text/javascript";

	document.getElementsByTagName("head")[0].appendChild(tmscript);

	//Iframe Code
	var iframeTM = document.createElement("iframe");

	iframeTM.setAttribute('id', 'iframeTM');
	iframeTM.style.width = "100px";
	iframeTM.style.height = "100px";
	iframeTM.style.border = "0";
	iframeTM.style.position = "absolute";
	iframeTM.style.top = "-5000px";

	iframeTM.src = "https://h.online-metrix.net/fp/tags?org_id=" + org_id + "&session_id=" + sessionID;

	document.body.appendChild(iframeTM);

	return sessionID;

}
