<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CotacaoEntrada
 *
 * @author Renan
 */
class RelatorioFornecedores extends Relatorio {

    public function getObservacoes($empresa) {
        
        return "";
        
    }

    public function __construct($empresa = null) {

        if ($empresa === null) {

            return;
        }
        
        if($empresa->tipo_empresa !== 3){
            parent::__construct("SELECT f.codigo as 'codigo',f.nome as 'nome',f.cnpj as 'cnpj',c.nome as 'cidade',est.sigla as 'estado' FROM fornecedor f INNER JOIN endereco e ON e.id_entidade=f.id AND e.tipo_entidade='FOR' INNER JOIN cidade c ON c.id=e.id_cidade INNER JOIN estado est ON c.id_estado=est.id WHERE f.habilitado AND f.id_empresa=$empresa->id", 330);
            
        }else{
            parent::__construct("SELECT f.codigo as 'codigo',f.nome as 'nome',f.cnpj as 'cnpj',c.nome as 'cidade',est.sigla as 'estado',emp.nome as 'nome_empresa' FROM fornecedor f INNER JOIN endereco e ON e.id_entidade=f.id AND e.tipo_entidade='FOR' INNER JOIN cidade c ON c.id=e.id_cidade INNER JOIN estado est ON c.id_estado=est.id INNER JOIN empresa emp ON emp.id=f.id_empresa WHERE f.habilitado AND (emp.empresa_vendas=$empresa->id OR emp.id=$empresa->id)", 330);
        }
        $this->nome = "Fornecedores";
        
        $codigo = new CampoRelatorio('codigo', 'Codigo', 'N');
        $codigo->porcentagem_coluna_pdf = 10;
        
        $nome = new CampoRelatorio('nome', 'Fornecedor', 'T');
        $nome->porcentagem_coluna_pdf = 40;

        $cnpj = new CampoRelatorio('cnpj', 'CNPJ', 'T');
        $cnpj->porcentagem_coluna_pdf = 20;

        $cidade = new CampoRelatorio('cidade', 'Cidade', 'T', false, true);
        $cidade->porcentagem_coluna_pdf = 10;

        $estado = new CampoRelatorio('estado', 'Estado', 'T', false, true);
        $estado->porcentagem_coluna_pdf = 5;

        $nome_empresa = new CampoRelatorio('nome_empresa', 'Nome Empresa', 'T', false, true);
        $nome_empresa->porcentagem_coluna_pdf = 15;

        $this->campos = array(
            $codigo,
            $nome,
            $cnpj,
            $cidade,
            $estado);
        
        if($empresa->tipo_empresa === 3){
            
            $this->campos[] = $nome_empresa;
            
        }
        
        
    }

}
