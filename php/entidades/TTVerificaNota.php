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
class TTVerificaNota extends TipoTarefa {

    function __construct($id_empresa) {

        parent::__construct(52, $id_empresa);

        $this->nome = "Verificar Nota";
        $this->tempo_medio = 0.2;
        $this->prioridade = 2;
        $this->cargos = array(
            Empresa::CF_CONTADOR($id_empresa)
        );
        $this->carregarDados();
    }

}
