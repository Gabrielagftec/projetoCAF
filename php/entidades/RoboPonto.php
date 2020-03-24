<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RegraTabela
 *
 * @author Renan
 */
class RoboPonto {

    public $dia;
    public $mes;
    public $ano;
    public $hora;
    public $minuto;
    public $segundo;
    public $momento;

    public function __construct() {

        date_default_timezone_set("America/Sao_Paulo");

        $this->momento = round(microtime(true) * 1000);

        $str = explode(':', date('d:m:Y:H:i:s', $this->momento / 1000));
        $this->dia = intval($str[0]);
        $this->mes = intval($str[1]);
        $this->ano = intval($str[2]);
        $this->hora = intval($str[3]);
        $this->minuto = intval($str[4]);
        $this->segundo = intval($str[5]);
    }

    public function executar($con) {
        
        $str = "";
        
        $ps = $con->getConexao()->prepare("SELECT UNIX_TIMESTAMP(k.momento)*1000,u.nome FROM (SELECT MIN(momento) as 'min',id_usuario as 'idu' FROM ponto GROUP BY id_usuario,cast(momento as date)) k INNER JOIN expediente e ON DAYOFWEEK(k.min)=e.dia_semana AND e.id_usuario=k.idu INNER JOIN usuario u ON u.id=k.idu WHERE cast(k.min as data)=DATE_SUB(CURRENT_DATE,INTERVAL 1 DAY) AND ((HOUR(k.min)=e.inicio AND MINUTE(k.min)>10) OR HOUR(k.min)>e.inicio)"); 
        $ps->execute();
        $ps->bind_result($momento,$nome);
        
        while($ps->fetch()){
            
            $str .= "<strong>$nome</strong> bateu ponto em <strong>".date("d/m/Y H:i",$momento/1000)."</strong><hr>";
            
        }
        
        $ps->close();
        
        if($str !== ""){
            
            Sistema::avisoDEVS_MASTER($str);
            
        }
        
    }

}
