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
class VarreduraCompleta{

    private $usuarios;
    private $solicitante;
    
    private $reduzido;
    
    function __construct($usuarios,$solicitante,$reduzido) {
        $this->usuarios = $usuarios;
        $this->solicitante = $solicitante;
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
                    
                    $p = new ProblemaCFG();
                    $p->id_empresa = $this->solicitante->id;
                    $p->tipo = $problema;
                    $p->usuario = $us;
                    $p->merge($con);
                    
                }
                
            }
            
        }
        
    }
    
    public function start(){
        $this->run();
    }

}
