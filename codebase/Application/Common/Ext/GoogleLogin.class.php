<?php

namespace Common\Ext;

/**
 *
 */
class GoogleLogin
{


    private string $redirect_url;
    private string $client_id;
    private string $client_secret;

    function __construct()
    {
        $this->redirect_url = SITE_URL . 'Login/googleRedirect';
        $this->client_id = GOOGLE_CLIENT_ID;
        $this->client_secret = GOOGLE_CLIENT_SECRET;
    }

    /**
     * @return mixed
     */
    public function loginURL()
    {
        $login_url = 'https://accounts.google.com/o/oauth2/v2/auth?scope=' . urlencode('https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email') . '&redirect_uri=' . urlencode($this->redirect_url) . '&response_type=code&client_id=' . $this->client_id . '&access_type=online';
        return $this->respHandle($login_url);
    }

    /**
     * @param $info
     * @return mixed
     */
    private function respHandle($info)
    {
        return $info;
    }

    /**
     * @param $access_token
     * @return mixed
     */
    public function profile($access_token)
    {

        $url = 'https://www.googleapis.com/oauth2/v2/userinfo?fields=name,given_name,family_name,email,id,picture,verified_email';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
        $exec = curl_exec($ch);

        return json_decode($exec, true);
    }

    function verify($code)
    {
        $url = 'https://www.googleapis.com/oauth2/v4/token';
        $curlPost = 'client_id=' . $this->client_id . '&redirect_uri=' . $this->redirect_url . '&client_secret=' . $this->client_secret . '&code=' . $code . '&grant_type=authorization_code';

        $data = $this->customPost($url, $curlPost);
        return json_decode($data, true);
    }


    private function customPost($method_url, $post_array)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $method_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $post_array,
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}