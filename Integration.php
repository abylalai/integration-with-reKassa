<?
// 25.07.2022 => 38 functions
class Integration
{
    private $API; # api рекассы
    private $cash_id; # номер кассы 
    private $id_user; # id пользователя в нашем сайте!

    public $reg_date; # дата связки реккассой с нашем сайтом!

    public $shift_page = 0; # номер страницы (смены)
    public $shift_size = 20; # кол-во записей на одном странице (смен)
    public $ticket_page = 0; # номер страницы (тикет)
    public $ticket_size = 20; # кол-во страницы на одном страницы (тикет)

    public $shiftCount = 0; # кол-во смен

    public $auto_ticket_to_next_page = false; # автоматический переключаться на след странице (тикет)
    public $auto_shift_to_next_page = false; # автоматичесмкйи переключаться на след странице (смены)
 
    public $user_last_shift; # последний номер смены введенный пользоваетелем
    
    public $new_ticket_info = array(); # массив тикетов

    public $pin_code; # пин код (исп. при закрывание смены)

    public $ticketSum = 0; # сумма тикета (500 тг и тд (без тг))
    
    public $shift_id = 0; # номер смены

    private $ticket_type; # тип тикета (operation_sell и т.д.)

    /**
    *  name: Construct
    *  do: делают авторизацию на рекассу
    *  @param Integer $id_user - айди пользователя
    */
    function __construct($id_user)
    {   
        $this->id_user = $id_user;
        $this->API = new RekassaAPI;
        $this->AUTH(); # авторизация рекасса
    }
    
    /**
    *  name: Check Shift Open
    *  do: проверка есть ли открытая смена
    *  @var boolean $all_last_shift_id = отправляаем запрос в рекассу для проверки (method: shiftOpenCheck())
    *  @return string - true (есть): false (нет)
    */
    public function checkShiftsOpen(){
        $all_last_shift_id = $this->shiftOpenCheck;

        if($all_last_shift_id == true){
            return 'true';
        }else{
            return 'false';
        }

    }

    /**
    *  name: Tickets To SQL
    *  do: собирать все данные и записать в базу (тикеты) -> записывается сумма и дата тикета
    *  @var array $tickets_info = все тикеты из рекассы (method: doShiftTicketListInfo() -> собирает все тикеты, method: clearHasAlreadyTickets() -> удалюят из массива уже записанные в базу тикеты)
    *  @var String $t
    *  @return String - success or error Insert(SQL)
    */
    public function ticketsToSQL(){
        global $mysqli;

        $tickets_info = $this->doShiftTicketListInfo();
        $tickets_info = $this->clearHasAlreadyTickets($tickets_info);

        if(!$tickets_info) return 'нет уникальных записей';

        $sql = $this->doTemplateTicketToSQL($tickets_info);

        if($mysqli->query($sql)) {
            $this->lastShiftNumberToSql();
            return 'success';
        }else{
            return 'Error INSERT';
        }
    }

    /**
    *  name: Last Shift Tickets To SQL
    *  do: записываем в базу тикетов последней смены
    *  @return String - success or error Insert(SQL)
    */
    public function lastShiftTicketsToSQL(){ 
        global $mysqli;

        $shift_info = $this->getRekassaLastShift('all');
        $tickets_info = $this->doShiftTicketListInfo($shift_info);
        $tickets_info = $this->clearHasAlreadyTickets($tickets_info);

        if(!$tickets_info) return 'нет уникальных записей';

        $sql = $this->doTemplateTicketToSQL($tickets_info);

        if($mysqli->query($sql)) {
            return 'success';
        }else{
            return 'Error INSERT';
        }

    }
    // шаблон записа тикета в базу (SQL template)
    private function doTemplateTicketToSQL($tickets_info){
        $cash_order_last_num = $this->getLastCashOrderNumber() + 1;
        $account_cash_last_num = $this->getLastAccountCashNumber() + 1;

        $ticketCount = count($tickets_info);
        $loaded_ticket_id = '';

        $tickets_info = array_reverse($tickets_info);

        $sql = 'INSERT INTO `i_rekassa` (`id_user`, `date`, `summa`, `shift_number`, `ticket_id`, `id_client`, `number`, `operation_type`) VALUES ';
        foreach($tickets_info as $ticket_info){
            if($ticket_info['operation'] == 'OPERATION_SELL' || $ticket_info['operation'] == 'MONEY_PLACEMENT_DEPOSIT'){
                $cash_order_last_num++;
                $doc_num = $cash_order_last_num;
            }else if($ticket_info['operation'] == 'OPERATION_SELL_RETURN' || $ticket_info['operation'] == 'MONEY_PLACEMENT_WITHDRAWAL'){
                $account_cash_last_num++;
                $doc_num = $account_cash_last_num;
            }else{
                $doc_num = 1;
            }

            $comma = (0 != --$ticketCount ? ',' : '');
            $sql .= "\n(".$this->id_user.", '".$ticket_info['date']."', '".$ticket_info['sum']."', '".$ticket_info['shiftNumber']."', '".$ticket_info['ticket_id']."', 0, ".$doc_num.", '".$ticket_info['operation']."')".$comma."";
            $loaded_ticket_id .= $ticket_info['ticket_id'] . $comma;
        }
        return $sql;
    }

