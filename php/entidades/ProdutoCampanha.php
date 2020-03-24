<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Cidade
 *
 * @author Renan
 */
class ProdutoCampanha {
   
    public $id;
    public $validade;
    public $campanha;
    public $produto;
    public $valor;
    public $limite;
    public $compra0_encomenda1;
    public $valor_boas_vindas;
    public $valor_trator;
    public $limite_boas_vindas;
    public $de;
    
    function __construct() {
        
        $this->id = 0;
        $this->produto = null;
        $this->campanha = null;
        $this->validade = round(microtime(true)*1000);
        $this->valor = 0;
        $this->limite = 0;
        $this->compra0_encomenda1 = 0;
        $this->valor_boas_vindas = 0;
        $this->limite_boas_vindas = 0;
        $this->de = 0;
        $this->valor_trator = 0;

    }

    public function isCompra($con){

        $ps = $con->getConexao()->prepare("SELECT id FROM produto_campanha WHERE id=$this->id AND compra0_encomenda1=0");
        $ps->execute();
        $ps->bind_result($id);
        $r = $ps->fetch();
        $ps->close();

        return $r;

    }
    
    public function merge($con) {

        if ($this->id == 0) {

            $ps = $con->getConexao()->prepare("INSERT INTO produto_campanha(validade,id_campanha,id_produto,valor,limite,compra0_encomenda1,valor_boas_vindas,limite_boas_vindas,de,valor_trator) VALUES(FROM_UNIXTIME($this->validade/1000),".$this->campanha->id.",".$this->produto->codigo.",".$this->valor.",$this->limite,$this->compra0_encomenda1,$this->valor_boas_vindas,$this->limite_boas_vindas,$this->de,$this->valor_trator)");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
            
        }else{
            
            $ps = $con->getConexao()->prepare("UPDATE produto_campanha SET validade = FROM_UNIXTIME($this->validade/1000), id_campanha=".$this->campanha->id.", id_produto=".$this->produto->codigo.", valor=$this->valor, limite = $this->limite, valor_boas_vindas = $this->valor_boas_vindas, limite_boas_vindas = $this->limite_boas_vindas,de=$this->de,valor_trator=$this->valor_trator WHERE id = ".$this->id);
            $ps->execute();
            $ps->close();
            
        }
        
    }
    
    public function delete($con){
        
        $ps = $con->getConexao()->prepare("DELETE FROM produto_campanha WHERE id = ".$this->id);
        $ps->execute();
        $ps->close();
        
    }
    
}
