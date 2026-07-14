<?php
/**
 * Hook Loader
 *
 * WordPress ke saare actions/filters ko ek jagah collect karke,
 * ek hi baar me register karta hai. Isse hooks ka management
 * clean aur centralized rehta hai.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_Loader
 */
class PAD_Loader {

	/**
	 * Register hone wale saare actions.
	 *
	 * @var array
	 */
	protected $actions = array();

	/**
	 * Register hone wale saare filters.
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Naya action queue me add karta hai.
	 *
	 * @param string $hook          WordPress hook name.
	 * @param object $component     Class instance jiska method call hoga.
	 * @param string $callback      Method name.
	 * @param int    $priority      Hook priority.
	 * @param int    $accepted_args Callback ko milne wale arguments ki count.
	 * @return void
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Naya filter queue me add karta hai.
	 *
	 * @param string $hook          WordPress hook name.
	 * @param object $component     Class instance jiska method call hoga.
	 * @param string $callback      Method name.
	 * @param int    $priority      Hook priority.
	 * @param int    $accepted_args Callback ko milne wale arguments ki count.
	 * @return void
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Ek naya hook entry array me push karta hai (shared logic).
	 *
	 * @param array  $hooks         Existing hooks array.
	 * @param string $hook          Hook name.
	 * @param object $component     Class instance.
	 * @param string $callback      Method name.
	 * @param int    $priority      Priority.
	 * @param int    $accepted_args Accepted arguments count.
	 * @return array
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {

		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	/**
	 * Queue kiye gaye saare actions/filters ko WordPress me
	 * actually register karta hai.
	 *
	 * @return void
	 */
	public function run() {

		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
	}
}
