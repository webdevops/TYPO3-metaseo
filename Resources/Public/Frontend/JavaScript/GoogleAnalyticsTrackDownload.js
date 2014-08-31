function tqSeoGa_AddTracker(type, obj) {
	var callback = function(e) {
		tqSeoGa_TrackLink(e, obj);
	}

	if (obj.addEventListener) {
		obj.addEventListener("click", callback, true);
	} else if (obj.attachEvent) {
		obj.attachEvent("onclick", callback);
	}
}

function tqSeoGa_TrackLink(e, target) {
	try {
		if( target && target.hostname ) {
			var linkLocation = String(target);
			var currentHost	 = location.host;

			if( target.hostname == currentHost ) {
				// only match http/https-links and remove protocol-host part
				var regexp		= new RegExp("http(s)?://"+currentHost+"");
				var hostMatch	= regexp.exec(linkLocation);
				if( hostMatch && hostMatch[0] ) {
					linkLocation = linkLocation.slice( hostMatch[0].length );
					// track pageview
					_gaq.push(['_trackPageview', linkLocation]);
				}
			}
		}
	} catch(e) {}
}

(function() {
	var currentHost		= location.host;
	var linkList		= document.getElementsByTagName("a");
	var linkListLength	= linkList.length;

	for (var i=0; i < linkListLength; i++) {
		var link			= linkList[i];
		var linkLocation	= String(link);

		if(currentHost == link.hostname) {
			if(linkLocation.match(/\.(doc|docx|xls|xlsx|ppt|pptx|odt|ods|pdf|zip|tar|gz|txt|vsd|vxd|rar|exe|wma|mov|avi|ogg|ogm|mkv|wmv|mp3|webm)$/)) {
				tqSeoGa_AddTracker("download", link);
			}
		}
	}
})();