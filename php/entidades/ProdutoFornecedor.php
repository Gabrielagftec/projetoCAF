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
class ProdutoFornecedor {

    public $id;
    public $produto;
    public $fornecedor;
    public $preco1;
    public $comis1;
    public $preco2;
    public $comis2;
    public $preco3;
    public $comis3;
    public $preco4;
    public $comis4;
    public $validade;
    public $valor;

    function __construct()
    {
        $this->id = 0;
        $this->produto = null;
        $this->validade = round(microtime(true)*1000);
        $this->fornecedor = null;
        $this->valor = 0;
        $this->preco1 = 0;
        $this->comis1 = 0;
        $this->preco2 = 0;
        $this->comis2 = 0;
        $this->preco3 = 0;
        $this->comis3 = 0;
        $this->preco4 = 0;
        $this->comis4 = 0;

    }

    public function merge($con){


        if($this->id == 0){

            $ps = $con->getConexao()->prepare("INSERT INTO produto_fornecedor(id_fornecedor,id_produto,preco1,comis1,preco2,comis2,preco3,comis3,preco4,comis4,validade,valor) VALUES(".$this->fornecedor->id.",".$this->produto->id.",'$this->preco1','$this->comis1','$this->preco2','$this->comis2','$this->preco3','$this->comis3','$this->preco4','$this->comis4',FROM_UNIXTIME($this->validade/1000),$this->valor)");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();

        }else{

            $ps = $con->getConexao()->prepare("UPDATE produto_fornecedor SET id_fornecedor=".$this->fornecedor->id.", id_produto=".$this->produto->id.", preco1='$this->preco1',comis1='$this->comis1',preco2='$this->preco2',comis2='$this->comis2',preco3='$this->preco3',comis3='$this->comis3',preco4='$this->preco4',comis4='$this->comis4',validade=FROM_UNIXTIME($this->validade/1000),valor=$this->valor WHERE id=$this->id");
            $ps->execute();
            $ps->close();

        }

    }

    public function delete($con){

        $ps = $con->getConexao()->prepare("DELETE produto_fornecedor WHERE id=$this->id");
        $ps->execute();
        $ps->close();

    }


}
