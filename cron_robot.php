<?
echo ('<p>[' . date('d.m.Y H:i:s') .'] start</p>' . "\n");
date_default_timezone_set('Asia/Almaty');
if (
	//(date("G") >= 3 && date("G") <= 6)			// С 03:00 до 06:00
	true
)
{
	$lang="ru";
	$title='reKassa инсерт в базу';
	
	header('Content-Type: text/html; charset=utf-8');
	include_once($_SERVER["DOCUMENT_ROOT"].'/general/mysql.php');
    include_once($_SERVER["DOCUMENT_ROOT"].'/general/api.php');
    // CLASS REKASSA =====================
    include_once($_SERVER["DOCUMENT_ROOT"].'/rekassa/abylai/REKASSA.php'); # класс мост между апи и интеграцией
	
	ini_set("max_execution_time", "900");
	set_time_limit(900);
	ini_set("memory_limit", "512M");
	
	$id_cron = 15;
	$sCron = $mysqli->query("SELECT * FROM `i_cron` WHERE `id`='".$id_cron."' AND `active`=1 LIMIT 1");
	$stop = $sCron->num_rows;
	$rCron = $sCron->fetch_array();

	if ($stop == 0 && strtotime(date("Y-m-d")) != strtotime($rSub["check_date"]))
	{
		$sql_updateD = "UPDATE `i_cron` SET `active`=1, `check_end`=0 WHERE `id`='".$id_cron."'";
		$updateD = $mysqli->query($sql_updateD);	
		$stop = 1;
	}
	
	if ($stop == 1)
	{
		$limitH = 3;
		$s=$mysqli->query("SELECT * FROM `i_rekassa_info` WHERE `check_date` is null ORDER BY `id` ASC LIMIT ".$limitH);		
		if ($s->num_rows>0)
		{		
            foreach($s as $r){
                $id_user = $r['id_user'];

                $rekassa = new REKASSA($id_user);
                
                $rekassa->Integration
                ->setShiftPage(0) # номер страницы смены
                ->setShiftSize(20) # кол-во смен на одном странице
                ->setAutoShiftToNextPage(true); # автомаический переключатся на вторую страницу смен 
                                                # (допустим выводились все записи на странице 0 и автоматический будут ввыодит следущую страницу)
                
                $result = $rekassa->Integration->shiftsToSQL();
                
                $rekassa->Integration->debug($result);
                $upd = $mysqli->query('UPDATE `i_rekassa_info` SET `check_date` = \'1\' WHERE `id_user`=\''.$id_user.'\'');
            }
		}
		else
		{
			// СТОП
			$sql_updateD = "UPDATE `i_cron` SET `active`=0, `check_end`=1, `check_date`='".date("Y-m-d")."' WHERE `id`='".$id_cron."'";
			$updateD = $mysqli->query($sql_updateD);
            $upd = $mysqli->query('UPDATE `i_rekassa_info` SET `check_date` = NULL WHERE `check_date` = 1');
			echo 'End today!';
		}
	}
}

echo ('<p>[' . date('d.m.Y H:i:s') .'] end</p>' . "\n");

?>
