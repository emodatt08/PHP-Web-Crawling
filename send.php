
<?php

class Send{

    public $conn;
    /**
     * Database config variables
     */
    const HOST = "10.7.1.2";
    const USER = "tf_bank";
    const PORT = "3306";
    const PASSWORD = "R0++3nFru!T";
    const DATABASE = "tfBank";
    const local = "http://localhost/tf_bank_api/public/api/v1/t24/cash?api_token=somerandomtoken";
    const apex = "http://10.7.1.61/api/v1/t24/cash?api_token=somerandomtoken";

    /**
     * Database connection method
     * @return mixed
     */
    public function connect(){
        // Connecting to mysql database
            $this->conn = new PDO('mysql:host='.self::HOST.';port='.self::PORT.';dbname=' . self::DATABASE . ';charset=utf8', self::USER, self::PASSWORD);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
   
        return $this->conn;
    }


/**
 * Gets settle log record
 * @return array
 */
public function all($request){
   
    $stmt = $this->connect()->prepare("SELECT * FROM settle_log WHERE id = :id");    
            //$stmt->bind_param("sss", $status, $request['merchant_id'], $request['source_id']);
            $stmt->bindValue(':id', $request, PDO::PARAM_STR);
            
       
            if ($stmt->execute() > 0) {
                
                $record = $stmt->fetchAll(PDO::FETCH_ASSOC);

             
                        return $record[0];                       
            } else {
                        return null;
            }
            
}
/**
 * Prepares request for core-banking
 * @param $postRequest
 * @return array
 */
private function prepareRequest($postRequest){
    return [
        "DEBITACCTNO" =>  $postRequest['destination_account_number'],
        "DEBITCURRENCY" =>  "GHS",
        "CREDITACCTNO" =>  $postRequest['source_account_number'],
        "ORDERINGBANK" =>  "ARB",
        "PAYMENTDETAILS" =>  "Reversal For ".$this->company($postRequest['destination_account_company_code']). "/". $postRequest['amount']."/". $this->transdate($postRequest),
        'COMPANYCODE' => $postRequest['source_account_company_code'],
        'BRANCHCODE' => $postRequest['source_account_branch_id'],
        'ENV' => $postRequest['env'],
         "AMOUNT" =>          $postRequest['amount'],
        "TransflowTransactionId" =>  strtoupper(date('Ymd').uniqid())

    ];
    


}
/**
 * Prepares reverse request for core-banking
 * @param $find
 * @return array
 */
private function prepareReversalRequest($find){
    return [
        'source_trans_id' => $find['trans_id'],
        'companyCode' => $find['company_code'],
        'serviceCode' => $find['service_code'],
        'branchId' =>  $find['branch_id'],
        'currency' =>  $find['currency'],
        'env' =>  $find['env'],
        'accountType' => 'tf_comp_collection'
            ];
}



/**
 * Get reversal record
 * @return array value
 */
public function reversalRecord($id){
    $status = "1";
   
    $stmt = $this->connect()->prepare("SELECT
	s1.service_code As service_code,
	s1.currency As currency,
	s2.*
	
FROM
	settle_register s1
JOIN settle_log s2 ON s1.settle_id = s2.settle_id
WHERE
	s2.settle_id = :id");    
            $stmt->bindValue(':id', $id, PDO::PARAM_STR);
            
        
            if ($stmt->execute() > 0) {
                
                $record = $stmt->fetchAll(PDO::FETCH_ASSOC);
           
             
                        return $record[0];                       
            } else {
                        return null;
            }
            
}



/**
 * Get company code
 * @return array value
 */
public function company($code){
    $status = "1";
   
    $stmt = $this->conn->prepare("SELECT * FROM companies WHERE company_code = :id");    
            $stmt->bindValue(':id', $code, PDO::PARAM_STR);
            
        
            if ($stmt->execute() > 0) {
                
                $record = $stmt->fetchAll(PDO::FETCH_ASSOC);

             
                        return $record[0]['company_name'];                       
            } else {
                        return null;
            }
            
}


/**
 * Gets transaction date
 * @return array value
 */
public function transdate($request){
   
    $stmt = $this->conn->prepare("SELECT transdate FROM settle_register WHERE settle_id = :id");    
            $stmt->bindValue(':id', $request['settle_id'], PDO::PARAM_STR);          
       
            if ($stmt->execute() > 0) {
                
                $record = $stmt->fetchAll(PDO::FETCH_ASSOC);

             
                        return $record[0]['transdate'];                       
            } else {
                        return null;
            }
            
}
/**
 * Curl request to send data to the T24 endpoint
 * @param $data
 * @return object
 */

public function sendToBank($data){

    $data_string = json_encode($data);
        $ch = curl_init(self::apex);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );

        $result = curl_exec($ch);
        $this->logAction($data['DEBITACCTNO'],$data['CREDITACCTNO'],(isset($result->data->bank_trans_id)) ? $result->data->bank_trans_id: "NA", $data);
        return $result;
        
}

/**
 * Bootstrap the reversal process 
 * @param $id
 * @return void
 */

public function processRecord($id){
    //get settle record per settle_id
    $data = $this->all($id);
    //prepare the T24 params from the settle record 
    $prepare = $this->prepareRequest($data);
    //send to T24 endpoint
    $sendToBank = $this->sendToBank($prepare);
 
}
/**
 * Logs data on the process
 * @param $source,$dest,$transID, $data
 * @return void
 */


public function logAction($source,$dest,$transID, $data){
    date_default_timezone_set('Africa/Accra');
    //$logfile = $_SERVER['DOCUMENT_ROOT'].$this->docroot."/logs/bridgeLogs/".date('Y-m-d').".csv";
    $logfile = "storage/" .date('Y-m-d').".csv";
    $insert= "\n\n   Reversal from ".$source ." to " . $dest." with FT as ". $transID. " and request data as ". json_encode($data);

    file_put_contents($logfile,$insert,FILE_APPEND | LOCK_EX);
}

public function scrape_it($url)
{


    $header = array();
    $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
    $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
    $header[] =  "Cache-Control: max-age=0";
    $header[] =  "Connection: keep-alive";
    $header[] = "Keep-Alive: 300";
    $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
    $header[] = "Accept-Language: en-us,en;q=0.5";
    $header[] = "Pragma: "; // browsers keep this blank.


    $options = Array(
        CURLOPT_RETURNTRANSFER => TRUE,  // Setting cURL's option to return the webpage data
        CURLOPT_FOLLOWLOCATION => TRUE,  // Setting cURL to follow 'location' HTTP headers
        CURLOPT_HTTPHEADER => $header,
        CURLOPT_AUTOREFERER => TRUE, // Automatically set the referer where following 'location' HTTP headers
        CURLOPT_CONNECTTIMEOUT => 120,   // Setting the amount of time (in seconds) before the request times out
        CURLOPT_TIMEOUT => 120,  // Setting the maximum amount of time for cURL to execute queries
        CURLOPT_MAXREDIRS => 10, // Setting the maximum number of redirections to follow
        CURLOPT_USERAGENT => "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1a2pre) Gecko/2008073000 Shredder/3.0a2pre ThunderBrowse/3.2.1.8",  // Setting the useragent
        CURLOPT_URL => $url, // Setting cURL's URL option with the $url variable passed into the function
    );

    $ch = curl_init();  // Initialising cURL
    curl_setopt_array($ch, $options);   // Setting cURL's options using the previously assigned array data in $options
    $data = curl_exec($ch); // Executing the cURL request and assigning the returned data to the $data variable
    curl_close($ch);    // Closing cURL
    return $data;   // Returning the data from the function
}





public function scrape_between($data, $start, $end)
{
    $data = stristr($data, $start); // Stripping all data from before $start
    $data = substr($data, strlen($start));  // Stripping $start
    $stop = stripos($data, $end);   // Getting the position of the $end of the data to scrape
    $data = substr($data, 0, $stop);    // Stripping all data from after and including the $end of the data to scrape
    return $data;   // Returning the scraped data from the function

}

public function curl($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
//parse the json output
public function getResults($json){
    $results = array();
    $json_array = json_decode($json, true);
    foreach($json_array['query']['pages'] as $page){
        if(count($page['images']) > 0){
            foreach($page['images'] as $image){
                $title = str_replace(" ", "_", $image["title"]);
                $imageinfourl = "http://en.wikipedia.org/w/api.php?action=query&titles=".$title."&prop=imageinfo&iiprop=url&format=json";
                $imageinfo = $this->curl($imageinfourl);
                $iamge_array = json_decode($imageinfo, true);
                $image_pages = $iamge_array["query"]["pages"];
                foreach($image_pages as $a){
                    $results[] = $a["imageinfo"][0]["url"];
                }
            }
        }
    }
    return $results;
}


}
/**
 * Settle IDs to reverse in ascending order
 * @param $data
 * @return object
 */

$process = ['9134', '9133', '9132'];
$send = new Send();

foreach($process as $id){
    $send->processRecord($id);
}

