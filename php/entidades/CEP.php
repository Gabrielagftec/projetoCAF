<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CotacaoEntrada
 *
 * @author Renan
 */
class CEP {

    public $valor;

    function __construct($str = "") {

        $this->valor = str_replace(array("-"), array(""), $str);


        if (strlen($this->valor) != 8) {

            $this->valor = "00000-000";
        } else {

            $this->valor = substr($this->valor, 0, 5) . "-" . substr($this->valor, 5, 3);
        }
    }
    
    public function getEnderecoViaService($con){
        
        $cep = str_replace("-", "", $this->valor);
        
        if(strlen($cep) !== 8){
            
            return null;
            
        }
        
        $e = file_get_contents("https://viacep.com.br/ws/$cep/json/unicode/");
        
        $e = Utilidades::fromJson($e);
        
        if(isset($e->erro)){
            if($e->erro){
                return null;
            }
        }
        
        $end = new Endereco();
        
        $end->bairro = $e->bairro;
        $end->cep = $this;
        $end->rua = $e->logradouro;
        
        $end->numero = 0;
        
        if($e->complemento !== ""){
            
            $end->rua .= " ".$e->complemento;
            
        }
        
        
        $cidades = Sistema::getCidades($con, "cidade.nome like '%".$e->localidade."%' AND estado.sigla like '".$e->uf."'");
        
        if(count($cidades)>0){
            
            $end->cidade = $cidades[0];
            
        }else{
            
            $cidade = Sistema::getCidades($con,"cidade.id=(SELECT MIN(id) FROM cidade)");
            
            $end->cidade = $cidade[0];
            
        }
        
        return $end;
        
    }

}
