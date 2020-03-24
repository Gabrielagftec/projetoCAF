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
class PROBAcessoEmpresas extends Problema {

    function __construct() {

        parent::__construct(6,"Acesso as Empresas");
        
    }
    
    public static function getEmpresasColaborador($con,$usuario){
        
        $total = 0;
        
        $empresas = array();
        
        $ps = $con->getConexao()->prepare("SELECT k.id_cargo,k.id_empresa,COUNT(*),(SELECT COUNT(*) FROM usuario WHERE excluido=false AND id_empresa=".$usuario->empresa->id.") FROM (SELECT u.id_cargo as 'id_cargo',u2.id_empresa as 'id_empresa' FROM usuario u INNER JOIN usuario u2 ON u.cpf=u2.cpf AND u2.excluido=false WHERE u.id_empresa=".$usuario->empresa->id.") k GROUP BY k.id_cargo,k.id_empresa");
        $ps->execute();
        $ps->bind_result($cargo,$empresa,$quantidade,$tot);
        while($ps->fetch()){
            
            $total = $tot;
            
            if(!isset($empresas[$empresa])){
                $empresas[$empresa] = 0;
            }
            
            $empresas[$empresa] += $quantidade*($cargo===$usuario->cargo->id?10:1);
            
        }
        $ps->close();
                
        $ps = $con->getConexao()->prepare("SELECT id_empresa FROM usuario WHERE excluido=false AND cpf=(SELECT u1.cpf FROM usuario u1 WHERE u1.id=$usuario->id)");
        $ps->execute();
        $ps->bind_result($id_empresa);
        while($ps->fetch()){
            $empresas[$id_empresa] = $total;
        }
        $ps->close();
        
        $sugestoes = array();

        foreach($empresas as $id_empresa=>$quantidade){
            
            if((($quantidade*100)/$total)>70){
                
                $sugestoes[] = new Empresa($id_empresa,$con);
                
            }
            
        }
                
        return $sugestoes;
        
    }
    
    public function estaComProblema($con, $usuario) {
        
        $empresas = self::getEmpresasColaborador($con, $usuario);
        
        $empresas_obtidas = array();
        
        $ps = $con->getConexao()->prepare("SELECT id_empresa FROM usuario WHERE excluido=false AND cpf=(SELECT u1.cpf FROM usuario u1 WHERE u1.id=$usuario->id)");
        $ps->execute();
        $ps->bind_result($id_empresa);
        while($ps->fetch()){
            $empresas_obtidas[$id_empresa] = $id_empresa;
        }
        $ps->close();
        
        foreach($empresas as $key=>$value){
            if(!isset($empresas_obtidas[$value->id])){
                return true;
            }
        }
        
        return false;
        
    }
    
    /*
     * {
     * empresas:[
     *  ]
     * }
     */
    
    
    public function resolucaoCompleta($con, $usuario, $parametros) {
        
        $nomes_empresas = array();
        
        $ps = $con->getConexao()->prepare("SELECT id,nome FROM empresa WHERE excluida=false");
        $ps->execute();
        $ps->bind_result($id,$nome);
        while($ps->fetch()){
            $nomes_empresas[$id] = $nome;
        }
        $ps->close();
        
        $log = "Correcao de empresas, usuario $usuario->id - $usuario->nome <hr>";
        
        $empresas = $parametros->empresas;
        
        $empresas_obtidas = array();
        
        $ps = $con->getConexao()->prepare("SELECT id_empresa FROM usuario WHERE excluido=false AND cpf=(SELECT u1.cpf FROM usuario u1 WHERE u1.id=$usuario->id)");
        $ps->execute();
        $ps->bind_result($id_empresa);
        while($ps->fetch()){
            $empresas_obtidas[$id_empresa] = $id_empresa;
        }
        $ps->close();
        
        foreach($empresas as $key=>$value){
            if(!isset($empresas_obtidas[$value->id])){
                
                $clone = Utilidades::copyId0($usuario);
                $clone->endereco = Utilidades::copyId0($usuario->endereco);
                $clone->login .= "_$value->id";
                $clone->telefones = Utilidades::copyId0($usuario->telefones);
                $clone->email = Utilidades::copyId0($usuario->email);
                $clone->empresa = $value;
                $clone->merge($con);
                
                $log .= "Colocado na empresa $value->nome <br>";
            }else{
                
                $log .= "Ja estava na empresa $value->nome <br>";
                unset($empresas_obtidas[$value->id]);
            }
        }
        
        foreach($empresas_obtidas as $id_empresa=>$value){
            
            $log .= "Retirado da empresa $value->nome <br>";
            
            $ps = $con->getConexao()->prepare("UPDATE usuario SET excluido=true WHERE id_empresa=$id_empresa AND cpf=(SELECT u1.cpf FROM (SELECT * FROM usuario) u1 WHERE u1.id=$usuario->id)");
            $ps->execute();
            $ps->close();
            
        }
        
        $ps = $con->getConexao()->prepare("INSERT INTO log_cfg(log,data) VALUES('$log',CURRENT_TIMESTAMP)");
        $ps->execute();
        $ps->close();
        
    }
    
    public function resolucaoRapida($con, $usuario) {
        
        $log = "Correcao de empresas, usuario $usuario->id - $usuario->nome <hr>";
        
        $empresas = self::getEmpresasColaborador($con, $usuario);
        
        $empresas_obtidas = array();
        
        $ps = $con->getConexao()->prepare("SELECT id_empresa FROM usuario WHERE excluido=false AND cpf=(SELECT u1.cpf FROM usuario u1 WHERE u1.id=$usuario->id)");
        $ps->execute();
        $ps->bind_result($id_empresa);
        while($ps->fetch()){
            $empresas_obtidas[$id_empresa] = $id_empresa;
        }
        $ps->close();
        
        foreach($empresas as $key=>$value){
            if(!isset($empresas_obtidas[$value->id])){
                
                $clone = Utilidades::copyId0($usuario);
                $clone->endereco = Utilidades::copyId0($usuario->endereco);
                $clone->login .= "_$value->id";
                $clone->telefones = Utilidades::copyId0($usuario->telefones);
                $clone->email = Utilidades::copyId0($usuario->email);
                $clone->empresa = $value;
                $clone->merge($con);
                
                $log .= "Colocado na empresa $value->nome <br>";
                
            }
        }
        
        $ps = $con->getConexao()->prepare("INSERT INTO log_cfg(log,data) VALUES('$log',CURRENT_TIMESTAMP)");
        $ps->execute();
        $ps->close();
        
    }

}
