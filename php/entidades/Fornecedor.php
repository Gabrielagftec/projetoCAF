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
class Fornecedor {

    public $id;
    public $nome;
    public $email;
    public $telefones;
    public $endereco;
    public $cnpj;
    public $excluido;
    public $empresa;
    public $inscricao_estadual;
    public $habilitado;
    public $codigo;
    public $codigo_contimatic;

    public $produtos;

    public $vendedores;

    function __construct() {

        $this->id = 0;
        $this->nome = "";
        $this->telefones = array();
        $this->endereco = new Endereco();
        $this->excluido = false;
        $this->cnpj = new CNPJ("");
        $this->empresa = null;
        $this->habilitado = false;
        $this->email = new Email("");
        $this->codigo = 0;
        $this->codigo_contimatic = 0;

        $this->produtos = null;
        
        $this->vendedores = null;

    }


    public function setDocumentos($docs, $con) {

        $ps = $con->getConexao()->prepare("UPDATE documento SET id_entidade=0 WHERE tipo_entidade='FOR' AND id_entidade=$this->id");
        $ps->execute();
        $ps->close();

        foreach ($docs as $key => $doc) {

            $doc->merge($con);

            $ps = $con->getConexao()->prepare("UPDATE documento SET tipo_entidade='FOR', id_entidade=$this->id WHERE id=$doc->id");
            $ps->execute();
            $ps->close();
        }
    }

    public function getDocumentos($con) {

        $categorias_documento = Sistema::getCategoriaDocumentos();

        $docs = array();

        $ps = $con->getConexao()->prepare("SELECT id,UNIX_TIMESTAMP(data_insercao)*1000,id_categoria,numero,link FROM documento WHERE tipo_entidade='FOR' AND id_entidade=$this->id AND excluido=false");
        $ps->execute();
        $ps->bind_result($id, $data, $id_categoria, $numero, $link);

        while ($ps->fetch()) {

            $d = new Documento();

            $d->id = $id;
            $d->data_insercao = $data;
            $d->numero = $numero;
            $d->link = $link;

            foreach ($categorias_documento as $key => $value) {
                if ($value->id == $id_categoria) {

                    $d->categoria = $value;

                    $docs[] = $d;

                    continue 2;
                }
            }
        }

        $ps->close();

        return $docs;
    }

    public function getVendedores($con){

        $usuarios = $this->empresa->getUsuarios($con,0,1000,"usuario.id IN (SELECT r.id_vendedor FROM vendedor_fornecedor r WHERE r.id_fornecedor=$this->id)");

        $this->vendedores = $usuarios;

        return $usuarios;

    }

    public function getProdutos($con){


        $inp = "(-1";

        $this->produtos = array();

        $ps = $con->getConexao()->prepare("SELECT id,id_produto,preco1,comis1,preco2,comis2,preco3,comis3,preco4,comis4,UNIX_TIMESTAMP(validade)*1000,valor FROM produto_fornecedor WHERE id_fornecedor = $this->id");
        $ps->execute();
        $ps->bind_result($id,$id_produto,$preco1,$comis1,$preco2,$comis2,$preco3,$comis3,$preco4,$comis4,$validade,$valor);
        while($ps->fetch()){

            $p = new ProdutoFornecedor();
            $p->id = $id;
            $p->id_produto = $id_produto;
            $p->preco1 = $preco1;
            $p->fornecedor = $this;
            $p->comis1 = $comis1;
            $p->preco2 = $preco2;
            $p->comis2 = $comis2;
            $p->valor = $valor;
            $p->preco3 = $preco3;
            $p->comis3 = $comis3;
            $p->preco4 = $preco4;
            $p->comis4 = $comis4;
            $p->validade = $validade;

            $this->produtos[] = $p;

            $inp .= ",$id_produto";

        }
        $ps->close();

        $inp .= ")";

        $p = $this->empresa->getProdutos($con,0,1000,"produto.id IN $inp","");

        foreach ($this->produtos as $key => $value) {
            
            foreach ($p as $k => $v2) {
                
                if($v2->id === $value->id_produto){

                    $value->produto = $v2;
                    continue 2;

                }

            }

        }


        return $this->produtos;

    }

