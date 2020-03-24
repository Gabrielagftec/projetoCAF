<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Atividade
 *
 * @author Renan
 */
class UsuarioReduzido {

    public $id;
    public $nome;
    public $id_empresa;
    public $nome_empresa;
    
    public function getUsuario($con){
        
        $empresa = null;
        
        $ps = $con->getConexao()->prepare("SELECT id,tipo_empresa FROM empresa WHERE id=$this->id_empresa");
        $ps->execute();
        $ps->bind_result($id,$tipo_empresa);
        if($ps->fetch()){
            $empresa = array($id,$tipo_empresa);
        }
        $ps->close();
        
        $empresa = Sistema::getEmpresa($empresa[1], $empresa[0], $con);
        
        if($empresa === null){
            return null;
        }
        
        $usuarios = $empresa->getUsuarios($con, 0, 1, "usuario.id=$this->id");
        $usuario = $usuarios[0];
        
        return $usuario;
        
    }
    
}
