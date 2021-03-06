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
class CFOP {

    public static $VENDA_DENTRO_ESTADO = "5106";
    public static $VENDA_FORA_ESTADO = "6106";
    public static $REMESSA_DEPOSITO = "5905";
    public static $REMESSA_DEPOSITO_FORA_ESTADO = "6905";
    public static $REMESSA_DEPOSITO_2 = "5905";
    public static $RETORNO_DEPOSITO = "5907";
    public static $ISENTO = "6110";
    public static $VENDA_DENTRO_ESTADO_2 = "5102";
    public static $VENDA_DENTRO_ESTADO_F = "6102";
    public static $TRANSFERENCIA = "6152";
    public static $TRANSFERENCIA_DENTRO_ESTADO = "5152";
    public static $ANULACAO_DENTRO_ESTADO = "5206";
    public static $ANULACAO_FORA_ESTADO = "6206";
    public static $DEVOLUCAO = "6202";
    public static $COMPRA = "1202";
    public static $COMPRA_F = "2202";
    public static $OUTRA_ENTRADA = "1949";
    public static $OUTRA_ENTRADA_F = "2949";
    public static $VENDA_PRODUCAO_ESTABELECIMENTO = "5101";
    public static $VENDA_PRODUCAO_ESTABELECIMENTO_FORA = "6101";
    public static $MERC_CONSERTO = "5915";
    public static $MERC_CONSERTO_F = "6915";

    public static $EMPRESTIMO = "5949";
    public static $ICMS = "5602";

    
    public static function descricao($cfop) {

        $cfop = str_replace(array("."), array(""), $cfop . "");

        if ($cfop === self::$VENDA_DENTRO_ESTADO) {
            return "Venda Merc.A.R.T.que nao deva por ele transitar";
        } else if ($cfop === self::$VENDA_FORA_ESTADO) {
            return "Venda Merc.A.R.T.que nao deva por ele transitar";
        } else if ($cfop === self::$REMESSA_DEPOSITO) {
            return "Remessa para deposito fechado ou armazem geral";
        }else if ($cfop === self::$REMESSA_DEPOSITO_FORA_ESTADO) {
            return "Retorno de mercadoria deposito fechado";
        }else if ($cfop === self::$REMESSA_DEPOSITO_2) {
            return "Remessa de mercadoria para deposito";
        } else if ($cfop === self::$RETORNO_DEPOSITO) {
            return "Retorno de mercadoria para deposito";
        } else if ($cfop === self::$ISENTO) {
            return "Venda de merc destinada a zona franca de manaus";
        } else if ($cfop === self::$VENDA_DENTRO_ESTADO_2) {
            return "Venda de mercadoria dentro do estado.";
        } else if ($cfop === self::$VENDA_DENTRO_ESTADO_F) {
            return "Venda de mercadoria dentro do estado.";
        } else if ($cfop === self::$TRANSFERENCIA) {
            return "Transferencia de mercadoria fora do estado";
        }else if ($cfop === self::$TRANSFERENCIA_DENTRO_ESTADO) {
            return "Transferencia de mercadoria dentro do estado";
        }else if ($cfop === self::$ANULACAO_FORA_ESTADO) {
            return "Anulacao de valor referente a prestacao de servico";
        }else if ($cfop === self::$ANULACAO_DENTRO_ESTADO) {
            return "Anulacao de valor referente a prestacao de servico";
        }else if ($cfop === self::$DEVOLUCAO) {
            return "Devolucao de mercadoria";
        }else if ($cfop === self::$COMPRA) {
            return "Compra de produtos de fornecedor, sem subst trib";
        }else if ($cfop === self::$COMPRA_F) {
            return "Compra de produtos de fornecedor, sem subst trib";
        }else if ($cfop === self::$OUTRA_ENTRADA) {
            return "Outras Entradas";
        }else if ($cfop === self::$OUTRA_ENTRADA_F) {
            return "Outras Entradas fora do estado";
        }else if ($cfop === self::$VENDA_PRODUCAO_ESTABELECIMENTO) {
            return "Venda de producao fora do estabelecimento";
        }else if ($cfop === self::$VENDA_PRODUCAO_ESTABELECIMENTO_FORA) {
            return "Venda de producao fora do estabelecimento";
        }else if ($cfop === self::$MERC_CONSERTO) {
            return "Remessa de mercadoria para conserto";
        }else if ($cfop === self::$MERC_CONSERTO_F) {
            return "Remessa de mercadoria para conserto fora do estado";
        }else if ($cfop === self::$EMPRESTIMO) {
            return "Emprestimo de Ativo Imobilizado em comodato";
        }else if ($cfop === self::$ICMS) {
            return "Transferencia de saldo credor de ICMS para outro estabelecimento da mesma empresa, destinado a compensacao de saldo devedor de ICMS";
        }

        return "Indefinido";
    }

}
