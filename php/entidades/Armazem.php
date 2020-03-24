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
class Armazem {

    public $id;
    public $comprimento;
    public $largura;
    public $empresa;
    public $porta_palets;
    
    function __construct() {

        $this->id = 0;
        $this->empresa = null;
        $this->comprimento = 0;
        $this->largura = 0;
        $this->porta_palets = array();
        
    }


    public function merge($con) {

        if ($this->id == 0) {

            $ps = $con->getConexao()->prepare("INSERT INTO armazen(comprimento,largura,id_empresa) VALUES($this->comprimento,$this->largura,".$this->empresa->id.")");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
            
        } else {

            $ps = $con->getConexao()->prepare("UPDATE armazen SET comprimento=$this->comprimento,largura=$this->largura,id_empresa=".$this->empresa->id." WHERE id=$this->id");
            $ps->execute();
            $ps->close();
        }
        
    }

    public function delete($con) {

        $ps = $con->getConexao()->prepare("DELETE FROM armazen WHERE id = " . $this->id);
        $ps->execute();
        $ps->close();
    }

}
