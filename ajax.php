<?
// error_reporting(E_ALL);
// ini_set('display_errors', TRUE);
// ini_set('display_startup_errors', TRUE);

if (
	isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') &&
	isset($_POST['do']) &&
	isset($_POST['x']) && ($_POST['x']=='secure')
	)
{
	header('Content-Type: text/html; charset=utf-8');
	$lang = 'ru';
	include_once($_SERVER["DOCUMENT_ROOT"].'/modules/mysql.php');
	include_once($_SERVER["DOCUMENT_ROOT"].'/modules/api.php');
  include_once($_SERVER["DOCUMENT_ROOT"].'/rekassa/abylai/REKASSA.php'); # класс мост между апи и интеграцией

    if($_POST['do'] != 're_auth'){
        $rekassa = new REKASSA($api->Users->user_id);
        $rekassa->Integration
        ->setShiftPage(0) # номер страницы смены
        ->setShiftSize(20) # кол-во смен на одном странице
        ->setTicketPage(0) # номер страницы тикета
        ->setTicketSize(20); # кол-во тикетов на одном странице 
    }

    if(
        $_POST['do'] == 're_auth' &&
        isset($_POST['password']) &&
        isset($_POST['login'])
    ){
        $rekassa = new REKASSA();
        $login = $_POST['login'];
        $password = $_POST['password'];

        $result = $rekassa->checkAUTH($login, $password);

        if($result == 'success'){
            $result = $rekassa->authInfoToSQL($login, $password);
        }
        
        echo $result;
    }else if(
        $_POST['do'] == 're_new_ticket' &&
        isset($_POST['sum']) &&
        (
            $_POST['operation_type'] == 'OPERATION_SELL' || 
            $_POST['operation_type'] == 'OPERATION_SELL_RETURN'
        )
    ){
        $sum = intval($_POST['sum']);
        $operation_type = $_POST['operation_type'];
        if($sum == 0 || $sum == '' || $sum < 0){
            echo json_encode([
                'message' => 'Неправильная сумма'
            ]);
            exit();
        }

        $newTicketInfo = $rekassa->Integration->setTicketSum($sum)->setTicketType($operation_type)->createTicket(); 

        if(isset($newTicketInfo['message'])){
            echo json_encode([
                'message' => $rekassa->Integration->errorsToRu($newTicketInfo['code'])
            ]);
        }else{
            $result = $rekassa->Integration->setNewTicketInfo($newTicketInfo)->oneTicketToSQL();
            if($result != 'error'){
                echo json_encode($result);
            }else{
                echo $result;
            }
        }

        
    }else if(
        $_POST['do'] == 're_close_shift' &&
        isset($_POST['pin_code'])
    ){
        $pin = $_POST['pin_code'];
        $result = $rekassa->Integration->setUserPin($pin)->setShiftId($rekassa->Integration->shiftCount)->closeShift();
        if(!isset($result['result']['code'])){
            $rekassa->Integration->setAutoTicketToNextPage(true)->lastShiftTicketsToSQL();
            echo 'Успешно закрылся смен';
        }else{
            echo $rekassa->Integration->errorsToRu($result['result']['code']);
        }
    }else if(
        $_POST['do'] == 're_get_all_shifts'
    ){
        $result = $rekassa->Integration->setAutoTicketToNextPage(true)->setAutoShiftToNextPage(true)->ticketsToSQL();
        echo $result;
    }else if(
        $_POST['do'] == 'checkOpenShift'
    ){
        $result = $rekassa->Integration->checkShiftsOpen();
        echo $result;
    }
    else
    {
        echo '
        <script type="text/javascript">
            setTimeout(function() { self.location = "/"; }, 50);
        </script>
        ';
    }

	exit;
}
?>
