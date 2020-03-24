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
class Banner {

    public $id;
    public $campanha;
    public $data_inicial;
    public $data_final;
    public $json;
    public $empresa;
    public $tipo;
    public $boas_vindas;
    public $ordem;

    function __construct() {

        $this->id = 0;
        $this->campanha = null;
        $this->data_inicial = round(microtime(true) * 1000);
        $this->data_final = round(microtime(true) * 1000);
        $this->json = null;
        $this->tipo = 0;
        $this->empresa = null;
        $this->boas_vindas = 0;
        $this->ordem = 0;

    }

    public function setOrdem($con){

        $ps = $con->getConexao()->prepare("UPDATE banner SET data_final=data_final,data_inicial=data_inicial,ordem=$this->ordem WHERE id=$this->id");
        $ps->execute();
        $ps->close();

    }

    public function merge($con) {

        if ($this->id == 0) {
            
            $ps = $con->getConexao()->prepare("INSERT INTO banner(id_campanha,data_inicial,data_final,json,id_empresa,tipo,boas_vindas,ordem) VALUES(" . (($this->campanha !== null) ? $this->campanha->id : 0) . ",FROM_UNIXTIME($this->data_inicial/1000),FROM_UNIXTIME($this->data_final/1000),'" . addslashes($this->json) . "',".$this->empresa->id.",$this->tipo,$this->boas_vindas,$this->ordem)");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
        } else {

            $ps = $con->getConexao()->prepare("UPDATE banner SET id_campanha=" . (($this->campanha !== null) ? $this->campanha->id : 0) . ",data_inicial=FROM_UNIXTIME($this->data_inicial/1000),data_final=FROM_UNIXTIME($this->data_final/1000),json='" . addslashes($this->json) . "',id_empresa=" . $this->empresa->id . ", tipo=$this->tipo, boas_vindas=$this->boas_vindas, ordem=$this->ordem  WHERE id = " . $this->id);
            $ps->execute();
            $ps->close();
        }
    }

    public function delete($con) {

        $ps = $con->getConexao()->prepare("DELETE FROM banner WHERE id = " . $this->id);
        $ps->execute();
        $ps->close();
    }

    private function replacer($str, $produto,$cliente) { 

        date_default_timezone_set('America/Sao_Paulo');

        if($cliente !== null){

            $linkboasvindas = "https://www.rtcagro.com.br/comprar.php?idbv=".Utilidades::base64encodeSPEC($cliente->id."_".$cliente->razao_social);

            $linkmodulo0 = "https://www.rtcagro.com.br/consigna_produto.php?idc=".Utilidades::base64encodeSPEC($cliente->id."_".$cliente->razao_social);

            $linkmodulo2 = "https://www.rtcagro.com.br/carrinho-de-compras.php?idcm2=".Utilidades::base64encodeSPEC($cliente->id."_".$cliente->razao_social)."&idpm2=".Utilidades::base64encodeSPEC($this->empresa->id."_".$produto->id."_".$produto->campanha->id);
            
            $str = str_replace(array("@linkboasvindas","@linkmodulo0","@linkmodulo2"), array($linkboasvindas,$linkmodulo0,$linkmodulo2), $str);

        }
        
        if ($produto === null) {

            return $str;
        }

        $nome_produto = $produto->produto->nome;
        $validade = date('d/m/y', $produto->validade/1000);
        $inicio = date('d/m/y', $produto->campanha->inicio/1000);;
        $fim = date('d/m/y', $produto->campanha->fim/1000);
        $valor = $produto->valor;

        $unitario = round($valor/max($produto->produto->quantidade_unidade,1),2);

        if($this->boas_vindas > 0){
            
            $valor = $produto->valor_boas_vindas;

        }

        $limite = $produto->limite;
        $de = $produto->de;
        if($de == 0){
            $de = $produto->produto->valor_base;
        }
        $imagem = $produto->produto->imagem;
        if ($limite < 0) {
            $limite = "Sem limite";
        }


        return str_replace(array("@nome_produto", "@validade", "@data_inicio", "@data_fim", "@valor", "@limite","@imagem","@de","@unitario"), array($nome_produto, $validade, $inicio, $fim, $valor, $limite, $imagem,$de,$unitario), $str);
    }

