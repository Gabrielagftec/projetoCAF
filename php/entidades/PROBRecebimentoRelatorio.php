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
class PROBRecebimentoRelatorio extends Problema {

    function __construct() {

        parent::__construct(8,"Recebimento de Relatorios");
        
    }
    
    public static function getRelatorios(){
        
        return array(
            array("Relatorio Detona",Email::$LOGISTICA),
            array("Relatorio Contas",Email::$ADMINISTRATIVO)
        );
        
    }
    
    public static function getSugestaoRecebimento($con,$usuario){
        
        $retorno = array();
       
        $grupo = $usuario->empresa->getFiliais($con);
        $grupo[] = $usuario->empresa;
        
        $relatorios = self::getRelatorios();
         
        foreach($relatorios as $ir=>$relatorio){
            
            $tipos = array();
            
            foreach($grupo as $ie=>$empresa){
                $nr = Utilidades::copy($relatorio);
                $nr[2] = $empresa;
                $nr[3] = false;
                $tipos[$empresa->id] = $nr;
            }
            
            $rec = self::getRecebedoresRelatorio($con, $relatorio, $usuario->id);
            
            if(!isset($rec[$usuario->empresa->id]))continue;
            
            $rec = $rec[$usuario->empresa->id];
            
            
            foreach($rec as $id_cargo=>$empresas){
                foreach($empresas as $id_empresa=>$usuarios){
                    if(isset($usuarios[$usuario->id])){
                        if(isset($tipos[$id_empresa])){
                            $tipos[$id_empresa][3] = true;
                        }
                    }
                }
            }
            
            foreach($tipos as $key=>$value){
                $retorno[] = $value;
            }
            
        }
        
        return $retorno;
        
    }
    
    public static function getRecebedoresRelatorio($con,$relatorio,$usuario_especifico=-1){
        
        $recebedores = array();
        
        $sql = "SELECT u.id_cargo,u.id,u.nome,e.id_entidade,u.id_empresa FROM usuario u INNER JOIN email ue ON ue.tipo_entidade='USU' AND ue.endereco NOT like 'emailinvalido%' AND ue.id_entidade=u.id INNER JOIN email e ON e.tipo_entidade='EMP' AND e.endereco REGEXP CONCAT('".$relatorio[1].":[^;]*',SUBSTRING_INDEX(SUBSTRING_INDEX(ue.endereco, ';', 1),',',1)) WHERE u.excluido=false";
        
        if($usuario_especifico >= 0){
            
            $sql .= " AND u.id=$usuario_especifico";
            
        }
        
        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_cargo,$id_usuario,$nome,$id_empresa,$empresa_usuario);
        while($ps->fetch()){
            
            if(!isset($recebedores[$empresa_usuario])){
                $recebedores[$empresa_usuario] = array();
            }
            
            if(!isset($recebedores[$empresa_usuario][$id_cargo])){
                $recebedores[$empresa_usuario][$id_cargo] = array();
            }
            
            if(!isset($recebedores[$empresa_usuario][$id_cargo][$id_empresa])){
                $recebedores[$empresa_usuario][$id_cargo][$id_empresa] = array();
            }
            
            $recebedores[$empresa_usuario][$id_cargo][$id_empresa][$id_usuario] = $nome;
            
        }
        
