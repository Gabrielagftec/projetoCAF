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
class RelatorioClientesMaisCompram extends Relatorio {

    public function  getObservacoes($empresa){

        return "Relatorio de clientes e numero de compras";

    }

    public function __construct($empresa = null) {


        $sql = "SELECT "
                . "c.codigo as 'codigo',"
                . "c.razao_social as 'razao_social',"
                . "cid.nome as 'cidade',"
                . "est.sigla as 'estado',"
                . "ROUND(SUM(k.total),2) as 'total_comprado',"
                . "COUNT(*) as 'numero_pedidos',"
                . "MAX(k.data) as 'ultima_compra_nf',"
                . "DATE_FORMAT(MAX(k.data),'%d/%m/%Y') as 'ultima_compra' "
                . "FROM cliente c "
                . "INNER JOIN "
                . "(SELECT p.id as 'id',((pp.juros+pp.valor_base+pp.icms+pp.ipi)*pp.quantidade) as 'total',"
                . "p.id_cliente as 'id_cliente',p.data as 'data' FROM pedido p "
                . "INNER JOIN produto_pedido_saida pp ON pp.id_pedido=p.id "
                . "WHERE p.id_status <> 10 "
                . "AND p.excluido=false GROUP BY p.id) k "
                . "ON k.id_cliente=c.id "
                . "INNER JOIN endereco e ON e.id_entidade=c.id AND e.tipo_entidade='CLI' "
                . "INNER JOIN cidade cid ON e.id_cidade=cid.id "
                . "INNER JOIN estado est ON est.id=cid.id_estado GROUP BY c.id";
        
        parent::__construct($sql, 337);

        $this->nome = "Relatorio Clientes Mais Compram";
        
        $codigo = new CampoRelatorio('codigo', 'Cod', 'N',false,false,10);
        $nome = new CampoRelatorio('razao_social', 'Nome', 'T',false,false,30);
        $nome->ordem = 1;
        
        $cidade = new CampoRelatorio('cidade', 'Cidade', 'T',false,false,20);
        $estado = new CampoRelatorio('estado', 'Estado', 'T',false,false,5);
        $total = new CampoRelatorio('total_comprado', 'Total Comprado', 'N',false,false,15);
        
        $numero_pedidos = new CampoRelatorio('numero_pedidos', 'Numero Compras', 'N',false,false,5);
        $numero_pedidos->ordem = 2;
        
        $ultima_compra_nf = new CampoRelatorio('ultima_compra_nf', 'Ultima Compra', 'D',false,false,15);
        $ultima_compra_nf->somente_filtro = true;
        $ultima_compra_nf->inicio = (round(microtime(true))*1000)-365*24*60*60*1000;
        $ultima_compra_nf->fim = (round(microtime(true))*1000);
        
        $ultima_compra = new CampoRelatorio('ultima_compra', 'Ultima Compra', 'T',false,false,15);
        
        $this->campos = array(
            $ultima_compra_nf,
            $codigo,
            $nome,
            $cidade,
            $estado,
            $total,
            $numero_pedidos,
            $ultima_compra
            );
    }

}
