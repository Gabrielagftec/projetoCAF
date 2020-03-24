<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Logistica
 *
 * @author Renan
 */

class TabelaLogistica {

	public $id;
	public $empresa;
	public $taxa_minima;
	public $toxicidade;
	public $largura;
	public $altura;
	public $comprimento;
	public $peso_medio;
	public $peso_maximo;
	public $valor_estoque;
	public $cintagem;
	public $aplicacao;
	public $strech;
	public $despaletizado;
	public $notas_por_veiculo;
	public $media_embalagem_veiculo;
	public $palets_veiculo;
	public $palets_mes;
	public $palets_dia;
	public $carreta_dia;
	public $carreta_mes;
	public $pico_carretas;
	public $strech_film;
	public $arquivo_movimentacao_diaria;
	public $separacao_palet_fechado;
	public $separacao_fracionada;
	public $embalagens_por_pedido;
	public $sku_por_pedido;
	public $pico_pedidos;
	public $despaletizado_saida;
	public $paletizado_saida;
	public $carregamento_transportadora_cd;
	public $tipos_veiculo_saida;
	public $arquivo_movimentacao_saida;
	public $rembalagens;
	public $etiquetagem;


	function __construct()
	{

		$this->id = 0;
		$this->numero_palets = 0;
		$this->taxa_minima = 0;
		$this->empresa = null;
		$this->toxicidade = 0;
		$this->largura = 0;
		$this->altura = 0;
		$this->comprimento = 0;
		$this->peso_medio = 0;
		$this->peso_maximo = 0;
		$this->valor_estoque = 0;
		$this->cintagem = 0;
		$this->aplicacao = 0;
		$this->strech = 0;
		$this->despaletizado = 0;
		$this->notas_por_veiculo = 0;
		$this->media_embalagem_veiculo = 0;
		$this->palets_veiculo = 0;
		$this->palets_mes = 0;
		$this->palets_dia = 0;
		$this->carreta_dia = 0;
		$this->carreta_mes = 0;
		$this->pico_carretas = 0;
		$this->strech_film = 0;
		$this->arquivo_movimentacao_diaria = 0;
		$this->separacao_palet_fechado = 0;
		$this->separacao_fracionada = 0;
		$this->embalagens_por_pedido = 0;
		$this->sku_por_pedido = 0;
		$this->pico_pedidos = 0;
		$this->despaletizado_saida = 0;
		$this->paletizado_saida = 0;
		$this->carregamento_transportadora_cd = 0;
		$this->tipos_veiculo_saida = 0;
		$this->arquivo_movimentacao_saida = 0;
		$this->rembalagens = 0;
		$this->etiquetagem = 0;

	}

	public function merge($con){

		if($this->id == 0){

			$sql = "INSERT INTO tabela_logistica(
				taxa_minima,
				id_empresa,
				numero_palets,
				toxicidade,
				largura,
				altura,
				comprimento,
				peso_medio,
				peso_maximo,
				valor_estoque,
				cintagem,
				aplicacao,
				strech,
				despaletizado,
				notas_por_veiculo,
				media_embalagem_veiculo,
				palets_veiculo,
				palets_mes,
				palets_dia,
				carreta_dia,
				carreta_mes,
				pico_carretas,
				strech_film,
				arquivo_movimentacao_diaria,
				separacao_palet_fechado,
				separacao_fracionada,
				embalagens_por_pedido,
				sku_por_pedido,
				pico_pedidos,
				despaletizado_saida,
				paletizado_saida,
				carregamento_transportadora_cd,
				tipos_veiculo_saida) VALUES(
				$this->taxa_minima,
				".$this->empresa->id.",
				$this->numero_palets,
				$this->toxicidade,
				$this->largura,
				$this->altura,
				$this->comprimento,
				$this->peso_medio,
				$this->peso_maximo,
				$this->valor_estoque,
				$this->cintagem,
				$this->aplicacao,
				$this->strech,
				$this->despaletizado,
				$this->notas_por_veiculo,
				$this->media_embalagem_veiculo,
				$this->palets_veiculo,
				$this->palets_mes,
				$this->palets_dia,
				$this->carreta_dia,
				$this->carreta_mes,
				$this->pico_carretas,
				$this->strech_film,
				$this->arquivo_movimentacao_diaria,
				$this->separacao_palet_fechado,
				$this->separacao_fracionada,
				$this->embalagens_por_pedido,
				$this->sku_por_pedido,
				$this->pico_pedidos,
				$this->despaletizado_saida,
				$this->paletizado_saida,
				$this->carregamento_transportadora_cd,
				$this->tipos_veiculo_saida
			)";
		
			$ps = $con->getConexao()->prepare($sql);
			$ps->execute();
			$this->id = $ps->insert_id;
			$ps->close();

		}else{

			$sql = "UPDATE tabela_logistica SET 
				taxa_minima=$this->taxa_minima,
				id_empresa=".$this->empresa->id.",
				numero_palets=$this->numero_palets,
				toxicidade=$this->toxicidade,
				largura=$this->largura,
				altura=$this->altura,
				comprimento=$this->comprimento,
				peso_medio=$this->peso_medio,
				peso_maximo=$this->peso_maximo,
				valor_estoque=$this->valor_estoque,
				cintagem=$this->cintagem,
				aplicacao=$this->aplicacao,
				strech=$this->strech,
				despaletizado=$this->despaletizado,
				notas_por_veiculo=$this->notas_por_veiculo,
				media_embalagem_veiculo=$this->media_embalagem_veiculo,
				palets_veiculo=$this->palets_veiculo,
				palets_mes=$this->palets_mes,
				palets_dia=$this->palets_dia,
				carreta_dia=$this->carreta_dia,
				carreta_mes=$this->carreta_mes,
				pico_carretas=$this->pico_carretas,
				strech_film=$this->strech_film,
				arquivo_movimentacao_diaria=$this->arquivo_movimentacao_diaria,
				separacao_palet_fechado=$this->separacao_palet_fechado,
				separacao_fracionada=$this->separacao_fracionada,
				embalagens_por_pedido=$this->embalagens_por_pedido,
				sku_por_pedido=$this->sku_por_pedido,
				pico_pedidos=$this->pico_pedidos,
				despaletizado_saida=$this->despaletizado_saida,
				paletizado_saida=$this->paletizado_saida,
				carregamento_transportadora_cd=$this->carregamento_transportadora_cd,
				tipos_veiculo_saida=$this->tipos_veiculo_saida WHERE id=$this->id";

				$ps = $con->getConexao()->prepare($sql);
				$ps->execute();
				$ps->close();

		}

	}

}
