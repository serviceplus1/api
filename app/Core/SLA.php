<?php

namespace App\Core;

class SLA
{

    private static $timezone;
    private static $horas_uteis;
    private static $inicio_util;
    private static $final_util;
    private static $sabado;
    private static $horas_sabado;
    private static $domingo;
    private static $horas_domingo;

    public function __construct()
    {

        self::$timezone = "America/Sao_Paulo";
        self::$horas_uteis = 9;
        self::$inicio_util = "08:00:00";
        self::$final_util = "18:00:00";
        self::$sabado = false;
        self::$horas_sabado = 4;
        self::$domingo = false;
        self::$horas_domingo = 4;
    }

    /**
     * Set the value of timezone
     *
     * @return  self
     */
    public function setTimezone($timezone)
    {
        self::$timezone = $timezone;

        return new self;
    }

    /**
     * Set the value of horas_uteis
     *
     * @return  self
     */
    public function setHorasUteis($horas_uteis)
    {
        self::$horas_uteis = $horas_uteis;

        return new self;
    }

    /**
     * Set the value of inicio_util
     *
     * @return  self
     */
    public function setInicioUtil($inicio_util)
    {
        self::$inicio_util = $inicio_util;

        return new self;
    }

    /**
     * Set the value of final_util
     *
     * @return  self
     */
    public function setFinalUtil($final_util)
    {
        self::$final_util = $final_util;

        return new self;
    }

    /**
     * Set the value of sabado
     *
     * @return  self
     */
    public function setSabado($sabado)
    {
        self::$sabado = $sabado;

        return new self;
    }

    /**
     * Set the value of horas_sabado
     *
     * @return  self
     */
    public function setHorasSabado($horas_sabado)
    {
        self::$horas_sabado = $horas_sabado;

        return new self;
    }

    /**
     * Set the value of domingo
     *
     * @return  self
     */
    public function setDomingo($domingo)
    {
        self::$domingo = $domingo;

        return new self;
    }

    /**
     * Set the value of horas_domingo
     *
     * @return  self
     */
    public function setHorasDomingo($horas_domingo)
    {
        self::$horas_domingo = $horas_domingo;

        return new self;
    }

    private static function feriados($ano,$posicao)
    {

        date_default_timezone_set(self::$timezone);

        $dia           = 86400;
        $pascoa        = easter_date($ano);
        $sexta_santa   = $pascoa - (2 * $dia);
        $carnaval      = $pascoa - (47 * $dia);
        $corpus_cristi = $pascoa + (60 * $dia);

        $feriados = array (
            '01/01', // ano novo
            date('d/m',$carnaval),
            date('d/m',$sexta_santa),
            date('d/m',$pascoa),
            '21/04', // tiradentes
            '01/05', // dia do trabalho
            date('d/m',$corpus_cristi),
            '07/09', // indepêndencia
            '12/10', // nossa sra aparecida
            '02/11', // finados
            '15/11', // proclamação da república
            '25/12', // natal
        );

        if (in_array($posicao, $feriados))  return true;
        else                                return false;
    }

    public static function calculate($inicio, $final, $util=false, $format="segundos")
    {

        new SLA();

        date_default_timezone_set(self::$timezone);

        $dti = explode(" ", $inicio);
        $data_inicial = $dti[0];
        $hora_inicial = $dti[1];

        $dtf = explode(" ", $final);
        $data_final = $dtf[0];
        $hora_final = $dtf[1];

        if ($util) {

            if ( $data_final==$data_inicial ) {

                $total = strtotime($hora_final) - strtotime($hora_inicial);

            } else {

                $dias     = (int)0;
                $domingos = (int)0;
                $sabados  = (int)0;
                $feriados = (int)0;
                $datai = date ("Y-m-d", strtotime("+1 day", strtotime($data_inicial)));
                $dataf = date ("Y-m-d", strtotime("-1 day", strtotime($data_final)));

                while (strtotime($datai) <= strtotime($dataf)) {

                    $dia_semana = date('w', strtotime($datai));
                    $feriado    = self::feriados(date('Y', strtotime($datai)), date('d/m', strtotime($datai)));

                    if ($dia_semana==0 && self::$domingo==1) {

                        $domingos++;
                    }
                    elseif ($dia_semana==6 && self::$sabado==1) {

                        $sabados++;
                    }
                    elseif ($feriado==1) {

                        $feriados++;
                    }
                    else {

                        $dias++;
                    }

                    $datai = date ("Y-m-d", strtotime("+1 day", strtotime($datai))); // incremento
                }

                $segundos_uteis_dias = (int)$dias * (self::$horas_uteis*3600);
                $segundos_uteis_domingos = (int)$domingos * (self::$horas_domingo*3600);
                $segundos_uteis_sabados = (int)$sabados * (self::$horas_sabado*3600);

                // if (date('w', strtotime($data_inicial))==6) {

                //    $total_first = strtotime('12:00:00') - strtotime($hora_inicial);

                // } else {

                    if (strtotime($hora_inicial) < strtotime(self::$final_util)) {

                        $total_first = strtotime(self::$final_util) - strtotime($hora_inicial);

                    } else{

                        $total_first = 0;
                    }

                // }


                if (strtotime($hora_final) > strtotime(self::$inicio_util)) {

                    $total_last  = strtotime($hora_final) - strtotime(self::$inicio_util);

                } else{

                    $total_last = 0;
                }

                $total = $segundos_uteis_dias + $segundos_uteis_domingos + $segundos_uteis_sabados + $total_first + $total_last;

            }

        } else {

            $total = strtotime($data_final.' '.$hora_final) - strtotime($data_inicial.' '.$hora_inicial);
        }

        // $total = $total<0 ? 0 : $total;

        if ($format=="horas") {
            $horas = floor($total / 3600);
            $minutos = floor(($total - ($horas * 3600)) / 60);
            $segundos = floor($total % 60);

            return $horas.":".str_pad($minutos, 2, 0, STR_PAD_LEFT).":".str_pad($segundos, 2, 0, STR_PAD_LEFT);
        }

        return $total;
    }

}
