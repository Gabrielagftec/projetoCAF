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
class RelatorioPosVenda extends Relatorio {

    public function  getObservacoes($empresa){

        return "Relatorio pos venda";

    }

    public function __construct($empresa = null) {


        $sql = "SELECT "
                . "c.codigo as 'codigo',"
                . "c.razao_social as 'razao_social',"
                . "cid.nome as 'cidade',"
                . "est.sigla as 'estado' "
                . "FROM cliente c "
                . "INNER JOIN endereco end ON end.id_entidade=c.id AND end.tipo_entidade='CLI' "
                . "INNER JOIN cidade cid ON end.id_cidade=cid.id "
                . "INNER JOIN estado est ON est.id=cid.id_estado "
                . "WHERE c.id_empresa=$empresa->id AND c.classe_virtual=8 ";
        
        parent::__construct($sql, 377);

        $this->nome = "Relatorio Pos Venda";
        
        $codigo = new CampoRelatorio('codigo', 'Cod', 'N',false,false,5);
        $nome = new CampoRelatorio('razao_social', 'Nome', 'T',false,false,50);
        $nome->ordem = 1;
        
        $cidade = new CampoRelatorio('cidade', 'Cidade', 'T',false,false,30);
        $estado = new CampoRelatorio('estado', 'Estado', 'T',false,false,15);
        
        $this->campos = array(
            $codigo,
            $nome,
            $cidade,
            $estado
            );
    }

}
