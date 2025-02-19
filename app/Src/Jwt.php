<?php
namespace App\Src;

class Jwt
{

    private $config;
    private $key;
    private $header;
    private $payload;

    public function __construct()
    {

        $this->config = Config::get("jwt");

        $this->key = $this->config["public_key"];

        $this->header["typ"] = "JWT";

        $this->header["alg"] = $this->config["alg"];

        // $required_claims = [
        //     'iss', // Issuer
        //     'aud', // Audience
        //     'jti', // JWT ID
        //     'iat', // Issued At
        //     'sub', // Subject
        //     'exp', // Expiration Time
        //     'nbf', // Not Before
        // ];
    }

    public function generate($claims=null, $headers=null)
    {

        $now = time();

        if ($this->config["audience"])
            $this->payload["aud"] = $this->config["audience"];

        if ($this->config["subject"])
            $this->payload["sub"] = $this->config["subject"];

        if ($this->config["not_before"])
            $this->payload["nbf"] = $now + $this->config["not_before"];

        if ($this->config["expires_at"])
            $this->payload["exp"] = $now + $this->config["expires_at"];

        $this->payload["iat"] = $now;
        $this->payload["iss"] = $this->url_origin();
        $this->payload["jti"] = substr(sha1($now), 0, 8);

        if ($claims) {

            $claims = (array)$claims;

            foreach ($claims as $claim => $value) {

                $this->payload[$claim] = $value;
            }
        }

        if ($headers) {

            $headers = (array)$headers;

            foreach ($headers as $header => $value) {

                $this->header[$header] =$value;
            }
        }

        //JSON
        $header = json_encode($this->header);
        $payload = json_encode($this->payload);

        //Base 64
        $header = $this->base64UrlEncode($header);
        $payload = $this->base64UrlEncode($payload);

        //Sign
        $sign = hash_hmac('sha256', $header . "." . $payload, $this->key, true);
        // $sign = base64_encode($sign);
        $sign = $this->base64UrlEncode($sign);

        //Token
        $token = $header . '.' . $payload . '.' . $sign;

        return $token;
    }

    private function parts($token)
    {

        $t = explode(".", $token);

        $parts["header"]  = $t[0];
        $parts["payload"] = $t[1];
        $parts["sign"]    = $t[2];

        return $parts;
    }

    public function validate($token)
    {

        $parts = $this->parts($token);

        if (count($parts)!=3)
            return false;

        if ($parts["header"]===null || $parts["payload"]===null || $parts["sign"]===null)
            return false;

        if (empty($parts["header"]) || empty($parts["payload"]) || empty($parts["sign"]))
            return false;

        // build a signature based on the header and payload using the secret
        $signature = hash_hmac('sha256', $parts["header"] . "." . $parts["payload"], $this->key, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        // verify it matches the signature provided in the token
        if ($base64UrlSignature !== $parts["sign"])
            return false;

        $payload = json_decode($this->base64UrlDecode($parts["payload"]));

        $now = time();

        if (isset($payload->iat) && $payload->iat > $now)
            return false;

        if (isset($payload->nbf) && $payload->nbf > $now)
            return false;

        // Check if this token has expired.
        if (isset($payload->exp) && $now >= $payload->exp)
            return false;

        return true;

    }

    public function getClaims($token, $claim=null)
    {
        $payload = $this->parts($token)["payload"];
        $payload = $this->base64UrlDecode($payload);
        $payload = json_decode($payload);

        if ($claim)
            return $payload->{$claim};
        else
            return $payload;
    }

    public function getHeaders($token, $header=null)
    {
        $headers = $this->parts($token)["header"];
        $headers = $this->base64UrlDecode($headers);
        $headers = json_decode($headers);

        if ($header)
            return $headers->{$header};
        else
            return $headers;
    }

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    private function url_origin()
    {
        $s        = $_SERVER;
        $ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
        $sp       = strtolower( $s['SERVER_PROTOCOL'] );
        $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
        return $protocol . '://' . $s['SERVER_NAME'] . $s['REQUEST_URI'];
    }


}