    # записываем в базу один тикет
    public function oneTicketToSQL(){
        global $mysqli;

        $ticket_info = $this->new_ticket_info;

        if($ticket_info['operation'] == 'OPERATION_SELL' || $ticket_info['operation'] == 'MONEY_PLACEMENT_DEPOSIT'){
            $doc_num = $this->getLastCashOrderNumber() + 1;
        }else if($ticket_info['operation'] == 'OPERATION_SELL_RETURN' || $ticket_info['operation'] == 'MONEY_PLACEMENT_WITHDRAWAL'){
            $doc_num = $this->getLastAccountCashNumber() + 1;
        }else{
            $doc_num = 1;
        }

        $sql = 'INSERT INTO `i_rekassa` (`id_user`, `date`, `summa`, `shift_number`, `ticket_id`, `id_client`, `number`, `operation_type`) VALUES ('.$this->id_user.', \''.$ticket_info['date'].'\', \''.$ticket_info['sum'].'\', \''.$ticket_info['shiftNumber'].'\', \''.$ticket_info['ticket_id'].'\', 0, '.$doc_num.', \''.$ticket_info['operation'].'\')';
        

        if($mysqli->query($sql)){
            $info['id'] = $mysqli->insert_id;
            $info['date'] = date('d.m.Y', strtotime($ticket_info['date']));
            $info['sum'] = number_format($ticket_info['sum'], 0, '', ' ');
            $info['number'] = $doc_num;
           return $info;
        }else{
            return 'error';
        }
    }
    // сеттер номер тикета
    public function setNewTicketInfo($ticket_info){
        $this->new_ticket_info = $ticket_info;
        return $this;
    }

    # записываем последний введенный номер смены в базу для каждого пользователя
    private function lastShiftNumberToSql(){
        global $mysqli;
        $update = $mysqli->query("UPDATE `i_rekassa_info` SET `lastShiftNumber` = " . $this->rekassaLastShift);
        if($update) return true;
    }

    # получаем логин и пароль пользователя на рекассе
    private function getUserLogin(){

        global $mysqli;
        
        $s = $mysqli->query("SELECT * FROM `i_rekassa_info` WHERE `id_user`='".$this->id_user."' LIMIT 1");
        $r = $s->fetch_array();

        if($s->num_rows == 0) return false;

        $login = array();

        $login['number'] = $r["number"];
        $login['password'] = $r["password"];
        $this->id_user = $r['id_user'];
        $this->reg_date = $r['create_date'];
        $this->user_last_shift = $r['lastShiftNumber'];
        $this->user_pin = $r['pin_code'];

        return $login;
    }

    # авторизация на рекассе
    private function AUTH(){
        $login = $this->getUserLogin();
        $auth = $this->API->authREKASSA($login['number'], $login['password']);
        if(isset($auth['error']))
            return $auth;
        else
            $this->getCashId();
    }

    # получаем список смен
    public function getCashList(){
        $cash_list = $this->API->getCashList()['result']['_embedded']['userCashRegisterRoles'];
    }

    # получаем айди кассы на рекассе
    public function getCashId(){
        $cash_list = $this->API->getCashList()['result']['_embedded']['userCashRegisterRoles'];

        if(!$cash_list) return false;

        $this->cash_id = $cash_list[0]['cashRegister']['id'];
        $this->shiftCount = $cash_list[0]['cashRegister']['shiftNumber'];
        $this->shiftOpenCheck = $cash_list[0]['cashRegister']['shiftOpen'];
        return $this->cash_id;
    }

