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
class HistoricoCliente {

    public $id;
    public $historico;
    public $conversas;
    
    function __construct($hist = "") {
        
        $this->id = 0;
        $this->historico = $hist==null?"":$hist;
        
        $this->historico = str_replace(array("\n","\r","\"","\'"), array("<br>","<br>","",""), $this->historico);
        
        $this->conversas = explode("Data -",$this->historico);
        $this->conversas = count($this->conversas)-1;
        
    }
    

}
