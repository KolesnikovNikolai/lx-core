let alerts = null;

function initAlerts() {
	alerts = lx.Box.rise(lx.getAlertsElement());
	alerts.key = 'alerts';
}

lx.Alert = function(msg) {
	if (!alerts) initAlerts();
	lx.dependencies.promiseModules(['lx.ActiveBox'], ()=>__print(msg));
};

function __print(msg) {
	var el = new lx.ActiveBox({
		parent: alerts,
		geom: [10, 5, 80, 80],
		depthCluster: lx.DepthClusterMap.CLUSTER_URGENT,
		key: 'lx_alert',
		header: 'Alert',
		closeButton: {click: function(){this.parent.parent.del();}}
	});
	el.overflow('auto');
	el->body.html('<pre>' + msg + '</pre>');
}
