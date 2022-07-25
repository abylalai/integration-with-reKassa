<?
class Curl{
    
    public $response;
    public $http_code;
    public $error;

    public function POST($url = '', $header = '', $data = '')
    {
        if($url == '') exit('ошибка');
        
        $curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		if($data != ''){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_POST, true);
        } 
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);		
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, "CURL_HTTP_VERSION_1_1");		
		if($header != '') curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        $this->response = curl_exec($curl);
		$this->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->error = curl_error($curl);

        curl_close($curl);	
    }

    public function GET($url = '', $header = ''){
        if($url == '') exit('ошибка');

        $curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);		
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, "CURL_HTTP_VERSION_1_1");		
		if($header != '') curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        $this->response = curl_exec($curl);
		$this->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->error = curl_error($curl);

        curl_close($curl);	
    }

}

?>
