<?php

/*
	[Discuz!] (C)2001-2099 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: db.class.php 1059 2011-03-01 07:25:09Z monkey $
*/

if(!defined('IN_COMSENZ')) {
	exit('Access Denied');
}

class dbstuff {
	var $querynum = 0;
	var $link;
	var $histories;
	var $time;
	var $tablepre;

	function connect($dbhost, $dbuser, $dbpw, $dbname = '', $dbcharset, $pconnect = 0, $tablepre='', $time = 0) {
		$this->time = $time;
		$this->tablepre = $tablepre;

		if(!$this->link = mysqli_connect($dbhost, $dbuser, $dbpw)) {
			$this->halt('Can not connect to MySQL server');
		}

		if($this->version() > '4.1') {
			if($dbcharset) {
				mysqli_query($this->link, "SET character_set_connection=".$dbcharset.", character_set_results=".$dbcharset.", character_set_client=binary");
			}

			if($this->version() > '5.0.1') {
				mysqli_query($this->link, "SET sql_mode=''");
			}
		}

		if($dbname) {
			mysqli_select_db($this->link, $dbname);
		}

	}

	function escape_string($query) {
		return mysqli_escape_string($this->link, $query);
	}
	
	function fetch_array($query, $result_type = MYSQLI_ASSOC) {
		return mysqli_fetch_array($query, $result_type);
	}

	function result_first($sql, &$data) {
		$query = $this->query($sql);
		$data = $this->result($query, 0);
	}

	function fetch_first($sql, &$arr) {
		$query = $this->query($sql);
		$arr = $this->fetch_array($query);
	}

	function fetch_all($sql, &$arr) {
		$query = $this->query($sql);
		while($data = $this->fetch_array($query)) {
			$arr[] = $data;
		}
	}

	function cache_gc() {
		$this->query("DELETE FROM {$this->tablepre}sqlcaches WHERE expiry<$this->time");
	}

	function query($sql, $type = '', $cachetime = FALSE) {
		$resultmode = $type == 'UNBUFFERED' ? MYSQLI_USE_RESULT : MYSQLI_STORE_RESULT;
		if(!($query =  mysqli_query($this->link, $sql, $resultmode)) && $type != 'SILENT') {
			$this->halt('MySQL Query Error', $sql);
		}
		$this->querynum++;
		$this->histories[] = $sql;
		return $query;
	}

	function affected_rows() {
		return mysqli_affected_rows($this->link);
	}

	function error() {
		return (empty($this->link) ?'mysqli_link_empty': mysqli_error($this->link) );
	}

	function errno() {
		return empty($this->link) ?0: intval(mysqli_errno ($this->link) );
	}

	function result($query, $row) {
		mysqli_data_seek($query, $row);
		$f = mysqli_fetch_array( $query );
		return $f[0];
	}

	function num_rows($query) {
		$query = mysqli_num_rows($query);
		return $query;
	}

	function num_fields($query) {
		return mysqli_num_fields($query);
	}

	function free_result($query) {
		return mysqli_free_result($query);
	}

	function insert_id() {
		return ($id = mysqli_insert_id($this->link)) >= 0 ? $id : $this->result($this->query("SELECT last_insert_id()"), 0);
	}

	function fetch_row($query) {
		$query = mysqli_fetch_row($query);
		return $query;
	}

	function fetch_fields($query) {
		return mysqli_fetch_field($query);
	}

	function version() {
		return mysqli_get_server_info($this->link);
	}

	function close() {
		return mysqli_close($this->link);
	}

	function halt($message = '', $sql = '') {
		show_error('run_sql_error', $message.'<br /><br />'.$sql.'<br /> '.$this->error(), 0);
	}
}

?>