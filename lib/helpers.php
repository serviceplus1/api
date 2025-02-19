<?php
/**
 * Funcões do Sistema
 */
function printa($array, $die=false) {

    echo '<pre style="background:#fafafa;padding:20px;">';
        print_r($array);
    echo '</pre>';
    if ($die)
        die;
}

function dd($a)
{
    var_dump($a);
    die;
}

function storage()
{
    return URL_BASE. "/storage/";
}

function is_email(string $email):bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function is_json($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

function datebr($data): string
{
    if ($data=="now") $data = date("Y-m-d");
    return date("d/m/Y", strtotime($data));
}

function datetimebr($data, $sec=false, $format=null): string
{
    if ($data=="now") $data = date($format??"Y-m-d H:i:s");
    $format = $format ?? 'd/m/Y H:i'.($sec==true?':s':'');
    return date($format, strtotime($data));
}

function timebr($hour, $sec=false): string
{
    if ($hour=="now") $hour = date("H:i:s");
    $format = 'H:i'.($sec==true?':s':'');
    return date($format, strtotime($hour));
}

function format_date($date)
{
    return implode('-',array_reverse(explode('/', trim($date))));
}

function asset(string $path, $time = true): string
{
    $file = URL_BASE. "/public/assets/{$path}";
    $fileOnDir = dirname(__DIR__, 1)."/public/assets/{$path}";
    if ($time && file_exists($fileOnDir)) {
        $file .= "?time=".filemtime($fileOnDir);
    }
    return $file;
}

function lists($name)
{
    ini_set("allow_url_include", "1");
    $file = URL_BASE . "/storage/lists/" . $name . ".list";
    return parse_ini_file( $file );
}

function input_upper($field, $upper)
{
    if (in_array($field,$upper) || (isset($upper[0]) && $upper[0]=="*")){
        return "input-upper";
    }
}

function is_upper($field, $upper)
{
    return in_array($field,$upper) || (isset($upper[0]) && $upper[0]=="*");
}

function only_numbers($text)
{
    return preg_replace('/[^\d]/', '', $text);
}

function cpf($cpf)
{
    return substr($cpf, 0, 3). '.' .
           substr($cpf, 3, 3). '.' .
           substr($cpf, 6, 3). '-' .
           substr($cpf, 9, 2);
}

function int2cnpj($valor){

    return substr($valor, 0, 2). '.' .
           substr($valor, 2, 3). '.' .
           substr($valor, 5, 3). '/' .
           substr($valor, 8, 4). '-' .
           substr($valor, 12, 2);
}

function decimal2float($valor)
{
    return str_replace(",", ".", $valor);
}

function money2float($valor)
{
    return str_replace(",", ".", str_replace(".", "", $valor));
}

function moedabr($valor)
{
    return number_format($valor, 2, ",", ".");
}

function format_percent($valor)
{
    return number_format($valor, 1, ",", "");
}

function age($date){

    $time = strtotime($date);
    if($time === false){
      return '';
    }

    $year_diff = '';
    $date = date('Y-m-d', $time);
    list($year,$month,$day) = explode('-',$date);
    $year_diff = date('Y') - $year;
    $month_diff = date('m') - $month;
    $day_diff = date('d') - $day;
    if ($day_diff < 0 || $month_diff < 0) $year_diff--;

    return $year_diff;
}

function codeurl($texto) {

    $array1 = array('-', 'ª','á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','º','ó','ò','ô','õ','ö','ú','ù','û','ü','ç','ñ','Á','À','Â','Ã','Ä','É','È','Ê','Ë','Í','Ì','Î','Ï','Ó','Ò','Ô','Õ','Ö','Ú','Ù','Û','Ü','Ç','Ñ');
    $array2 = array('', 'a','a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','o','u','u','u','u','c','n','A','A','A','A','A','E','E','E','E','I','I','I','I','O','O','O','O','O','U','U','U','U','C','N');
    $texto  = str_replace( $array1, $array2, $texto);
    $texto  = preg_replace('/( )/i','-',$texto);
    $texto  = preg_replace('/[^a-z0-9\-]/i','',$texto);
    $texto  = preg_replace('/--/i','-',$texto);
    return strtolower($texto);
}

function base64ToImage($base64_string, $output_file) {
    $file = fopen($output_file, "wb");
    $data = explode(',', $base64_string);
    fwrite($file, base64_decode($data[1]));
    fclose($file);
    return $output_file;
}

function valorPorExtenso( $valor = 0, $bolExibirMoeda = true, $bolPalavraFeminina = false ) {

    // $valor = self::removerFormatacaoNumero( $valor );

    $singular = null;
    $plural = null;

    if ( $bolExibirMoeda )
    {
        $singular = array("centavo", "real", "mil", "milhão", "bilhão", "trilhão", "quatrilhão");
        $plural = array("centavos", "reais", "mil", "milhões", "bilhões", "trilhões","quatrilhões");
    }
    else
    {
        $singular = array("", "", "mil", "milhão", "bilhão", "trilhão", "quatrilhão");
        $plural = array("", "", "mil", "milhões", "bilhões", "trilhões","quatrilhões");
    }

    $c = array("", "cem", "duzentos", "trezentos", "quatrocentos","quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos");
    $d = array("", "dez", "vinte", "trinta", "quarenta", "cinquenta","sessenta", "setenta", "oitenta", "noventa");
    $d10 = array("dez", "onze", "doze", "treze", "quatorze", "quinze","dezesseis", "dezesete", "dezoito", "dezenove");
    $u = array("", "um", "dois", "três", "quatro", "cinco", "seis","sete", "oito", "nove");


    if ( $bolPalavraFeminina )
    {

        if ($valor == 1)
        {
            $u = array("", "uma", "duas", "três", "quatro", "cinco", "seis","sete", "oito", "nove");
        }
        else
        {
            $u = array("", "um", "duas", "três", "quatro", "cinco", "seis","sete", "oito", "nove");
        }


        $c = array("", "cem", "duzentas", "trezentas", "quatrocentas","quinhentas", "seiscentas", "setecentas", "oitocentas", "novecentas");


    }


    $z = 0;

    $valor = number_format( $valor, 2, ".", "." );
    $inteiro = explode( ".", $valor );

    for ( $i = 0; $i < count( $inteiro ); $i++ )
    {
        for ( $ii = mb_strlen( $inteiro[$i] ); $ii < 3; $ii++ )
        {
            $inteiro[$i] = "0" . $inteiro[$i];
        }
    }

    // $fim identifica onde que deve se dar junção de centenas por "e" ou por "," ;)
    $rt = null;
    $fim = count( $inteiro ) - ($inteiro[count( $inteiro ) - 1] > 0 ? 1 : 2);
    for ( $i = 0; $i < count( $inteiro ); $i++ )
    {
        $valor = $inteiro[$i];
        $rc = (($valor > 100) && ($valor < 200)) ? "cento" : $c[$valor[0]];
        $rd = ($valor[1] < 2) ? "" : $d[$valor[1]];
        $ru = ($valor > 0) ? (($valor[1] == 1) ? $d10[$valor[2]] : $u[$valor[2]]) : "";

        $r = $rc . (($rc && ($rd || $ru)) ? " e " : "") . $rd . (($rd && $ru) ? " e " : "") . $ru;
        $t = count( $inteiro ) - 1 - $i;
        $r .= $r ? " " . ($valor > 1 ? $plural[$t] : $singular[$t]) : "";
        if ( $valor == "000")
            $z++;
        elseif ( $z > 0 )
            $z--;

        if ( ($t == 1) && ($z > 0) && ($inteiro[0] > 0) )
            $r .= ( ($z > 1) ? " de " : "") . $plural[$t];

        if ( $r )
            $rt = $rt . ((($i > 0) && ($i <= $fim) && ($inteiro[0] > 0) && ($z < 1)) ? ( ($i < $fim) ? ", " : " e ") : " ") . $r;
    }

    $rt = mb_substr( $rt, 1 );

    return($rt ? trim( $rt ) : "zero");

}

function nome_mes($mes, $len=null) {

    $meses = [
                "01"=>"Janeiro",
                "02"=>"Fevereiro",
                "03"=>"Março",
                "04"=>"Abril",
                "05"=>"Maio",
                "06"=>"Junho",
                "07"=>"Julho",
                "08"=>"Agosto",
                "09"=>"Setembro",
                "10"=>"Outubro",
                "11"=>"Novembro",
                "12"=>"Dezembro"
    ];

    return $len ? substr($meses[$mes], 0, $len) : $meses[$mes];
}

function dia_semana($dia, $len=null) {

    $dias = array(0=>"Domingo", 1=>"Segunda-Feira", 2=>"Terça-Feira", 3=>"Quarta-Feira", 4=>"Quinta-Feira", 5=>"Sexta-Feira", 6=>"Sábado");
    if ($len) return substr($dias[$dia], 0, $len);
    else      return $dias[$dia];
}

function PrimeiroDiaSemana() {

    $dia_da_semana = date('w');
    $dia = date('d');
    $mes = date('m');
    $ano = date('Y');
    $primeiro_dia = mktime ( 0, 0, 0, $mes, $dia - $dia_da_semana, $ano );
    return strftime("%Y-%m-%d", $primeiro_dia);
}

function UltimoDiaSemana() {

    $dia_da_semana = date('w');
    $dia = date('d');
    $mes = date('m');
    $ano = date('Y');
    $somar_dias = 6 - $dia_da_semana;
    $ultimo_dia   = mktime ( 0, 0, 0, $mes, $dia + $somar_dias, $ano );
    return strftime("%Y-%m-%d", $ultimo_dia);
}

function calculaDias($data_final, $data_inicial=null) {

    $data_inicial = ($data_inicial) ? implode('-',array_reverse(explode('/', $data_inicial))) : date('Y-m-d');
    $data_final   = implode('-',array_reverse(explode('/', $data_final)));

    // Calcula a diferença em segundos entre as datas
    $diferenca = strtotime($data_final) - strtotime($data_inicial);

    //Calcula a diferença em dias
    $dias = (int)floor( $diferenca / (60 * 60 * 24));

    return $dias;
}

function isMobile() {

    $useragent=$_SERVER['HTTP_USER_AGENT'];
    if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4)))
        return true;
    else
        return false;
}

