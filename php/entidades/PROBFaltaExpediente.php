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
class PROBFaltaExpediente extends Problema {

    function __construct() {

        parent::__construct(5,"Falta de expediente");
        
    }
    
    public function estaComProblema($con, $usuario) {
       
        $ps = $con->getConexao()->prepare("SELECT id FROM expediente WHERE id_usuario=".$usuario->id);
        $ps->execute();
        $ps->bind_result($id);
        $tem = $ps->fetch();
        $ps->close();
        
        return !$tem;
        
    }
    /*
     * [
     *  {
     *      dia_semana:0,
     *      inicio:0.0
     *      fim: 0.0
     *  }
     * ]
     */
    public function resolucaoCompleta($con, $usuario, $parametros) {
        
        $ps = $con->getConexao()->prepare("DELETE FROM expediente WHERE id_usuario=$usuario->id");
        $ps->execute();
        $ps->close();
        
        foreach($parametros as $key=>$value){
            
            $ps = $con->getConexao()->prepare("INSERT INTO expediente (inicio,fim,dia_semana,id_usuario) VALUES($value->inicio,$value->fim,$value->dia_semana,$usuario->id)");
            $ps->execute();
            $ps->close();
            
        }
        
        $log = "Expediente o usuario $usuario->nome, foi alterado por comparacao";
        
        $ps = $con->getConexao()->prepare("INSERT INTO log_cfg(log,data) VALUES('$log',CURRENT_TIMESTAMP)");
        $ps->execute();
        $ps->close();
        
    }
    
    public function resolucaoRapida($con, $usuario) {
        
        if(!$this->estaComProblema($con, $usuario)){
            
            return;
        
        }
        
        $usuario_semelhante = 0;
        
        $ps = $con->getConexao()->prepare("SELECT u.id FROM usuario u INNER JOIN expediente e ON e.id_usuario=u.id WHERE u.id <> $usuario->id AND u.excluido=false AND u.id_empresa=".$usuario->empresa->id." ORDER BY (u.id_cargo=".$usuario->cargo->id.") DESC");
        $ps->execute();
        $ps->bind_result($id);
        if($ps->fetch()){
            $usuario_semelhante = $id;
        }
        $ps->close();
        
        if($usuario_semelhante > 0){
        
            $params = array();
           
            $ue = null;
            
            $ps = $con->getConexao()->prepare("SELECT inicio,fim,dia_semana FROM expediente WHERE id_usuario=$usuario_semelhante"); 
            $ps->execute();
            $ps->bind_result($inicio,$fim,$dia_semana);
            while($ps->fetch()){
                $e = new stdClass();
                $e->inicio = $inicio;
                $e->fim = $fim;
                $e->dia_semana = $dia_semana;
                
                if(!isset($params[$e->dia_semana])){
                    $params[$e->dia_semana] = array();
                }
                
                $params[$e->dia_semana][] = $e;
                $ue = $e;
            }
            $ps->close();
            
            for($i=1;$i<6;$i++){
                if(!isset($params[$i])){
                    $c = Utilidades::copy($ue);
                    $c->dia_semana=$i;
                    $params[$i] = array($c);
                }
            }
            
            $rp = array();
            
            foreach($params as $key=>$value){
                foreach($value as $k2=>$v2){
                    $rp[] = $v2;
                }
            }
            
            $this->resolucaoCompleta($con, $usuario, $rp);
            
        }
                
    }

}
