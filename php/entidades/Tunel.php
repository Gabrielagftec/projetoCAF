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
class Tunel {

    public $id;
    public $porta_palet;
    
    public $x_inicial;
    public $y_inicial;
    public $z_inicial;
    
    public $x_final;
    public $y_final;
    public $z_final;
    
    function __construct() {

        $this->id = 0;
        $this->porta_palet = null;
        
        $this->x_inicial = 0;
        $this->y_inicial = 0;
        $this->z_inicial = 0;
        
        $this->x_final = 0;
        $this->y_final = 0;
        $this->z_final = 0;
        
    }


    public function merge($con) {

        if ($this->id == 0) {

            $ps = $con->getConexao()->prepare("INSERT INTO tunel(x_inicial,y_inicial,z_inicial,x_final,y_final,z_final,id_porta_palet) VALUES($this->x_inicial,$this->y_inicial,$this->z_inicial,$this->x_final,$this->y_final,$this->z_final,".$this->porta_palet->id.")");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
            
        } else {

            $ps = $con->getConexao()->prepare("UPDATE tunel SET x_inicial=$this->x_inicial,y_inicial=$this->y_inicial,z_inicial=$this->z_inicial,x_final=$this->x_final,y_final=$this->y_final,z_final=$this->z_final,id_porta_palet=".$this->porta_palet->id." WHERE id=$this->id");
            $ps->execute();
            $ps->close();
        }
        
    }

    public function delete($con) {

        $ps = $con->getConexao()->prepare("DELETE FROM tunel WHERE id = " . $this->id);
        $ps->execute();
        $ps->close();
    }

}
