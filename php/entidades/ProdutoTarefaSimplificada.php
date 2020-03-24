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
class ProdutoTarefaSimplificada{
    
    public $id;
    public $tarefa;
    public $produto;
    public $quantidade;
    public $influencia;
    public $valor;
    
    function __construct() {
        
        $this->id = 0;
        $this->tarefa = null;
        $this->produto = null;
        $this->quantidade = 0;
        $this->influencia = 0;
        $this->valor = 0;
        
    }
    
    public function merge($con){
        
        $dif = $this->quantidade-($this->influencia*-1);
        
        $ps = $con->getConexao()->prepare("UPDATE produto SET estoque=estoque-($dif),disponivel=disponivel-($dif) WHERE id=".$this->produto->id);
        $ps->execute();
        $ps->close();
        
        $this->influencia -= $dif;
        
        if($this->id === 0){
        
            $ps = $con->getConexao()->prepare("INSERT INTO produto_tarefa_simplificada(id_tarefa,id_produto,quantidade,influencia,valor) VALUES(".$this->tarefa->id.",".$this->produto->id.",$this->quantidade,$this->influencia,$this->valor)");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
            
        }else{
            
            $ps = $con->getConexao()->prepare("UPDATE produto_tarefa_simplificada SET id_tarefa=".$this->tarefa->id.",id_produto=".$this->produto->id.",quantidade=$this->quantidade,influencia=$this->influencia,valor=$this->valor WHERE id=$this->id");
            $ps->execute();
            $ps->close();
            
        }
        
    }

    public function delete($con){
        
        $ps = $con->getConexao()->prepare("UPDATE produto SET estoque=estoque-($this->influencia),disponivel=disponivel-($this->influencia) WHERE id=".$this->produto->id);
        $ps->execute();
        $ps->close();
        
        $ps = $con->getConexao()->prepare("DELETE FROM produto_tarefa_simplificada WHERE id=$this->id");
        $ps->execute();
        $ps->close();
        
    }
    
}
