<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Email
 *
 * @author Renan
 */
class PortaPalet {

    public $id;
    public $largura_posicao;
    public $comprimento_posicao;
    public $altura_posicao;
    public $largura;
    public $comprimento;
    public $altura;
    public $x;
    public $y;
    public $armazem;
    public $tuneis;
    public $inflamavel;
    
    public $rua_inicial;
    public $numero_inicial;
    
    function __construct() {

        $this->id = 0;
        $this->largura_posicao = 0;
        $this->comprimento_posicao = 0;
        $this->altura_posicao = 0;
        $this->largura = 0;
        $this->comprimento = 0;
        $this->altura = 0;
        $this->x = 0;
        $this->y = 0;
        $this->armazem = null;
        $this->tuneis = array();
        $this->inflamavel = 0;
        
        $this->rua_inicial = 0;
        $this->numero_inicial = 0;

    }


    public function merge($con) {

        if ($this->id == 0) {

            $ps = $con->getConexao()->prepare("INSERT INTO porta_palet(largura_posicao,comprimento_posicao,altura_posicao,largura,comprimento,altura,x,y,id_armazen,rua_inicial,numero_inicial,inflamavel) "
                    . "VALUES($this->largura_posicao,$this->comprimento_posicao,$this->altura_posicao,$this->largura,$this->comprimento,$this->altura,$this->x,$this->y,".$this->armazem->id.",$this->rua_inicial,$this->numero_inicial,$this->inflamavel)");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
            
        } else {

            $ps = $con->getConexao()->prepare("UPDATE porta_palet SET largura_posicao=$this->largura_posicao, comprimento_posicao=$this->comprimento_posicao, altura_posicao=$this->altura_posicao,largura=$this->largura,comprimento=$this->comprimento,altura=$this->altura,x=$this->x,y=$this->y,id_armazen=".$this->armazem->id.",rua_inicial=$this->rua_inicial,numero_inicial=$this->numero_inicial,inflamavel=$this->inflamavel WHERE id=$this->id");
            $ps->execute();
            $ps->close();
        }
        
    }

    public function delete($con) {

        $ps = $con->getConexao()->prepare("DELETE FROM porta_palet WHERE id = " . $this->id);
        $ps->execute();
        $ps->close();
    }

}
