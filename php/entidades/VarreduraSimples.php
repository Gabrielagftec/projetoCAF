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
class VarreduraSimples{

    private $usuarios;
    private $reduzido;
    
    function __construct($usuarios,$reduzido) {
        $this->usuarios = $usuarios;
        $this->reduzido = $reduzido;
    }
    
    public function run(){
        
        $con = new ConnectionFactory();
        
        foreach($this->usuarios as $ku=>$usuario){
           
            $problemas = Sistema::getProblemasCFGMaster();
            
            $us = $usuario;
            
            if($this->reduzido){
                $us = $us->getUsuario($con);
            }
            
            foreach($problemas as $kp=>$problema){
                 
                if($problema->estaComProblema($con, $us)){
                    
                    $problema->resolucaoRapida($con, $us);
                    
                }
                
            }
            
        }
        
    }
    
    public function start(){
        $this->run();
    }

}
