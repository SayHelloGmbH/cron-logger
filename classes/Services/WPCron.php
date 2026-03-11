<?php

namespace CronLogger\Services;

use CronLogger\Components\Component;
use CronLogger\Plugin;
use WP_Post;

class WPCron extends Component
{

	private array $times = [];

	public function onCreate(): void
	{

		if (defined('DOING_CRON') && DOING_CRON) {
			add_action("plugins_loaded", [$this, "start"]);
			add_action("shutdown", [$this, "shutdown"]);

			// publish posts schedule logs
			add_action('publish_future_post', [$this, 'publish_future_post_start'], 1, 1);
			add_action('publish_future_post', [$this, 'publish_future_post_finish'], 100, 1);
		}
	}

	function start(): void
	{
		do_action(Plugin::ACTION_WP_CRON_START);
		$this->plugin->log->start("wp-cron.php");
		$this->addCronActions();
	}

	function shutdown(): void
	{
		$this->plugin->log->update($this->plugin->timer->getDuration(), 'Done wp-cron.php 🎉 ');
		do_action(Plugin::ACTION_WP_CRON_FINISH);
		$this->plugin->log->clean();
	}

	function addCronActions(): void
	{
		$crons      = _get_cron_array();
		$registered = [];

		foreach ($crons as $timestamp => $cronhooks) {
			foreach ($cronhooks as $hook => $keys) {
				add_action($hook, [$this, "before_execute_cron_hook"], 0);
				add_action($hook, [$this, "after_execute_cron_hook"], 999);
				$registered[] = $hook;
			}
		}

		$msg = __("No registered hooks? Something went wrong. There should be at least WordPress core cron hooks.", Plugin::DOMAIN);
		if (count($registered) > 0) {
			$msg = sprintf(__("Registered hooks: %s", Plugin::DOMAIN), implode(', ', $registered));
		}
		$this->plugin->log->addInfo($msg);
	}

	function before_execute_cron_hook(): void
	{
		$this->times[current_filter()] = time();
		$this->plugin->log->addInfo("Starts " . current_filter());
	}

	function after_execute_cron_hook(): void
	{
		$this->plugin->log->addInfo("Finished " . current_filter(), time() - $this->times[current_filter()]);
	}

	/**
	 * start future post schedule
	 *
	 * @param int $post_id
	 */
	public function publish_future_post_start($post_id): void
	{
		$this->plugin->log->addInfo("Check post -> $post_id");
		add_action('transition_post_status', [$this, 'transition_post_status'], 10, 3);
	}

	/**
	 * after future post schedule finished
	 *
	 * @param $post
	 */
	public function publish_future_post_finish($post): void
	{
		remove_action('transition_post_status', [$this, 'transition_post_status'], 10);
	}

	/**
	 * log which posts were published
	 *
	 * @param string $new_status
	 * @param string $old_status
	 * @param WP_Post $post
	 */
	function transition_post_status($new_status, $old_status, $post): void
	{
		$this->plugin->log->addInfo(
			"Status changed from <b>$old_status</b> -> <b>$new_status</b> of '{$post->post_title}'"
		);
	}
}
