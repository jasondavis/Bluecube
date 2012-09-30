<?php
/**
 *
 */
class TDbSessionController extends TObject implements ISessionController
{
	protected $db;
	protected $lifetime;
	protected $table;

	public function open($save_path, $session_name)
	{
		$config = Engine::GetConfig('/authorization/controller[@class="TDbSessionController"]/option', Engine::SITECONFIG);
		$session_config = Engine::GetConfig('/authorization/session', Engine::SITECONFIG);

		foreach($session_config[0] as $key => $opt)
		{
			if($key == 'lifetime')
			{
				$this->lifetime = $opt;
				break;
			}
		}

		$options = array();

		foreach($config as $opt)
		{
			$options[$opt['name']] = $opt['value'];
		}

		$this->table = $options['table'];

		$this->db = new TMySQL(new TDatabaseConnection($options['connection']));

		$this->db->Query('
			create table if not exists `'.$this->table.'`
			(
				`session_id` char(255) character set utf8 collate utf8_bin NOT NULL,
  				`session_access` datetime not null,
  				`session_data` blob,
  				UNIQUE `session_id` (`session_id`),
  				INDEX `session_access` (`session_access`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
		');

		return true;
	}

	public function close()
	{
		return true;
	}

	public function read($session_id)
	{
		$set = $this->db->Query('
			select
				`session_data`
			from
				`'.$this->table.'`
			where
				`session_id` = ?
					and
				`session_access` < NOW() + INTERVAL ? SECOND
		', array($session_id, (int) $this->lifetime));

		if(count($set) == 0)
		{
			return '';
		}

		$this->db->Query('
			update
				`'.$this->table.'`
			set
				`session_access` = NOW()
			where
				`session_id` = ?
		', array($session_id));

		return $set[0]['session_data'];
	}

	public function write($session_id, $value)
	{
		$this->db->Query('
			insert into	`'.$this->table.'`
			(
				`session_id`,
				`session_access`,
				`session_data`
			)
			values
			(
				:name,
				NOW(),
				:data
			)
			on duplicate key update
				`session_access` = NOW(),
				`session_data` = :data
		', array(
				'name' => $session_id,
				'data' => $value
			)
		);

		return true;
	}

	public function destroy($session_id)
	{
		$this->db->Query('
			delete from `'.$this->table.'` where `session_id` = ?
		', array($session_id));

		return true;
	}

	public function clean()
	{
		$this->db->Query('
			delete from `'.$this->table.'` where `session_access` < NOW() - INTERVAL ? SECOND
		', array((int) $this->lifetime));

		return true;
	}
}