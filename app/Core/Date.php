<?php
namespace App\Core;

use DateInterval;
use DatePeriod;
use DateTime;

class Date
{

    private static $date;
    private static $timezone;
    private static $format;
    private static $format_br;
    private static $lang;
    private static $holidays = [];

    public function __construct()
    {
        self::$timezone = "America/Sao_Paulo";

        self::$format = "Y-m-d H:i:s";
        self::$format_br = "d/m/Y H:i:s";
        self::$lang = "br";

        date_default_timezone_set(self::$timezone);
    }

    public static function setTimezone($timezone=null)
    {

        if ($timezone) {
            self::$timezone = $timezone;
        }

        date_default_timezone_set(self::$timezone);
    }

    public static function setLang($lang=null)
    {
        if ($lang) {
            self::$lang = $lang;
        }
    }

    public static function create($date=null)
    {
        new self;

        if (!$date) {

            return new DateTime("now");

        } else {

            if (is_string($date)) {

                $date = date($date);
            }

            return new DateTime($date);
        }
    }

    public static function set($date=null)
    {

        self::$date = self::create($date);
        return new self;
    }

    public static function now()
    {
        self::$date = new DateTime("now");
        return new self;
    }

    public function setFormat($format)
    {
        if ($format=="br") {
            $format = "d/m/Y H:i:s";
        }

        self::$format = $format;
        return new self;
    }

    public static function addHoliday($day, $month)
    {
        self::$holidays[] = $day."/".$month;
        return new self;
    }

    public function onlyDate()
    {
        return self::$date->format("Y-m-d");
    }

    public function onlyTime($sec=false)
    {

        $format = "H:i".($sec?":s":"");
        return self::$date->format($format);
    }

    public function addDay()
    {
        return self::add(1, "day");
    }

    public function addDays($n)
    {
        return self::add($n, "day");
    }

    public function addMonth()
    {
        return self::add(1, "month");
    }

    public function addMonths($n)
    {
        return self::add($n, "month");
    }

    public function addYear()
    {
        return self::add(1, "year");
    }

    public function addYears($n)
    {
        return self::add($n, "year");
    }

    public function addHour()
    {
        return self::add(1, "hour");
    }

    public function addHours($n)
    {
        return self::add($n, "hour");
    }

    public function addMinute()
    {
        return self::add(1, "minute");
    }

    public function addMinutes($n)
    {
        return self::add($n, "minute");
    }

    public function addSecond()
    {
        return self::add(1, "second");
    }

    public function addSeconds($n)
    {
        return self::add($n, "second");
    }

    public function subDay()
    {
        return self::subtract(1, "day");
    }

    public function subDays($n)
    {
        return self::subtract($n, "day");
    }

    public function subMonth()
    {
        return self::subtract(1, "month");
    }

    public function subMonths($n)
    {
        return self::subtract($n, "month");
    }

    public function subYear()
    {
        return self::subtract(1, "year");
    }

    public function subYears($n)
    {
        return self::subtract($n, "year");
    }

    public function subHour()
    {
        return self::subtract(1, "hour");
    }

    public function subHours($n)
    {
        return self::subtract($n, "hour");
    }

    public function subMinute()
    {
        return self::subtract(1, "minute");
    }

    public function subMinutes($n)
    {
        return self::subtract($n, "minute");
    }

    public function subSecond()
    {
        return self::subtract(1, "second");
    }

    public function subSeconds($n)
    {
        return self::subtract($n, "second");
    }

    public static function diff($start, $end=null)
    {

        $start = self::create($start);
        $end = self::create($end);

        self::$date = $start->diff($end);

        return new self;
    }

    public function print()
    {
        return self::$date;
    }

    public function format($format=null)
    {

        if ($format) {
            self::setFormat($format);
        }

        return self::$date->format(self::$format);
    }

    public static function diffInSeconds($start, $end=null)
    {

        new self;

        $start = self::toStr($start);
        if ($end) {
            $end = self::toStr($end);
        } else {
            $end = self::toStr(date("Y-m-d H:i:s"));
        }

        $diff = $end - $start;

        return $diff;
    }

    /**
     * Função que verifica se a data é final de semana
     *
     * @return boolean
     */
    public function isWeekend()
    {
        return self::$date->format('N') >= 6;
    }

    /**
     * Função que verifica se é feriado nacional ou não
     *
     * @return boolean
     */
    public function isHoliday()
    {

        $d = self::$date->format("d");
        $m = self::$date->format("m");
        $y = self::$date->format("Y");

        self::holidays($y);

        return in_array($d."/".$m, self::$holidays);
    }

    /**
     * Retorna se é um dia útil de trabalho
     * Regra: Não ser final de semana, e não ser feriado
     *
     * @return boolean
     */
    public function isBusinessDay()
    {
        return !self::isWeekend() && !self::isHolyday();
    }

