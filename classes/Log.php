<?php

namespace CronLogger;

use CronLogger\Components\Database;

class Log extends Database
{

	private $log_id = null;
	public $errors = [];
	public string $table;

	public function init()
	{
		$this->table = $this->wpdb->prefix . Plugin::TABLE_LOGS;
	}

	function start($info = ""): void
	{
		if ($this->log_id != null) {
			error_log("Only start logger once per session.", 4);

			return;
		}
		$this->wpdb->insert(
			$this->table,
			[
				'executed' => Plugin::instance()->timer->getStart(),
				'duration' => 0,
				'info'     => "Running ⏳ $info",
			],
			[
				'%d',
				'%d',
				'%s',
			]
		);
		$this->log_id = $this->wpdb->insert_id;
	}

	function update($duration, $info = null): int
	{

		if ($this->log_id == null) {
			$this->start();
		}
		$data        = ['duration' => $duration];
		$data_format = ['%d'];
		if ($info != null) {
			$data['info']  = $info;
			$data_format[] = '%s';
		}

		return $this->wpdb->update(
			$this->table,
			$data,
			[
				'id' => $this->log_id,
			],
			$data_format,
			[
				'%d',
			]
		);
	}

	function addInfo($message, $duration = null): void
	{
		// sanitize message to allow safe HTML but strip dangerous tags
		if (function_exists('wp_kses_post')) {
			$message = wp_kses_post($message);
		} else {
			$message = sanitize_text_field($message);
		}

		$result = $this->wpdb->insert(
			$this->table,
			[
				'parent_id' => $this->log_id,
				'info'      => $message,
				'executed'  => time(),
				'duration'  => $duration,
			],
			[
				'%d',
				'%s',
				'%d',
				'%d',
			]
		);
		if ($result == false) {
			$error_message  = "DB error: " . $this->wpdb->last_error;
			$this->errors[] = $error_message;
			error_log("Cron Logger: " . $error_message);
		} else {
			$this->update(
				Plugin::instance()->timer->getDuration()
			);
		}
	}

	function getList($args = []): array
	{
		$args = (object) array_merge(
			[
				"count"       => 15,
				"page"        => 1,
				"min_seconds" => null,
			],
			$args
		);
		$count  = $args->count;
		$page   = $args->page;
		$offset = $count * ($page - 1);

		$count = intval($count);
		$offset = intval($offset);
		$where_min_seconds = '';
		if ($args->min_seconds !== null) {
			$min = intval($args->min_seconds);
			$where_min_seconds = $this->wpdb->prepare(' AND duration >= %d', $min);
		}

		$sql = "SELECT * FROM {$this->table} WHERE parent_id IS NULL" . $where_min_seconds . " ORDER BY executed DESC LIMIT %d, %d";
		return $this->wpdb->get_results($this->wpdb->prepare($sql, $offset, $count));
	}

	function getSublist($log_id, $count = 50, $page = 0): array
	{
		$offset = $count * $page;

		$log_id = intval($log_id);
		$count  = intval($count);
		$offset = intval($offset);

		$sql = "SELECT * FROM {$this->table} WHERE parent_id = %d ORDER BY id DESC LIMIT %d, %d";
		return $this->wpdb->get_results($this->wpdb->prepare($sql, $log_id, $offset, $count));
	}

	function clean(): void
	{
		$table     = $this->table;
		$days = intval(apply_filters(Plugin::FILTER_EXPIRE, 14));
		$cutoff = time() - ($days * 86400);

		$parent_ids = $this->wpdb->get_col($this->wpdb->prepare("SELECT id FROM {$this->table} WHERE parent_id IS NULL AND executed < %d", $cutoff));
		if (! empty($parent_ids)) {
			$in = implode(',', array_map('intval', $parent_ids));
			$this->wpdb->query("DELETE FROM {$table} WHERE parent_id IN ($in)");
			$this->wpdb->query("DELETE FROM {$table} WHERE id IN ($in)");
		}
	}

	function createTables()
	{
		parent::createTables();
		dbDelta("CREATE TABLE IF NOT EXISTS " . $this->table . "
		(
		 id bigint(20) unsigned not null auto_increment,
		 parent_id bigint(20) unsigned default null,
		 executed bigint(20) unsigned default null ,
		 duration int(11) unsigned default null,
		 info text,
		 primary key (id),
		 key ( executed ),
		 key (duration),
		 key (parent_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
	}
}
