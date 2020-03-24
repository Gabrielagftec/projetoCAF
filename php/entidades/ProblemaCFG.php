<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CategoriaDocumento
 *
 * @author Renan
 */
class ProblemaCFG {
   
    public $id;
    public $usuario;
    public $tipo;
    public $id_empresa;
            
    function __construct() {

        $this->id = 0;
        $this->usuario = null;
        $this->tipo = null;
        $this->id_empresa = 0;
        
    }
    
    public function merge($con) {
 
        
        $ps = $con->getConexao()->prepare("SELECT id FROM problema_cfg WHERE id_empresa=$this->id_empresa AND id_usuario=".$this->usuario->id." AND id_tipo=".$this->tipo->id);
        $ps->execute();
        $ps->bind_result($id);
        if($ps->fetch()){
            $ps->close();
            return;
        }
        $ps->close();
        
       
        
        if ($this->id == 0) {
            
            $ps = $con->getConexao()->prepare("INSERT INTO problema_cfg(id_tipo,id_usuario,id_empresa) VALUES(".$this->tipo->id.",".$this->usuario->id.",".$this->id_empresa.")");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
            
        } else {

            $ps = $con->getConexao()->prepare("UPDATE problema_cfg SET id_tipo=".$this->tipo->id.",id_usuario=".$this->usuario->id.",id_empresa=".$this->id_empresa." WHERE id=$this->id");
            $ps->execute();
            $ps->close();
        }

    }

    public function delete($con) {

        $ps = $con->getConexao()->prepare("DELETE FROM problema_cfg WHERE id = " . $this->id);
        $ps->execute();
        $ps->close();
    }

}
