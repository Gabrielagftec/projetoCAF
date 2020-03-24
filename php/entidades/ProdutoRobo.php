<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CategoriaDocumento
 *
 * @author Renan
 */
class ProdutoRobo {
   
    public $id;
    public $nome;
    public $imagem;
    public $empresa;
    public $fabricante;
    public $preco;
    public $descricao;
    public $categoria;
    public $url;
    public $qualidade;
    public $classe;
    public $subclasse;
    
    function __construct() {
        $this->id = 0;
        $this->nome = "";
        $this->imagem = "";
        $this->empresa = null;
        $this->preco = 0;
        $this->fabricante = "";
        $this->categoria = "";
        $this->url = "";
        $this->qualidade = 0;
        $this->classe = 0;
        $this->subclasse = 0;
    }
    
}
