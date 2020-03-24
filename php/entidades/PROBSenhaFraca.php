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
class PROBSenhaFraca extends Problema {

    function __construct() {

        parent::__construct(11, "Senha fraca");
    }

    public static function getDificuldadeSenha($senha) {

        $tipos = array(
            array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'),
            array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'),
            array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'),
            array('!', '@', '#', '$', '%', '*', '(', ')', '-', '+', '_', '=', '[', ']', '{', '}')
        );

        $diff = array();
        foreach($tipos as $key=>$value){
            $diff[$key] = array();
        }
        
        for ($i = 0; $i < strlen($senha); $i++) {

            $c = $senha{$i};

            foreach ($tipos as $key => $value) {
                if (in_array($c, $value)) {
                    $diff[$key][$c] = true;
                    break;
                }
            }
        }

        $nv = 0;
        
        foreach($diff as $key=>$value){
            
            $nv += min(4,count($value));
            
        }
        
        
        return round((($nv/(count($tipos)*4))*100),2);
    }

    public static function gerarSenhaComDificuldade($x) {
        
        $x = min(100,$x);
        
        $tipos = array(
            array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'),
            array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'),
            array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'),
            array('!', '@', '#', '$', '%', '*', '(', ')', '-', '+', '_', '=', '[', ']', '{', '}')
        );
        
        $sel = array();
        
        foreach($tipos as $key=>$value){
            
            $sel[$key] = 0;
            
        }
        
        $senha = "";
        
        $nv = round((count($tipos)*4)*($x/100));
       
        for($i=0;$i<$nv;$i++){
            
            $itipo = rand(0,count($tipos)-1);
            $tipo = $tipos[$itipo];
            
            $iletra = rand(0,count($tipo)-1);
            $senha .= $tipo[$iletra];
            
            array_splice($tipo, $iletra,1);
            
            $sel[$itipo]++;
            
            if($sel[$itipo]==4){
                
                array_splice($tipos, $itipo,1);
                
            }
            
        }
     
        return $senha;
        
    }

    public function estaComProblema($con, $usuario) {
     
        $dificuldade = self::getDificuldadeSenha($usuario->senha);
        
        return $dificuldade<40;
        
    }

    /*
     * {
     * senha:
     * }
     */
    public function resolucaoCompleta($con, $usuario, $parametros) {
        
        $ps = $con->getConexao()->prepare("SELECT id FROM usuario WHERE id=$usuario->id");
        $ps->execute();
        $ps->bind_result($id);
        $existe = $ps->fetch();
        $ps->close();
        
        if($existe){
            $ps = $con->getConexao()->prepare("UPDATE usuario SET senha='".addslashes($parametros->senha)."' WHERE cpf=(SELECT u1.cpf FROM (SELECT * FROM usuario) u1 WHERE u1.id=$usuario->id)");
            $ps->execute();
            $ps->close();
        }
        
        $html = "Usuario: $usuario->login <br> Senha: $parametros->senha";
        
        $emp = new Empresa($usuario->empresa->id,$con);
        $emp->email->enviarEmail($usuario->email,"Acessos RTC",$html);
        
        $log = "Senha do $usuario->nome alterada";
        $ps = $con->getConexao()->prepare("INSERT INTO log_cfg(log,data) VALUES('$log',CURRENT_TIMESTAMP)");
        $ps->execute();
        $ps->close();
        
        
    }

    public function resolucaoRapida($con, $usuario) {
        
        $parametros = new stdClass(); 
        $parametros->senha = self::gerarSenhaComDificuldade(30);
        
        $this->resolucaoCompleta($con, $usuario, $parametros);
        
    }

}
