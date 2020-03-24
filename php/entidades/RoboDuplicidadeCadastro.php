<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RegraTabela
 *
 * @author Renan
 */
class RoboDuplicidadeCadastro {

    public $dia;
    public $mes;
    public $ano;
    public $hora;
    public $minuto;
    public $segundo;
    public $momento;

    public function __construct() {

        date_default_timezone_set("America/Sao_Paulo");

        $this->momento = round(microtime(true) * 1000);

        $str = explode(':', date('d:m:Y:H:i:s', $this->momento / 1000));
        $this->dia = intval($str[0]);
        $this->mes = intval($str[1]);
        $this->ano = intval($str[2]);
        $this->hora = intval($str[3]);
        $this->minuto = intval($str[4]);
        $this->segundo = intval($str[5]);
    }

    public function executar($con) {
        
        $empresas = Sistema::getEmpresas($con, 'empresa.tipo_empresa=3');
        
        foreach($empresas as $key=>$empresa){
            
            $clientes = $empresa->getEmpresasClientes($con);
           
            foreach($clientes as $keyc=>$cliente){
                
                
                $pessoas = $cliente->getClientes($con,0,20,"cliente.id NOT IN (SELECT idc1 FROM duplicidade_clientes_verificadas)");
                
                foreach($pessoas as $keyp=>$pessoa){
                    
                    $repetidos = $cliente->getClientes($con,0,20,
                            "cliente.id <> $pessoa->id "
                            . "AND ((cliente.cnpj='".$pessoa->cnpj->valor."' "
                            . "AND cliente.cpf='".$pessoa->cpf->valor."') OR cliente.razao_social='". addslashes($pessoa->razao_social)."')");
                    
                    $ps = $con->getConexao()->prepare("INSERT INTO duplicidade_clientes_verificadas(idc1,idc2) VALUES($pessoa->id,0)");
                    $ps->execute();
                    $ps->close();
                    
                    foreach($repetidos as $keyr=>$repetido){
                        
                        $ps = $con->getConexao()->prepare("INSERT INTO duplicidade_clientes_verificadas(idc1,idc2) VALUES($pessoa->id,$repetido->id)");
                        $ps->execute();
                        $ps->close();
                        
                    }
                    
                }
                
                
            }
            
        }
        
    }

}
