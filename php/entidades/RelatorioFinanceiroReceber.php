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
class RelatorioFinanceiroReceber extends Relatorio {

    public function getObservacoes($empresa) {
        
        $con = new ConnectionFactory();
        
        $cnpjs = array($empresa->cnpj->valor);
        
        $f = $empresa->getFiliais($con);
        
        foreach($f as $key=>$value){
            $cnpjs[] = $value->cnpj->valor;
        }
        
        $f = "('000'";
        
        foreach($cnpjs as $key=>$value){
            $f .= ",'".$value."'";
        }
        
        $f .= ")";
        
        $id_empresa = "($empresa->id)";
        
        if($empresa->tipo_empresa === 5){
            
            $id_empresa = "($empresa->id";
            
            $ps = $con->getConexao()->prepare("SELECT id FROM empresa WHERE empresa_adm=$empresa->id");
            $ps->execute();
            $ps->bind_result($id_emp);
            while($ps->fetch()){
                $id_empresa .= ",$id_emp";
            }
            $ps->close();
            
            $id_empresa .= ")";
            
        }
        
        $valor = 0;
        
        $sql = "SELECT SUM(k.valor) FROM (SELECT (GREATEST(ROUND((vencimento.valor-(SUM(IFNULL(movimento.valor,0)))),2),0)) as 'valor' FROM nota INNER JOIN vencimento ON vencimento.id_nota = nota.id LEFT JOIN cliente ON cliente.id=nota.id_cliente LEFT JOIN fornecedor ON fornecedor.id=nota.id_fornecedor LEFT JOIN movimento ON movimento.id_vencimento=vencimento.id AND movimento.data<=FROM_UNIXTIME(".$this->campos[0]->fim."/1000) WHERE nota.id_empresa IN $id_empresa AND nota.cancelada=false AND nota.saida=true AND nota.excluida=false ";
        
        $sql .= "AND vencimento.data>=FROM_UNIXTIME(".$this->campos[0]->inicio."/1000) AND vencimento.data<=FROM_UNIXTIME(".$this->campos[0]->fim."/1000) AND (CASE WHEN nota.saida THEN cliente.cnpj NOT IN $f ELSE fornecedor.cnpj NOT IN $f END) ";
        
        $sql .= " GROUP BY vencimento.id) k";
        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($val);
        if($ps->fetch()){
            $valor = $val;
        }        
        $ps->close();
        
        if($empresa->tipo_empresa !== 5){
        
            return "Relatorio de contas a pagar e receber da empresa '$empresa->nome' de " .
                    date("d/m/Y", doubleval($this->campos[0]->inicio . "") / 1000) .
                    " ate " . date("d/m/Y", doubleval($this->campos[0]->fim . "") / 1000).
                    ", Receber: R$ $valor";

        }else{
            
            return "Relatorio de contas a pagar e receber das empresas " .
                    date("d/m/Y", doubleval($this->campos[0]->inicio . "") / 1000) .
                    " ate " . date("d/m/Y", doubleval($this->campos[0]->fim . "") / 1000).
                    ", Receber: R$ $valor";
            
        }
        
    }

    public $copia_sql = "";

    public function attSql(){

        $this->sql = str_replace(array("@data_final@"), array("FROM_UNIXTIME(".$this->campos[0]->fim."/1000)"), $this->copia_sql);

    }