        return $recebedores;
        
    }
    
    public function estaComProblema($con, $usuario) {
        
        $usuarios = array();
        
        $relatorios = $this->getRelatorios();
        
        $quantidade_usuarios = 0;
        
        $ps = $con->getConexao()->prepare("SELECT COUNT(*) FROM usuario WHERE excluido=false AND id_empresa=".$usuario->empresa->id);
        $ps->execute();
        $ps->bind_result($qtd);
        if($ps->fetch()){
            $quantidade_usuarios = $qtd;
        }
        $ps->close();
        
        
        
        foreach($relatorios as $key=>$value){
            
            $r = self::getRecebedoresRelatorio($con, $value);
        
            if(isset($r[$usuario->empresa->id])){
                
                $empresa_recebimento = array();
                $quantidade = array();
                
                foreach($r[$usuario->empresa->id] as $id_cargo=>$empresas){
                    
                    foreach($empresas as $id_empresa=>$usuarios){
                        
                        if(isset($usuarios[$usuario->id])){
                            
                            $empresa_recebimento[$id_empresa] = true;
                            
                        }
                        
                        $valor = ($id_cargo===$usuario->cargo->id)?$quantidade_usuarios:count($usuarios);
                        
                        if(!isset($quantidade[$id_empresa])){
                            
                            $quantidade[$id_empresa] = 0;
                        
                        }
                        
                        $quantidade[$id_empresa] += $valor;
                        
                    }
                    
                }
                
                foreach($quantidade as $id_empresa=>$qtd){
                    
                    $devo_receber = (($qtd/$quantidade_usuarios)*100)>60;
                    $recebo = isset($empresa_recebimento[$id_empresa]);
                    
                    if($devo_receber && !$recebo){
                        
                        return true;
                        
                    }
                    
                }
                
            }
            
        }
        
    }

    /*
     * [
     *      {
     *          id_empresa:0
     *          relatorio:[]
     *      }
     * ]
     * 
     */
    
    public function resolucaoCompleta($con, $usuario, $parametros) {
       
        $email_principal_usuario = explode(';',$usuario->email->endereco);
        $email_principal_usuario = $email_principal_usuario[0];
        
        $log = "$usuario->nome esta recebendo agora os relatorios: ";
        
        foreach($parametros as $key=>$value){
            
            $tipo_relatorio = $value->relatorio[1];
            
            $log .= $value->relatorio[0]."<br>";
            
            $email = "";
            $ps = $con->getConexao()->prepare("SELECT endereco FROM email WHERE tipo_entidade='EMP' AND id_entidade=".$value->id_empresa);
            $ps->execute();
            $ps->bind_result($str);
            if($ps->fetch()){
                $email = $str;
            }
            $ps->close();
            
            $grupos = explode(';', $email);
            $index = -1;
            
            foreach($grupos as $id=>$valor){
                if(strpos($valor,$tipo_relatorio) !== false){
                    $index = $id;
                    break;
                }
            }
            
            if($index<0){
                
                $grupos[] = $tipo_relatorio.":".$email_principal_usuario;
                
            }else{
                
                if(strpos($grupos[$index],$email_principal_usuario) === false){
                    
                    $grupos[$index] .= ",".$email_principal_usuario;
                    
                }
                
            }
            
            $str = "";
            
            foreach($grupos as $id=>$valor){
                $str .= $valor.";";
            }
            
            $ps = $con->getConexao()->prepare("UPDATE email SET endereco='$str' WHERE tipo_entidade='EMP' AND id_entidade=".$value->id_empresa);
            $ps->execute();
            $ps->close();
            
            
        }
        
        
        $ps = $con->getConexao()->prepare("INSERT INTO log_cfg(log,data) VALUES('$log',CURRENT_TIMESTAMP)");
        $ps->execute();
        $ps->close();
        
    }
    
    public function resolucaoRapida($con, $usuario) {
        
        
        $relatorios = $this->getRelatorios();
        
        $areceber = array();
        
        $quantidade_usuarios = 0;
        
        $ps = $con->getConexao()->prepare("SELECT COUNT(*) FROM usuario WHERE excluido=false AND id_empresa=".$usuario->empresa->id);
        $ps->execute();
        $ps->bind_result($qtd);
        if($ps->fetch()){
            $quantidade_usuarios = $qtd;
        }
        $ps->close();
        
        
        foreach($relatorios as $key=>$value){
            
            $r = self::getRecebedoresRelatorio($con, $value);
        
            
            if(isset($r[$usuario->empresa->id])){
                
                $empresa_recebimento = array();
                $quantidade = array();
                
                foreach($r[$usuario->empresa->id] as $id_cargo=>$empresas){
                    
                    foreach($empresas as $id_empresa=>$usuarios){
                        
                        if(isset($usuarios[$usuario->id])){
                            
                            $empresa_recebimento[$id_empresa] = true;
                            
                        }
                        
                        $valor = ($id_cargo===$usuario->cargo->id)?$quantidade_usuarios:count($usuarios);
                        
                        if(!isset($quantidade[$id_empresa])){
                            
                            $quantidade[$id_empresa] = 0;
                        
                        }
                        
                        $quantidade[$id_empresa] += $valor;
                        
                    }
                    
                }
                
                foreach($quantidade as $id_empresa=>$qtd){
                    
                    $devo_receber = (($qtd/$quantidade_usuarios)*100)>60;
                    $recebo = isset($empresa_recebimento[$id_empresa]);
                    
                    if($devo_receber){
                        
                        $p = new stdClass();
                        $p->id_empresa = $id_empresa;
                        $p->relatorio = $value;
                        
                        $areceber[] = $p;
                        
                    }
                    
                }
                
            }
            
        }
        
        $this->resolucaoCompleta($con, $usuario, $areceber);
        
    }

}