function dateRange($first, $last, $step = '+1 day', $format = 'd/m/Y' ) {

    $dates   = array();
    $current = strtotime($first);
    $last    = strtotime($last);

    while( $current <= $last ) {

        $dates[] = date($format, $current);
        $current = strtotime($step, $current);
    }

    return $dates;
}

function base64_urlencode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64_urldecode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

function mostraTempo($date) {

    $data_inicial = date('Y-m-d H:i:s');
    $data_final   = $date;

    $segundos = strtotime($data_inicial) - strtotime($data_final);

    $minutos  = round($segundos / 60);
    $horas    = round($minutos / 60);
    $dias     = round($horas / 24);
    $semanas  = round($dias / 7);
    $meses    = round($dias / 30);
    $anos     = round($dias / 365);

    if ($segundos<60)                    $retorno = $segundos.' segundos atrás';
    else if ($minutos==1)                $retorno = $minutos.' minuto atrás';
    else if ($minutos>1 && $minutos<60)  $retorno = $minutos.' minutos atrás';
    else if ($horas==1)                  $retorno = $horas.' hora atrás';
    else if ($horas>1 && $horas<24)      $retorno = $horas.' horas atrás';
    else if ($dias==1)                   $retorno = $dias.' dia atrás';
    else if ($dias>1 && $dias<7)         $retorno = $dias.' dias atrás';
    else if ($semanas==1)                $retorno = $semanas.' semana atrás';
    else if ($semanas>1 && $semanas<4)   $retorno = $semanas.' semanas atrás';
    else if ($meses==1)                  $retorno = $meses.' mês atrás';
    else if ($meses>1 && $meses<12)      $retorno = $meses.' meses atrás';
    else if ($anos==1)                   $retorno = $anos.' ano atrás';
    else if ($anos>1)                    $retorno = $anos.' anos atrás';

    return $retorno;
}

