<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Boleto
 *
 * @author T-Gamer
 */
class Cheque extends FormaPagamento{
    
    function __construct() {
        
        $this->id = 4;
        $this->nome = "Cheque";
        
    }
    
}
