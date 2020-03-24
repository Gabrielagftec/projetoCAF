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
class CartaCorrecao {

    public $id;
    public $nota;
    public $motivo;
    public $protocolo;
    public $data;
    
    function __construct() {
        
        $this->id = 0;
        $this->nota = null;
        $this->motivo = "";
        $this->protocolo = "0";
        $this->data = round(microtime(true)*1000);

    }
 
    public function merge($con){
        
        if($this->id === 0){
            
            $ps = $con->getConexao()->prepare("INSERT INTO carta_correcao(id_nota,motivo,protocolo,data) VALUES(".$this->nota->id.",'".addslashes($this->motivo)."','". addslashes($this->protocolo)."',FROM_UNIXTIME($this->data/1000))");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
            
        }else{
            
            $ps = $con->getConexao()->prepare("UPDATE carta_correcao SET id_nota=".$this->nota->id.", motivo='".addslashes($this->motivo)."', protocolo='".addslashes($this->protocolo)."',data=FROM_UNIXTIME($this->data/1000) WHERE id=$this->id");
            $ps->execute();
            $ps->close();
            
        }
        
    }
    
    
}
