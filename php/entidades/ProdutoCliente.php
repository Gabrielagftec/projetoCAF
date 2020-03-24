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
class ProdutoCliente {

    public $id;
    public $produto;
    public $cliente;
    public $preco1;
    public $comis1;
    public $preco2;
    public $comis2;
    public $preco3;
    public $comis3;
    public $preco4;
    public $comis4;
    public $valor;


    function __construct()
    {
        $this->id = 0;
        $this->produto = null;
        $this->cliente = null;
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

            $ps = $con->getConexao()->prepare("INSERT INTO promocao_cliente(id_cliente,id_produto,preco1,comis1,preco2,comis2,preco3,comis3,preco4,comis4) VALUES(".$this->cliente->id.",".$this->produto->id.",'$this->preco1','$this->comis1','$this->preco2','$this->comis2','$this->preco3','$this->comis3','$this->preco4','$this->comis4')");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();

        }else{

            $ps = $con->getConexao()->prepare("UPDATE promocao_cliente SET id_cliente=".$this->cliente->id.", id_produto=".$this->produto->id.", preco1='$this->preco1',comis1='$this->comis1',preco2='$this->preco2',comis2='$this->comis2',preco3='$this->preco3',comis3='$this->comis3',preco4='$this->preco4',comis4='$this->comis4' WHERE id=$this->id");
            $ps->execute();
            $ps->close();

        }

    }

    public function delete($con){

        $ps = $con->getConexao()->prepare("DELETE promocao_cliente WHERE id=$this->id");
        $ps->execute();
        $ps->close();

    }


}