function parseHeaders( $headers ) {

    $head = array();
    foreach( $headers as $k=>$v )
    {
        $t = explode( ':', $v, 2 );
        if( isset( $t[1] ) )
            $head[ trim($t[0]) ] = trim( $t[1] );
        else
        {
            $head[] = $v;
            if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
                $head['reponse_code'] = intval($out[1]);
        }
    }
    return $head;
}

function preenche($texto, $num=5, $caracter='0', $side='left') {

    if ($side=='left')      return str_pad($texto, $num, $caracter, STR_PAD_LEFT);
    else if ($side=='both') return str_pad($texto, $num, $caracter, STR_PAD_BOTH);
    else                    return str_pad($texto, $num, $caracter); // RIGHT default

}

function sec2hour($seconds, $sec=true, $sepH=":", $sepM=":") {

    $horas    = floor($seconds / 3600);
    $minutos  = floor(($seconds - ($horas * 3600)) / 60);
    $segundos = floor($seconds % 60);

    return str_pad($horas, 2, 0, STR_PAD_LEFT)
           .$sepH
           .str_pad($minutos, 2, 0, STR_PAD_LEFT)
           .($sec==true ? $sepM : "")
           .($sec==true ? str_pad($segundos, 2, 0, STR_PAD_LEFT) : "");
}