    /**
     * Retorna todas as datas entre o período, de acordo com o intervalo e a periodicidade
     *
     * @param string $start
     * @param string $end
     * @param integer $interval
     * @param string $period
     * @return void
     */
    public static function interval($start, $end, $interval=1, $period="day", $format="Y-m-d")
    {

        $dateStart = self::create($start);
        $dateEnd = self::create($end)->modify("+1 day");

        while ($dateStart <= $dateEnd) {

            $dateRange[] = $dateStart->format($format);
            $dateStart = $dateStart->modify('+'.$interval.$period);
        }

        return $dateRange;
    }

    /**
     * Retorna todos as datas com uma periodicidade de 1 dia
     * Com a possibilidade de filtrar pelos dias da semana (ex: só segundas. $days=[1])
     *
     * @param string $start
     * @param string $end
     * @param array $days - dias da semana [0 ~ 6]
     * @param string $format
     * @return void
     */
    public static function daysBetween($start, $end, $days=null, $format="Y-m-d")
    {

        $start = self::create($start);
        $end = self::create($end)->modify('+1 day');

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);

        if ($days) {

            $days = (array)$days;

            foreach($period as $date) {

                $day_of_week = $date->format("w");

                if (in_array($day_of_week, $days))
                    $dates[] = $date->format($format);
            }

        } else {

            foreach($period as $date){

                $dates[] = $date->format($format);
            }
        }

