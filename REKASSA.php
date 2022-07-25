<?
date_default_timezone_set('Asia/Almaty');

include_once($_SERVER["DOCUMENT_ROOT"].'/rekassa/abylai/RekassaAPI.php'); # апи реккассы
include_once($_SERVER["DOCUMENT_ROOT"].'/rekassa/abylai/Integration.php'); # интеграция рекассы с нашем сайтом

class REKASSA
{
	public $Integration;

	function __construct($id_user = 0)
	{
		if($id_user != 0)
			$this->Integration = new Integration($id_user);
	}

	public function checkAUTH($login, $password){
		$API = new RekassaAPI();
		$res = $API->authREKASSA($login, $password);
		if(isset($res['error']) && $res['access_token'] == ''){
			$res['error'] = ($res['error'] == 'No access' ? 'Неправильный пароль или логин' : '');
			return $res['error'];
		}else{
			return 'success';
		}
	}

	public function authInfoToSQL($login, $password){
		global $mysqli, $api;
		
		$check = $mysqli->query("SELECT * FROM i_rekassa_info WHERE id_user = '".$api->Users->user_id."' LIMIT 1")->num_rows;

		if($check != 0){
			echo 'Касса в базе уже есть';
		}else{

			if($mysqli->query("INSERT INTO `i_rekassa_info`(`id_user`,`number`, `password`, `lastShiftNumber`, `pin_code`) VALUES(".$api->Users->user_id.", '".$login."', '".$password."', '0', '0000')")){
				echo 'касса успешно добавлена';
			}else{
				echo 'Ошибка на стороне сервера';
			}

		}

	}

	function __destruct()
	{
		unset($this->Integration);
	}

}

?>
