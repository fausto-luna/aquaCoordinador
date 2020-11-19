<?php
    function checkToken($url){
        $curl = curl_init();
    
        curl_setopt_array($curl, array(
        CURLOPT_URL => $url . "/equipos/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "token: ".$_COOKIE['AquaCoordinadorToken']
        ),
        ));
        
        $response = curl_exec($curl);
        
        curl_close($curl);
    
        return json_decode($response);
    }

?>