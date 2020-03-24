<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Aloc_Produto
 *
 * @author T-Gamer
 */
class Aloc_Produto {
    
    public static $LIQUIDO = "0";
    public static $PO = "1";
    public static $SOLIDO = "2";
    
    private static $uidp = 0;
    
    public $alg_aux = false;
    
    public $uid;
    public $id;
    public $nome;
    public $peso;
    public $quantidade;
    public $nivel_saida;
    public $ponto_fulgor;
    public $categorias;
    public $estado;
    
    public $real;
    
    public $altura = 0;
    public $numero = 0;
    public $rua = 0;
    
    public function __construct() {
        
        $this->uid = Aloc_Produto::$uidp;
        $this->real = true;
        
        Aloc_Produto::$uidp++;
        
    }
    
}
