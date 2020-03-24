<?php


include("apiIncludes.php");

$campos[] = "peso";
$campos[] = "valor";

$parametros = getParametros($campos,$parametros);

$con = new ConnectionFactory();

$emp = new Empresa(1735,$con);

$transportadoras = $emp->getTransportadoras($con,0,1000,"tabela.nome IS NOT NULL");

$fretes = array();

$peso = doubleval($parametros["peso"]);
$valor = doubleval($parametros["valor"]);
$cidade = $parametros["cidadeCliente"];

$cidades = Sistema::getCidades($con,"cidade.nome = '".strtoupper($cidade)."'");

if(count($cidades) == 0){

    echo '{"error":"Cidade nao encontrada no banco de cidades",code:101}';
    exit;

}

$cidade = $cidades[0];

foreach ($transportadoras as $key => $transp) {

    if($transp->tabela !== null){
        $tabela = $transp->tabela;
        if($tabela->atende($cidade,$peso,$valor)){
            $f = new stdClass();
            $f->id_transportadora = $transp->id;
            $f->nome_transportadora = $transp->razao_social;
            $f->valor = round($tabela->valor($cidade,$peso,$valor),2);
            if($f->valor==0)continue;
            $fretes[] = $f;

        }
    }
}

echo Utilidades::toJson($fretes);


?>