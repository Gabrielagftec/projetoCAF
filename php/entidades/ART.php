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
class ART{

    public $id;
    public $empresa;
    public $numero;
    public $quantidade;
    public $ultimo_numero;

    public function merge($con){

    	if($this->id == 0){

    		$ps = $con->getConexao()->prepare("INSERT INTO art(numero,quantidade,ultimo_numero,id_empresa) VALUES($this->numero,$this->quantidade,$this->ultimo_numero,".$this->empresa->id.")");
            
    		$ps->execute();
    		$this->id = $ps->insert_id;
    		$ps->close();

    	}else{

    		$ps = $con->getConexao()->prepare("UPDATE art SET numero=$this->numero,quantidade=$this->quantidade,ultimo_numero=$this->ultimo_numero,id_empresa=".$this->empresa->id." WHERE id=$this->id");
    		$ps->execute();
    		$ps->close();

    	}

    }

    public function delete($con){

    	$ps = $con->getConexao()->prepare("DELETE FROM art WHERE id=$this->id");
    	$ps->execute();
    	$ps->close();

    }

}