    private function compileJson($item, $produto,$cliente) {

        $str = "";
        
        $p = $produto;

        if ($item->tipo === -1) {

            return "</$item->valor>";
        }

        if ($item->tipo === 0) {

            $tag = "<$item->valor";
            $stl = false;
            foreach ($item->atributos as $att => $value) {

                if ($value === true) {
                    $tag .= " $att";
                    continue;
                }
                
                if($produto !== null && $att=='style'){
                    $value = $value{0}."cursor:pointer;".substr($value, 1);
                    $str = true;
                }

                $tag .= " $att=$value";
            }
            
            if($produto !== null){
                
                $tag .= " onclick=\"filtro('".$produto->produto->nome."')\"";
                
                if(!$stl){
                    
                    $tag .= " style='cursor:pointer'";
                    
                }
                
            }

            $tag .= ">";

            if (isset($item->atributos->inicio) && isset($item->atributos->fim)) {

                if ($this->campanha === null)
                    return "";

                $str = "";

                $pri = intval($item->atributos->inicio);
                $ult = intval($item->atributos->fim);

                for (; $pri < $ult; $pri++) {
                    if (isset($this->campanha->produtos[$pri])) {
                        $prod = $this->campanha->produtos[$pri];
                        $str .= $this->replacer($tag, $prod,$cliente);
                        foreach ($item->filhos as $key => $value) {
                            $str .= $this->compileJson($value, $prod,$cliente);
                        }
                        if ($item->fechamento !== null) {
                            $str .= $this->compileJson($item->fechamento, $prod,$cliente);
                        }
                    }
                }

                return $str;
            }else if(isset($item->atributos->campanha)){

                $c = $item->atributos->campanha;

                $c = substr($c, 1,strlen($c)-2);

                $con = new ConnectionFactory();

                $limite = -1;

                if(isset($item->atributos->limite)){
                    
                    $limite = intval($item->atributos->limite."");

                }


                $inicio = -1;
                $fim = -1;

                if(isset($item->atributos->inicio_campanha)){
                    $inicio = intval($item->atributos->inicio_campanha."");
                }

                if(isset($item->atributos->fim_campanha)){
                    $fim = intval($item->atributos->fim_campanha."");
                }

                $reducao = -1;

                if(isset($item->atributos->reducao)){
                    $reducao = doubleval($item->atributos->reducao."");
                }

                $campanhas = $this->empresa->getCampanhas($con,0,15,"campanha.nome like '%$c%' AND (cast(campanha.inicio as date)<=GREATEST(CURRENT_TIMESTAMP,FROM_UNIXTIME($this->data_inicial/1000)) AND cast(campanha.fim as date)>=GREATEST(CURRENT_TIMESTAMP,FROM_UNIXTIME($this->data_inicial/1000)) OR cast(campanha.inicio as date)=GREATEST(CURRENT_DATE,cast(FROM_UNIXTIME($this->data_inicial/1000) as date)))");

                $x = 0;
                foreach($campanhas as $key=>$value){
                    foreach($value->produtos as $kp=>$produto){

                        if($inicio>=0){
                            if($x<$inicio){
                                $x++;
                                continue;
                            }
                        }

                        if($fim>=0){
                            if($x>$fim){
                                break;
                            }
                        }

                        if($reducao >= 0){

                            $fat = (100-$reducao)/100;

                            $produto->valor = round($produto->valor*$fat,2);

                        }

                        if($limite >= 0 && $x>=$limite){
                            break 2;
                        }

                        $str .= $this->replacer($tag,$produto,$cliente);
                        foreach ($item->filhos as $key => $value) {
                            $str .= $this->compileJson($value, $produto,$cliente);
                        }
                        if ($item->fechamento !== null) {
                            $str .= $this->compileJson($item->fechamento, $produto,$cliente);
                        }

                        $x++;

                    }
                }
                return $str;

            } else if (isset($item->atributos->produto)) {

                if ($this->campanha === null)
                    return "";

                $pro = intval($item->atributos->produto);

                if (isset($this->campanha->produtos[$pro])) {
                    $str = "";
                    $prod = $this->campanha->produtos[$pro];
                    $str .= $this->replacer($tag, $prod, $cliente);
                    foreach ($item->filhos as $key => $value) {
                        $str .= $this->compileJson($value, $prod,$cliente);
                    }
                    if ($item->fechamento !== null) {
                        $str .= $this->compileJson($item->fechamento, $prod,$cliente);
                    }
                }

                return $str;
            } else {

                $str = "";
                $str .= $this->replacer($tag, $produto,$cliente);
                foreach ($item->filhos as $key => $value) {
                    $str .= $this->compileJson($value, $produto,$cliente);
                }
                if ($item->fechamento !== null) {
                    $str .= $this->compileJson($item->fechamento, $produto,$cliente);
                }

                return $str;
            }
        }

        if ($item->tipo === 1) {

            return $this->replacer($item->valor, $produto, $cliente);
        }

        return "";
    }

    public function getHTML($cliente = null) {

        $json = file_get_contents($this->json);

        $obj = json_decode($json);

        return $this->compileJson($obj, null,$cliente);
    }

}