    # обрабатываем список смен с рекассы
    public function doShiftListInfo(){

        $shift_list = $this->getShift();
        
        if(!$shift_list) return false;

        $info = array();

        $i = 0;

        foreach($shift_list as $shift){
            if($shift['closeTime'] < $this->reg_date) continue;

            $info[$i]['shiftNumber'] = $shift['shiftNumber'];
            $info[$i]['shiftTicketCount'] = $shift['shiftTicketNumber'];
            
            $i++;
        }
        
        return $info;
    }

    # получаем список смен с рекассы 
    public function getShift(){

        if($this->getRekassaLastShift() == $this->user_last_shift) return false;

        if($this->shiftCount > $this->shift_size && $this->auto_shift_to_next_page == true){
            $shift_list = array();
            for ($i=0; $i < $this->shiftCount / $this->shift_size; $i++) { 
                $shift_list = array_merge($shift_list, $this->API->getShiftList($this->cash_id, $this->shift_page + $i, $this->shift_size)['result']['_embedded']['shifts']);
            }
        }else{
            $shift_list = $this->API->getShiftList($this->cash_id, $this->shift_page, $this->shift_size)['result']['_embedded']['shifts'];
        }

        return $shift_list;
    }

    # получаем последний айди смены или указываем
    public function getRekassaLastShift($get = 'id'){
        $s = $this->API->getShiftList($this->cash_id, 0, 1)['result']['_embedded']['shifts'][0];
        if($get == 'id'){
            $response = $s['shiftNumber'];
        }else{
            $response[0]['shiftNumber'] = $s['shiftNumber'];
            $response[0]['shiftTicketCount'] = $s['shiftTicketNumber'];
        }
        $this->rekassaLastShift = $s['shiftNumber'];
        return $response;
    }
    
    # Обрабатываем список тикетов с рекассы
    public function doShiftTicketListInfo($shifts_info = 'default'){
        if($shifts_info == 'default') $shifts_info = $this->doShiftListInfo();

        if(!$shifts_info) return false;

        $i = 0;

        foreach($shifts_info as $shift_info){
            $tickets = $this->getShiftTicket($shift_info);
            foreach($tickets as $ticket){
                if($ticket['command'] == 'COMMAND_TICKET'){
                    $bills = $ticket['data']['ticket']['amounts']['total']['bills'];
                    $coins = $ticket['data']['ticket']['amounts']['total']['coins'];
                    $info[$i]['operation'] = $ticket['data']['ticket']['operation'];
                }else if($ticket['command'] == 'COMMAND_MONEY_PLACEMENT'){
                    $bills = $ticket['data']['moneyPlacement']['sum']['bills'];
                    $coins = $ticket['data']['moneyPlacement']['sum']['coins'];
                    $info[$i]['operation'] = $ticket['data']['moneyPlacement']['operation'];
                }
                $info[$i]['sum'] = $bills . '.' .  (strlen($coins) == 1 ? '0' : '') . $coins;
                $info[$i]['shiftNumber'] = $ticket['shiftNumber'];
                $info[$i]['ticket_id'] = $ticket['id'];
                $info[$i]['date'] = $ticket['messageTime'];
                $i++;
            }
        }
        
        return $info;
    }   

    # получаем список тикетов с рекассы 
    public function getShiftTicket($shift_info){
        if($shift_info['shiftTicketCount'] > $this->ticket_size && $this->auto_ticket_to_next_page == true){
            $tickets = array();
            for ($i=0; $i < $shift_info['shiftTicketCount'] / $this->ticket_size; $i++) { 
                $tickets = array_merge($tickets, $this->API->getShiftTicketList($this->cash_id, $shift_info['shiftNumber'], $this->ticket_page + $i, $this->ticket_size)['result']['_embedded']['tickets']);
            }
        }else{
            $tickets = $this->API->getShiftTicketList($this->cash_id, $shift_info['shiftNumber'], $this->ticket_page, $this->ticket_size)['result']['_embedded']['tickets'];
        }
        
        return $tickets;
    }

    # получать один тикет

