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
class TTVerificacaoCadastro extends TipoTarefa {

    function __construct($id_empresa) {

        parent::__construct(92, $id_empresa);

        $this->nome = "Verificacao duplicidade de cliente";
        $this->tempo_medio = 0.2;
        $this->prioridade = 2;
        $this->carregarDados();
    }

    public function aoFinalizar($tarefa, $usuario) {

       
    }

}