function reArrayFiles(&$file_post) {

    $file_ary = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);

    for ($i=0; $i<$file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$i][$key] = $file_post[$key][$i];
        }
    }

    return $file_ary;

}

function show_link($texto, $blank=true) {

    if (!is_string ($texto))
        return $texto;

     $texto = str_replace("https://", "", str_replace("http://", "", $texto));

     $er = "/(http:\/\/(www\.|.*?\/)?|www\.)([a-zA-Z0-9]+|_|-)+(\.(([0-9a-zA-Z]|-|_|\/|\?|=|&)+))+/i";
     preg_match_all ($er, $texto, $match);

     foreach ($match[0] as $link) {

         //coloca o 'http://' caso o link não o possua
         $link_completo = (stristr($link, "http://") === false) ? "http://" . $link : $link;

         $link_len = strlen ($link);

         //troca "&" por "&", tornando o link válido pela W3C
         $web_link = str_replace ("&", "&amp;", $link_completo);

         $target = ($blank==true) ? 'target="_blank"' : '';

         $texto = str_ireplace ($link, '<a href="' . strtolower($web_link) . '" '.$target.'>'. (($link_len > 60) ? substr ($web_link, 0, 25). '...'. substr ($web_link, -15) : $web_link) .'</a>', $texto);

         return $texto;
     }

}

function diasRestantes($date) {

    $data_final = $date;
    $data_inicial = date('Y-m-d');

    $time_inicial = strtotime($data_inicial);
    $time_final   = strtotime($data_final);
    // Calcula a diferença de segundos entre as duas datas:
    $diferenca = $time_final - $time_inicial; // 19522800 segundos
    // Calcula a diferença de dias
    $dias = (int)floor( $diferenca / (60 * 60 * 24)); // 225 dias

    return $dias;
}

function linkWhatsapp($number, $text="Olá!") {

    $fone = '55'.preg_replace("/[^0-9]/", "", $number);

    $useragent=$_SERVER['HTTP_USER_AGENT'];
    if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| ||a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))) {

        return "https://api.whatsapp.com/send?phone=".$fone."&text=".$text;

    } else {

        return "https://web.whatsapp.com/send?phone=".$fone."&text=".$text;
    }
}

