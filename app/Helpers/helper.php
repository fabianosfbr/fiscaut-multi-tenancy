<?php

use App\Models\Issuer;
use App\Models\PlanoDeConta;
use Illuminate\Support\Carbon;
use Saloon\XmlWrangler\XmlReader;
use Illuminate\Support\Facades\DB;
use NFePHP\NFe\Common\Standardize;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;




function percent($number)
{
    return number_format($number * 100, 2, ',', '.') . ' %';
}

function formatar_moeda($valor)
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function issuersViewHelper()
{
    $issuers = DB::table('users')
        ->join('users_issuers_permissions', 'users.id', '=', 'users_issuers_permissions.user_id')
        ->where('users_issuers_permissions.user_id', Auth::user()->id)
        ->pluck('users_issuers_permissions.issuer_id');

    return $issuers;
}

function getCurrentIssuer()
{
    // $issuer_id = Cache::forever('get-current-issuer', function () {
    //     return Auth::user()->issuer_id;
    // });

    return Auth::user()->issuer_id;
}

function tipoEventoManifesto($evento)
{
    $array = [
        210200 => 'Confirmação da Operação',
        210210 => 'Ciência da Emissão',
        210220 => 'Desconhecimento da Operação',
        210240 => 'Operação não Realizada',
    ];

    return $array[$evento];
}

function tipoDocumentoNaoFiscal($tipo)
{
    $array = [
        1 => 'NFS Tomada',
        2 => 'Fatura',
        3 => 'Boleto',
        4 => 'Nota Débito',
    ];

    return $array[$tipo];
}




function formatar_cnpj_cpf($value)
{
    $CPF_LENGTH = 11;
    $cnpj_cpf = preg_replace("/\D/", '', $value);

    if (strlen($cnpj_cpf) === $CPF_LENGTH) {
        return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", '$1.$2.$3-$4', $cnpj_cpf);
    }

    return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", '$1.$2.$3/$4-$5', $cnpj_cpf);
}

function formatar_cep($value)
{
    return substr($value, 0, 5) . '-' . substr($value, 5, 3);
}

function formatar_telefone($numero)
{
    // primeiro substr pega apenas o DDD e coloca dentro do (), segundo subtr pega os números do 3º até faltar 4, insere o hifem, e o ultimo pega apenas o 4 ultimos digitos
    $number = '(' . substr($numero, 0, 2) . ') ' . substr($numero, 2, -4) . '-' . substr($numero, -4);

    return $number;
}

function dateTimeFormat($date, $format = 'd-m-Y H:i:s')
{
    return (new DateTime($date))->format($format);
}

function xmlNfeToStd($xml)
{
    $st = new Standardize($xml);
    $stdRes = $st->toStd();

    return $stdRes;
}

function xmlNfeToArray($xml)
{
    $st = new Standardize($xml);
    $stdRes = $st->toArray();

    return $stdRes;
}

function getTagValue($theObj, $keyName, $extraTextBefore = '', $extraTextAfter = '', $itemNum = 0)
{
    if (empty($theObj)) {
        return '';
    }
    $vct = $theObj->getElementsByTagName($keyName)->item($itemNum);
    if (isset($vct)) {
        $value = trim($vct->nodeValue);
        if (strpos($value, '&') !== false) {
            //existe um & na string, então deve ser uma entidade
            $value = html_entity_decode($value);
        }

        return $extraTextBefore . $value . $extraTextAfter;
    }

    return '';
}

function getTagDate($theObj, $keyName, $extraText = '')
{
    if (!isset($theObj) || !is_object($theObj)) {
        return '';
    }
    $vct = $theObj->getElementsByTagName($keyName)->item(0);
    if (isset($vct)) {
        $theDate = explode('-', $vct->nodeValue);

        return $extraText . $theDate[2] . '/' . $theDate[1] . '/' . $theDate[0];
    }

    return '';
}

