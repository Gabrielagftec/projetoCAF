<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Fornecedor
 *
 * @author Renan
 */
class ProdutoReduzido {

    public $id;
    public $nome;
    public $codigo;
    public $imagem;
    public $quantidade_unidade;
    public $unidade;
    public $valor_base;

    function __construct() {

        $this->id = 0;
        $this->nome = "";
        $this->codigo = 0;
        $this->imagem = "";
        $this->imagem_padrao = "";
        $this->quantidade_unidade = 1;
        $this->unidade = "Gl";
        $this->valor_base = 0;
        
    }

}
