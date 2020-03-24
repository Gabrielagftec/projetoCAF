<?php

include("apiIncludes.php");

$campos[] = "paginaInicial";
$campos[] = "paginaFinal";
$campos[] = "filtro";

$parametros = getParametros($campos,$parametros);

$con = new ConnectionFactory();

$emp = new Empresa(1734,$con);

$produtos = $emp->getProdutos($con,$parametros["paginaInicial"],$parametros["paginaFinal"],"produto.disponivel > 0 AND produto.nome LIKE '%".$parametros["filtro"]."%' AND produto.codigo IN (SELECT pc.id_produto FROM produto_campanha pc INNER JOIN campanha c ON c.id=pc.id_campanha WHERE c.id_empresa=$emp->id AND c.inicio <= CURRENT_TIMESTAMP AND c.fim >= CURRENT_TIMESTAMP)");


$lotes = array();
$ps = $con->getConexao()->prepare("SELECT l.id_produto,UNIX_TIMESTAMP(l.validade)*1000,SUM(l.quantidade_real) FROM lote l INNER JOIN (SELECT p.id as 'id_produto' FROM produto p INNER JOIN produto_campanha pc ON pc.id_produto=p.codigo INNER JOIN campanha c ON pc.id_campanha=c.id AND c.excluida=false AND c.inicio <= CURRENT_TIMESTAMP AND c.fim >= CURRENT_TIMESTAMP WHERE p.id_empresa=$emp->id GROUP BY p.id) k ON k.id_produto=l.id_produto WHERE l.excluido=false GROUP BY l.validade, l.id_produto");
$ps->execute();
$ps->bind_result($id_produto,$validade,$quantidade);

while($ps->fetch()){

    if(!isset($lotes[$id_produto])){
        $lotes[$id_produto] = array();
    }

    $lotes[$id_produto][$validade.""] = $quantidade;

}

$ps->close();

$retorno = array();

foreach($produtos as $key=>$produto){

    $obj = new stdClass();
    $obj->id = $produto->id;
    $obj->nome = $produto->nome;
    $obj->imagem = $produto->imagem;
    $obj->peso = $produto->peso_bruto;
    $obj->disponivel = 0;
    $obj->estoque = 0;

    foreach ($produto->ofertas as $key2 => $oferta) {

        if(!isset($lotes[$produto->id][$oferta->validade.""]))
            continue;

        $validade = new stdClass();
        $validade->validade = $oferta->validade;
        $validade->valor = $oferta->valor;

        if($oferta->valor_trator > 0)
            $validade->valor = $oferta->valor;

        $validade->limite = $oferta->limite;
        $validade->de = $oferta->de;
        $validade->campanha = $oferta->campanha->nome;
        $validade->disponivel = $lotes[$produto->id][$oferta->validade.""];



        $obj->disponivel += $validade->disponivel;
        $obj->estoque += $validade->disponivel;
        $obj->validades[] = $validade;

        $retorno[$obj->id] = $obj;

    }

}

$retorno_sk = new stdClass();
$retorno_sk->produtos = array();
$retorno_sk->quantidade = 0;

foreach ($retorno as $key => $value)
    $retorno_sk->produtos[] = $value;

$ps = $con->getConexao()->prepare("SELECT COUNT(*) FROM produto WHERE produto.disponivel > 0 AND produto.excluido=false AND produto.nome LIKE '%".$parametros["filtro"]."%' AND produto.codigo IN (SELECT pc.id_produto FROM produto_campanha pc INNER JOIN campanha c ON c.id=pc.id_campanha WHERE c.id_empresa=$emp->id AND c.inicio <= CURRENT_TIMESTAMP AND c.fim >= CURRENT_TIMESTAMP)");
$ps->execute();
$ps->bind_result($qtd);
if($ps->fetch()){
    $retorno_sk->quantidade = $qtd;
}
$ps->close();

echo Utilidades::toJson($retorno_sk);

?>