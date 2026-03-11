<?php

/**
 * Created by PhpStorm.
 * User: edward
 * Date: 29.11.17
 * Time: 15:43
 */

namespace CronLogger;

use CronLogger\Components\Component;

class Page extends Component
{

	const ARG_ITEMS = "cron-logs-items";

	const ARG_PAGE = "cron-logs-page";

	const ARG_DURATION_MIN = "cron-logs-dm";

	public function onCreate()
	{
		add_action('admin_menu', [$this, 'menu_pages']);
	}

	public function menu_pages()
	{
		add_submenu_page(
			'tools.php',
			__('Cron Logs', Plugin::DOMAIN),
			__('Cron Logs', Plugin::DOMAIN),
			'manage_options',
			'cron-logs',
			[
				$this,
				"render",
			]
		);
	}

	function getArgs()
	{
		$args        = (object) [];
		$args->items = 10;
		if (! empty($_GET[self::ARG_ITEMS]) && intval($_GET[self::ARG_ITEMS]) > 0) {
			$args->items = intval($_GET[self::ARG_ITEMS]);
		}
		$args->page = 1;
		if (! empty($_GET[self::ARG_PAGE]) && intval($_GET[self::ARG_PAGE]) > 0) {
			$args->page = intval($_GET[self::ARG_PAGE]);
		}
		$args->duration_min = null;
		if (! empty($_GET[self::ARG_DURATION_MIN])) {
			$args->duration_min = intval($_GET[self::ARG_DURATION_MIN]);
		}

		return $args;
	}

	function render()
	{
		?>
		<div class="wrap">
			<h2>Cron Logs</h2>
			<?php
			$timezone = wp_timezone_string();
			try {
				$time = new \DateTime("now", new \DateTimeZone($timezone));
			} catch (\Exception $e) {
				echo "<p>" . __("Missing »timezone_string« entry in options table. Please fix! Otherwise execution times could be wrong.", Plugin::DOMAIN) . "</p>";
				$time = new \DateTime('now');
			}
			$args = $this->getArgs();
			?>

            <form method="GET" action="<?php echo admin_url( 'tools.php' ); ?>">
                <input type="hidden" name="page" value="cron-logs"/>
                <label>
					<?php _e( 'Minimum duration of x seconds', Plugin::DOMAIN ); ?><br>
                    <input type="number"
                           name="<?php echo self::ARG_DURATION_MIN ?>"
                           placeholder="x"
                           value="<?php echo $args->duration_min; ?>"/>
                </label><br>
                <label>
					<?php _e( "Page", Plugin::DOMAIN ); ?><br>
                    <input type="number" min="1"
                           name="<?php echo self::ARG_PAGE ?>" required
                           value="<?php echo $args->page; ?>"/>
                </label><br>
                <label>
					<?php _e( 'Logs per Page', Plugin::DOMAIN ); ?><br>
                    <input type="number" min="1" max="50" maxlength="2"
                           name="<?php echo self::ARG_ITEMS ?>"
                           required
                           value="<?php echo $args->items; ?>"/>
                </label>

				<?php
				submit_button(__("Filter", Plugin::DOMAIN));
				?>
            </form>

			<?php submit_button( __( 'Toggle open/close log details', Plugin::DOMAIN ), 'small', "toggle_logs" ); ?>

            <table class="widefat striped">
                <thead>
                <tr>
                    <th style="width: 145px;" scope="col"
                        title="<?php echo $timezone; ?>">
						<?php _e( 'Executed', Plugin::DOMAIN ); ?>
                    </th>
                    <th style="width: 90px;" scope="col"><?php _e( 'Duration', Plugin::DOMAIN ); ?></th>
                    <th scope="col"><?php _e( 'Info', Plugin::DOMAIN ); ?></th>
                </tr>
                </thead>
                <tbody>
				<?php
				$list = $this->plugin->log->getList( array(
					"count"       => $args->items,
					"page"        => $args->page,
					"min_seconds" => $args->duration_min,
				) );
				foreach ( $list as $log ) {
					?>
                    <tr style="cursor: pointer"
                        data-log-id="<?php echo $log->id; ?>">
                        <td style="border-top: 3px solid #333;"><?php
							$time->setTimestamp( $log->executed );
							echo $time->format( "Y-m-d H:i:s" );
							?></td>
                        <td style="border-top: 3px solid #333;"><?php echo $this->getDurationString( $log->duration ); ?></td>
                        <td style="border-top: 3px solid #333;"><?php echo $log->info; ?></td>
                    </tr>
					<?php
					$sublist = $this->plugin->log->getSublist( $log->id );
					foreach ( $sublist as $sub ) {
						?>
                        <tr data-parent-id="<?php echo $log->id; ?>">
                            <td></td>
                            <td><?php echo $this->getDurationString( $sub->duration ); ?></td>
                            <td><?php echo $sub->info; ?></td>
                        </tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>
		<script>
			jQuery(function($) {
				const $logs = $('[data-log-id]');
				$logs.on('click', function() {
					const id = $(this).attr('data-log-id');
					console.log('clicked', id);
					$('[data-parent-id=' + id + ']').toggle();
				});
				let isVisible = true;
				$('[name=toggle_logs]').on('click', function() {
					if (isVisible) {
						$('[data-parent-id]').hide();
					} else {
						$('[data-log-id]').trigger('click');
					}
					isVisible = !isVisible;
				});
			});
		</script>
		<?php
	}

	private function getDurationString($duration): string
	{
		if ($duration == null) {
			return "";
		}

		return $duration . "s";
	}
}
