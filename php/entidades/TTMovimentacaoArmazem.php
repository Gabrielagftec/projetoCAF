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
class TTMovimentacaoArmazem extends TipoTarefa {

    function __construct($id_empresa) {

        parent::__construct(94, $id_empresa);

        $this->nome = "Movimentacao Armazem";
        $this->tempo_medio = 1;
        $this->prioridade = 1;
        $this->cargos = array(
            Logistica::CF_SEPARADOR(new Empresa($id_empresa))
        );
        $this->carregarDados();
    }

    public function aoFinalizar() {
        
    }

}
