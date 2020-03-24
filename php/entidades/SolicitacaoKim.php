<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Email
 *
 * @author Renan
 */
class SolicitacaoKim {

    public $id;
    public $hash;
    public $matches;
    public $recusada;
    public $atendida;
    
    function __construct() {

        $this->id = 0;
        $this->hash = null;
        $this->matches = 0;
        $this->recusada = false;
        $this->atendida = false;
        
    }
    

}
