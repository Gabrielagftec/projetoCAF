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
class RoboAtualizaPreco {

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
        
        $con = new ConnectionFactory();
        
        $produtos = array();
        
        $ps = $con->getConexao()->prepare("SELECT p.id,p.formula_preco,SUM(i.preco)/COUNT(*),i.usado FROM produto p INNER JOIN itens_kim_go i ON p.id_empresa=2069 AND i.relacoes LIKE CONCAT(CONCAT('%;P',p.id),';%') AND i.desativa_media NOT LIKE CONCAT(CONCAT('%;P',p.id),';%') GROUP BY p.id,i.usado;");
        $ps->execute();
        $ps->bind_result($id,$formula,$preco,$usado);
        while($ps->fetch()){
            
            if(!isset($produtos[$id])){
                $produtos[$id] = new stdClass();
                $produtos[$id]->formula = $formula;
                $produtos[$id]->usado = 0;
                $produtos[$id]->novo = 0;
                $produtos[$id]->id = $id;
            }
            
            $a = $produtos[$id];
            
            if($usado == 1){
                $a->usado = $preco;
            }else{
                $a->novo = $preco;
            }
            
        }
        $ps->close();
        
        foreach($produtos as $key=>$value){
            
            if($value->usado < 0.1){
               $value->usado = $value->novo;
            }
            
            $f = str_replace(array("mu"), $value->usado."", $value->formula);
            $f = str_replace(array("m"), $value->novo."",$f);
            
            $x = 0;

            eval('$x='.$f.";");
            
            $ps = $con->getConexao()->prepare("UPDATE produto SET valor_base=ROUND($x,2) WHERE id=$key");
            $ps->execute();
            $ps->close();
            
            
        }

    }

}
