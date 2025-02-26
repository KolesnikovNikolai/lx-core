#lx:module lx.MessageBox;

#lx:use lx.Box;

class MessageBox extends lx.Box #lx:namespace lx {
	build(config) {
		super.build(config);

		this.msgConfig = config.messageConfig || {};

		var streamConfig = config.stream || {};
		if (streamConfig.height === undefined)
			streamConfig.height = 'auto';

		this.stream(streamConfig);
	}

	decorate(config) {
		this.msgConfig = config;
	}

	add(...args) {
		var config = this.msgConfig.lxClone();
		config.text = args.join(' ');
		super.add(lx.Box, config);
	}
}
