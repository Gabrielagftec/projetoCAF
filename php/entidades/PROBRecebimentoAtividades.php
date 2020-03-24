<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Logistica
 *
 * @author Renan
 */
class PROBRecebimentoAtividades extends Problema {

    function __construct() {

        parent::__construct(2,"Definicao de cargo");
        
    }
    
    public static function getTiposAtividade($con,$usuario){
        
        $cargos = $usuario->empresa->getTiposTarefa($con);
        
        return $cargos;
        
    }
    
    public function estaComProblema($con, $usuario) {
       
        return false;
        
    }
    
    /*
     * Cargo no proprio usuario
     */
    public function resolucaoCompleta($con, $usuario, $parametros) {
        
        $parametros->merge($con);
        
        $log = "Cargo do usuario $parametros->nome alterado para ";
        
        $log .= $parametros->cargo->nome;
        
        $ps = $con->getConexao()->prepare("INSERT INTO log_cfg(log,data) VALUES('$log',CURRENT_TIMESTAMP)");
        $ps->execute();
        $ps->close();
        
    }
    
    public function resolucaoRapida($con, $usuario) {
        
        $usuario->merge($con);
        
    }

}