function welcome($nome) {
    $hora = date("H");
    $diasemana = date('w');
    $dia = date('d');
    $mes = date('n');
    $ano = date ('Y');

    $dias_da_semana = [0=> "Domingo", 1=>"Segunda-Feira", 2=>"Terça-Feira", 3=>"Quarta-Feira", 4=>"Quinta-Feira", 5=>"Sexta-Feira", 6=>"Sábado"];
    $meses_do_ano   = [1=>"Janeiro", 2=>"Fevereiro", 3=>"Março", 4=>"Abril", 5=>"Maio", 6=>"Junho", 7=>"Julho", 8=>"Agosto", 9=>"Setembro", 10=>"Outubro", 11=>"Novembro", 12=>"Dezembro"];

    if (($hora >= 0) && ($hora < 12)) echo "Bom dia, ";
    else if (($hora >= 12) && ($hora < 18)) echo "Boa tarde, ";
    else echo "Boa Noite, ";
    echo $nome;
    echo " | ".$dias_da_semana[$diasemana].", ".$dia." de ".$meses_do_ano[$mes]." de ".$ano;
}

function birthday($date) {

    $dia = date('d', strtotime($date));
    $mes = date('m', strtotime($date));

    if ($dia==date('d') && $mes==date('m')) return true;
    else 									return false;
}

function imagemVimeo($link, $tamanho = 'thumbnail_medium'){

    if (preg_match_all('/^http[s]?:\/\/vimeo\.com\/([0-9]+)[\/]?$/', $link, $saida)){

      @$retornoApi = file_get_contents("http://vimeo.com/api/v2/video/".$saida[1][0].".php");

      if ($retornoApi){

        $video = unserialize($retornoApi);

        if (is_array($video)){

          if (isset($video[0][$tamanho])){

            return $video[0][$tamanho];
          }
        }
      }
    }

    return 'http://seudominio.com.br/link-img-error.jpg';
}

function calculaRaiz($numero, $grau){
    return pow($numero, (1/$grau));
}

function capitalize($texto) {

    $words = explode(" ", $texto);
    $array = array('DE', 'DA', 'EM', 'NA', 'NO', "WEB");

    for ($i=0; $i<count($words); $i++) {

        // if (array_search($words[$i], $array, true)) {

        //     $words[$i] = mb_strtolower($words[$i], "UTF-8");
        // }
        // else if (strlen($words[$i]) > 3) {

            // $words[$i] = mb_strtolower($words[$i], "UTF-8");
            $words[$i] = mb_convert_case($words[$i], MB_CASE_TITLE, 'UTF-8');

        // }
    };
    return implode(' ', $words);
}

function dateDiff( $dateStart, $dateEnd, $format = '%a'){

    try{

        $d1 = new \DateTime($dateStart);
        $d2 = new \DateTime($dateEnd);

        //Calcula a diferença entre as datas
        $diff = $d1->diff($d2);

        //Formata no padrão esperado e retorna
        return $diff->format($format);

    }catch(Exception $e){
        echo $e->getMessage();
    }
}

function dateAtual() {
    return date('Y-m-d H:i:s');
}

function __output_header__($__success = true, $__message = null, $_dados = array()){
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        array(
            'success' => $__success,
            'message' => $__message,
            'dados'   => $_dados
        )
    );
    # por ser a ultima funcao, podemos matar o processo aqui.
    exit;
}

function response_json($data, $code=200, $iso="utf-8", $option=JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) {

    header_remove();

    header('Access-Control-Allow-Origin: *');

    if ($iso)
        header("Content-type: application/json; charset=".$iso);
    else
        header("Content-type: application/json");

    http_response_code($code);
    echo json_encode($data, $option);
    exit();
}

function unauthorized() {
    response_json(["message"=>"Unauthorized"], 401);
}

