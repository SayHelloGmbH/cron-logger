<?php

namespace CronLogger\Services;

use CronLogger\Components\Component;

class SolrPlugin extends Component
{

	public function onCreate(): void
	{
		add_action("solr_cron_start", [$this, "onStart"]);
		add_action("solr_cron_finish", [$this, "onFinish"]);
	}

	function onStart(): void
	{
		$this->plugin->log->start('Solr cron.php');
		$this->plugin->log->addInfo("Solr cron.php starts");
	}

	function onFinish(): void
	{
		$this->plugin->log->update($this->plugin->timer->getDuration(), "Solr finished 🔎 🎉");
	}
}
