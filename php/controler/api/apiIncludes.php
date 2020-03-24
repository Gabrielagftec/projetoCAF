<?php

include("includes.php");

$campos = array("nomeCliente","cidadeCliente","estadoCliente","ieCliente","documentoCliente");

$parametros = array();

function getParametros($campos,$parametros){

    foreach ($campos as $key => $value) {
        if(!isset($_GET[$value])){
            echo '{"erro":"informe o parametro '.$value.'",code:'.(100+$key).'}';
            exit;
        }
        $parametros[$value] = $_GET[$value];
    }
    
    return $parametros;

}

?>