function formatField($campo = '', $mascara = '')
{
    if ($campo == '' || $mascara == '') {
        return $campo;
    }
    //remove qualquer formatação que ainda exista
    $sLimpo = preg_replace("(/[' '-./ t]/)", '', $campo);
    // pega o tamanho da string e da mascara
    $tCampo = strlen($sLimpo);
    $tMask = strlen($mascara);
    if ($tCampo > $tMask) {
        $tMaior = $tCampo;
    } else {
        $tMaior = $tMask;
    }
    //contar o numero de cerquilhas da mascara
    $aMask = str_split($mascara);
    $z = 0;
    $flag = false;
    foreach ($aMask as $letra) {
        if ($letra == '#') {
            $z++;
        }
    }
    if ($z > $tCampo) {
        //o campo é menor que esperado
        $flag = true;
    }
    //cria uma variável grande o suficiente para conter os dados
    $sRetorno = '';
    $sRetorno = str_pad($sRetorno, $tCampo + $tMask, ' ', STR_PAD_LEFT);
    //pega o tamanho da string de retorno
    $tRetorno = strlen($sRetorno);
    //se houve entrada de dados
    if ($sLimpo != '' && $mascara != '') {
        //inicia com a posição do ultimo digito da mascara
        $x = $tMask;
        $y = $tCampo;
        $cI = 0;
        for ($i = $tMaior - 1; $i >= 0; $i--) {
            if ($cI < $z) {
                // e o digito da mascara é # trocar pelo digito do campo
                // se o inicio da string da mascara for atingido antes de terminar
                // o campo considerar #
                if ($x > 0) {
                    $digMask = $mascara[--$x];
                } else {
                    $digMask = '#';
                }
                //se o fim do campo for atingido antes do fim da mascara
                //verificar se é ( se não for não use
                if ($digMask == '#') {
                    $cI++;
                    if ($y > 0) {
                        $sRetorno[--$tRetorno] = $sLimpo[--$y];
                    } else {
                        //$sRetorno[--$tRetorno] = '';
                    }
                } else {
                    if ($y > 0) {
                        $sRetorno[--$tRetorno] = $mascara[$x];
                    } else {
                        if ($mascara[$x] == '(') {
                            $sRetorno[--$tRetorno] = $mascara[$x];
                        }
                    }
                    $i++;
                }
            }
        }
        if (!$flag) {
            if ($mascara[0] != '#') {
                $sRetorno = '(' . trim($sRetorno);
            }
        }

        return trim($sRetorno);
    } else {
        return '';
    }
}



// function getPlanoDeContas($issuer_id)
// {
//     $planoDeContasFiltered =  Cache::remember('plano_de_contas_' . $issuer_id, 1800, function () use ($issuer_id) {
//         $planoDeContas = PlanoDeConta::where('issuer_id', $issuer_id)
//             ->get()
//             ->map(function ($planoDeConta) {
//                 return [
//                     'id' => $planoDeConta->codigo,
//                     'name' => $planoDeConta->codigo . ' | ' . $planoDeConta->nome,
//                     'tipo' => $planoDeConta->tipo,
//                 ];
//             })->toArray();

//         return $planoDeContas;
//     });

//     return $planoDeContasFiltered;
// }






function sanitize(?string $data): ?string
{
    if (is_null($data)) {
        return null;
    }
    return (string) preg_replace('/[^A-Za-z0-9]/', '', $data);
}

function getMesesAnterioresEPosteriores(): array
{
    $meses = [];
    $dataAtual = Carbon::now();


    // Adiciona os 6 meses anteriores
    for ($i = 6; $i > 0; $i--) {
        $mes = $dataAtual->copy()->subMonths($i);
        $chave = $mes->format('Y-m-01'); // Chave no formato '2025-12-01'
        $valor = ucfirst($mes->translatedFormat('F - Y'));
        $meses[$chave] = $valor;
    }

    // Adiciona o mês atual
    $chave = $dataAtual->format('Y-m-01'); // Chave no formato '2025-12-01'
    $valor = ucfirst($dataAtual->translatedFormat('F - Y'));
    $meses[$chave] = $valor;

    // Adiciona os 6 meses posteriores
    for ($i = 1; $i <= 6; $i++) {
        $mes = $dataAtual->copy()->addMonths($i);
        $chave = $mes->format('Y-m-01'); // Chave no formato '2025-12-01'
        $valor = ucfirst($mes->translatedFormat('F - Y'));
        $meses[$chave] = $valor;
    }

    $meses = array_reverse($meses);

    return $meses;
}
