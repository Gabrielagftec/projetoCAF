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
class RoboInventario {

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
        
        $empresas = Sistema::getEmpresas($con, 'empresa.rtc>=5 AND empresa.id=1734');
        
        foreach($empresas as $key=>$empresa){
            
            $ps = $con->getConexao()->prepare("SELECT UNIX_TIMESTAMP(MAX(data))*1000 FROM inventario WHERE id_empresa=$empresa->id");
            $ps->execute();
            $ps->bind_result($data);
            if(!$ps->fetch()){
                $ps->close();
                continue;
            }
            $ps->close();
            
            if($data == null){
                
                $data = (microtime(true)*1000)-(48*60*60*1000);
                
            }
            
            $data += 24*60*60*1000;
            
            $data = Utilidades::normalizarDia($data);
            $this->momento = Utilidades::normalizarDia($this->momento);
            
            while($data<=($this->momento-24*60*60*1000)){
                
                $produtos = $empresa->getProdutosInventario($con);
            
                $inventario = $empresa->getItensInventario($con, $produtos, $data);
                
                foreach($inventario as $key=>$value){
                    
                    $value->merge($con);
                    
                }
                
                unset($produtos);
             
                $data += 24*60*60*1000;
                
                continue;
                
            }
            
        }
        
    }

}
