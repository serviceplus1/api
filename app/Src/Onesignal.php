<?php
namespace App\Src;

use App\Core\Config;
use ErrorException;

class OneSignal
{

    /** @var Config */
    private static $config;
    private static $token;
    private static $app_id;
    private static $data;
    private static $url;
    private static $base_url;
    private static $players = [];

    public function __construct()
    {

        self::$config = Config::get("onesignal");

        self::$token = self::$config["token"];
        self::$app_id = self::$config["app_id"];

        self::$base_url = "https://onesignal.com/api/v1";

    }

    /**
     * Set the App Id from OneSignal
     *
     * @param  string $app_id
     * @return void
     */
    public function setAppId($app_id)
    {
        self::$app_id = $app_id;
    }

    /**
     * Set the Player Id
     *
     * @param string $players
     * @return void
     */
    public function setPlayerId($id)
    {
        self::$players[] = $id;
    }

    public static function sendNotify($title, $text, $data=[], $url=null, $playerId)
    {
        new OneSignal();

        if (!self::$app_id) {
            throw new ErrorException("The App Id is required");
        }

        if ($playerId) {
            self::setPlayerId($playerId);
        }

        if (empty(self::$players)) {
            throw new ErrorException("The Player Ids is required");
        }

        $headings["en"] = $title;

        $contents["en"] = $text;

        // Fields
        $fields["app_id"] = self::$app_id;
        $fields["include_player_ids"] = self::$players;
        $fields["headings"] = $headings;
        $fields["contents"] = $contents;

        if (!empty($data)) {
            $fields["data"] = $data;
        }

        if ($url) {
            $fields["web_url"] = $url;
        }

        $endpoint = self::$base_url."/notifications";

        $headers = ['Content-Type: application/json; charset=utf-8'];

        self::request($endpoint, $fields, $headers);
    }

    private static function request($url, $data, $headers=[], $method="POST")
    {

        if (!$url)
            die("The url is required!");

        if (!$data)
            die("The fields is required!");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
        }

        switch ($method) {
        case "GET":
            break;
        case "POST":
            curl_setopt($ch, CURLOPT_POST, true);
            break;
        default:
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        if (!is_json($data))
            $fields = json_encode($data);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

}

/* Helpers */
function is_json($string) {
    if (!is_string($string)) {
        return false;
    } else {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
