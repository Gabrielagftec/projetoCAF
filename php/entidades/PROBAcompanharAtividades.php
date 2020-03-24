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
class PROBAcompanharAtividades extends Problema {

    function __construct() {

        parent::__construct(1,"Acompanhamento de Atividades e Organograma");
        
    }
    
    public function estaComProblema($con, $usuario) {
        
        $o = new Organograma($usuario->empresa);
        
        $nodo = $o->getNodo($con, $usuario);
        
        return $nodo === null;
        
    }
    private static function getNodos($nodo,$filtro){
        
        $possiveis = array();
        
        if(isset($filtro[$nodo->id_usuario])){
            $possiveis[] = $nodo;
        }
        
        foreach($nodo->filhos as $key=>$value){
            
            $part = self::getNodos($value, $filtro);
            
            foreach($part as $k2=>$v2){
                
                $possiveis[] = $v2;
                
            }
            
        }
        
        return $possiveis;
        
    }
    
    private static function nodoMaisBaixo($raiz,$i){
        
        if(count($raiz->filhos) === 0){
            return array($i,$raiz);
        }
        
        $menor = null;
        
        foreach($raiz->filhos as $key=>$value){
            
            $k = self::nodoMaisBaixo($value, $i+1);
            
            if($menor === null){
                $menor = $k;
            }else if($menor[0]<$k[0]){
                $menor = $k;
            }
            
        }
        
        return $menor;
        
    }
    
    public static function encotrarNodoAdequado($con,$usuario){
        
        $cargo_usuario = array();
        
        $ps = $con->getConexao()->prepare("SELECT id,id_cargo FROM usuario WHERE id_empresa=".$usuario->empresa->id." AND excluido=false AND id_cargo=".$usuario->cargo->id);
        $ps->execute();
        $ps->bind_result($id,$id_cargo);
        while($ps->fetch()){
            $cargo_usuario[$id] = $id_cargo;
        }
        $ps->close();
        
        $o = new Organograma($usuario->empresa);
        $raiz = $o->getRaiz($con);
        
        $nodos = self::getNodos($raiz,$cargo_usuario);
        
        $menor_equipe = -1;
        foreach($nodos as $key=>$value){
            if($menor_equipe<0){
                $menor_equipe = $key;
            }else{
                if(count($value->filhos)<count($nodos[$menor_equipe]->filhos)){
                    $menor_equipe = $key;
                }
            }
        }
        
        if($menor_equipe>=0){
            
            return array($raiz,$nodos[$menor_equipe]);
            
        }
        
        $mais_baixo = self::nodoMaisBaixo($raiz, 0);
        $mais_baixo = $mais_baixo[1];
        
        return array($raiz,$mais_baixo);
        
    }
    
    /*
     * Sem parametros
     */
    
    public function resolucaoCompleta($con, $usuario, $parametros) {
       
        if($this->estaComProblema($con, $usuario)){
        
            $raiz_adequado = self::encotrarNodoAdequado($con, $usuario);
            
            $raiz = $raiz_adequado[0];
            $nodo = $raiz_adequado[1];
            
            $novo_nodo = new NodoOrganograma();
            $novo_nodo->id_usuario = $usuario->id;
            $novo_nodo->nome_usuario = $usuario->nome;
            $novo_nodo->filhos = array();
            $novo_nodo->pai = $nodo;
            $nodo->filhos[] = $novo_nodo;
            
            $o = new Organograma($usuario->empresa);
            $o->alterar($con, $raiz);
            
            $log = "$usuario->nome colocado abaixo de $nodo->nome_usuario";
            $ps = $con->getConexao()->prepare("INSERT INTO log_cfg(log,data) VALUES('$log',CURRENT_TIMESTAMP)");
            $ps->execute();
            $ps->close();

        }
        
    }
    
    public function resolucaoRapida($con, $usuario) {
        $this->resolucaoCompleta($con, $usuario, null);
    }

}
