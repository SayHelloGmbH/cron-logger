<?php

namespace CronLogger;

use CronLogger\Services\SolrPlugin;
use CronLogger\Services\WPCron;

class Services
{

	public function __construct(Plugin $plugin)
	{

		new WPCron($plugin);
		new SolrPlugin($plugin);

		add_action('plugins_loaded', [ $this, 'plugins_loaded' ]);
	}

	function plugins_loaded(): void
	{
		do_action(Plugin::ACTION_INIT, Plugin::instance());
	}
}
