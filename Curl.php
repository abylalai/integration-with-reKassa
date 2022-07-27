<?
class Curl extends CurlClient{
    
    public $response = null;
    public $http_code = null;
    public $error = null;

    private $url = null;

    public $options = [];


    public function POST($url = '', $header = '', $data = ''){
        $this->setUrl($url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'POST');
        $this->setOpt(CURLOPT_POST, TRUE);

        if($data != '') 
            $this->setOpt(CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        
        if($header != '')
            $this->setOpt(CURLOPT_HTTPHEADER, $header);

        $this->doRequest();
    }

    public function GET($url = '', $header = ''){
        $this->setUrl($url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');
        if($header != '')
            $this->setOpt(CURLOPT_HTTPHEADER, $header);

        $this->doRequest();
    }

    public function DELETE($url = '', $header = ''){
        
    }
    
    public function PUT($url = '', $header = ''){
        
    }
    
    public function PATCH($url = '', $header = ''){
        
    }
}

class CurlClient{

    public $maxRedirs = 10;
    public $timeout = 30;
    public $curlHttpVersion = 'CURL_HTTP_VERSION_1_1';

    protected function doRequest(){
        $curl = curl_init($this->url);
        $this->setDefaultOptions();
        curl_setopt_array($curl, $this->options);

        $this->response = curl_exec($curl);
        $this->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->error = curl_error($curl);
        curl_close($curl);
    }

    public function setOpt($option_name, $option_value){
        $this->options[$option_name] = $option_value;
        return $this;
    }

    protected function setDefaultOptions(){
        $this->setOpt(CURLOPT_RETURNTRANSFER, TRUE);
        $this->setOpt(CURLOPT_MAXREDIRS, $this->maxRedirs);
        $this->setOpt(CURLOPT_TIMEOUT, $this->timeout);
        $this->setOpt(CURLOPT_HTTP_VERSION, $this->curlHttpVersion);
    }

    protected function setUrl($url){
        $this->url = $url;
        return $this;
    }
}
?>