    public function getOneTicket(){
        $ticket = $this->API->getTicket($this->cash_id, $this->ticket_id)['result'];
        
        if($ticket['command'] == 'COMMAND_TICKET'){
            $bills = $ticket['data']['ticket']['amounts']['total']['bills'];
            $coins = $ticket['data']['ticket']['amounts']['total']['coins'];
            $info['operation'] = $ticket['data']['ticket']['operation'];
        }else if($ticket['command'] == 'COMMAND_MONEY_PLACEMENT'){
            $bills = $ticket['data']['moneyPlacement']['sum']['bills'];
            $coins = $ticket['data']['moneyPlacement']['sum']['coins'];
            $info['operation'] = $ticket['data']['moneyPlacement']['operation'];
        }

        $info['ticket_id'] = $ticket['id'];
        $info['shiftNumber'] = $ticket['shiftNumber'];
        $info['date'] = $ticket['messageTime'];
        $info['sum'] = $bills . '.' .  (strlen($coins) == 1 ? '0' : '') . $coins;

        return $info;
    }
    
    # закрываем смену 

    public function closeShift(){
        $close_shift = $this->API->closeShift($this->cash_id, $this->shift_id, $this->user_pin);
        
        return $close_shift;
    }

    # удаляем уже записанные тикеты в нашу базу (с массива)
    private function clearHasAlreadyTickets($tickets_info){
        global $mysqli;
        
        $ticketCount = count($tickets_info);
        $where_in = '';
        $info = array();

        if(!$tickets_info) return false;

        foreach($tickets_info as $ticket_info){
            $comma = (0 != --$ticketCount ? ',' : '');
            $where_in .= $ticket_info['ticket_id'] . $comma;
            $tickets[] = $ticket_info['ticket_id'];
        }

        $where_in = $this->clearNotUnique($where_in);

        $already_shifts = $mysqli->query('SELECT `ticket_id` FROM `i_rekassa` WHERE `id_user`=' . $this->id_user . ' AND `ticket_id` IN('.$where_in.')');
        
        if($already_shifts->num_rows == 0)
            return $tickets_info;

        foreach($already_shifts as $s){
            $already_shift[] = $s['ticket_id'];
        }

        $unique_tickets = array_diff($tickets, $already_shift);

        foreach($tickets_info as $ticket_info){
            if(in_array($ticket_info['ticket_id'], $unique_tickets)){
                $info[] = $ticket_info;
            }
        }

        return $info;
    }

    # удаляем неуникальные айдишники
    private function clearNotUnique($str, $seperator = ','){
        $exp = explode($seperator, $str);
        $exp = array_unique($exp);
        $response = implode($seperator, $exp);
        return $response;
    }

    # получаем последний номер расходника в этом году с нашей базы

