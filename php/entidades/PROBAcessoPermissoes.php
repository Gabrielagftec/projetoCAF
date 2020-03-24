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
class PROBAcessoPermissoes extends Problema {

    function __construct() {

        parent::__construct(7,"Acesso permissoes");
        
    }
    
    public static function getQuadroPermissaoPercentualObj($con,$usuario){
        
        $quadro = self::getQuadroPermissaoPercentual($con, $usuario);
        
        $permissoes = $usuario->empresa->getRTC($con);
        $permissoes = Utilidades::copy($permissoes->permissoes);
        
        $mapa_permissoes = array();
        
        foreach($permissoes as $key=>$value){
            $mapa_permissoes[$value->id] = $value;
        }
        
        $res = array();
        
        foreach($quadro as $key=>$value){
            
            if(!isset($mapa_permissoes[$key])){
                continue;
            }
            
            $p = $mapa_permissoes[$key];
            $p->alt = $value[0];
            $p->in = $value[1];
            $p->del = $value[2];
            $p->cons = $value[3];
            
            $res[] = $p;
            
        }
        
        return $res;
        
    }
    
    public static function getQuadroPermissaoPercentual($con,$usuario){
        
        $total = 0;
        
        $ps = $con->getConexao()->prepare("SELECT COUNT(*) FROM usuario WHERE excluido=false AND id_empresa=".$usuario->empresa->id);
        $ps->execute();
        $ps->bind_result($qtd);
        
        if($ps->fetch()){
            
            $total = $qtd;
        
        }
        
        $ps->close();
        
        $quadro = array();
        
        $permissoes = $usuario->empresa->getRTC($con);
        
        $permissoes = Utilidades::copy($permissoes->permissoes);
        
        $permissoes_cargo = $usuario->cargo->getPermissoes($con);
        
        foreach($permissoes as $key=>$value){
            
            $quadro[$value->id] = array(0,0,0,0);
            
        }
        
        foreach($permissoes_cargo as $key=>$value){
            
            if(!isset($quadro[$value->id])){
                continue;
            }
            
            if($value->alt){
                $quadro[$value->id][0] = 100;
            }
            
            if($value->in){
                $quadro[$value->id][1] = 100;
            }
            
            if($value->del){
                $quadro[$value->id][2] = 100;
            }
            
            if($value->cons){
                $quadro[$value->id][3] = 100;
            }
            
        }
        
        $ps = $con->getConexao()->prepare("SELECT up.id_permissao,up.incluir,up.deletar,up.alterar,up.consultar FROM usuario_permissao up INNER JOIN usuario u ON u.id=up.id_usuario AND u.id_empresa=".$usuario->empresa->id);
        $ps->execute();
        $ps->bind_result($id_permissao,$incluir,$deletar,$alterar,$consultar);
        
        while($ps->fetch()){
            if(isset($quadro[$id_permissao])){
                
                if($alterar==1){
                    $quadro[$id_permissao][0] = min(100,$quadro[$id_permissao][0]+(100/$total));
                }
                if($incluir==1){
                    $quadro[$id_permissao][1] = min(100,$quadro[$id_permissao][1]+(100/$total));
                }
                if($deletar==1){
                    $quadro[$id_permissao][2] = min(100,$quadro[$id_permissao][2]+(100/$total));
                }
                if($consultar==1){
                    $quadro[$id_permissao][3] = min(100,$quadro[$id_permissao][3]+(100/$total));
                }
                
            }
        }
        
        $ps->close();
        
        return $quadro;
        
    }
    
    public function estaComProblema($con, $usuario) {

        return true;
       
        $permissoes = array();
        
        foreach($usuario->permissoes as $key=>$value){
            
            $permissoes[$value->id] = $value;
            
        }
        
        $quantidade_possiveis_problemas = 0;
        
        
        $quadro = self::getQuadroPermissaoPercentual($con, $usuario);
        
        foreach($quadro as $id_permissao=>$valor){
            
            if(!isset($permissoes[$id_permissao])){
                $quantidade_possiveis_problemas++;
                continue;
            }
            
            $prm = $permissoes[$id_permissao];
            
            $alterar = $valor[0]>80;
            $incluir = $valor[1]>70;
            $deletar = $valor[2]>90;
            $consultar = $valor[3]>60;
            
            if($alterar && !$prm->alt){
                $quantidade_possiveis_problemas++;
            }
            if($incluir && !$prm->in){
                $quantidade_possiveis_problemas++;
            }
            if($deletar && !$prm->del){
                $quantidade_possiveis_problemas++;
            }
            if($consultar && !$prm->cons){
                $quantidade_possiveis_problemas++;
            }
            
        }
        
        return $quantidade_possiveis_problemas>5;
        
    }
    
    /*
     * sem parametros permissao no proprio usuario
     */
    
    public function resolucaoCompleta($con, $usuario, $parametros) {
        
        $log = "";
        
        $d = 0;
        foreach($parametros->permissoes as $key=>$prm){
            if($prm->alt){
                $d++;
            }
            if($prm->in){
                $d++;
            }
            if($prm->del){
                $d++;
            }
            if($prm->cons){
                $d++;
            }
        }
        
        $log = "Permissoes o usuario $parametros->nome alteradas, para $d direitos";
        
        $ps = $con->getConexao()->prepare("INSERT INTO log_cfg(log,data) VALUES('$log',CURRENT_TIMESTAMP)");
        $ps->execute();
        $ps->close();
        
        $parametros->merge($con);
        
    }
    
    public function resolucaoRapida($con, $usuario) {
        
        $permissoes = array();
        
        foreach($usuario->permissoes as $key=>$value){
            
            $permissoes[$value->id] = $value;
            
        }
        
        $quadro = self::getQuadroPermissaoPercentual($con, $usuario);
        
        foreach($quadro as $id_permissao=>$valor){
            
            if(!isset($permissoes[$id_permissao])){
                
                $permissoes[$id_permissao] = new Permissao($id_permissao, "");
                $usuario->permissoes[] = $permissoes[$id_permissao];
                
            }
            
            $prm = $permissoes[$id_permissao];
            
            $alterar = $valor[0]>80;
            $incluir = $valor[1]>70;
            $deletar = $valor[2]>95;
            $consultar = $valor[3]>60;
            
            if($alterar && !$prm->alt){
                $prm->alt = true;
            }
            if($incluir && !$prm->in){
                $prm->in = true;
            }
            if($deletar && !$prm->del){
                $prm->del = true;
            }
            if($consultar && !$prm->cons){
                $prm->cons = true;
            }
            
        }
        
        $this->resolucaoCompleta($con, $usuario, $usuario);
        
    }

}
