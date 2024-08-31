<?php

namespace EPH\Cache\APIs;

use EPH\Cache\CacheApi;
use EPH\Cache\CacheApiInterface;

/**
 * PostgreSQL Cache API class
 *
 * @package CacheAPI
 */
class Postgres extends CacheApi implements CacheApiInterface {

	/** @var string */
	private $db_prefix;

	/** @var resource result of pg_connect. */
	private $db_connection;

	public function __construct() {

		global $db_prefix, $db_connection;

		$this->db_prefix = $db_prefix;
		$this->db_connection = $db_connection;

		parent::__construct();
	}

	/**
	 * {@inheritDoc}
	 */
	public function connect() {

		$result = pg_query_params($this->db_connection, 'SELECT 1
			FROM   pg_tables
			WHERE  schemaname = $1
			AND    tablename = $2',
			[
				'public',
				$this->db_prefix . 'cache',
			]
		);

		if (pg_affected_rows($result) === 0) {
			pg_query($this->db_connection, 'CREATE UNLOGGED TABLE ' . $this->db_prefix . 'cache (key text, value text, ttl bigint, PRIMARY KEY (key))');
		}

		$this->prepareQueries(
			[
				'eph_cache_get_data',
				'eph_cache_put_data',
				'eph_cache_delete_data',
			],
			[
				'SELECT value FROM ' . $this->db_prefix . 'cache WHERE key = $1 AND ttl >= $2 LIMIT 1',
				'INSERT INTO ' . $this->db_prefix . 'cache(key,value,ttl) VALUES($1,$2,$3)
				ON CONFLICT(key) DO UPDATE SET value = $2, ttl = $3',
				'DELETE FROM ' . $this->db_prefix . 'cache WHERE key = $1',
			]
		);

		return true;
	}

	/**
	 * Stores a prepared SQL statement, ensuring that it's not done twice.
	 *
	 * @param array $stmtnames
	 * @param array $queries
	 */
	private function prepareQueries(array $stmtnames, array $queries) {

		$result = pg_query_params(
			$this->db_connection,
			'SELECT name FROM pg_prepared_statements WHERE name = ANY ($1)',
			['{' . implode(', ', $stmtnames) . '}']
		);

		$arr = pg_num_rows($result) == 0 ? [] : array_map(
			function ($el) {

				return $el['name'];
			},
			pg_fetch_all($result)
		);

		foreach ($stmtnames as $idx => $stmtname) {
			if (!in_array($stmtname, $arr)) {
				pg_prepare($this->db_connection, $stmtname, $queries[$idx]);
			}
		}

	}

	/**
	 * {@inheritDoc}
	 */
	public function isSupported($test = false) {

		global $smcFunc;

		if ($smcFunc['db_title'] !== POSTGRE_TITLE) {
			return false;
		}

		$result = pg_query($this->db_connection, 'SHOW server_version_num');
		$res = pg_fetch_assoc($result);

		if ($res['server_version_num'] < 90500) {
			return false;
		}

		return $test ? true : parent::isSupported();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getData($key, $ttl = null) {

		$result = pg_execute($this->db_connection, 'eph_cache_get_data', [$key, time()]);

		if (pg_affected_rows($result) === 0) {
			return null;
		}

		$res = pg_fetch_assoc($result);

		return $res['value'];
	}

	/**
	 * {@inheritDoc}
	 */
	public function putData($key, $value, $ttl = null) {

		$ttl = time() + (int) ($ttl !== null ? $ttl : $this->ttl);

		if ($value === null) {
			$result = pg_execute($this->db_connection, 'eph_cache_delete_data', [$key]);
		} else {
			$result = pg_execute($this->db_connection, 'eph_cache_put_data', [$key, $value, $ttl]);
		}

		return pg_affected_rows($result) > 0;
	}

	/**
	 * {@inheritDoc}
	 */
	public function cleanCache($type = '') {

		if ($type == 'expired') {
			pg_query($this->db_connection, 'DELETE FROM ' . $this->db_prefix . 'cache WHERE ttl < ' . time() . ';');
		} else {
			pg_query($this->db_connection, 'TRUNCATE ' . $this->db_prefix . 'cache');
		}

		$this->invalidateCache();

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getVersion() {

		return pg_version($this->db_connection)['server'];
	}

	/**
	 * {@inheritDoc}
	 */
	public function housekeeping() {

		$this->createTempTable();
		$this->cleanCache();
		$this->retrieveData();
		$this->deleteTempTable();
	}

	/**
	 * Create the temp table of valid data.
	 *
	 * @return void
	 */
	private function createTempTable() {

		pg_query($this->db_connection, 'CREATE LOCAL TEMP TABLE IF NOT EXISTS ' . $this->db_prefix . 'cache_tmp AS SELECT * FROM ' . $this->db_prefix . 'cache WHERE ttl >= ' . time());
	}

	/**
	 * Delete the temp table.
	 *
	 * @return void
	 */
	private function deleteTempTable() {

		pg_query($this->db_connection, 'DROP TABLE IF EXISTS ' . $this->db_prefix . 'cache_tmp');
	}

	/**
	 * Retrieve the valid data from temp table.
	 *
	 * @return void
	 */
	private function retrieveData() {

		pg_query($this->db_connection, 'INSERT INTO ' . $this->db_prefix . 'cache SELECT * FROM ' . $this->db_prefix . 'cache_tmp ON CONFLICT DO NOTHING');
	}

}

?>