    public function merge($con) {

        if($this->codigo === 0){
            
            $ps = $con->getConexao()->prepare("SELECT IFNULL(MAX(codigo)+1,0) FROM fornecedor WHERE id_empresa=".$this->empresa->id);
            $ps->execute();
            $ps->bind_result($idn);
            
            if($ps->fetch()){
                
                $this->codigo = $idn;
                
            }
            
            $ps->close();
            
        }

        if ($this->id == 0) {
            
            
            $ps = $con->getConexao()->prepare("INSERT INTO fornecedor(nome,cnpj,excluido,id_empresa,inscricao_estadual,habilitado,codigo) VALUES('" . addslashes($this->nome) . "','" . $this->cnpj->valor . "',false," . $this->empresa->id . ",'$this->inscricao_estadual',".($this->habilitado?"true":"false").",$this->codigo)");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
        } else {

            $ps = $con->getConexao()->prepare("UPDATE fornecedor SET nome = '" . addslashes($this->nome) . "', cnpj='" . $this->cnpj->valor . "',excluido=false, id_empresa=" . $this->empresa->id . ", inscricao_estadual='$this->inscricao_estadual', habilitado=".($this->habilitado?"true":"false").",codigo=$this->codigo WHERE id = " . $this->id);
            $ps->execute();
            $ps->close();
        }
        
        if($this->codigo_contimatic > 0){
            
            $ps = $con->getConexao()->prepare("UPDATE fornecedor SET codigo_contimatic=$this->codigo_contimatic WHERE id=$this->id");
            $ps->execute();
            $ps->close();
            
        }

        $this->email->merge($con);

        $ps = $con->getConexao()->prepare("UPDATE email SET id_entidade=" . $this->id . ", tipo_entidade='FOR' WHERE id = " . $this->email->id);
        $ps->execute();
        $ps->close();

        $this->endereco->merge($con);

        $ps = $con->getConexao()->prepare("UPDATE endereco SET id_entidade=" . $this->id . ", tipo_entidade='FOR' WHERE id = " . $this->endereco->id);
        $ps->execute();
        $ps->close();

        $tels = array();
        $ps = $con->getConexao()->prepare("SELECT id,numero FROM telefone WHERE tipo_entidade='FOR' AND id_entidade=$this->id AND excluido=false");
        $ps->execute();
        $ps->bind_result($idt, $numerot);
        while ($ps->fetch()) {
            $t = new Telefone($numerot);
            $t->id = $idt;
            $tels[] = $t;
        }

        foreach ($tels as $key => $value) {

            foreach ($this->telefones as $key2 => $value2) {

                if ($value->id == $value2->id) {

                    continue 2;
                }
            }

            $value->delete($con);
        }

        foreach ($this->telefones as $key => $value) {

            $value->merge($con);

            $ps = $con->getConexao()->prepare("UPDATE telefone SET tipo_entidade='FOR', id_entidade=$this->id WHERE id=" . $value->id);
            $ps->execute();
            $ps->close();
        }

        if($this->produtos != null){

            $atuais = array();

            $ps = $con->getConexao()->prepare("SELECT id FROM produto_fornecedor WHERE id_fornecedor=$this->id");
            $ps->execute();
            $ps->bind_result($id);
            while($ps->fetch()){

                $p = new ProdutoFornecedor();
                $p->id = $id;
                $atuais[] = $p;

            }
            $ps->close();

            foreach ($atuais as $key => $value) {
            
                foreach ($this->produtos as $key2 => $value2) {
                    
                    if($value->id === $value2->id)
                        continue 2;

                }

                $value->delete($con);

            }

            foreach ($this->produtos as $key => $value) {

                $value->merge($con);

            }

        }

        if($this->vendedores != null){

            $ps = $con->getConexao()->prepare("DELETE FROM vendedor_fornecedor WHERE id_fornecedor=$this->id");
            $ps->execute();
            $ps->close();

            foreach ($this->vendedores as $key => $value) {
                
                $ps = $con->getConexao()->prepare("INSERT INTO vendedor_fornecedor(id_fornecedor,id_vendedor) VALUES($this->id,$value->id)");
                $ps->execute();
                $ps->close();

            }

        }


    }

    public function delete($con) {

        $ps = $con->getConexao()->prepare("UPDATE fornecedor SET excluido = true WHERE id = " . $this->id);
        $ps->execute();
        $ps->close();
    }

}
