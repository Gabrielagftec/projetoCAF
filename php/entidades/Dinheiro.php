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
class Dinheiro extends FormaPagamento{
    
    function __construct() {
        
        $this->id = 3;
        $this->nome = "Dinheiro";
        
    }
    
}
