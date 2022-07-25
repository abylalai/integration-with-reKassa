<?
class RekassaAPI
{
	public $access_token_rekassa;	
	public $url_server = 'https://api-test.rekassa.kz';
	public $api_key = 'df2a0940-d5d4-11ec-9d64-0242ac120002';
	
	// Аутентификация, получение токена ==========================
	public function authREKASSA($number, $password)
	{	
		$info = Array();
		$info["access_token"] = '';
										
		$header = array(				
			"content-type: application/json"
		);

		$data = array(
			"number" => $number,
			"password" => $password
		);

		$data_json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		$url = $this->url_server."/api/auth/login?apiKey=".$this->api_key;				

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data_json);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);		
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, "CURL_HTTP_VERSION_1_1");		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($http_code == 200)
		{
			$info["access_token"] = $response;						
		}
		else
		{
			$result = json_decode($response, true);				
			$info["error"] = $result["message"];				
		}

		//$info["http_code"] = $http_code;			
		//$info["result"] = $result;		
		//$info["url"] = $url;						

		curl_close($curl);			
		
		$this->access_token_rekassa	= $info["access_token"];
		
		return $info;
	}
	
	// Current user ==========================
	public function getUser()
	{		
		$info = Array();	
		
		$header = array(
			"Accept: application/hal+json",
			"Authorization: Bearer ".$this->access_token_rekassa
		);
				
		$url = $this->url_server."/api/auth/me";

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);		
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, "CURL_HTTP_VERSION_1_1");		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($http_code == 200)
		{
			$result = json_decode($response, true);
			
			$info["id"] = $result["id"];
			$info["uid"] = $result["uid"];
			$info["name"] = $result["name"];
			$info["email"] = $result["email"];
			$info["phone"] = $result["phone"];
			$info["status"] = $result["status"];
			$info["roles"] = $result["roles"];
			$info["settings"] = $result["settings"];
			$info["registrationDate"] = $result["registrationDate"];						
		}
		else if ($http_code == 401)
			$info["error"] = 'Не прошел авторизацию';			
		
		/*	
		$info["http_code"] = $http_code;
		$info["curl_error"] = curl_error($curl);
		$info["result"] = $result;
		*/
		
		curl_close($curl);	
				
		return $info;		
	}
	
	// Cash Register list - Список касс ==========================
	public function getCashList()
	{		
		$info = Array();	
		
		$header = array(
			"Accept: application/hal+json",
			"Authorization: Bearer ".$this->access_token_rekassa
		);
				
		$url = $this->url_server."/api/crs";

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);		
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, "CURL_HTTP_VERSION_1_1");		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($http_code == 200)
		{
			$result = json_decode($response, true);						
									
		}
		else
			$info["error"] = 'Ошибка - '.$http_code;			
		
		
		$info["http_code"] = $http_code;
		$info["curl_error"] = curl_error($curl);
		$info["result"] = $result;
		
		
		curl_close($curl);	
				
		return $info;		
	}
	
	// Single Cash Register - Определенная касса ==========================
	public function getCashSingle($id)
	{		
		$info = Array();	
		
		$header = array(
			"Accept: application/hal+json",
			"Authorization: Bearer ".$this->access_token_rekassa
		);
				
		$url = $this->url_server."/api/crs/".$id;

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);		
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, "CURL_HTTP_VERSION_1_1");		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($http_code == 200)
		{
			$result = json_decode($response, true);						
									
		}
		else if ($http_code == 401)
			$info["error"] = 'Не прошел авторизацию';
		else
			$info["error"] = 'Ошибка - '.$http_code;			
		
		
		$info["http_code"] = $http_code;
		$info["curl_error"] = curl_error($curl);
		$info["result"] = $result;
		
		
		curl_close($curl);	
				
		return $info;		
	}		
	
	// Cash Register Shift list
	public function getShiftList($id, $page, $size)
	{		
		$info = Array();	
		
		$header = array(
			"Accept: application/hal+json",
			"Authorization: Bearer ".$this->access_token_rekassa
		);
				
		$url = $this->url_server."/api/crs/".$id."/shifts?includeOpen=false&page=".$page."&size=".$size;

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);		
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, "CURL_HTTP_VERSION_1_1");		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($http_code == 200)
		{
			$result = json_decode($response, true);						
									
		}
		else if ($http_code == 401)
			$info["error"] = 'Не прошел авторизацию';		
		else
		{
			$result = json_decode($response, true);	
			$info["error"] = 'Ошибка - '.$http_code;
		}		
		
		$info["http_code"] = $http_code;
		$info["curl_error"] = curl_error($curl);
		$info["result"] = $result;
		$info["url"] = $url;		
		
		curl_close($curl);	
				
		return $info;		
	}
	
	// Single Shift
	public function getShift($id_cash, $id_shift)
	{		
		$info = Array();	
		
		$header = array(
			"Accept: application/hal+json",
			"Authorization: Bearer ".$this->access_token_rekassa
		);
				
		$url = $this->url_server."/api/crs/".$id_cash."/shifts/".$id_shift;

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);		
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, "CURL_HTTP_VERSION_1_1");		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($http_code == 200)
		{
			$result = json_decode($response, true);						
									
		}
		else if ($http_code == 401)
			$info["error"] = 'Не прошел авторизацию';		
		else
		{
			$result = json_decode($response, true);	
			$info["error"] = 'Ошибка - '.$http_code;
		}		
		
		$info["http_code"] = $http_code;
		$info["curl_error"] = curl_error($curl);
		$info["result"] = $result;
		$info["url"] = $url;		
		
		curl_close($curl);	
				
		return $info;		
	}
	
	// Shift Ticket list
	public function getShiftTicketList($id_cash, $id_shift, $page, $size)
	{		
		$info = Array();	
		
		$header = array(
			"Accept: application/hal+json",
			"Authorization: Bearer ".$this->access_token_rekassa
		);
				
		$url = $this->url_server."/api/crs/".$id_cash."/shifts/".$id_shift."/tickets?page=".$page."&size=".$size;

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);		
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, "CURL_HTTP_VERSION_1_1");		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($http_code == 200)
		{
			$result = json_decode($response, true);						
									
		}
		else if ($http_code == 401)
			$info["error"] = 'Не прошел авторизацию';		
		else
		{
			$result = json_decode($response, true);	
			$info["error"] = 'Ошибка - '.$http_code;
		}		
		
		$info["http_code"] = $http_code;
		$info["curl_error"] = curl_error($curl);
		$info["result"] = $result;
		$info["url"] = $url;		
		
		curl_close($curl);	
				
		return $info;		
	}
	
	// New Ticket

	public function createNewTicket($id_cash, $summa = 0, $ticket_type){
		$info = array();
		$url = $this->url_server."/api/crs/".$id_cash."/tickets";

		$full_sum = explode('.', $summa);
		$request_info['bills'] = $full_sum[0];
		if(!empty($full_sum[1])){
			$request_info['coins'] = $full_sum[1];
		}else{
			$request_info['coins'] = 0;
		}

		$request_info['ticket_type'] = $ticket_type;

		$request = $this->doTempCreateNewTicket($request_info);

		$curl = curl_init($url);
		curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
				"Accept: application/json",
                'Content-Type: application/json',
				"Authorization: Bearer ".$this->access_token_rekassa
            ],
            CURLOPT_POSTFIELDS => json_encode($request),
        ]);

        $response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($http_code == 200)
		{
			$result = json_decode($response, true);	
		}
		else if ($http_code == 401)
			$info["error"] = 'Не прошел авторизацию';		
		else
		{
			$result = json_decode($response, true);	
			$info["error"] = 'Ошибка - '.$http_code;
		}		
		
		$info["http_code"] = $http_code;
		$info["curl_error"] = curl_error($curl);
		$info["result"] = $result;
		$info["url"] = $url;		
		
		curl_close($curl);	
		
		return $info;		
	}

	

	private function doTempCreateNewTicket($request_info){
		$info = $request_info;
		$request = [
			"operation" => $info['ticket_type'], 
			"dateTime" => 
			[
				"date" => [
					"year" => "".date('Y')."", 
					"month" => "".date('n')."", 
					"day" => "".date('j')."" 
				], 
				"time" => [
					"hour" => "".date('G')."", 
					"minute" => "".date('i')."", 
					"second" => "".date('s').""
				] 
			], 
			"domain" => 
			[
				"type" => "DOMAIN_SERVICES" 
			], 
			"items" => 
			[
				[
					"type" => "ITEM_TYPE_COMMODITY", 
					"commodity" => 
					[
						"name" => "Позиция", 
						"sectionCode" => "1", 
						"quantity" => 1, 
						"price" => [
							"bills" => $info['bills'], 
							"coins" => $info['coins']
						], 
						"sum" => [
							"bills" => $info['bills'], 
							"coins" => $info['coins']
						], 
						"auxiliary" => [
							[
								"key" => "UNIT_TYPE", 
								"value" => "PIECE" 
							] 
						] 
					] 
				] 
			], 
			"payments" => 
			[
				[
					"type" => "PAYMENT_CASH", 
					"sum" => [
						"bills" => $info['bills'], 
						"coins" => $info['coins']
					] 
				] 
			], 
			"amounts" => [
				"total" => 
				[
					"bills" => $info['bills'], 
						"coins" => $info['coins']
				], 
				"taken" => 
				[
					"bills" => $info['bills'], 
					"coins" => $info['coins']
				], 
				"change" => 
				[
					"bills" => "0", 
					"coins" => 0 
				] 
			] 
		 ]; 
		 return $request;
	}

	// Get Ticket
	public function getTicket($id_cash, $ticket_id){
		$info = Array();	
		
		$header = array(
			"Accept: application/hal+json",
			"Authorization: Bearer ".$this->access_token_rekassa
		);
				
		$url = $this->url_server."/api/crs/".$id_cash."/tickets/" . $ticket_id;

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);		
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, "CURL_HTTP_VERSION_1_1");		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($http_code == 200)
		{
			$result = json_decode($response, true);						
									
		}
		else if ($http_code == 401)
			$info["error"] = 'Не прошел авторизацию';		
		else
		{
			$result = json_decode($response, true);	
			$info["error"] = 'Ошибка - '.$http_code;
		}		
		
		$info["http_code"] = $http_code;
		$info["curl_error"] = curl_error($curl);
		$info["result"] = $result;
		$info["url"] = $url;		
		
		curl_close($curl);	
				
		return $info;		
	}
	// Cancel Ticket
	// Report X
	// Close Shift
	public function closeShift($id_cash, $shift_id, $pin_code){
		$info = Array();	
		
		$header = array(
			"Accept: application/hal+json",
			"cash-register-password: " . $pin_code,
			"Authorization: Bearer ".$this->access_token_rekassa
		);
				
		$url = $this->url_server."/api/crs/".$id_cash."/shifts/" . $shift_id . '/close';

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");	
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);	
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);		
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, "CURL_HTTP_VERSION_1_1");		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($http_code == 200)
		{
			$result = json_decode($response, true);						
		}
		else if ($http_code == 401)
			$info["error"] = 'Не прошел авторизацию';		
		else
		{
			$result = json_decode($response, true);	
			$info["error"] = 'Ошибка - '.$http_code;
		}		
		
		$info["http_code"] = $http_code;
		$info["curl_error"] = curl_error($curl);
		$info["result"] = $result;
		$info["url"] = $url;		
		
		curl_close($curl);	
				
		return $info;		
	}

	// Register Ticket Receipts - Download, Email, WhatsApp, Printer
	// QR
	
}

?>