        return $dates;
    }

    /**
     * Retorna a data formatada para o Brasil
     * Caso não passada, retornará a data atual
     *
     * @param string $date
     * @param boolean $time
     * @param boolean $sec
     * @return void
     */
    public static function datebr($date=null, $time=false, $sec=true)
    {

        $format = "d/m/Y";
        $format .= $time ? " H:i" : "";
        $format .= $time&&$sec ? ":s" : "";

        if (!$date) {

            return self::create()->format($format);

        } else {

            return self::create($date)->format($format);

        }
    }

    /**
     * Retorna a data e a hora formatada para o Brasil
     * Caso não seja passada, retornará a data e hora atual
     *
     * @param string $date
     * @param boolean $sec
     * @return void
     */
    public static function datetimebr($date=null, $sec=true)
    {
        return self::datebr($date, true, $sec);
    }

    /**
     * Retorna a hora formatada para o Brasil
     * Caso não seja passada, retornará a hora atual
     *
     * @param [type] $time
     * @param boolean $sec
     * @return void
     */
    public static function timebr($time=null, $sec=true)
    {

        $format  = "H:i";
        $format .= $sec ? ":s" : "";

        if (!$time) {

            return self::create()->format($format);

        } else {

            return self::create($time)->format($format);

        }
    }

    /**
     * Retorna o primeiro dia da Semana
     *
     * @param string $date
     * @return void
     */
    public static function firstDayOfWeek($date=null)
    {

        $day_of_week = self::create($date)->format("w");

        $date = self::set($date)->subDays($day_of_week)->format("Y-m-d");

        return $date;

        // $d = date('d');
        // $m = date('m');
        // $y = date('Y');
        // $first_day = mktime(0, 0, 0, $m, $d - $day_of_week, $y);

        // return strftime("%Y-%m-%d", $first_day);
    }

    /**
     * Retorna o último dia da semana
     *
     * @param string $date
     * @return void
     */
    public static function lastDayOfWeek($date=null)
    {

        $day_of_week = self::create($date)->format("w");

        // $d = date('d');
        // $m = date('m');
        // $y = date('Y');
        $sum = 6 - $day_of_week;

        // $last_day = mktime(0, 0, 0, $m, $d + $sum, $y);
        // return strftime("%Y-%m-%d", $last_day);

        $date = self::set($date)->addDays($sum)->format("Y-m-d");

        return $date;

    }

    /**
     * Retorna a Idade de acordo com a data passada
     *
     * @param string $date
     * @return void
     */
    public static function age($date)
    {

        $time = self::toStr($date);

        $year_diff = 0;

        $date = date('Y-m-d', $time);

        list($year, $month, $day) = explode('-', $date);

        $year_diff = date('Y') - $year;
        $month_diff = date('m') - $month;
        $day_diff = date('d') - $day;

        if ($day_diff < 0 || $month_diff < 0) $year_diff--;

        return $year_diff;
    }

    /**
     * Retorna a string de quanto tempo falta
     *
     * @param int $seconds
     * @return void
     */
    public function timeLeft($seconds)
    {

        if ($seconds<0) {

            $time = "0 segundo";
        }
        elseif ($seconds<60) {

            $time = $seconds.' '.($seconds>1?'segundos':'segundo');
        }
        elseif ($seconds<3600) {

            $minutes = round($seconds/60);
            $time = $minutes.' '.($minutes>1?'minutos':'minuto');
        }
        else {

            $hours   = floor($seconds / 3600);
            $minutes = floor(($seconds - ($hours * 3600)) / 60);

            $time  = $hours.' '.($hours>1?'horas':'hora');
            $time .= ' e ';
            $time .= $minutes.' '.($minutes>1?'minutos':'minuto');

        }

        return $time;
    }

    /**
     * Retorna em qual dia da semana estamos, de acordo com a data passada
     *
     * @param string|null $date
     * @param integer|null $length
     * @param boolean $feira
     * @return void
     */
    public static function dayOfWeek(string $date=null, int $length=null, bool $feira=true)
    {

        $week = [
            0 => ["br" => "Domingo", "en" =>"Sunday"],
            1 => ["br" => "Segunda".($feira?"-feira":""), "en" => "Monday"],
            2 => ["br" => 'Terça'.($feira?"-feira":""), "en" =>"Tuesday"],
            3 => ["br" => 'Quarta'.($feira?"-feira":""), "en" =>"Wednesday"],
            4 => ["br" => 'Quinta'.($feira?"-feira":""), "en" =>"Thursday"],
            5 => ["br" => 'Sexta'.($feira?"-feira":""), "en" =>"Friday"],
            6 => ["br" => 'Sábado', "en" =>"Saturday"],
        ];

        $d = self::create($date)->format("w");

        if ($length) {
            return substr($week[$d][self::$lang], 0, $length);
        } else {
            return $week[$d][self::$lang];
        }

    }

    /**
     * Retorna o nome do mês
     *
     * @param  string|null $month
     * @param  boolean $length
     * @return void
     */
    public static function monthName($month=null, $length=false)
    {

        $months = [
            "01" => ["br" => "Janeiro", "en" => "January"],
            "02" => ["br" => "Fevereiro", "en" => "February"],
            "03" => ["br" => "Março", "en" => "March"],
            "04" => ["br" => "Abril", "en" => "April"],
            "05" => ["br" => "Maio", "en" => "May"],
            "06" => ["br" => "Junho", "en" => "June"],
            "07" => ["br" => "Julho", "en" => "July"],
            "08" => ["br" => "Agosto", "en" => "August"],
            "09" => ["br" => "Setembro", "en" => "September"],
            "10" => ["br" => "Outubro", "en" => "October"],
            "11" => ["br" => "Novembro", "en" => "November"],
            "12" => ["br" => "Dezembro", "en" => "December"],
        ];

        if (!$month) {
            $month = self::create()->format("m");
        }

        if ($length) {
            return substr($months[$month][self::$lang], 0, $length);
        } else {
            return $months[$month][self::$lang];
        }

    }

    /**
     * Adicionar Período na Data
     */
    private static function add($n, $period)
    {

        // Strtotime
        // $new_date = date(
        //                     self::$format,
        //                     strtotime("+ {$n}{$period}",
        //                         strtotime(self::$date)
        //                     )
        //                 );

        // DateTime
        $new_date = self::$date->modify("+{$n}{$period}");

        return $new_date;
    }

    private static function subtract($n, $period)
    {
        $new_date = self::$date->modify("-{$n}{$period}");
        return $new_date;
    }

    /**
     * Função que verifica todos os feriados nacionais brasileiros
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @return void
     */
    private static function holidays($year)
    {

        $seconds        = 86400; // segundos em um dia
        $pascoa         = easter_date($year);
        $sexta_santa    = $pascoa - (2 * $seconds);
        $carnaval       = $pascoa - (47 * $seconds);
        $corpus_christi = $pascoa + (60 * $seconds);

        $holidays = array(
            '01/01', // ano novo
            date('d/m', $carnaval),
            date('d/m', $sexta_santa),
            date('d/m', $pascoa),
            '21/04', // tiradentes
            '01/05', // dia do trabalho
            date('d/m', $corpus_christi),
            '07/09', // indepêndencia
            '12/10', // nossa sra aparecida
            '02/11', // finados
            '15/11', // proclamação da república
            '25/12', // natal
        );

        self::$holidays = array_merge(self::$holidays, $holidays);

        return new self;
    }

    private static function periods($period)
    {

        $period = strtolower($period);

        switch ($period) {

            case 'd':
            case 'dia':
            case 'dias':
            case 'day':
            case 'days':
                $p = "days";
                break;
            case 'd':

            case 'm':
            case 'mes':
            case 'meses':
            case 'month':
            case 'months':
                $p = "months";
                break;

            case 'a':
            case 'y':
            case 'ano':
            case 'anos':
            case 'year':
            case 'years':
                $p = "years";
                break;

            case 'h':
            case 'hora':
            case 'horas':
            case 'hour':
            case 'hours':
                $p = "hours";
                break;

            case 'i':
            case 'min':
            case 'minuto':
            case 'minutos':
            case 'minute':
            case 'minutes':
                $p = "minutes";
                break;

            case 's':
            case 'seg':
            case 'sec':
            case 'segundo':
            case 'segundos':
            case 'second':
            case 'seconds':
                $p = "seconds";
                break;

            default:
                $p = "days";
                break;
        }

        return $p;

    }

    private static function toStr($date)
    {
        return strtotime($date);
    }


}
