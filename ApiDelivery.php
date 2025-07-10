<?php

class ApiDelivery
{
    private $baseUrl = 'https://stilam.ma/api/v1';  // ✅ Base URL
    private $apiKey = '39|Isa7W9LGOnIxOkHEND2qjc8UUUwvP0wUYd9JEL9Pec734eca';  // ✅ API Key

    public function __construct()
    {
        // Nothing to initialize since baseUrl and apiKey are predefined
    }

    private function get($endpoint)
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$this->apiKey}",
            "Accept: application/json"
        ]);

        // ⚠️ Disable SSL verification (for testing only; REMOVE in production)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return "cURL Error: " . $error_msg;
        }

        curl_close($ch);
        return $response;
    }
    /**
     * Fetches cities from the API
     */
    public function getCities()
    {
        return $this->get('cities');
    }

    /**
     * Retrieve parcel information by tracking number.
     *
     * @param string $trackingNum The tracking number of the parcel.
     * @return mixed The API response containing parcel details.
     */
    public function getParcel(string $trackingNum)
    {
        return $this->get('/parcel/' . $trackingNum);
    }
    public function createParcel(array $parcelData)
    {
        echo '' . json_encode($parcelData) . '';
        $url = $this->baseUrl . '/parcels';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$this->apiKey}",
            "Accept: application/json",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parcelData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            throw new Exception(curl_error($ch));
        }
        curl_close($ch);
        return $response;
    }
}

?>