function time_left($seconds) {

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

function distancia($origem, $destino, $unidade="km") {

    $lat1 = explode(",", $origem)[0];
    $lon1 = explode(",", $origem)[1];

    $lat2 = explode(",", $destino)[0];
    $lon2 = explode(",", $destino)[1];

    $lat1 = deg2rad($lat1);
    $lat2 = deg2rad($lat2);
    $lon1 = deg2rad($lon1);
    $lon2 = deg2rad($lon2);

    $dist = (6371 * acos( cos( $lat1 ) * cos( $lat2 ) * cos( $lon2 - $lon1 ) + sin( $lat1 ) * sin($lat2) ) );
    $dist = number_format($dist, 2, '.', '');

    if ($unidade=="km")
        return $dist;
    elseif ($unidade=="m")
        return $dist * 1000;

}

function remove_acentos($texto) {

    $array1 = array('á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç','ñ','Á','À','Â','Ã','Ä','É','È','Ê','Ë','Í','Ì','Î','Ï','Ó','Ò','Ô','Õ','Ö','Ú','Ù','Û','Ü','Ç','Ñ');
    $array2 = array('a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','n','A','A','A','A','A','E','E','E','E','I','I','I','I','O','O','O','O','O','U','U','U','U','C','N');
    $texto  = str_replace( $array1, $array2, $texto);
    return $texto;
}

function hashtag($text, $link=null) {

    $replace = $link ? '<a href="'.$link.'%23\2">#\2</a>' : '<span>#\2</span>';

    $text = preg_replace('/(^|\s)#(\w*[a-zA-Z_]+\w*)/', '\1'.$replace, $text);

    return $text;

}

function users($text, $link=null) {

    $replace = $link ? '<a href="'.$link.'%23\2">@\2</a>' : '<span>@\2</span>';

    $text = preg_replace('/(^|\s)@(\w*[a-zA-Z_]+\w*)/', '\1'.$replace, $text);

    return $text;

}

function rearrangeFiles( $arr ) {
    foreach( $arr as $key => $all ) {
        foreach( $all as $i => $val ) {
            $new[$i][$key] = $val;
        }
    }
    return $new;
}

function limitaTexto($text, $len, $s="...", $start=0) {

    $texto  = substr($text, $start, $len);
    $texto .= strlen($text)>$len ? $s : '';
    return $texto;
}

function colorIsLight($hexa) {

    $hexa  = str_replace("#", "", trim($hexa));
    $longo = strlen($hexa) > 3;

    $r = $longo ? hexdec(substr($hexa, 0, 2)) : hexdec(substr($hexa, 0, 1)) * 17;
    $g = $longo ? hexdec(substr($hexa, 3, 2)) : hexdec(substr($hexa, 1, 1)) * 17;
    $b = $longo ? hexdec(substr($hexa, 5, 2)) : hexdec(substr($hexa, 2, 1)) * 17;

    $luminosidade = ($r * 299 + $g * 587 + $b * 114) / 1000;

    if ($luminosidade > 128) {
        return true;
    } else {
        return false;
    }
}

// Agrupar array multidimensional
function array_group_by($key, $haystack) {
    $result = array();
    $haystack = (array)$haystack;
    foreach($haystack as $val) {
        if(array_key_exists($key, $val)){
            $result[$val[$key]][] = $val;
        }else{
            $result[""][] = $val;
        }
    }
    return $result;
}

function array_unique_mult($array, $key, $reorder=true) {

    $key_array = [];
    $i = 0;

    foreach ($array as $val) {

        if (!in_array($val[$key], $key_array)) {

            $key_array[] = $val[$key];

            if ($reorder) $temp_array[] = $val;
            else          $temp_array[$i] = $val;

        }

        $i++;
    }
    return $temp_array;

}

// Remover apenas uma chave de um array
function array_substract($key, $haystack) {

    foreach ($haystack as $a) {

        $new[] = $a[$key];
    }

    return $new;
}

function nreg($array) {

    return isset($array) ? count((array)$array) : 0;
}