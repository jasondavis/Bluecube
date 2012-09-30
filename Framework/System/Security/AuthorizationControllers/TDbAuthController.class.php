<?php
/**
 *
 */
class TDbAuthController extends TObject implements IAuthController
{
	public function getUserData($username, $password)
	{
		$config = Engine::GetConfig('authorization/controller[@class="TDbAuthController"]/option', Engine::SITECONFIG);

		$options = array();

		foreach($config as $option)
		{
			$options[$option['name']] = $option['value'];
		}

		$db = new TMySQL(new TDatabaseConnection($options['connection']));

		if(!isset($options['table'])) throw new ConfigurationException('Option `table` not found');

		$set = $db->Query("
			select * from `{$options['table']}` where `username` = ? and `password` = ? and `active` = '1'
		",
			array($username, $password)
		);

		if(count($set) == 1)
		{
			$roles = TVar::toArray($set[0]['roles']);

			array_walk($roles,'trim');

			return array(
				'uid' => $set[0]['id_users'],
				'username' => $set[0]['username'],
				'roles' => $roles,
				'name' => $set[0]['name'],
				'surname' => $set[0]['surname']
			);
		}
		else
		{
			return false;
		}
	}
}