    private function getLastAccountCashNumber(){
        global $mysqli;

        $doc_num = '1';

        $sN = $mysqli->query("
            (SELECT `number` FROM `i_account_cash` WHERE `id_user`='".$this->id_user."' AND (`date` > '".date("Y")."-01-01 00:00:01' AND `date` < '".date("Y")."-12-31 23:59:59')) 
            UNION ALL 
            (SELECT `number` FROM `i_rekassa` WHERE (`operation_type` = 'OPERATION_SELL_RETURN' OR `operation_type`='MONEY_PLACEMENT_WITHDRAWAL') AND `id_user`='".$this->id_user."' AND (`date` > '".date("Y")."-01-01 00:00:01' AND `date` < '".date("Y")."-12-31 23:59:59')) 
            ORDER BY `number` DESC LIMIT 1
        ");

        if($sN->num_rows > 0){
            $rN = $sN->fetch_array();
            if(intval($rN['number'])!=0)
                $doc_num = intval($rN['number']);
        }
        
        return $doc_num;
    }

    # получаем последний номер приходника в этом году с нашей базы
    private function getLastCashOrderNumber(){
        global $mysqli;
        
        $doc_num = '1';

        $sN=$mysqli->query("
            (SELECT `number` FROM `i_cash_order` WHERE `id_user`='".$this->id_user."' AND (`date` > '".date("Y")."-01-01 00:00:01' AND `date` < '".date("Y")."-12-31 23:59:59')) 
            UNION ALL 
            (SELECT `number` FROM `i_rekassa` WHERE (`operation_type` = 'OPERATION_SELL' OR `operation_type`='MONEY_PLACEMENT_DEPOSIT') AND `id_user`='".$this->id_user."' AND (`date` > '".date("Y")."-01-01 00:00:01' AND `date` < '".date("Y")."-12-31 23:59:59')) 
            ORDER BY `number` DESC LIMIT 1
        ");
        
        if ($sN->num_rows>0)
        {
            $rN=$sN->fetch_array();
            if (intval($rN["number"])!=0)
                $doc_num = intval($rN["number"]);
        }

        return $doc_num;
    }

    # перевод ошибок с рекассы
    public function errorsToRu($error){
        $errors = [
            'ACCESS_DENIED' => 'Неверный логин или пароль',
            'PERMISSION_ERROR' => 'Отсутствует разрешение',
            'INVALID_TOKEN' => 'Ошибка ОФД',
            'BLOCKED' => 'Касса заблокирована',
            'CASH_REGISTER_BLOCKED' => 'Касса заблокирована',
            'PROTOCOL_ERROR' => 'Invalid enum value: OPERATION_WRONG for enum type: kkm.proto.OperationTypeEnum',
            'NOT_ENOUGH_CASH' => 'В кассе недостаточно денег',
            'SAME_TAXPAYER_AND_CUSTOMER' => 'Один и тот же налогоплательщик и клиент ИИН или БИН',
            'CASH_REGISTER_WRONG_STATUS' => 'Неверный статус кассы',
            'CASH_REGISTER_OFFLINE_PERIOD_EXPIRED' => 'Срок оффлайн работы кассового аппарата истек',
            'CASH_REGISTER_SHIFT_PERIOD_EXPIRED' => 'Срок смены истек',
            'CANT_CANCEL_TICKET' => 'ТК ОФД не поддерживает данную операцию',
            'CASH_REGISTER_SHOULD_HAVE_SHIFT_OPEN' => 'В кассовом аппарате должна быть открыта смена',
            'WRONG_PASSWORD' => 'Неправильный пин или пароль',
        ];
        $errors['CASH_REGISTER_OFFLINE_PERIOD_EXPIRED'] = 'Требуется закрыть смену';
        $errors['CASH_REGISTER_SHIFT_PERIOD_EXPIRED'] = 'Требуется закрыть смену';
        return $errors[$error];
    }

    # создать тикет
    public function createTicket(){
        if($this->ticketSum == 0){
            return 'Введите сумму';
        }else{
            $create = $this->API->createNewTicket($this->cash_id, $this->ticketSum, $this->ticket_type)['result'];

            if(isset($create['message'])){
                return $create;
            }

            $bills = $create['data']['ticket']['amounts']['total']['bills'];
            $coins = $create['data']['ticket']['amounts']['total']['coins'];

            $info['ticket_id'] = $create['id'];
            $info['shiftNumber'] = $create['shiftNumber'];
            $info['date'] = $create['messageTime'];
            $info['sum'] = $bills . '.' .  (strlen($coins) == 1 ? '0' : '') . $coins;
            $info['operation'] = $create['data']['ticket']['operation'];

            $this->ticketSum = 0;
        }

        return $info;
    }

    # DEBUG LOG
    public function debug($s){
        echo '<pre>';
        print_r($s);
        echo '<pre>';
    }

    # СЕТТЕРЫ

    # setter номер тикета
    public function setTicketId($ticket_id){
        $this->ticket_id = $ticket_id;
        return $this;
    }

    # сеттер сумма тикета
    public function setTicketSum($ticketSum){
        $this->ticketSum = $ticketSum;
        return $this;
    }

    # setter pin code
    public function setUserPin($user_pin){
        $this->user_pin = $user_pin;
        return $this;
    }
    # setter shift id
    public function setShiftId($shift_id){
        $this->shift_id = $shift_id;
        return $this;
    }
    # setter shift page
    public function setShiftPage($shift_page){
        $this->shift_page = $shift_page;
        return $this;
    }
    # setter shift size
    public function setShiftSize($shift_size){
        $this->shift_size = $shift_size;
        return $this;
    }
    # setter ticket page
    public function setTicketPage($ticket_page){
        $this->ticket_page = $ticket_page;
        return $this;
    }
    # setter ticket size
    public function setTicketSize($ticket_size){
        $this->ticket_size = $ticket_size;
        return $this;
    }

    # setter ticket next page auto
    public function setAutoTicketToNextPage($status){
        $this->auto_ticket_to_next_page = $status;
        return $this;
    }

    # setter auto shift page next
    public function setAutoShiftToNextPage($status){
        $this->auto_shift_to_next_page = $status;
        return $this;
    }
    # setter type ticket
    public function setTicketType($type){
        $this->ticket_type = $type;
        return $this;
    }
}
