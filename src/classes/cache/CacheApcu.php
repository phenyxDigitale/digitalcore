<?php

/**
 * Our Cache API class
 *
 * @package CacheAPI
 */
class CacheApcu extends CacheApi implements CacheApiInterface {

	/**
	 * {@inheritDoc}
	 */
	public function isSupported($test = false) {

		$supported = function_exists('apcu_fetch') && function_exists('apcu_store');

		if ($test) {
			return $supported;
		}

		return parent::isSupported() && $supported;
	}

	/**
	 * {@inheritDoc}
	 */
	public function connect() {

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getData($key, $ttl = null) {

		$key = $this->prefix . strtr($key, ':/', '-_');

		$value = apcu_fetch($key . 'eph');

		return !empty($value) ? $value : null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function putData($key, $value, $ttl = null) {

		$key = $this->prefix . strtr($key, ':/', '-_');

		// An extended key is needed to counteract a bug in APC.

		if ($value === null) {
			return apcu_delete($key . 'eph');
		} else {
			return apcu_store($key . 'eph', $value, $ttl !== null ? $ttl : $this->ttl);
		}

	}

	/**
	 * {@inheritDoc}
	 */
	public function cleanCache($type = '') {

		$this->invalidateCache();

		return apcu_clear_cache();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getVersion() {

		return phpversion('apcu');
	}

}

?>