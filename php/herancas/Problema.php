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
class Problema {

    public $id;
    public $nome;

    function __construct($id=0,$nome="") {

        $this->id = $id;
        $this->nome = $nome;
    }

    public function estaComProblema($con, $usuario) {
        
        return false;
        
    }

    public function resolucaoRapida($con, $usuario) {
        
        return false;
        
    }

    public function resolucaoCompleta($con,$usuario,$parametros) {

        return false;
        
    }

}
