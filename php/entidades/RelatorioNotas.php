
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
class RelatorioNotas extends Relatorio {

    public function __construct($empresa = null) {

        if ($empresa === null) {

            return;
        }


        parent::__construct("SELECT k.data_emissao as 'data',k.cancelada as 'cancelada',k.tipo as 'tipo',k.pessoa as 'pessoa',IFNULL(p.id,'-----') as 'pedido',k.numero as 'numero',k.cfop as 'cfop',ROUND(k.total,2) as 'total',IFNULL(CASE WHEN ret.numero = 0 THEN 'Aguardo...' ELSE ret.numero END,'--------') as 'retorno' FROM (SELECT n.id as 'id',(CASE WHEN n.saida = 1 THEN 'Saida' ELSE 'Entrada' END) as 'tipo',n.id_empresa as 'id_empresa',n.data_emissao as 'data_emissao',IFNULL(c.razao_social,f.nome) as 'pessoa',CASE WHEN n.cancelada THEN 'CANCELADA' ELSE 'ATIVA' END as 'cancelada',n.id_pedido as 'id_pedido',MAX(pn.cfop) as 'cfop',SUM(pn.quantidade*pn.valor_unitario) as 'total',n.numero as 'numero' FROM nota n LEFT JOIN cliente c ON c.id=n.id_cliente LEFT JOIN fornecedor f ON f.id=n.id_fornecedor INNER JOIN produto_nota pn ON n.id=pn.id_nota WHERE n.excluida=false AND n.emitida=true AND n.id_empresa=$empresa->id AND n.chave <> '' GROUP BY n.id) k LEFT JOIN pedido p ON p.id=k.id_pedido AND k.id_empresa=p.id_empresa LEFT JOIN nota ret ON ret.id_pedido=p.id AND ret.id_empresa <> p.id_empresa", 33);

        $this->nome = "Relatorio de Notas";

        $data = new CampoRelatorio('data', 'Data', 'D');
        $data->ordem = 1;
        
        $cancelada = new CampoRelatorio('cancelada', 'Status', 'T');
        
        $pessoa = new CampoRelatorio('pessoa', 'Destinatario', 'T',false,false,20);
        
        $tipo = new CampoRelatorio('tipo', 'Tipo', 'T');
        
        $id_pedido = new CampoRelatorio('pedido', 'Pedido', 'N');
        
        $numero = new CampoRelatorio('numero', 'Numero', 'N');
        
        $cfop = new CampoRelatorio('cfop', 'CFOP', 'T');
        
        $total = new CampoRelatorio('total', 'Total', 'N');
     
        $retorno = new CampoRelatorio('retorno', 'Retorno', 'T');
     
        $this->campos = array(
            $data,
            $cancelada,
            $pessoa,
            $tipo,
            $id_pedido,
            $numero,
            $cfop,
            $total,
            $retorno);
    }

}