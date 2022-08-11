<?
// 10.08.2022 => 38 functions
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

    public $user_pin; # пин код (исп. при закрывание смены) 
    private $last_user_pin; # пин код (исп. при закрывание смены)

    public $ticketSum = 0; # сумма тикета (500 тг и тд (без тг))
    
    public $shift_id = 0; # номер смены

    private $ticket_type; # тип тикета (operation_sell и т.д.)

    public $getLastShift = 0; # последний смена вводит
    
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
     * 
     */

    public function getCashSingle()
    {
        return $this->API->getCashSingle(639385);
    }

     /**
      * 

      */

    public function doCheckPhoto(){
        $cashInfo = $this->getCashSingle()['result'];
        $this->setTicketId(76735118);
        $ticketInfo = $this->API->getTicket($this->cash_id, $this->ticket_id)['result'];
        $check = '
        <table class="table table-striped table-bordered">
            <tr>
                <td></td>
                <td colspan="2">'.$cashInfo['data']['service']['regInfo']['org']['title'].'</td>
                <td></td>
            </tr>
            <tr>
                <td>
                <td colspan="2">БИН (ИИН): '.$cashInfo['data']['service']['regInfo']['org']['inn'].'</td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td colspan="2">'.$cashInfo['data']['service']['regInfo']['org']['address'].'</td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td colspan="2">ФИСКАЛЬНЫЙ ЧЕК</td>
                <td></td>
            </tr>
            '.($ticketInfo['offlineTicketNumber'] != '' ? '
            <tr>
                <td>АВТОНОМНЫЙ</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            ' : '').'
            <tr>
                <td>Продажа:</td>
                <td>№'.$ticketInfo['shiftDocumentNumber'].'</td>
                <td>Смена:</td>
                <td style="text-align: right">'.$ticketInfo['shiftNumber'].'</td>
            </tr>
            <tr>
                <td>Дата:</td>
                <td style="text-align: right">10-08-2022</td>
                <td>Время:</td>
                <td>13:42:19</td>
            </tr>
            <tr>
                <td>ФП:</td>
                <td style="text-align: right">1145203939</td>
                <td>Кассир:</td>
                <td style="text-align: right">306102</td>
            </tr>
            <tr>
                <td>РНМ:</td>
                <td style="text-align: right">010102008801</td>
                <td>ЗНМ:</td>
                <td>SGGXHB98-EX1</td>
            </tr>
            <tr>
                <td></td>
                <td colspan="2">ДУБЛИКАТ</td><td></td>
            </tr>
            <tr>
                <td>1. Позиция</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
                <tr>
                <td>1.0 шт. × 10.00 ₸</td>
                <td></td>
                <td></td>
                <td>10.00 ₸</td>
            </tr>
                <tr>
                <td>Стоимость</td>
                <td></td>
                <td></td>
                <td>10.00 ₸</td>
            </tr>
                <tr>
                <td>ИТОГО</td>
                <td></td>
                <td></td>
                <td>10.00 ₸</td>
            </tr>
                <tr>
                <td>Наличные</td>
                <td></td>
                <td></td>
                <td>10.00 ₸</td>
            </tr>
                <tr>
                <td colspan="3">ОФД АО «Транстелеком»</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="3">Для проверки чека отсканируйте QR-</td>
                <td></td>
            </tr>
                <tr>
                <td></td>
                <td colspan="2">код (https://ofd1.kz)</td>
                <td></td>
            </tr>
                <tr>
                <td colspan="4">reKassa.kz - Бесплатный онлайн кассовый аппарат</td>
            </tr>
            </table>
        ';

        return $check;
     }

    /**
    *  name: Tickets To SQL
    *  do: собирать все данные и записать в базу (тикеты) -> записывается сумма и дата тикета
    *  @var Array $tickets_info = все тикеты из рекассы (method: doShiftTicketListInfo() -> собирает все тикеты, method: clearHasAlreadyTickets() -> удалюят из массива уже записанные в базу тикеты)
    *  @var String $sql = собирает Insert запрос (method: dotemplateTicketToSQL())
    *  @method lastShiftNumberToSql() = записывает последний номер смены в базу 
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
    /**
    * name: Do Template Ticket To SQL
    * do: шаблон записа тикета в базу (SQL template)
    * @return String $sql = INSERT запрос
    */
    private function doTemplateTicketToSQL($tickets_info){
        $cash_order_last_num = $this->getLastCashOrderNumber() + 1;
        $account_cash_last_num = $this->getLastAccountCashNumber() + 1;

        $ticketCount = count($tickets_info);
        $loaded_ticket_id = '';

        $tickets_info = array_reverse($tickets_info);

        $sql = 'INSERT INTO `i_rekassa` (`id_user`, `date`, `summa`, `shift_number`, `ticket_id`, `id_client`, `number`, `operation_type`) VALUES ';
        foreach($tickets_info as $ticket_info){
            if($ticket_info['operation'] == 'OPERATION_SELL' || $ticket_info['operation'] == 'MONEY_PLACEMENT_DEPOSIT' || $ticket_info['operation'] == 'OPERATION_BUY_RETURN'){
                $cash_order_last_num++;
                $doc_num = $cash_order_last_num;
            }else if($ticket_info['operation'] == 'OPERATION_SELL_RETURN' || $ticket_info['operation'] == 'MONEY_PLACEMENT_WITHDRAWAL' || $ticket_info['operation'] == 'OPERATION_BUY'){
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

    /**
    * name: One Ticket To SQL
    * do: записываем в базу один тикет
    * @return Array $info = информация о записанном тикете в базу
    */
    public function oneTicketToSQL(){
        global $mysqli;

        $ticket_info = $this->new_ticket_info;

        if($ticket_info['operation'] == 'OPERATION_SELL' || $ticket_info['operation'] == 'MONEY_PLACEMENT_DEPOSIT' || $ticket_info['operation'] == 'OPERATION_BUY_RETURN'){
            $doc_num = $this->getLastCashOrderNumber() + 1;
        }else if($ticket_info['operation'] == 'OPERATION_SELL_RETURN' || $ticket_info['operation'] == 'MONEY_PLACEMENT_WITHDRAWAL' || $ticket_info['operation'] == 'OPERATION_BUY'){
            $doc_num = $this->getLastAccountCashNumber() + 1;
        }else{
            $doc_num = 1;
        }

        $sql = 'INSERT INTO `i_rekassa` (`id_user`, `date`, `summa`, `shift_number`, `ticket_id`, `id_client`, `number`, `operation_type`, `payment_type`, `items_info`) VALUES ('.$this->id_user.', \''.$ticket_info['date'].'\', \''.$ticket_info['sum'].'\', \''.$ticket_info['shiftNumber'].'\', \''.$ticket_info['ticket_id'].'\', 0, '.$doc_num.', \''.$ticket_info['operation'].'\', \''.$ticket_info['payment_type'].'\', \''.$ticket_info['item_info'].'\')';
        
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
    
    /**
    * name: Last Shift Number To SQL
    * do: записываем последний введенный номер смены в базу для каждого пользователя
    * @return boolean
    */
    private function lastShiftNumberToSql(){
        global $mysqli;
        $update = $mysqli->query("UPDATE `i_rekassa_info` SET `lastShiftNumber` = " . $this->rekassaLastShift . " WHERE `id_user` = " . $this->id_user);
        if($update) return true;
    }
    
    /**
    * name: Get User Login
    * do: получаем логин и пароль пользователя на рекассе
    * @return Array $login = логин и пароль
    */
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
        $this->last_user_pin = $r['pin_code'];

        return $login;
    }
    
    /**
    * name: AUTH
    * do: авторизация на рекассе
    * @if (error)
    * @return Array $auth = если есть ошибка тогда ошибка 
    * @else
    * @return Integer - айди кассы
    */
    private function AUTH(){
        $login = $this->getUserLogin();
        $auth = $this->API->authREKASSA($login['number'], $login['password']);
        if(isset($auth['error']))
            return $auth;
        else
            $this->getCashId();
    }

    /**
    * name: Get Cash Id
    * do: получаем айди кассы на рекассе
    * @return Integer $this->cash_id = номер кассы
    */
    public function getCashId(){
        $cash_list = $this->API->getCashList()['result']['_embedded']['userCashRegisterRoles'];

        if(!$cash_list) return false;

        $this->cash_id = $cash_list[0]['cashRegister']['id'];
        $this->shiftCount = $cash_list[0]['cashRegister']['shiftNumber'];
        $this->shiftOpenCheck = $cash_list[0]['cashRegister']['shiftOpen'];
        return $this->cash_id;
    }
    
    /**
    * name: Do Shift List Info
    * do: обрабатываем список смен с рекассы
    * @return Array @info = информация о сменах
    */
    public function doShiftListInfo(){

        $shift_list = $this->getShift();
        if(!$shift_list) return false;

        $info = array();

        $i = 0;

        foreach($shift_list as $shift){
            if($shift['closeTime'] < $this->reg_date || $shift['shiftNumber'] <= $this->user_last_shift) continue;

            $info[$i]['shiftNumber'] = $shift['shiftNumber'];
            $info[$i]['shiftTicketCount'] = $shift['shiftTicketNumber'];
            $info[$i]['date'] = $shift['closeTime'];
           
            foreach($shift['data']['taxes'] as $taxesOperations){
                foreach($taxesOperations['operations'] as $taxes){
                    if($taxes['operation'] == 'OPERATION_SELL')
                        $info[$i]['taxes'][0]['OPERATION_SELL'] += $taxes['sum']['bills'] . '.' . $taxes['sum']['coins'];
                    else if($taxes['operation'] == 'OPERATION_SELL_RETURN')
                        $info[$i]['taxes'][1]['OPERATION_SELL_RETURN'] += $taxes['sum']['bills'] . '.' . $taxes['sum']['coins'];
                    else if($taxes['operation'] == 'OPERATION_BUY')
                        $info[$i]['taxes'][2]['OPERATION_BUY'] += $taxes['sum']['bills'] . '.' . $taxes['sum']['coins'];
                    else if($taxes['operation'] == 'OPERATION_BUY_RETURN')
                        $info[$i]['taxes'][3]['OPERATION_BUY_RETURN'] += $taxes['sum']['bills'] . '.' . $taxes['sum']['coins'];
                }
            }

            foreach($shift['data']['ticketOperations'] as $operations){
                foreach($operations['payments'] as $payments){
                    if($operations['operation'] == 'OPERATION_SELL')
                        $info[$i]['payments'][0]['OPERATION_SELL'][$payments['payment']] += $payments['sum']['bills'] . '.' . $payments['sum']['coins'];
                    else if($operations['operation'] == 'OPERATION_SELL_RETURN')
                        $info[$i]['payments'][1]['OPERATION_SELL_RETURN'][$payments['payment']] += $payments['sum']['bills'] . '.' . $payments['sum']['coins'];
                    else if($operations['operation'] == 'OPERATION_BUY')
                        $info[$i]['payments'][2]['OPERATION_BUY'][$payments['payment']] += $payments['sum']['bills'] . '.' . $payments['sum']['coins'];
                    else if($operations['operation'] == 'OPERATION_BUY_RETURN')
                        $info[$i]['payments'][3]['OPERATION_BUY_RETURN'][$payments['payment']] += $payments['sum']['bills'] . '.' . $payments['sum']['coins'];
                }
            }

            foreach($shift['data']['moneyPlacements'] as $moneyPlacements){
                if($moneyPlacements['operation'] == 'MONEY_PLACEMENT_WITHDRAWAL')
                    $info[$i]['payments'][4]['MONEY_PLACEMENT_WITHDRAWAL']['PAYMENT_CARD'] += $moneyPlacements['operationsSum']['bills'] . '.' . $payments['operationsSum']['coins'];
                else if($moneyPlacements['operation'] == 'MONEY_PLACEMENT_DEPOSIT')
                    $info[$i]['payments'][5]['MONEY_PLACEMENT_DEPOSIT']['PAYMENT_CARD'] += $moneyPlacements['operationsSum']['bills'] . '.' . $payments['operationsSum']['coins'];
            }

            $i++;
        }
        
        return $info;
    }
    
    /** 
     * name: Shifts to SQL
     * do: Записывает смены (сумму и т.д.) в нашу базу
     * @return String : success or error SQL, и нет уникальных записей если нет новых смен
     */
    public function shiftsToSQL(){
        global $mysqli;
        $shifts_info = $this->doShiftListInfo();
        if(!$shifts_info) return 'нет уникальных записей';
        $shiftsCount = 0;
        $cash_order_last_num = $this->getLastCashOrderNumber() + 1;
        $account_cash_last_num = $this->getLastAccountCashNumber() + 1;

        $sql = 'INSERT INTO `i_rekassa` (`id_user`, `date`, `summa`, `shift_number`, `ticket_id`, `id_client`, `number`, `operation_type`, `payment_type`) VALUES ';
        $sqlValue = '';
        foreach($shifts_info as $shift_info){
            $shiftsCount = $shiftsCount + count($shift_info['payments']);
            foreach($shift_info['payments'] as $payments_type => $payments){

                $operation_name = key($payments);
                $payments = $payments[key($payments)];
                
                $payments[0] = $payments['PAYMENT_CARD'] + $payments['PAYMENT_MOBILE'];
                $payments[1] = $payments['PAYMENT_CASH'];

                $payment_name[0] = 'PAYMENT_CARD|MOBILE';
                $payment_name[1] = 'PAYMENT_CASH';

                $paymentsCount = count($payments);

                if($payments['PAYMENT_MOBILE'] > 0) $paymentsCount--;

                for ($i=0; $i < 2; $i++) {

                    if($payments[$i] <= 0) continue;

                    if($operation_name == 'MONEY_PLACEMENT_DEPOSIT' || $operation_name == 'OPERATION_SELL' || $operation_name == 'OPERATION_BUY_RETURN'){
                        $cash_order_last_num++;
                        $doc_num = $cash_order_last_num;
                    }
                    else if($operation_name == 'MONEY_PLACEMENT_WITHDRAWAL' || $operation_name == 'OPERATION_SELL_RETURN' || $operation_name == 'OPERATION_BUY')
                    {
                        $account_cash_last_num++;
                        $doc_num = $account_cash_last_num;
                    }
                    
                    $our_sum = $mysqli->query("SELECT SUM(summa) as oursum FROM `i_rekassa` WHERE `id_user` = '".$this->id_user."' AND `shift_number` = '".$shift_info['shiftNumber']."' AND `payment_type` = '".$payment_name[$i]."' AND `operation_type` = '".$operation_name."'")->fetch_assoc()['oursum'];
                    $payments[$i] = $payments[$i] - $our_sum;
                    if($payments[$i] <= 0) continue;
                    $sqlValue .= "\n(".$this->id_user.", '".$shift_info['date']."', '".$payments[$i]."', '".$shift_info['shiftNumber']."', 0, 0, '".$doc_num."', '".$operation_name."', '".$payment_name[$i]."'),";
                }
            }
        }

        if($sqlValue != '')
            $sql .= preg_replace('/\,$/', '', $sqlValue);
        else 
            return 'Нет уникальных записей';

        if($mysqli->query($sql)) {
            $this->lastShiftNumberToSql();
            return 'success';
        }else{
            return 'Error INSERT';
        }
    }



    /**
    * name: Get Shift
    * do: получаем список смен с рекассы 
    * @return Array $shift_list = информация о сменах
    */
    public function getShift(){

        if($this->getRekassaLastShift() < $this->user_last_shift) return false;

        if($this->shiftCount > $this->shift_size && $this->auto_shift_to_next_page == true){
            $shift_list = array();
            for ($i=0; $i < $this->shiftCount / $this->shift_size; $i++) { 
                $shift_list = array_merge($shift_list, $this->API->getShiftList($this->cash_id, $this->shift_page + $i, $this->shift_size)['result']['_embedded']['shifts']);
                if(end($shift_list['closeTime']) < $this->reg_date || end($shift_list['shiftNumber']) <= $this->user_last_shift) break;
            }
        }else if($this->getLastShift == 1){
            $this->getLastShift = 0;
            $shift_list = $this->API->getShiftList($this->cash_id, 0, 1)['result']['_embedded']['shifts'];
        }else{
            $shift_list = $this->API->getShiftList($this->cash_id, $this->shift_page, $this->shift_size)['result']['_embedded']['shifts'];
        }

        return $shift_list;
    }
    
    /**
    * name: Get reKassa Last Shift 
    * do: получаем последний айди смены или указываем
    * @param String $get = если id тогда только номер смены
    * @return Array $response
    */
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
    
    /**
    * name: Do Shift Ticket List Info
    * do: Обрабатываем список тикетов с рекассы
    * @param String $shifts_info = если default тогда автоматический берем тикеты
    * @return Array info = информация о тикетах
    */
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
    
    /**
    * name: Get Shift Ticket
    * do: получаем список тикетов с рекассы 
    * @return Array $tickets = информация о тикетах
    */
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

    /**
    * name: Clear Has Already Tickets
    * do: получать один тикет
    * @return Array $info = возращает информацию о тикете
    */
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
    
    /**
    * name: Close Shift
    * do: закрываем смену
    * @return String $close_shift = информация закрылся ли смена или ошибка
    */
    public function closeShift(){
        $close_shift = $this->API->closeShift($this->cash_id, $this->shift_id, $this->user_pin);
        
        return $close_shift;
    }
    
    /**
    * name: Clear Has Already Tickets
    * do: удаляем уже записанные тикеты в нашу базу (с массива)
    * @param Array $tickets_info = массив тикетов
    * @return Array $info = возращает уникальные тикеты
    */
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
    
    /**
    * name: Clear Not Unique
    * do: удаляем неуникальные айдишники (пример: 1,1,1,1,2,3 -> оставляет: 1,2,3)
    * @return String
    */
    private function clearNotUnique($str, $seperator = ','){
        $exp = explode($seperator, $str);
        $exp = array_unique($exp);
        $response = implode($seperator, $exp);
        return $response;
    }

    /**
    * name: Get Last Account Cash Number
    * do: получаем последний номер расходника в этом году с нашей базы
    */
    private function getLastAccountCashNumber(){
        global $mysqli;

        $doc_num = '1';

        $sN = $mysqli->query("
            (SELECT `number` FROM `i_account_cash` WHERE `id_user`='".$this->id_user."' AND (`date` > '".date("Y")."-01-01 00:00:01' AND `date` < '".date("Y")."-12-31 23:59:59')) 
            UNION ALL 
            (SELECT `number` FROM `i_rekassa` WHERE (`operation_type` = 'OPERATION_SELL_RETURN' OR `operation_type`='MONEY_PLACEMENT_WITHDRAWAL' OR `operation_type` = 'OPERATION_BUY') AND `id_user`='".$this->id_user."' AND (`date` > '".date("Y")."-01-01 00:00:01' AND `date` < '".date("Y")."-12-31 23:59:59')) 
            ORDER BY `number` DESC LIMIT 1
        ");

        if($sN->num_rows > 0){
            $rN = $sN->fetch_array();
            if(intval($rN['number'])!=0)
                $doc_num = intval($rN['number']);
        }
        
        return $doc_num;
    }
    /**
    * name: Get Last Cash Order Number
    * do: получаем последний номер приходника в этом году с нашей базы
    */
    private function getLastCashOrderNumber(){
        global $mysqli;
        
        $doc_num = '1';

        $sN=$mysqli->query("
            (SELECT `number` FROM `i_cash_order` WHERE `id_user`='".$this->id_user."' AND (`date` > '".date("Y")."-01-01 00:00:01' AND `date` < '".date("Y")."-12-31 23:59:59')) 
            UNION ALL 
            (SELECT `number` FROM `i_rekassa` WHERE (`operation_type` = 'OPERATION_SELL' OR `operation_type`='MONEY_PLACEMENT_DEPOSIT' OR `operation_type` = 'OPERATION_BUY_RETURN') AND `id_user`='".$this->id_user."' AND (`date` > '".date("Y")."-01-01 00:00:01' AND `date` < '".date("Y")."-12-31 23:59:59')) 
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
    /**
    * name: Errors To Ru
    * do: перевод ошибок с рекассы
    * @param String $error
    * @return String $error
    */
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
            'ERROR_500' => 'Error 500 / возможно вы изменили пароль для интеграции'
        ];
        $errors['CASH_REGISTER_OFFLINE_PERIOD_EXPIRED'] = 'Требуется закрыть смену';
        $errors['CASH_REGISTER_SHIFT_PERIOD_EXPIRED'] = 'Требуется закрыть смену';
        if(array_key_exists($error, $errors)) return $errors[$error];
        else return $error;
    }

    /**
    * name: Create Ticket
    * do: создают новый тикет
    */
    public function createTicket($ticket_dop_info){
        $info = array();
        if($this->ticketSum == 0){
            return 'Введите сумму';
        }else{
            $create = $this->API->createNewTicket($this->cash_id, $this->ticketSum, $this->ticket_type, $ticket_dop_info)['result'];

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
            $info['payment_type'] = ($ticket_dop_info['payment_type'] == 'PAYMENT_MOBILE' || $ticket_dop_info['payment_type'] == 'PAYMENT_CARD' ? 'PAYMENT_CARD|MOBILE' : $ticket_dop_info['payment_type']);

            $info['item_info'] = json_encode($ticket_dop_info);

            $this->ticketSum = 0;
        }

        return $info;
    }

    public function createDeposit(){
        $info = array();
        if($this->ticketSum == 0){
            return 'Введите сумму';
        }else{
            $create = $this->API->createNewDeposit($this->cash_id, $this->ticketSum, $this->ticket_type)['result'];

            if(isset($create['message'])){
                return $create;
            }

            $bills = $create['data']['moneyPlacement']['sum']['bills'];
            $coins = $create['data']['moneyPlacement']['sum']['coins'];

            $info['ticket_id'] = $create['id'];
            $info['shiftNumber'] = $create['shiftNumber'];
            $info['date'] = $create['messageTime'];
            $info['sum'] = $bills . '.' .  (strlen($coins) == 1 ? '0' : '') . $coins;
            $info['operation'] = $create['data']['moneyPlacement']['operation'];

            $this->ticketSum = 0;
        }

        return $info;
    }

    # change pin code rekassa
    public function changePinCode($pin){
        global $mysqli;
        if($this->last_user_pin != $pin)
            $mysqli->query("UPDATE `i_rekassa_info` SET `pin_code` = '" . $pin . "'");
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
    # сеттер номер тикета
    public function setNewTicketInfo($ticket_info){
        $this->new_ticket_info = $ticket_info;
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
    #
    public function setGetLastShift($type){
        $this->getLastShift = $type;
        return $this;
    }
}