    public function __construct($empresa = null) {

        if ($empresa === null) {

            return;
        }
        
        $con = new ConnectionFactory();
        
        $cnpjs = array($empresa->cnpj->valor);
        
        $f = $empresa->getFiliais($con);
        
        foreach($f as $key=>$value){
            $cnpjs[] = $value->cnpj->valor;
        }
        
        $f = "('000'";
        
        foreach($cnpjs as $key=>$value){
            $f .= ",'".$value."'";
        }
        
        $f .= ")";
        
        $id_empresa = "($empresa->id)";
        
        if($empresa->tipo_empresa === 5){
            
            $id_empresa = "($empresa->id";
            
            $ps = $con->getConexao()->prepare("SELECT id FROM empresa WHERE empresa_adm=$empresa->id");
            $ps->execute();
            $ps->bind_result($id_emp);
            while($ps->fetch()){
                $id_empresa .= ",$id_emp";
            }
            $ps->close();
            
            $id_empresa .= ")";
            
        }
        
        if($empresa->tipo_empresa !== 5){

            $this->copia_sql = "SELECT l.cliente as 'cliente',l.valor as 'valor',l.vencimento as 'vencimento',l.ficha as 'ficha',l.data as 'data',l.mes as 'mes',l.ano as 'ano',l.dia as 'dia',l.numero_nota as 'numero_nota' FROM (SELECT (CASE WHEN nota.saida THEN CONCAT(CONCAT(nota.id_cliente,' - '),cliente.razao_social) ELSE '---------' END) as 'cliente',(CASE WHEN nota.saida THEN '----------' ELSE CONCAT(CONCAT(nota.id_fornecedor,' - '),fornecedor.nome) END) as 'fornecedor',(GREATEST(ROUND((vencimento.valor-(SUM(IFNULL(movimento.valor,0)))),2),0)) as 'valor',UNIX_TIMESTAMP(vencimento.data)*1000 as 'vencimento', nota.ficha as 'ficha', vencimento.data as 'data', MONTH(vencimento.data) as 'mes', YEAR(vencimento.data) as 'ano', DAY(vencimento.data) as 'dia', nota.numero as 'numero_nota' FROM nota INNER JOIN vencimento ON vencimento.id_nota = nota.id LEFT JOIN cliente ON cliente.id=nota.id_cliente LEFT JOIN fornecedor ON fornecedor.id=nota.id_fornecedor LEFT JOIN movimento ON movimento.id_vencimento=vencimento.id AND movimento.id NOT IN (SELECT m.estorno FROM movimento m) AND movimento.data<@data_final@ WHERE nota.id_empresa IN $id_empresa AND nota.saida=true AND nota.cancelada=false AND nota.excluida=false AND (CASE WHEN nota.saida THEN cliente.cnpj NOT IN $f ELSE fornecedor.cnpj NOT IN $f END) GROUP BY vencimento.id) l WHERE l.valor>2";

            parent::__construct($this->copia_sql, 20);

        }else{

            $this->copia_sql = "SELECT l.cliente as 'cliente',l.valor as 'valor',l.vencimento as 'vencimento',l.ficha as 'ficha',l.data as 'data',l.mes as 'mes',l.ano as 'ano',l.dia as 'dia',l.numero_nota as 'numero_nota',l.nome_empresa as 'nome_empresa' FROM (SELECT (CASE WHEN nota.saida THEN CONCAT(CONCAT(nota.id_cliente,' - '),cliente.razao_social) ELSE '---------' END) as 'cliente',(CASE WHEN nota.saida THEN '----------' ELSE CONCAT(CONCAT(nota.id_fornecedor,' - '),fornecedor.nome) END) as 'fornecedor',(GREATEST(ROUND((vencimento.valor-(SUM(IFNULL(movimento.valor,0)))),2),0)) as 'valor',UNIX_TIMESTAMP(vencimento.data)*1000 as 'vencimento', nota.ficha as 'ficha', vencimento.data as 'data', MONTH(vencimento.data) as 'mes', YEAR(vencimento.data) as 'ano', DAY(vencimento.data) as 'dia', nota.numero as 'numero_nota',empresa.nome as 'nome_empresa' FROM nota INNER JOIN empresa ON empresa.id=nota.id_empresa INNER JOIN vencimento ON vencimento.id_nota = nota.id LEFT JOIN cliente ON cliente.id=nota.id_cliente LEFT JOIN fornecedor ON fornecedor.id=nota.id_fornecedor LEFT JOIN movimento ON movimento.id_vencimento=vencimento.id AND movimento.id NOT IN (SELECT m.estorno FROM movimento m) AND movimento.data<@data_final@ WHERE nota.id_empresa IN $id_empresa AND nota.saida=true AND nota.cancelada=false AND nota.excluida=false AND (CASE WHEN nota.saida THEN cliente.cnpj NOT IN $f ELSE fornecedor.cnpj NOT IN $f END) GROUP BY vencimento.id) l WHERE l.valor>2";

            parent::__construct($this->copia_sql, 20);
        }
        
        $this->nome = "Contas Receber";

        $cliente = new CampoRelatorio('cliente', 'Cliente', 'T');
        $cliente->porcentagem_coluna_pdf = 56;

        $valor = new CampoRelatorio('valor', 'Valor da Pendencia', 'N');
        $valor->agrupado = true;
        $valor->porcentagem_coluna_pdf = 10;

        $dia = new CampoRelatorio('dia', 'Dia', 'N', false, true);
        $dia->agrupado = true;
        $dia->porcentagem_coluna_pdf = 5;

        $mes = new CampoRelatorio('mes', 'Mes', 'N', false, true);
        $mes->porcentagem_coluna_pdf = 5;

        $ano = new CampoRelatorio('ano', 'Ano', 'N', false, true);
        $ano->porcentagem_coluna_pdf = 5;

        $ficha = new CampoRelatorio('ficha', 'Ficha', 'T');
        $ficha->porcentagem_coluna_pdf = 10;
        $ficha->agrupado = true;

        $data = new CampoRelatorio('data', 'Data', 'D');
        $data->somente_filtro = true;

        $nota = new CampoRelatorio('numero_nota', 'Numero Nota', 'N');
        $nota->agrupado = true;
        $nota->porcentagem_coluna_pdf = 10;

        $this->campos = array(
            $data,
            $cliente,
            $valor,
            $dia,
            $mes,
            $ano,
            $ficha,
            $nota);
        
        if($empresa->tipo_empresa === 5){
            
            $emp = new CampoRelatorio('nome_empresa', 'Nome Empresa', 'T');
            $emp->porcentagem_coluna_pdf = 10;
            
            $this->campos[] = $emp;
            
            $this->campos[1]->porcentagem_coluna_pdf -= 10;
            
        }
        
    }

}
