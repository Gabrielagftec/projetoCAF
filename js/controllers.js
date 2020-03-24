rtc.directive('arvoreRobo', function() {
    return {
        restrict: 'E',
        scope: {
            raiz: '='
        },
        templateUrl: 'arvoreRobo.html',
        link: function($scope, element, $attrs) {

            $scope.max = function() {

                var m = 0;

                for (var i = 0; i < $scope.raiz.filhos.length; i++) {

                    var k = $scope.raiz.filhos[i];

                    if (k.numero > m) {

                        m = k.numero;

                    }

                }

                return m;

            }

            $scope.novaClasse = function() {

                var nr = $scope.raiz.nome;

                if (nr == "RAIZ") {
                    nr = "";
                }

                var fn = window.prompt(nr + ", digite a continuacao do nome");

                if (fn == null)
                    return;


                var e = {
                    id: 0,
                    nome: nr + " " + fn,
                    numero: $scope.max() + 1,
                    pai: $scope.raiz.id,
                    filhos: []
                };

                $scope.raiz.filhos[$scope.raiz.filhos.length] = e;

            }

        }
    };
})

rtc.controller("crtVendasPrincipal", function($scope, formaPagamentoService, sistemaService, statusPedidoSaidaService, usuarioService, clienteService, tabelaService, empresaService, compraParceiroService, produtoService, sistemaService, produtoService, pedidoService, produtoPedidoService, baseService) {


    sistemaService.getUsuario(function(u) {

        clienteService.filtro_base = "(cliente.id_vendedor=" + u.usuario.id + ")";
        $scope.clientes = createAssinc(clienteService, 1, 10, 2);
        assincFuncs(
            $scope.clientes,
            "cliente", ["codigo", "razao_social"], "filtro");
        $scope.clientes.attList();

    })

    $scope.pedidos = [];

    $scope.formas_pagamento = [];



    $scope.voltarProduto = function() {

        $scope.cliente = null;

    }

    $scope.voltarAcompanharPedidos = function() {

            $scope.acompanhar_pedidos = false;
            $scope.finalizando = false;

        }
        //adicionado por André - em 20/12/2019 as 15h
        // start
    $scope.voltarFinalizarPedido = function() {

            $scope.acompanhar_pedidos = false;
            $scope.finalizando = false;

        }
        // end
    $scope.acompanharPedidos = function() {

        $scope.produto = null;
        $scope.finalizando = false;
        $scope.acompanhar_pedidos = true;

        usuarioService.getPedidos(function(u) {

            alert(paraJson(u.pedidos))
            $scope.pedidos = u.pedidos;

        })

    }

    $scope.produtos = createFilterList(compraParceiroService, 1, 60, 2);
    $scope.produtos["posload"] = function(elementos) {
        $scope.carregando_compra = false;
        sistemaService.getMesesValidadeCurta(function(p) {
            var produtos = [];
            for (var i = 0; i < elementos.length; i++) {
                for (var j = 0; j < elementos[i].produtos.length; j++) {
                    produtos[produtos.length] = elementos[i].produtos[j];
                }
            }
            produtoService.remessaGetValidades(p.meses_validade_curta, produtos, function() {});
        });
    }

    $scope.cliente = null;

    $scope.produto_fornecedor = null;

    $scope.produto = null;

    $scope.produtosFornecedor = [];



    empresaService.getProdutosFornecedor(function(p) {

        $scope.produtosFornecedor = p.produtos;

    });


    $scope.produto_real = null;

    $scope.quantidade = 0;

    $scope.preco = -1;

    $scope.validade = null;

    $scope.novo_pedido = null;

    $scope.novo_produto_pedido = null;

    $scope.acompanhar_pedidos = false;

    $scope.pedido = null;

    $scope.finalizando = false;

    $scope.frete = null;

    $scope.fretes = [];


    $scope.setFrete = function(frete) {

        $scope.frete = frete;

        $scope.pedido.transportadora = $scope.frete.transportadora;
        $scope.pedido.frete = $scope.frete.valor;

        $scope.atualizaCustos();

    }

    $scope.mergeando = false;

    $scope.finalizarPedido = function() {

        $scope.mergeando = true;

        $scope.pedido.observacoes = "Pedido realizado via simulador";

        baseService.merge($scope.pedido, function(r) {

            if (r.sucesso) {

                msg.alerta("Pedido realizado com sucesso");

                $scope.cliente = null;
                $scope.pedido = null;
                $scope.finalizando = false;
                $scope.frete = null;
                $scope.produto = null;
                $scope.produto_real = null;
                $scope.preco = -1;
                $scope.quantidade = 0;

            } else {

                msg.erro("Falha ao realizar pedido");

            }

            $scope.mergeando = false;

        })



    }

    $scope.getFretes = function() {

        var pesoTotal = 0;
        var valorTotal = 0;

        for (var i = 0; i < $scope.pedido.produtos.length; i++) {
            var p = $scope.pedido.produtos[i];
            valorTotal += (p.valor_base + p.juros + p.icms) * p.quantidade;
            pesoTotal += p.produto.peso_bruto * p.quantidade;
        }
        if ($scope.pedido.logistica === null) {
            tabelaService.getFretes(null, { cidade: $scope.pedido.cliente.endereco.cidade, valor: valorTotal, peso: pesoTotal }, function(f) {

                $scope.fretes = f.fretes;

            })
        } else {

            tabelaService.getFretes($scope.pedido.logistica, { cidade: $scope.pedido.cliente.endereco.cidade, valor: valorTotal, peso: pesoTotal }, function(f) {

                $scope.fretes = f.fretes;

            })
        }

    }

    $scope.setFinalizando = function() {

        $scope.finalizando = !$scope.finalizando;
        $scope.acompanhar_pedidos = false;


        $scope.atualizaCustos();

        $scope.getFretes();

    }

    $scope.attQuantidade = function() {

        if ($scope.validade.quantidade < $scope.quantidade) {

            $scope.preco = -1;
            $scope.validade = null;
            $scope.produto_real = null;

        }

    }

    $scope.setPreco = function(preco, validade, real) {

        $scope.preco = preco;
        $scope.validade = validade;
        $scope.produto_real = real;
        $scope.attQuantidade();
    }

    $scope.addProduto = function() {

        var pp = angular.copy($scope.novo_produto_pedido);

        pp.valor_base = $scope.preco;
        pp.validade_minima = $scope.validade.validade;
        pp.quantidade = $scope.quantidade;
        pp.produto = $scope.produto_real;
        pp.icms = 0;
        pp.ipi = 0;
        pp.frete = 0;
        pp.pedido = $scope.pedido;
        pp["comissao"] = $scope.getComissao();

        if ($scope.pedido.produtos == null) {
            $scope.pedido.produtos = [];
        }

        $scope.pedido.produtos[$scope.pedido.produtos.length] = pp;

        $scope.preco = -1;
        $scope.quantidade = 0;
        $scope.produto = null;
        $scope.validade = null;

    }

    $scope.getComissao = function() {

        if ($scope.validade == null)
            return 0;

        var c = 2;

        var p = $scope.getPrecos($scope.validade);

        var i = 0;

        for (; i < p.length; i++) {
            if (p[i] == $scope.preco)
                break;
        }

        c -= (c / p.length) * (i + 1);


        return $scope.preco * $scope.quantidade * (c / 100);

    }

    $scope.getTotalPedido = function() {

        if ($scope.pedido.produtos == null)
            return 0;

        var tot = 0;

        for (var i = 0; i < $scope.pedido.produtos.length; i++) {

            var p = $scope.pedido.produtos[i];

            tot += p.quantidade * (p.valor_base + p.icms + p.ipi + p.frete);

        }

        return tot;

    }

    $scope.getPrecos = function(validade) {

        var p = 3;

        var valor = validade.valor * $scope.quantidade;

        if (valor > 10000) {
            p = 5;
        }
        if (valor > 20000) {
            p = 8;
        }

        var dias = (validade.validade - new Date().getTime()) / (1000 * 60 * 60 * 24);

        if (dias < 60) {
            p += 5;
        } else if (dias < 90) {
            p += 1;
        } else if (dias < 120) {
            p += 0.5;
        }

        if ((validade.valor * 100 / $scope.produto.custo) >= 10) {

            if (dias > 120) {

                p += 2;

            } else {

                p += 4;

            }

        }

        var precos = [$scope.produto.valor_base];
        p /= 100;
        for (var i = 0; i < 3; i++) {
            precos[i + 1] = precos[0] * (1 - (p * (i + 1) / 3));
        }

        return precos;

    }

    produtoPedidoService.getProdutoPedido(function(pp) {

        $scope.novo_produto_pedido = pp.produto_pedido;

    })

    pedidoService.getPedido(function(p) {

        $scope.novo_pedido = p.pedido;
        $scope.novo_pedido.logistica = { id: 1735, _classe: "Logistica" };

        statusPedidoSaidaService.getStatus(function(s) {

            $scope.novo_pedido.status = s.status[0];

        });

    })

    $scope.formas_pagamento = [];

    $scope.atualizaCustos = function() {

        pedidoService.atualizarCustos($scope.pedido, function(np) {

            $scope.pedido = np.o;

            formaPagamentoService.getFormasPagamento($scope.pedido, function(f) {

                if (f.sucesso) {

                    $scope.formas_pagamento = f.formas;
                    $scope.pedido.forma_pagamento = f.formas[0];

                }

            })

        })

    }

    $scope.setCliente = function(cliente) {

        $scope.cliente = cliente;
        $scope.produtos.attList();

        $scope.pedido = angular.copy($scope.novo_pedido);
        $scope.pedido.cliente = cliente;
        $scope.pedido.frete = 0;

    }

    $scope.regras = [];

    $scope.setProdutoFornecedor = function(prod) {


        $scope.produto_fornecedor = prod;

        $scope.produto = prod.produto;

        $scope.produto.validades = [{
            validade: 1000,
            valor: prod.valor,
            quantidade: 500
        }];

        $scope.produto.produtos = [$scope.produto];

        $scope.produto.produtos.empresa = {
            id: "1734",
            nome: "Agro Fauna Comercio de Insumos"
        }

    }

    $scope.setProduto = function(produto) {

        $scope.produto = produto;

        $scope.produto_fornecedor = null;

    }

    $scope.filtroProduto = "";

    $scope.filtrarProduto = function() {


        for (var i = 0; i < $scope.produtos.filtro.length; i++) {

            var f = $scope.produtos.filtro[i];

            if (f._classe == "FiltroTextual") {
                f.valor = $scope.filtroProduto;
            }
        }

        $scope.produtos.attList();

    }

    $scope.cortar = function(texto, num) {

        if (texto.length < num) {

            return texto;

        }

        return texto.substring(0, num) + "...";

    }


})



rtc.controller("crtArt", function($scope, artService, baseService) {

    $scope.arts = null;

    $scope.art_nova = null;

    $scope.art = null;

    artService.getART(function(a) {

        $scope.art_nova = a.art;

    })

    artService.getARTS(function(a) {

        $scope.arts = createList(a.arts, 1, 50);
        $scope.arts.attList();

    })

    $scope.novaART = function() {

        $scope.art = angular.copy($scope.art_nova);

    }

    $scope.setART = function(a) {

        $scope.art = a;

    }

    $scope.delete = function(art) {

        baseService.delete(art, function(r) {

            if (r.sucesso) {

                var nl = [];

                for (var i = 0; i < $scope.arts.listaTotal.length; i++) {

                    if (art.id !== $scope.arts.listaTotal[i].id) {

                        nl[nl.length] = $scope.arts.listaTotal[i];

                    }

                }

                $scope.arts.listaTotal = nl;

                $scope.arts.attList();


            }

        })


    }

    $scope.merge = function() {

        baseService.merge($scope.art, function(r) {

            if (r.sucesso) {

                $scope.art = r.o;
                msg.alerta("Operacao efetuada com sucesso");

                artService.getARTS(function(a) {

                    $scope.arts = createList(a.arts, 1, 50);
                    $scope.arts.attList();

                })

            } else {

                msg.erro("Problema ao efetuar operacao");

            }

        })

    }

})

rtc.controller("crtCTE", function($scope, cteService) {

    $scope.ctes = createAssinc(cteService, 1, 10, 4);
    assincFuncs(
        $scope.ctes,
        "xml", ["id", "xml", "chave", "data_emissao", "chave_nota", "e.nome"]);

    $scope.ctes["posload"] = function(e) {

        for (var i = 0; i < e.length; i++) {

            e[i].xml_texto = e[i].xml;
            e[i].xml = xmlToJson(window.atob(e[i].xml));

        }

    }


    var dd = function download(text, filename) {
        var element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        element.setAttribute('download', filename);

        element.style.display = 'none';
        document.body.appendChild(element);

        element.click();

        document.body.removeChild(element);
    }

    $scope.baixar = function(cte) {


        dd(window.atob(cte.xml_texto), "XMLCTE_" + cte.xml.cteProc.CTe.infCte.infCTeNorm.infDoc.infNFe.chave + ".xml");

    }

    $scope.ctes.attList();


})


rtc.controller("crtNFE", function($scope, nfeService) {

    $scope.nfes = createAssinc(nfeService, 1, 10, 4);
    assincFuncs(
        $scope.nfes,
        "xml", ["id", "xml", "chave", "data_emissao", "e.nome"]);

    $scope.nfes["posload"] = function(e) {

        for (var i = 0; i < e.length; i++) {

            e[i].xml_texto = e[i].xml;
            e[i].xml = xmlToJson(window.atob(e[i].xml));

        }

    }

    var dd = function download(text, filename) {
        var element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        element.setAttribute('download', filename);

        element.style.display = 'none';
        document.body.appendChild(element);

        element.click();

        document.body.removeChild(element);
    }

    $scope.baixar = function(nfe) {

        dd(window.atob(nfe.xml_texto), "XMLNFE_" + nfe.xml.nfeProc.NFe.infNFe.Id + ".xml");

    }

    $scope.nfes.attList();


})



rtc.controller("crtImportarNota", function($scope, sistemaService, uploadService) {

    $scope.xmls = [];

    $scope.importando = false;

    $scope.importar = function() {

        $scope.importando = true;

        var i = 0;

        var imp = function() {

            loading.setProgress(((i + 1) / $scope.xmls.length) * 100);

            sistemaService.importarNota($scope.xmls[i], function(r) {

                i++;

                if (i < $scope.xmls.length) {
                    imp();
                } else {
                    loading.setProgress(100);
                    $scope.importando = false;
                }

            })


        }

        imp();


    }

    $scope.getTotal = function(nota) {

        if (!Array.isArray(nota.infNFe.det)) {

            nota.infNFe.det = [nota.infNFe.det];

        }

        var total = 0;

        for (var i = 0; i < nota.infNFe.det.length; i++) {

            var p = nota.infNFe.det[i];

            total += parseFloat(p.prod.vProd);

        }

        return total;

    }

    var findTag = function(json, tag) {

        if (typeof json === 'object') {

            for (a in json) {

                if (a.toUpperCase() == tag.toUpperCase()) {

                    return json[a];

                }

                var ret = findTag(json[a], tag);

                if (ret !== null) {

                    return ret;

                }

            }

        }

        return null;

    }

    $("#flXML").change(function() {

        var arquivos = $(this).prop("files");

        for (var i = 0; i < arquivos.length; i++) {
            var sp = arquivos[i].name.split(".");
            if (sp[sp.length - 1] != "xml") {
                msg.alerta("Arquivo: " + arquivos[i].name + ", invalido");
                $("#grpArquivos").removeClass("has-success").addClass("has-error");
                return;
            }
        }

        uploadService.upload(arquivos, function(arqs, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo");

            } else {

                $scope.arquivos = arqs;

                for (var i = 0; i < arquivos.length; i++) {

                    var reader = new FileReader();
                    reader["ii"] = i;
                    reader.onload = function(arquivo) {

                        var json = xmlToJson(arquivo.target.result);
                        $scope.xmls[this.ii] = findTag(json, "nfe");


                    };
                    reader.readAsText(arquivos[i]);
                }

            }

        })

    });


})

rtc.controller("crtCadastroEmpresa", function($scope, empresaService, cepService, cidadeService, uploadService) {

    $scope.carregando = false;

    $scope.estados = [];
    $scope.cidades = [];

    $scope.estado = null;

    $scope.certificado_comerciante = "";

    $scope.empresa = null;

    $("#flCertificado").change(function() {

        $scope.certificado_comerciante = "";

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo");

            } else {

                var doc = angular.copy($scope.documento);

                $scope.certificado_comerciante = arquivos[0];


            }

        })

    })

    var carregarCidades = function() {

        cidadeService.getElementos(function(p) {

            var estados = [];
            var cidades = p.elementos;
            $scope.cidades = cidades;

            lbl:
                for (var i = 0; i < cidades.length; i++) {
                    var c = cidades[i];
                    for (var j = 0; j < estados.length; j++) {
                        if (estados[j].id === c.estado.id) {
                            estados[j].cidades[estados[j].cidades.length] = c;
                            c.estado = estados[j];
                            continue lbl;
                        }
                    }
                    c.estado["cidades"] = [c];
                    estados[estados.length] = c.estado;
                }

            $scope.estados = estados;
            $scope.estado = estados[0];
            $scope.empresa.endereco.cidade = $scope.estado.cidades[0];


        })

    }

    $scope.liberado = function() {

        if ($scope.empresa == null) return false;

        if ($scope.empresa.nome != "" &&
            $scope.empresa.cnpj.valor != "" &&
            $scope.empresa.inscricao_estadual != "" &&
            $scope.empresa.email.endereco != "" &&
            $scope.empresa.endereco.bairro != "" &&
            $scope.empresa.endereco.numero != "" &&
            $scope.empresa.telefone.numero != "" &&
            $scope.certificado_comerciante != "") {

            return true;

        }

        return false;

    }

    empresaService.getNovaEmpresa(function(e) {

        $scope.empresa = e.empresa;
        $scope.empresa.email.endereco = "";
        $scope.empresa.endereco.cep.valor = "";
        $scope.empresa.telefone.numero = "";
        $scope.empresa.endereco.numero = "";
        $scope.empresa.cnpj.valor = "";

        carregarCidades();

    })

    $scope.trocaEstado = function() {

        if ($scope.estado.id !== $scope.empresa.endereco.cidade.estado.id) {

            $scope.empresa.endereco.cidade = $scope.estado.cidades[0];

        }

    }

    $scope.carregando = false;

    $scope.getEnderecoPeloCep = function() {

        $scope.carregando = true;
        cepService.getEndereco($scope.empresa.endereco.cep, function(e) {

            $scope.carregando = false;

            if (e.endereco !== null) {

                $scope.empresa.endereco = e.endereco;

                equalize($scope.empresa.endereco, "cidade", $scope.cidades);
                $scope.estado = $scope.empresa.endereco.cidade.estado;

                $scope.empresa.endereco.numero = "";

            }

        })

    }

    $scope.carregando_cadastro = false;

    $scope.cadastrar = function() {

        $scope.carregando_cadastro = true;

        empresaService.cadastrarEmpresa($scope.empresa, function(r) {

            if (r.sucesso) {

                msg.alerta("Cadastro efetuado com sucesso, os acessos foram enviados para o seu email");
                window.location = projeto + '/index.php?login=' + r.dados.login + "&senha=" + r.dados.senha;

            } else {

                msg.erro("Ocorreu um problema ao efetuar o cadastramento: " + r.mensagem);

            }

            $scope.carregando_cadastro = false;

        })

    }

});

rtc.controller("crtKim", function($scope, $interval, $sce, kimService) {


    $scope.produto = null;

    $scope.selecionados = [];

    $scope.raiz = null;

    $scope.buscando = false;

    $scope.busca = "";

    $scope.inserirBusca = function() {

        $scope.buscando = true;

        kimService.inserirBusca($scope.busca, function(r) {

            if (r.sucesso) {

                kimService.getBuscas(function(p) {

                    $scope.buscas = createList(p.buscas, 1, 50);

                    msg.alerta("Busca inserida, em poucos segundos o robo ja trara resultados");

                })

            }

            $scope.buscando = false;

        });

    }


    kimService.getLogsRobo(function(l) {

        $scope.raiz = l.raiz;

    })

    $scope.explicar = function(txt) {

        var str = "";

        var p = $scope.raiz;

        var k = txt.split(".");

        var numero = "";

        for (var i = 0; i < k.length; i++) {

            var j = parseInt(k[i]);

            for (var l = 0; l < p.filhos.length; l++) {

                if (p.filhos[l].numero == j) {

                    p = p.filhos[l];

                    if (numero != "") {
                        numero += ".";
                    }

                    numero += p.numero;

                    str += numero + ". " + p.nome + "<hr>";

                    break;

                }

            }

        }

        return $sce.trustAsHtml(str);

    }

    $scope.confirmarAlteracoes = function() {

        kimService.confirmarAlteracoes($scope.raiz, function(r) {

            if (r.sucesso) {

                $scope.raiz = r.o.raiz;
                msg.alerta("Operacao efetuada com sucesso");

            } else {

                msg.alerta("Falha ao efetuar operacao");

            }

        })

    }


    $scope.buscas = {};

    kimService.getBuscas(function(p) {

        $scope.buscas = createList(p.buscas, 1, 50);

    })

    $scope.attQualidadeEmpresa = function(p) {

        kimService.attQualidadeEmpresa(p, function(r) {

        })

    }

    $scope.trocaQualidade = function(p) {

        kimService.attQualidade(p, function(r) {

        })

        for (var i = 0; i < $scope.selecionados.length; i++) {

            if ($scope.selecionados[i].id !== p.id) {

                $scope.selecionados[i].classe = p.classe;
                $scope.selecionados[i].subclasse = p.subclasse;
                $scope.selecionados[i].qualidade = p.qualidade;
                $scope.selecionados[i].texto = p.texto;

                kimService.attQualidade($scope.selecionados[i], function(r) {

                })

            }

        }

    }


    $scope.produtos = createAssinc(kimService, 1, 50, 4);

    $scope.asel = [];

    $scope.produtos["posload"] = function(e) {

        $scope.selecionados = [];

        $scope.asel = e;

        setTimeout(function() {

            loading.redux();

        }, 300);


    }

    $scope.tudoIsSelecionado = function() {

        var tudo = true;

        lbl:
            for (var i = 0; i < $scope.asel.length; i++) {
                for (var j = 0; j < $scope.selecionados.length; j++) {
                    if ($scope.selecionados[j] !== null) {
                        if ($scope.selecionados[j].id === $scope.asel[i].id) {
                            continue lbl;
                        }
                    }
                }
                tudo = false;
                break;
            }

        return tudo;

    }

    $scope.selecionarTudo = function() {

        var tudo = $scope.tudoIsSelecionado();

        if (!tudo) {

            $scope.selecionados = [];

            for (var i = 0; i < $scope.asel.length; i++) {
                $scope.selecionados[$scope.selecionados.length] = $scope.asel[i];
            }

        } else {

            $scope.selecionados = [];

        }


    }

    $scope.isSelecionado = function(prod) {

        for (var i = 0; i < $scope.selecionados.length; i++) {

            if ($scope.selecionados[i].id === prod.id) {

                return true;

            }

        }

        return false;

    }

    $scope.selecionar = function(prod) {

        for (var i = 0; i < $scope.selecionados.length; i++) {

            if ($scope.selecionados[i].id === prod.id) {

                $scope.selecionados[i] = null;

                return;

            }

        }

        $scope.selecionados[$scope.selecionados.length] = prod;

    }

    assincFuncs(
        $scope.produtos,
        "p", ["id", "classe", "nome", "preco", "imagem", "empresa", "preco", "link"], "filtro2");
    $scope.produtos.attList();



})



rtc.controller("crtImportador", function($scope, uploadService, sistemaService, empresaService) {


    $scope.clientes = [];
    $scope.selecionado = null;



    empresaService.getEmpresa(function(e) {

        $scope.clientes[0] = e.empresa;
        $scope.selecionado = e.empresa;

        empresaService.getEmpresasClientes(function(r) {

            lbl: for (var i = 0; i < r.clientes.length; i++) {
                for (var j = 0; j < $scope.clientes.length; j++) {
                    if ($scope.clientes[j].id === r.clientes[i].id) {
                        continue lbl;
                    }
                }
                $scope.clientes[$scope.clientes.length] = r.clientes[i];
            }

        })

    })

    $scope.exemplo = [];
    for (var i = 0; i < 20; i++) $scope.exemplo[i] = { id: i };

    $scope.dados = null;
    $scope.cabecalho = null;

    $scope.migrando = false;

    $scope.concluirMigracao = function() {

        $scope.migrando = true;

        var progresso = function(p, txt) {

            $("#porcentagemLoading").html(txt);
            $("#loadingImportacao").css("width", p + "%");

        };

        progresso(0, "Iniciando Migracao...");

        var buffer = 5;

        var total = Math.ceil($scope.dados.length / buffer);
        var atual = 0;
        var colunas = $scope.getColunas($scope.config);

        var teste = 0;

        var migrar = function() {

            if (atual === total) {
                progresso(100, "Importacao Concluida");
                $scope.migrando = false;
                return;
            }

            var parte = [];

            for (var i = atual * buffer; i < (atual + 1) * buffer && i < $scope.dados.length; i++) {

                parte[parte.length] = $scope.dados[i];

            }


            sistemaService.migrar($scope.selecionado, angular.copy(colunas), angular.copy(parte), function(r) {

                if (r.sucesso) {

                    atual++;
                    progresso(((atual / total) * 100), "Migrando...(" + ((atual / total) * 100).toFixed(2).split(".").join(",") + ")");
                    migrar();

                } else {

                    $scope.migrando = false;
                    progresso(0, "Ocorreu uma falha");

                }

            })

        }

        migrar();

    }

    $scope.removerColuna = function(k) {

        delete $scope.cabecalho[k];

        for (var i = 0; i < $scope.dados.length; i++) {
            delete $scope.dados[i][k];
        }

    }

    $scope.batendo = function() {

        if ($scope.dados === null) {

            return true;

        }

        if ($scope.dados.length === 0) {

            return false;

        }

        var cols = 0;

        var a;
        for (a in $scope.dados[0]) {
            if (a !== "$$hashKey") {
                cols++;
            }
        }

        if (cols !== $scope.getColunas($scope.config).length) return false;


        return true;

    }

    $("#flArquivos").change(function() {


        $scope.dados = null;
        $scope.cabecalho = null;

        loading.setProgress = function(p) {

            $("#loadingImportacao").css("width", "0%");
            $("#porcentagemLoading").html("Subindo Arquivo... (" + p.toFixed(2) + "%)");
            $("#loadingUpload").css("width", p + "%");

        };

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao ler arquivo");

            } else {

                var extensoes = ["csv", "xls", "ods", "xlsx"];

                var aceitos = [];

                var erro = "";
                lbl:
                    for (var i = 0; i < arquivos.length; i++) {

                        var a = arquivos[i];

                        var ext = a.split(".");
                        ext = ext[ext.length - 1];

                        for (var j = 0; j < extensoes.length; j++) {
                            if (extensoes[j] === ext) {
                                aceitos[aceitos.length] = a;
                                continue lbl;
                            }
                        }

                        erro += "Arquivo: " + a + ", em formato nao permitido \n";

                    }

                if (erro !== "") {
                    if (aceitos.length > 0) {
                        msg.erro(erro + "\n os outros " + aceitos.length + ", arquivos estarao sendo importados");
                    } else {
                        msg.erro(erro);
                        return;
                    }
                }

                var progresso = function(p, txt) {

                    $("#porcentagemLoading").html(txt);
                    $("#loadingImportacao").css("width", p + "%");

                };
                loading.setProgress = function(p) { progresso(p, "Importando...(" + p + "%)"); };

                progresso(100, "Lendo arquivo" + (aceitos.length > 0 ? "s" : ""));

                var part = 0;

                $scope.dados = [];

                var fnRead = function() {
                    sistemaService.converterPlanilhasParaVetor(
                        part,
                        aceitos,
                        $scope.getColunas($scope.config),
                        function(r) {


                            if (r.sucesso) {

                                var resp = r.resposta;


                                if (resp.erro !== "") {

                                    msg.erro("Ocorreu o seguinte problema: " + resp.erro);

                                } else {

                                    if ($scope.dados === null) {
                                        $scope.dados = [];
                                    }

                                    if (resp.cabecalho !== null) {
                                        if ($scope.cabecalho === null) {
                                            $scope.cabecalho = resp.cabecalho;
                                            delete $scope.cabecalho._classe;
                                        }
                                    }

                                    for (var i = 0; i < resp.vetor.length; i++) {
                                        delete resp.vetor[i]._classe;
                                        $scope.dados[$scope.dados.length] = resp.vetor[i];
                                    }

                                    if ((part + 1) < resp.total) {

                                        progresso(((part + 1) / resp.total) * 100, "Lendo arquivo" + (aceitos.length > 0 ? "s" : "") + "(" + (((part + 1) / resp.total) * 100).toFixed(2) + ")");

                                        part++;
                                        fnRead();

                                    } else {

                                        progresso(100, "Leitura concluida");

                                    }

                                }

                            } else {

                                progresso(0, "Ocorreu uma falha");

                            }
                        });
                }
                fnRead();

            }

        })

    })


    $scope.config = {
        nota: {
            ficha: 0,
            numero: 0,
            valor: 0,
            data: 0,
            observacoes: "",
            tipo: "",
            chave: "",
            protocolo: ""
        },
        produto: {
            codigo: 0,
            nome: "",
            qtd: 0,
            valor: 0,
            custo: 0,
            cfop: "",
            estoque: 0,
            disponivel: 0,
            ncm: "",
            cst: 0,
            alicota_icms: 0,
            alicota_pis: 0,
            alicota_cofins: 0,
            base_calculo_icms: 0,

        },
        vencimento: {
            ficha: 0,
            data: 0,
            valor: 0
        },
        cliente: {
            id: 0,
            codigo_contimatic: 0,
            nome: "",
            cnpj: "",
            cpf: "",
            rua: "",
            numero: "",
            bairro: "",
            estado: "",
            cidade: ""
        },
        fornecedor: {
            id: 0,
            codigo_contimatic: 0,
            nome: "",
            cnpj: "",
            rua: "",
            numero: "",
            bairro: "",
            estado: "",
            cidade: ""
        },
        transportadora: {
            id: 0,
            nome: "",
            cnpj: "",
            rua: "",
            numero: "",
            bairro: "",
            estado: ""
        },
        banco: {
            id: 0,
            codigo: 0,
            numero_conta: 0,
            saldo: 0,
            agencia: "",
            nome: ""
        },
        movimento: {
            id: 0,
            valor: 0,
            data: 0,
            juros: 0,
            desconto: 0,
            operacao: "",
            historico: "",
            centro_custo: "",
            categoria: ""
        }
    };

    $scope.getColunas = function(conf) {

        var c = conf;

        var colunas = [];

        if (c.primitivo && c.utilizado) {

            c["nome"] = "";
            c["recem_retornado"] = true;
            return [c];

        } else if (!c.primitivo) {

            var ret = [];
            var tmp = [];

            var a;
            for (a in c.atributos) {

                ret = [];

                var k = $scope.getColunas(c.atributos[a]);

                for (var i = 0; i < k.length; i++) {

                    k[i].nome += (!k[i].recem_retornado ? " " : "") + a;
                    k[i].recem_retornado = false;

                }

                while (tmp.length + k.length > 0) {
                    if (tmp.length === 0) {
                        ret[ret.length] = k[k.length - 1];
                        k.length--;
                    } else if (k.length === 0) {
                        ret[ret.length] = tmp[tmp.length - 1];
                        tmp.length--;
                    } else {
                        if (tmp[tmp.length - 1].ordem > k[k.length - 1].ordem) {
                            ret[ret.length] = tmp[tmp.length - 1];
                            tmp.length--;
                        } else {
                            ret[ret.length] = k[k.length - 1];
                            k.length--;
                        }
                    }
                }
                tmp = ret;

                for (var i = 0, j = tmp.length - 1; i < j; i++, j--) {
                    var l = tmp[i];
                    tmp[i] = tmp[j];
                    tmp[j] = l;
                }

                ret = tmp;

            }

            return tmp;

        } else {

            return colunas;

        }

    }

    var ajuste = function(k) {

        if (typeof k === 'object') {

            var no = {
                atributos: {},
                utilizado: false,
                primitivo: false,
                pai: null
            };

            var a;
            for (a in k) {

                no.atributos[a] = ajuste(k[a]);
                no.atributos[a].pai = no;

            }

            return no;

        } else {

            return { valor: k, ordem: 1, utilizado: false, primitivo: true, pai: null };

        }

    };

    $scope.config = ajuste($scope.config);
    $scope.config.utilizado = true;

})

rtc.directive('configImportadorSetter', function() {
    return {
        restrict: 'E',
        scope: {
            config: '='
        },
        templateUrl: 'configImportador.html',
        link: function($scope, element, $attrs) {

            var maxOrdem = function(r) {

                if (r.primitivo && r.utilizado) {

                    return r.ordem;

                } else if (r.primitivo) {

                    return 0;

                }

                var max = 0;

                var a;
                for (a in r.atributos) {

                    var tmp = maxOrdem(r.atributos[a]);

                    if (max < tmp) {
                        max = tmp;
                    }

                }

                return max;

            }

            $scope.selecionarPrimitivo = function(c) {

                if (!c.utilizado) {

                    var root = c;

                    while (root["pai"] !== null) {
                        root = root["pai"];
                    }

                    if (root !== null) {

                        c.ordem = maxOrdem(root) + 1;
                    }

                }

                c.utilizado = !c.utilizado;

            }

            $scope.formatar = function(txt) {


                return (txt.charAt(0) + "").toUpperCase() + txt.substring(1);

            }

        }
    };
})


rtc.controller("crtArmazem", function($scope, armazemService, baseService) {

    $scope.armazem = null;


    $scope.addPortaPalet = function() {


        if ($scope.armazem.id == 0) {

            baseService.merge($scope.armazem, function(r) {

                $scope.armazem = r.o;

                armazemService.getPortaPalet(function(p) {

                    var pp = p.porta_palet;

                    $scope.armazem.porta_palets[$scope.armazem.porta_palets.length] = pp;
                    pp.armazem = $scope.armazem;


                })


            })

        } else {

            armazemService.getPortaPalet(function(p) {

                var pp = p.porta_palet;

                $scope.armazem.porta_palets[$scope.armazem.porta_palets.length] = pp;
                pp.armazem = $scope.armazem;

                baseService.merge(pp, function(r) {
                    pp.id = r.o.id;
                })

            })

        }

    }

    $scope.alterar = function(obj) {

        baseService.merge(obj, function(r) {

        })

    }

    $scope.addTunel = function(pp) {


        armazemService.getTunel(function(tu) {

            var t = tu.tunel;

            pp.tuneis[pp.tuneis.length] = t;
            t.porta_palet = pp;

            baseService.merge(t, function(r) {
                t.id = r.o.id;
            })

        })

    }

    $scope.removeTunel = function(p, t) {


        var nt = [];

        for (var i = 0; i < p.tuneis.length; i++) {
            var x = p.tuneis[i];
            if (x != t) {
                nt[nt.length] = x;
            }
        }

        p.tuneis = nt;

        baseService.delete(t, function(r) {

        })


    }

    $scope.removePortaPalet = function(p) {


        var np = [];

        for (var i = 0; i < $scope.armazem.porta_palets.length; i++) {
            var x = $scope.armazem.porta_palets[i];
            if (x != p) {
                np[np.length] = x;
            }
        }

        $scope.armazem.porta_palets = np;

        baseService.delete(p, function(r) {

        })

    }

    armazemService.getArmazens(function(e) {

        if (e.armazens.length > 0) {

            $scope.armazem = e.armazens[0];

        } else {

            armazemService.getArmazem(function(a) {

                $scope.armazem = a.armazem;

            })

        }

    })


})

rtc.directive('problema', function(problemaService, $parse, PROBSenhaFracaService, PROBRecebimentoRelatorioService, PROBRecebimentoAtividades, PROBAcessoEmpresasService, PROBAcessoPermissoesService, empresaService) {
    return {
        restrict: 'E',
        scope: {
            problema: '=',
            selecionavel: '=',
            aoResolver: '&',
            cabecalho: '=',
            liberado: '='
        },
        templateUrl: 'problema.html',
        link: function($scope, element, $attrs) {

            $scope.mso = false;

            if ($scope.cabecalho !== false) {
                $scope.cabecalho = true;
            }

            if ($scope.liberado !== false) {
                $scope.cabecalho = true;
            }

            $scope.resolvido = false;

            $scope.senha = "";

            $scope.carregando = false;

            $scope.tipo_senha = "Fraca";
            $scope.cor = "Red";

            $scope.definindo_permissoes = false;
            $scope.quadro = [];

            $scope.setP = function(p, att) {

                if (p[att] === 100) {
                    p[att] = 0;
                } else {
                    p[att] = 100;
                }

            }

            $scope.getCor = function(perc) {

                var rg = [255, 0];

                var x = Math.floor(255 * (perc / 100));

                var i = 0;

                rg[0] -= x;
                rg[1] += x;

                var cor = "";

                var hex = "0123456789ABCDEF";

                for (var i = 0; i < rg.length; i++) {

                    var h = "";

                    while (rg[i] > 0) {
                        h = hex.charAt(rg[i] % 16) + h;
                        rg[i] = (rg[i] - rg[i] % 16) / 16;
                    }

                    while (h.length < 2) {
                        h = "0" + h;
                    }

                    cor += h;

                }

                cor += "00";

                return "#" + cor;

            }

            $scope.definirPermissoes = function() {

                $scope.carregando = true;

                PROBAcessoPermissoesService.getQuadroPermissaoPercentual($scope.problema.usuario, function(q) {


                    $scope.quadro = q.quadro;
                    $scope.definindo_permissoes = true;
                    $scope.carregando = false;

                })

            }

            $scope.cargos = [];
            $scope.tipos_atividade = [];

            var relacional = [];

            $scope.definindo_cargo = false;

            var length = function(obj) {
                var size = 0,
                    key;
                for (key in obj) {
                    if (obj.hasOwnProperty(key)) size++;
                }
                return size;
            };

            $scope.attRelacional = function() {

                for (var i = 0; i < relacional.length; i++) {
                    for (var j = 0; j < relacional[i].length; j++) {
                        relacional[i][j].possivel = true;
                    }
                }

                for (var i = 0; i < relacional.length; i++) {

                    var prox = {};
                    var selecionados = {};

                    for (var j = 0; j < relacional[i].length; j++) {

                        if (relacional[i][j].selecionado) {

                            selecionados["n_" + relacional[i][j].id] = relacional[i][j].id;

                            for (var k = 0; k < relacional[i][j].relacoes.length; k++) {

                                prox["n_" + relacional[i][j].relacoes[k]] = relacional[i][j].relacoes[k];

                            }

                        }

                    }

                    if (length(selecionados) > 0) {

                        //---------------------------

                        for (var l = i - 1; l >= 0; l--) {

                            var selecionado2 = {};
                            var sselecionado2 = {};

                            var np = {};

                            for (var m = 0; m < relacional[l].length; m++) {

                                var r = relacional[l][m];

                                var sim = false;

                                for (var n = 0; n < r.relacoes.length; n++) {
                                    if (typeof selecionados["n_" + r.relacoes[n]] !== 'undefined') {
                                        sim = true;
                                        break;
                                    }
                                }

                                if (!sim) {

                                    r.possivel = false;

                                } else {

                                    if (l === i - 1) {
                                        for (var n = 0; n < r.relacoes.length; n++) {
                                            np["n_" + r.relacoes[n]] = r.relacoes[n];
                                        }
                                    }

                                    if (r.selecionado) {

                                        sselecionado2["n_" + r.id] = r.id;

                                    } else {

                                        selecionado2["n_" + r.id] = r.id;

                                    }

                                }

                            }

                            if (l === i - 1) {
                                for (var n = 0; n < relacional[i].length; n++) {
                                    if (typeof np["n_" + relacional[i][n].id] === 'undefined') {
                                        relacional[i][n].possivel = false;
                                    }
                                }
                            }

                            if (length(sselecionado2) > 0) {

                                selecionado = sselecionado2;

                            } else {

                                selecionado = selecionado2;

                            }

                        }

                        for (var l = i + 1; l < relacional.length; l++) {

                            var prox2 = {};
                            var sprox2 = {};

                            var ns = {};

                            for (var m = 0; m < relacional[l].length; m++) {

                                var r = relacional[l][m];

                                if (typeof prox["n_" + r.id] === 'undefined') {

                                    r.possivel = false;

                                } else if (l + 1 < relacional.length) {

                                    if (l === i + 1) {
                                        ns["n_" + r.id] = r.id;
                                    }

                                    if (r.selecionado) {
                                        for (var n = 0; n < r.relacoes.length; n++) {
                                            sprox2["n_" + r.relacoes[n]] = r.relacoes[n];
                                        }
                                    } else {
                                        for (var n = 0; n < r.relacoes.length; n++) {
                                            prox2["n_" + r.relacoes[n]] = r.relacoes[n];
                                        }
                                    }

                                } else {

                                    if (l === i + 1) {
                                        ns["n_" + r.id] = r.id;
                                    }

                                }

                            }

                            if (l === i + 1) {
                                lbl: for (var m = 0; m < relacional[i].length; m++) {
                                    for (var n = 0; n < relacional[i][m].relacoes.length; n++) {
                                        if (typeof ns["n_" + relacional[i][m].relacoes[n]] !== 'undefined') {
                                            continue lbl;
                                        }
                                    }
                                    relacional[i][m].possivel = false;
                                }
                            }

                            if (length(sprox2) > 0) {
                                prox = sprox2;
                            } else {
                                prox = prox2;
                            }

                        }



                        //-----------------------------

                    }

                }

            }

            $scope.sc = function(c) {

                c.selecionado = !c.selecionado;

                $scope.attRelacional();

            }

            $scope.definirCargo = function() {

                $scope.carregando = true;

                PROBRecebimentoAtividades.getTiposAtividade($scope.problema.usuario, function(u) {

                    $scope.tipos_atividade = [];

                    for (var i = 0; i < u.tipos.length; i++) {

                        var cs = u.tipos[i].cargos;

                        u.tipos[i].relacoes = [];
                        u.tipos[i].selecionado = false;
                        u.tipos[i].possivel = true;

                        if (cs.length === 0) {
                            continue;
                        }

                        $scope.tipos_atividade[$scope.tipos_atividade.length] = u.tipos[i];

                        lbl:
                            for (var j = 0; j < cs.length; j++) {

                                var c = cs[j];
                                c.selecionado = false;
                                c.relacoes = [];
                                c.possivel = true;



                                u.tipos[i].relacoes[u.tipos[i].relacoes.length] = c.id;

                                for (var k = 0; k < $scope.cargos.length; k++)
                                    if ($scope.cargos[k].id === c.id) continue lbl;

                                $scope.cargos[$scope.cargos.length] = c;

                            }



                    }

                    relacional = [$scope.tipos_atividade, $scope.cargos];

                    $scope.carregando = false;
                    $scope.definindo_cargo = true;

                })

            }

            $scope.definindo_relatorios = false;
            $scope.relatorios = [];

            $scope.definindo_expediente = false;
            $scope.expedientes = [];


            $scope.definindo_empresas = false;
            $scope.empresas = [];

            $scope.definirEmpresas = function() {

                $scope.carregando = true;
                empresaService.getGrupoEmpresarialDeEmpresa($scope.problema.usuario.empresa, function(g) {

                    $scope.empresas = g.grupo;
                    for (var i = 0; i < g.grupo.length; i++) {

                        g.grupo[i].selecionada = false;
                    }

                    PROBAcessoEmpresasService.getEmpresasColaborador($scope.problema.usuario, function(r) {

                        var e = r.empresas;

                        for (var i = 0; i < e.length; i++) {

                            for (var j = 0; j < $scope.empresas.length; j++) {

                                if (e[i].id === $scope.empresas[j].id) {

                                    $scope.empresas[j].selecionada = true;
                                    break;

                                }

                            }

                        }

                        $scope.definindo_empresas = true;
                        $scope.carregando = false;

                    })

                })

            }

            var ide = 0;

            $scope.definirExpediente = function() {

                for (var i = 1; i < 6; i++) {
                    $scope.expedientes[$scope.expedientes.length] = {
                        id: ide,
                        dia_semana: i,
                        inicio: 8,
                        fim: 12
                    }
                    ide++;
                    $scope.expedientes[$scope.expedientes.length] = {
                        id: ide,
                        dia_semana: i,
                        inicio: 13.2,
                        fim: 18
                    }
                    ide++;
                }

                $scope.definindo_expediente = true;

            }


            $scope.addExpediente = function() {

                var exp = {
                    id: ide,
                    dia_semana: 1,
                    inicio: 8,
                    fim: 13
                }

                $scope.expedientes[$scope.expedientes.length] = exp;
                ide++;

            }

            $scope.removeExpediente = function(exp) {

                var ne = [];

                for (var i = 0; i < $scope.expedientes.length; i++) {
                    var e = $scope.expedientes[i];
                    if (e.id !== exp.id) {
                        ne[ne.length] = e;
                    }
                }

                $scope.expedientes = ne;

            }

            $scope.dias_semana = [
                { id: 0, nome: "Domingo" },
                { id: 1, nome: "Segunda" },
                { id: 2, nome: "Terça" },
                { id: 3, nome: "Quarta" },
                { id: 4, nome: "Quinte" },
                { id: 5, nome: "Sexta" },
                { id: 6, nome: "Sabado" }
            ]

            $scope.dificuldade = 0;

            $scope.definirRelatorios = function() {
                $scope.carregando = true;
                if ($scope.relatorios.length === 0) {

                    PROBRecebimentoRelatorioService.getSugestaoRecebimento($scope.problema.usuario, function(s) {

                        if (s.sucesso) {

                            $scope.relatorios = s.sugestoes;
                            $scope.definindo_relatorios = true;

                        }

                        $scope.carregando = false;

                    })


                } else {

                    $scope.definindo_relatorios = true;
                    $scope.carregando = false;
                }


            }

            $scope.resolverManualmente = function() {

                $scope.carregando = true;

                var parametros = {};

                if ($scope.problema.tipo.id === 11) {

                    parametros = { senha: $scope.senha };

                } else if ($scope.problema.tipo.id === 8) {

                    parametros = [];

                    for (var i = 0; i < $scope.relatorios.length; i++) {
                        var rel = $scope.relatorios[i];
                        if (rel[3]) {
                            var p = {
                                id_empresa: rel[2].id,
                                relatorio: rel
                            };
                            parametros[parametros.length] = p;
                        }
                    }

                } else if ($scope.problema.tipo.id === 5) {

                    parametros = $scope.expedientes;

                } else if ($scope.problema.tipo.id === 2) {


                    var cargo = null;

                    for (var i = 0; i < $scope.cargos.length; i++) {
                        if ($scope.cargos[i].possivel && $scope.cargos[i].selecionado) {
                            cargo = $scope.cargos[i];
                            break;
                        }
                    }

                    if (cargo === null) {
                        cargo = $scope.cargos[0];
                    }

                    parametros = $scope.problema.usuario;
                    parametros.cargo = cargo;

                } else if ($scope.problema.tipo.id === 6) {

                    parametros = {
                        empresas: []
                    };

                    for (var i = 0; i < $scope.empresas.length; i++) {
                        if ($scope.empresas[i].selecionada) {
                            parametros.empresas[parametros.empresas.length] = { id: $scope.empresas[i].id };
                        }
                    }

                } else if ($scope.problema.tipo.id === 7) {

                    parametros = $scope.problema.usuario;

                    var permissoes = [];

                    for (var i = 0; i < $scope.quadro.length; i++) {

                        var q = angular.copy($scope.quadro[i]);
                        q.alt = q.alt > 80;
                        q.in = q.in > 70;
                        q.del = q.del > 95;
                        q.cons = q.cons > 60;

                        permissoes[permissoes.length] = q;

                    }

                    parametros.permissoes = permissoes;

                }

                problemaService.resolucaoCompleta($scope.problema, parametros, function(r) {

                    if (r.sucesso) {

                        $scope.resolvido = true;

                        if ($scope.aoResolver !== null) {

                            $scope.aoResolver({ arg_problema: r.o.problema, arg_parametros: r.o.parametros });

                        }

                        $scope.carregando = false;

                    }

                })

            }

            $scope.resolverAutomaticamente = function() {

                $scope.carregando = true;

                problemaService.resolucaoRapida($scope.problema, function(r) {

                    if (r.sucesso) {

                        $scope.resolvido = true;

                        if ($scope.aoResolver !== null) {

                            $scope.aoResolver({ arg_problema: r.o.problema, arg_parametros: r.o.parametros });

                        }

                        $scope.carregando = false;

                    }

                })

            }

            $scope.getDificuldadeSenha = function() {

                $scope.carregando = true;
                PROBSenhaFracaService.getDificuldadeSenha($scope.senha, function(r) {

                    $scope.dificuldade = r.dificuldade;

                    if ($scope.dificuldade < 20) {
                        $scope.tipo_senha = "Fraca";
                        $scope.cor = "Red";
                    } else if ($scope.dificuldade < 40) {
                        $scope.tipo_senha = "Media";
                        $scope.cor = "Orange";
                    } else if ($scope.dificuldade < 60) {
                        $scope.tipo_senha = "Boa";
                        $scope.cor = "SteelBlue";
                    } else {
                        $scope.tipo_senha = "Muito Boa";
                        $scope.cor = "Green";
                    }

                    $scope.carregando = false;

                });

            }

            $scope.setMso = function(v) {
                if ($scope.selecionavel) {
                    $scope.mso = v;
                }
            }

            $scope.selecionar = function() {

                $scope.problema.selecionado = !$scope.problema.selecionado;

            }


        }
    };
})

rtc.controller("crtCfgMaster", function($scope, $sce, empresaService, problemaService, usuarioReduzidoService, usuarioService, cepService, PROBRecebimentoAtividades, sistemaService) {

    $scope.usuarios_reduzidos = createAssinc(usuarioReduzidoService, 1, 10, 4);
    $scope.usuarios_reduzidos.attList();
    assincFuncs(
        $scope.usuarios_reduzidos,
        "u", ["id", "nome", "e.nome"], "filtro2");


    $scope.logs = [];
    $scope.lst_logs = null;

    empresaService.getLogsCFG(function(l) {

        $scope.logs = l.logs;

        $scope.lst_logs = createList($scope.logs, 1, 10, "log");

    })

    $scope.getAsHtml = function(txt) {

        return $sce.trustAsHtml(txt);

    }

    $scope.problemas = createAssinc(problemaService, 4, 1, 4);
    $scope.problemas.attList();
    assincFuncs(
        $scope.problemas,
        "p", ["p.id", "u.nome"], "filtro2");

    $scope.selecionados = [];

    $scope.usuario_novo = {};

    $scope.endereco = null;

    $scope.cadastroLiberado = function() {

        return $scope.endereco !== null &&
            $scope.usuario_novo.cpf.valor.length === 14 &&
            $scope.usuario_novo.nome !== "" &&
            $scope.usuario_novo.email.endereco !== "" &&
            $scope.usuario_novo.rg.valor !== "";

    }

    $scope.usuario_tratado = null;

    $scope.conclusaoCargo = function(problema, usuario) {

        $scope.usuario_tratado = problema.usuario;


    }

    $scope.varrendo = false;

    $scope.varreduraSimples = function(usuarios, redux) {

        $scope.varrendo = true;

        if (!Array.isArray(usuarios)) {
            usuarios = [usuarios];
        }

        sistemaService.varreduraSimples(usuarios, redux, function(r) {

            if (r.sucesso) {

                msg.alerta("Finalizado");

            }

            $scope.varrendo = false;

            $scope.usuario_tratado = null;
            $scope.endereco = null
            $scope.usuario_novo.endereco.cep.valor = "";
            $scope.usuario_novo.id = 0;
            $scope.usuario_novo.cpf.valor = "";
            $scope.usuario_novo.nome = "";
            $scope.usuario_novo.email.endereco = "";
            $scope.usuario_novo.rg.valor = "";

        })

    }

    $scope.varreduraCompleta = function(usuarios, redux) {

        $scope.varrendo = true;

        if (!Array.isArray(usuarios)) {
            usuarios = [usuarios];
        }

        sistemaService.varreduraCompleta(usuarios, redux, function(r) {

            if (r.sucesso) {

                msg.alerta("Varredura Finalizada");

            }

            $scope.varrendo = false;

            $scope.usuario_tratado = null;
            $scope.endereco = null
            $scope.usuario_novo.endereco.cep.valor = "";
            $scope.usuario_novo.id = 0;
            $scope.usuario_novo.cpf.valor = "";
            $scope.usuario_novo.nome = "";
            $scope.usuario_novo.email.endereco = "";
            $scope.usuario_novo.rg.valor = "";

        })

    }

    $scope.problema_cargo = {
        id: 0,
        id_empresa: 1111,
        usuario: null,
        tipo: null,
        _classe: "ProblemaCFG"
    };

    $scope.cemail = function() {

        $scope.problema_cargo.usuario.login = $scope.problema_cargo.usuario.email.endereco;

    }

    PROBRecebimentoAtividades.getPROBRecebimentoAtividades(function(p) {

        $scope.problema_cargo.tipo = p.prob;

        usuarioService.getUsuario(function(u) {

            u.usuario.endereco.cep.valor = "";
            u.usuario.cpf.valor = "";
            u.usuario.rg.valor = "";
            u.usuario.email.endereco = "";

            $scope.usuario_novo = u.usuario;
            $scope.problema_cargo.usuario = $scope.usuario_novo;
        })

    })

    $scope.attEndereco = function() {

        cepService.getEndereco($scope.usuario_novo.endereco.cep, function(e) {

            if (e.sucesso) {

                if (e.endereco !== null) {

                    $scope.endereco = e.endereco;

                    $scope.usuario_novo.endereco = $scope.endereco;

                } else {

                    $scope.endereco = null;

                }

            } else {

                $scope.endereco = null;

            }

        })

    }



    var bsearch = function(usuario) {



        var i = 0;
        var f = $scope.selecionados.length - 1;

        if (f < i) {
            return -1;
        }

        if ($scope.selecionados[i].id === usuario.id) {
            return i;
        } else if ($scope.selecionados[f].id === usuario.id) {
            return f;
        }

        while (f - i > 1) {

            var m = Math.floor((f + i) / 2);

            if ($scope.selecionados[m].id < usuario.id) {
                i = m;
            } else if ($scope.selecionados[m].id > usuario.id) {
                f = m;
            } else {
                return m;
            }

        }

        return -1;

    }

    $scope.isTodos = function() {

        for (var i = 0; i < $scope.usuarios_reduzidos.elementos.length; i++) {
            if (bsearch($scope.usuarios_reduzidos.elementos[i][0]) < 0) {
                return false;
            }
        }

        return true;

    }

    $scope.todos = function() {

        if ($scope.isTodos()) {

            for (var i = 0; i < $scope.usuarios_reduzidos.elementos.length; i++) {
                $scope.selecionar($scope.usuarios_reduzidos.elementos[i][0]);
            }

        } else {

            for (var i = 0; i < $scope.usuarios_reduzidos.elementos.length; i++) {
                if (!$scope.isSelecionado($scope.usuarios_reduzidos.elementos[i][0])) {
                    $scope.selecionar($scope.usuarios_reduzidos.elementos[i][0]);
                }
            }

        }

    }

    $scope.selecionar = function(usuario) {

        var idx = bsearch(usuario);

        if (idx < 0) {
            $scope.selecionados[$scope.selecionados.length] = usuario;
            var i = $scope.selecionados.length - 1;
            while (i > 0 && $scope.selecionados[i].id < $scope.selecionados[i - 1].id) {
                var k = $scope.selecionados[i];
                $scope.selecionados[i] = $scope.selecionados[i - 1];
                $scope.selecionados[i - 1] = k;
                i--;
            }
        } else {
            for (; idx < $scope.selecionados.length - 1; idx++) {
                $scope.selecionados[idx] = $scope.selecionados[idx + 1];
            }
            $scope.selecionados.length--;
        }

    }

    $scope.isSelecionado = function(usuario) {

        return bsearch(usuario) >= 0;

    }


})


rtc.controller("crtGrupoCidades", function($scope, grupoCidadesService, cidadeService, baseService) {

    $scope.grupos = createAssinc(grupoCidadesService, 1, 10, 10);
    $scope.grupos.attList();
    assincFuncs(
        $scope.grupos,
        "grupo_cidades", ["id", "nome"]);

    $scope.grupo_cidades_novo = {};
    $scope.grupo_cidades = {};
    $scope.cidades = [];
    $scope.lstCidades = {};

    $scope.strImportar = "";
    $scope.importando = false;

    var removeAcento = function(text) {
        text = text.replace(new RegExp('[����]', 'gi'), 'a');
        text = text.replace(new RegExp('[���]', 'gi'), 'e');
        text = text.replace(new RegExp('[���]', 'gi'), 'i');
        text = text.replace(new RegExp('[����]', 'gi'), 'o');
        text = text.replace(new RegExp('[���]', 'gi'), 'u');
        text = text.replace(new RegExp('[�]', 'gi'), 'c');
        return text;
    };

    $scope.importar = function() {

        $scope.importando = true;

        var nomes = removeAcento($scope.strImportar).toUpperCase().split(/\r|\r\n|\n/);

        for (var i = 0; i < nomes.length; i++) {

            var n = nomes[i];

            for (var j = 0; j < $scope.cidades.length; j++) {

                var c = $scope.cidades[j];

                if (removeAcento(c.nome).toUpperCase() === n) {

                    $scope.addCidade(c);
                    break;

                }

            }

        }

        $scope.importando = false;

        $("#importar").modal("hide");

        msg.alerta("Operacao efetuada com sucesso. Salve para confirmar");

    }

    grupoCidadesService.getGrupoCidades(function(p) {
        $scope.grupo_cidades_novo = p.grupo_cidades;
    })
    cidadeService.getElementos(function(c) {
        $scope.lstCidades = createList(c.elementos, 1, 10, "nome");
        $scope.cidades = c.elementos;
    })

    $scope.addCidade = function(cidade) {

        $scope.grupo_cidades.cidades[$scope.grupo_cidades.cidades.length] = cidade;
        $scope.grupo_cidades.lst_cidades.attList();

    }

    $scope.removeCidade = function(cidade) {

        var nc = [];

        for (var i = 0; i < $scope.grupo_cidades.cidades.length; i++) {

            var c = $scope.grupo_cidades.cidades[i];

            if (c.id !== cidade.id) {

                nc[nc.length] = c;

            }

        }

        $scope.grupo_cidades.cidades = nc;

        $scope.grupo_cidades.lst_cidades = createList($scope.grupo_cidades.cidades, 1, 10, "nome");

    }

    $scope.novoGrupoCidades = function() {

        $scope.setGrupoCidades(angular.copy($scope.grupo_cidades_novo));

    }

    $scope.setGrupoCidades = function(grupo) {

        $scope.grupo_cidades = grupo;

        grupo.lst_cidades = createList(grupo.cidades, 1, 10, "nome");

    }

    $scope.mergeGrupoCidades = function() {
        var env = angular.copy($scope.grupo_cidades);
        env.lst_cidades = undefined;
        baseService.merge(env, function(r) {
            if (r.sucesso) {
                $scope.grupo_cidades.id = r.o.id;
                msg.alerta("Operacao Efetuada com sucesso");
                $scope.grupos.attList();
            } else {
                msg.erro("Problema ao efetuar operacao. ");
            }
        });

    }
    $scope.deleteGrupoCidades = function() {
        var env = angular.copy($scope.grupo_cidades);
        env.lst_cidades = undefined;
        baseService.delete(env, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.grupos.attList();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
        });
    }

    $scope.removeDocumento = function(documento) {
        remove($scope.fornecedor.documentos, documento);
    }

    $scope.addDocumento = function() {

        $scope.fornecedor.documentos[$scope.fornecedor.documentos.length] = $scope.documento;
        $scope.documento = angular.copy($scope.documento_novo);
        $scope.documento.categoria = $scope.categorias_documento[0];

    }
    $scope.removeTelefone = function(tel) {

        remove($scope.fornecedor.telefones, tel);

    }
    $scope.addTelefone = function() {
        $scope.fornecedor.telefones[$scope.fornecedor.telefones.length] = $scope.telefone;
        $scope.telefone = angular.copy($scope.telefone_novo);
    }

})
rtc.controller("crtRelatorioLucros", function($scope, empresaService) {

    $scope.relatorio = "";
    $scope.inicio = new Date().getTime();
    $scope.fim = new Date().getTime();
    $scope.carregando_relatorio = false;

    $scope.icms = 1;
    $scope.juros = 1;
    $scope.ipi = 1;
    $scope.frete = 1;

    var ultimo_campo = [-1, "__"];

    $scope.orderBY = function(campo, vetor) {

        if (ultimo_campo[1] === campo) {
            ultimo_campo[0] = ultimo_campo[0] === -1 ? 1 : -1;
        } else {
            ultimo_campo = [-1, campo];
        }

        var cmp = function(x1, x2) {

            var v1 = x1[campo];
            var v2 = x2[campo];

            if (typeof v1 === 'string') {

                return v1.localeCompare(v2);

            } else {

                return (v1 > v2) ? 1 : ((v1 < v2) ? -1 : 0);

            }

        }

        for (var i = 1; i < vetor.length; i++) {
            for (var j = i; j > 0 && (cmp(vetor[j], vetor[j - 1]) * ultimo_campo[0]) > 0; j--) {
                var k = vetor[j - 1];
                vetor[j - 1] = vetor[j];
                vetor[j] = k;
            }
        }



    }

    $scope.gerar = function() {

        $scope.carregando_relatorio = true;
        empresaService.getBaseRelatorioLucros($scope.inicio, $scope.fim, $scope.icms, $scope.juros, $scope.frete, $scope.ipi, function(r) {

            if (r.sucesso) {

                $scope.relatorio = r.relatorio;
                $("#mdlRelatorio").modal('show');

            } else {

                msg.erro("Ocorreu um problema ao gerar o relatorio");

            }

            $scope.carregando_relatorio = false;

        })


    }


    $scope.gerarPDF = function() {

        $scope.carregando_relatorio = true;
        empresaService.getRelatorioLucros($scope.inicio, $scope.fim, $scope.icms, $scope.juros, $scope.frete, $scope.ipi, function(r) {

            if (r.sucesso) {

                $scope.relatorio = r.relatorio;
                $("#mdlRelatorioPDF").modal('show');

            } else {

                msg.erro("Ocorreu um problema ao gerar o relatorio");

            }

            $scope.carregando_relatorio = false;

        })

    }

})

rtc.controller("crtClienteRelatorio", function($scope, $sce, clienteRelatorioService, categoriaProspeccaoService, clienteService, sistemaService, empresaService, categoriaClienteService, categoriaDocumentoService, documentoService, cidadeService, baseService, telefoneService, uploadService) {

    $scope.filtros = [
        { id: 0, nome: "Clientes do email", sql: "cliente.cnpj IN (SELECT cnpj FROM filtro_padrao_relatorio_clientes)" },
        { id: 1, nome: "Clientes do Boas Vindas", sql: "cliente.boas_vindas_enviada" }
    ];

    $scope.clientes = createAssinc(clienteRelatorioService, 1, 10, 10);

    $scope.historico = null;
    $scope.cliente_historico = null;

    $scope.getHistorico = function(cliente) {

        clienteRelatorioService.getHistorico(cliente, function(r) {

            if (r.sucesso) {
                $scope.cliente_historico = cliente;
                $scope.historico = r.historico;
                $scope.historico.historico = $sce.trustAsHtml($scope.historico.historico);
                $("#mdlHistorico").modal("show")

            }

        })

    }

    assincFuncs(
        $scope.clientes,
        "cliente", ["razao_social", "id", "cnpj", "classe_virtual", "cidade_cliente.nome", "estado_cliente.sigla"], "filtro", false);
    $scope.clientes["posload"] = function() {

        if ($scope.filtro.id === 0) {

            //$scope.quantidade = 6273;
            //return;

        }

        $scope.quantidade = $scope.clientes.quantidade;

    };

    $scope.classes = [];

    $scope.filtro = $scope.filtros[0];

    //------------

    $scope.documento_novo = {};
    $scope.documento = {};

    $scope.telefone_novo = {};
    $scope.telefone = {};
    $scope.estado;
    $scope.categorias_cliente = [];
    $scope.categorias_documento = [];
    $scope.estados = [];
    $scope.cidades = [];

    $scope.quantidade = 0;

    categoriaProspeccaoService.getCategorias(function(c) {

        $scope.categorias_prospeccao = c.categorias;

    })

    empresaService.getEmpresasClientes(function(e) {

        $scope.empresas_clientes = e.clientes;

    })


    $scope.removeDocumento = function(documento) {
        remove($scope.cliente.documentos, documento);
    }

    $scope.addDocumento = function() {

        $scope.cliente.documentos[$scope.cliente.documentos.length] = $scope.documento;
        $scope.documento = angular.copy($scope.documento_novo);
        $scope.documento.categoria = $scope.categorias_documento[0];

    }
    $scope.removeTelefone = function(tel) {

        remove($scope.cliente.telefones, tel);

    }
    $scope.addTelefone = function() {

        $scope.cliente.telefones[$scope.cliente.telefones.length] = $scope.telefone;
        $scope.telefone = angular.copy($scope.telefone_novo);
    }

    $scope.removeCategoriaProspeccao = function(cat) {


        var nc = [];
        for (var i = 0; i < $scope.categorias_prospeccao_cliente.length; i++) {
            if ($scope.categorias_prospeccao_cliente[i].id === cat.id) {
                continue;
            }
            nc[nc.length] = $scope.categorias_prospeccao_cliente[i];
        }

        $scope.categorias_prospeccao_cliente = nc;

    }

    //------------

    $scope.addCategoriaProspeccao = function() {

        for (var i = 0; i < $scope.categorias_prospeccao_cliente.length; i++) {
            if ($scope.categorias_prospeccao_cliente[i].id === $scope.categoria_prospeccao.id) {
                msg.erro("Essa categoria ja esta adcionada");
                return;
            }
        }

        $scope.categorias_prospeccao_cliente[$scope.categorias_prospeccao_cliente.length] = $scope.categoria_prospeccao;

    }

    $("#uploaderDocumentoCliente").change(function() {

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo");

            } else {

                var doc = angular.copy($scope.documento);

                for (var i = 0; i < arquivos.length; i++) {

                    var d = angular.copy(doc);
                    $scope.documento = d;
                    d.link = arquivos[i];

                    $scope.addDocumento();

                }

                msg.alerta("Upload feito com sucesso");
            }

        })

    })

    clienteService.getCliente(function(p) {
        $scope.cliente_novo = p.cliente;
        $scope.cliente_novo["documentos"] = [];
    })
    categoriaClienteService.getElementos(function(p) {
        $scope.categorias_cliente = p.elementos;
    })
    categoriaDocumentoService.getElementos(function(p) {
        $scope.categorias_documento = p.elementos;
        $scope.documento.categoria = $scope.categorias_documento[0];
    })
    documentoService.getDocumento(function(p) {
        $scope.documento_novo = p.documento;
        $scope.documento = angular.copy($scope.documento_novo);
        $scope.documento.categoria = $scope.categorias_documento[0];
    })
    telefoneService.getTelefone(function(p) {

        $scope.telefone_novo = p.telefone;
        $scope.telefone = angular.copy($scope.telefone_novo);
    })

    cidadeService.getElementos(function(p) {
        var estados = [];
        var cidades = p.elementos;
        $scope.cidades = cidades;

        lbl:
            for (var i = 0; i < cidades.length; i++) {
                var c = cidades[i];
                for (var j = 0; j < estados.length; j++) {
                    if (estados[j].id === c.estado.id) {
                        estados[j].cidades[estados[j].cidades.length] = c;
                        c.estado = estados[j];
                        continue lbl;
                    }
                }
                c.estado["cidades"] = [c];
                estados[estados.length] = c.estado;
            }

        $scope.estados = estados;
    })

    //------------


    $scope.cliente = {};

    $scope.mergeCliente = function() {

        baseService.merge($scope.cliente, function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso");

            } else {

                msg.erro("Falha ao efetuar operacao");

            }

        });

    }

    $scope.setCliente = function(cliente) {

        clienteRelatorioService.getCliente(cliente, function(c) {

            $scope.cliente = c.cliente;

            equalize($scope.cliente, "categoria", $scope.categorias_cliente);

            clienteService.getDocumentos($scope.cliente, function(d) {
                $scope.cliente["documentos"] = d.documentos;
                for (var i = 0; i < d.documentos.length; i++) {
                    equalize(d.documentos[i], "categoria", $scope.categorias_documento);
                }
            })

            clienteService.getCategoriasProspeccao($scope.cliente, function(c) {
                $scope.categorias_prospeccao_cliente = c.categorias;

            })

            equalize($scope.cliente.endereco, "cidade", $scope.cidades);

            if (typeof $scope.cliente.endereco.cidade !== 'undefined') {
                $scope.estado = $scope.cliente.endereco.cidade.estado;
            } else {
                $scope.cliente.endereco.cidade = $scope.cidades[0];
                $scope.estado = $scope.cliente.endereco.cidade.estado;
            }

        })

    }

    $scope.filtro_estado = "";

    $scope.changeFiltro = function() {

        clienteRelatorioService.filtro_base = $scope.filtro.sql;

        if ($scope.filtro_estado != "") {

            clienteRelatorioService.filtro_base += " AND (estado_cliente.sigla LIKE '%" + $scope.filtro_estado + "%')";

        }

        if ($scope.classes.length > 0) {

            $scope.clientes.attList();

        }

    }

    $scope.changeFiltro();

    $scope.atualizar = function(cliente) {

        clienteRelatorioService.merge(cliente, function(r) {

            if (!r.sucesso) {

                msg.erro("Ocorreu uma falha ao atualizar");

            }

        })

    }

    $scope.getClasse = function(it) {

        for (var i = 0; i < $scope.classes.length; i++) {

            if ($scope.classes[i].id === it.classe) {

                return $scope.classes[i];

            }

        }

        return $scope.classes[0];

    }

    clienteRelatorioService.getClasses(function(r) {

        var classes = r.classes;

        for (var i = 0; i < classes.length; i++) {

            classes[i] = { id: classes[i][0], nome: classes[i][1], cor: classes[i][2] };

            $scope.filtros[$scope.filtros.length] = {
                id: $scope.filtros.length,
                nome: "Filtro classe '" + classes[i].nome + "'",
                sql: "cliente.classe_virtual=" + classes[i].id
            };

        }

        $scope.classes = classes;

        $scope.clientes.attList();

    })

})
rtc.controller("crtEncomendaTerceiros", function($scope, produtoService, encomendaTerceiroService, sistemaService, carrinhoEncomendaService) {

    $scope.locais = [];
    $scope.produto = null;

    $scope.carregando_encomenda = true;
    $scope.loaders = [{ id: 0 }, { id: 1 }, { id: 2 }, { id: 3 }, { id: 4 }, { id: 5 }];

    $scope.produtos = createFilterList(encomendaTerceiroService, 3, 6, 10);
    $scope.produtos["posload"] = function(els) {

        $scope.carregando_encomenda = false;

    }
    $scope.produtos.attList();



    $scope.qtd = 0;
    $scope.prod = null;
    $scope.val = null;

    var carrinho = [];

    carrinhoEncomendaService.getCarrinho(function(c) {

        carrinho = c.carrinho;

    })


    $scope.addCarrinho = function(produto) {

        $scope.prod = produto;

        $scope.qtd = parseFloat(window.prompt("Quantidade"));
        if (isNaN($scope.qtd)) {
            msg.erro("Quantidade incorreta");
            return;
        }

        $scope.qtd = parseInt(($scope.qtd + ""));


        var p = angular.copy($scope.prod);
        p.quantidade_comprada = $scope.qtd;

        if (p.limite > 0) {
            if (p.quantidade_comprada > p.limite) {
                msg.erro("Essa quantidade ultrapassa o limite para este produto");
                return;
            }
        }

        var a = false;
        for (var i = 0; i < carrinho.length; i++) {
            if (carrinho[i].id === p.id) {

                if (p.limite > 0) {
                    if (carrinho[i].quantidade_comprada + $scope.qtd > p.limite) {
                        msg.erro("Essa quantidade ultrapassa o limite para este produto");
                        return;
                    }
                }

                carrinho[i].quantidade_comprada += $scope.qtd;
                a = true;
                break;
            }
        }

        if (!a) {
            carrinho[carrinho.length] = p;
        }
        carrinhoEncomendaService.setCarrinho(carrinho, function(r) {

            if (r.sucesso) {

                msg.confirma("Adicionado com sucesso. Deseja finalizar ?", function() {
                    window.location = 'carrinho_encomenda.php';
                });


            } else {

                msg.erro("Falha ao adicionar o produto");

            }

        })

    }


    $scope.setProduto = function(produto) {
        $scope.prod = produto;
    }

    $scope.addLevel = function(op, filtro) {
        op.selecionada++;
        op.selecionada = op.selecionada % 2;

        for (var i = 0; i < filtro.opcoes.length; i++) {
            if (filtro.opcoes[i].selecionada > 0 && filtro.opcoes[i].id !== op.id) {
                filtro.opcoes[i].selecionada = 0;
            }
        }

        $scope.produtos.attList();
    }

    $scope.resetarFiltro = function() {

        for (var i = 0; i < $scope.produtos.filtro.length; i++) {
            var f = $scope.produtos.filtro[i];
            if (f._classe === 'FiltroTextual') {
                f.valor = "";
            } else if (f._classe === 'FiltroOpcional') {
                for (var j = 0; j < f.opcoes.length; j++) {
                    f.opcoes[j].selecionada = 0;
                }
            }
        }

        $scope.produtos.attList();

    }

    $scope.dividir = function(produtos, qtd) {

        var k = Math.ceil((produtos.length) / qtd);

        var m = [];

        for (var a = 0; a < qtd; a++) {
            m[a] = [];
            for (var i = a * k; i < (a + 1) * k && i < produtos.length; i++) {
                for (var j = 0; j < produtos[i].length; j++) {
                    m[a][m[a].length] = produtos[i][j];
                }
            }
        }

        return m;

    }

})
rtc.controller("crtCompraTerceiros", function($scope, produtoService, compraTerceiroService, sistemaService, carrinhoService) {

    $scope.tv = function(produto) {

        return typeof produto.produtos[0]["validades"] !== 'undefined';

    }

    $scope.locais = [];
    $scope.produto = null;

    $scope.carregando_compra = true;
    $scope.loaders = [{ id: 0 }, { id: 1 }, { id: 2 }, { id: 3 }, { id: 4 }, { id: 5 }];

    $scope.produtos = createFilterList(compraTerceiroService, 3, 6, 10);
    $scope.produtos["posload"] = function(elementos) {
        $scope.carregando_compra = false;
        sistemaService.getMesesValidadeCurta(function(p) {
            var produtos = [];
            for (var i = 0; i < elementos.length; i++) {
                for (var j = 0; j < elementos[i].produtos.length; j++) {
                    produtos[produtos.length] = elementos[i].produtos[j];
                }
            }
            produtoService.remessaGetValidades(p.meses_validade_curta, produtos, function() {});
        });
    }
    $scope.produtos.attList();

    $scope.nl = function(grupo) {
        var m = 0;
        for (var i = 0; i < grupo.produtos.length; i++) {
            if (grupo.produtos[i].validades.length > m) {
                m = grupo.produtos[i].validades.length;
            }
        }
        var ret = [];
        for (var i = 0; i < m; i++) {
            ret[i] = i;
        }
        return ret;
    }

    $scope.maisLocais = function(produto) {

        var principal = $scope.gp(produto);

        $scope.produto = produto;

        $scope.locais = [];

        for (var i = 0; i < produto.produtos.length; i++) {
            var p = produto.produtos[i];
            if (p !== principal) {
                for (var k = 0; k < p.validades.length; k++) {
                    $scope.locais[$scope.locais.length] = { local: p, validade: p.validades[k] };
                }
            }
        }

        $("#locaisProduto").modal('show');

    }

    $scope.gp = function(grupo) {

        var mi = -1;
        var mq = 0;

        for (var i = 0; i < grupo.produtos.length; i++) {
            var produto = grupo.produtos[i];
            var qtd = 0;
            for (var k = 0; k < produto.validades.length; k++) {
                qtd += produto.validades[k].quantidade;
            }
            if (mi < 0 || mq < qtd) {
                mq = qtd;
                mi = i;
            }
        }

        return grupo.produtos[mi];

    }


    $scope.qtd = 0;
    $scope.prod = null;
    $scope.val = null;
    $scope.meses_validade_curta = 3;

    var carrinho = [];


    carrinhoService.getCarrinho(function(c) {

        carrinho = c.carrinho;

    })

    sistemaService.getMesesValidadeCurta(function(p) {

        $scope.meses_validade_curta = p.meses_validade_curta;

    })

    $scope.addCarrinho = function(produto, validade) {

        $scope.prod = produto;

        $scope.qtd = parseFloat(window.prompt("Quantidade"));
        if (isNaN($scope.qtd)) {
            msg.erro("Quantidade incorreta");
            return;
        }

        $scope.qtd = parseInt(($scope.qtd + ""));

        $scope.val = validade;

        if ($scope.qtd > $scope.val.quantidade) {

            msg.erro("Nao temos essa quantidade");
            return;

        }

        var p = angular.copy($scope.prod);
        p.validade = $scope.val;
        p.quantidade_comprada = $scope.qtd;

        var limite = p.validade.limite;

        var a = false;
        for (var i = 0; i < carrinho.length; i++) {
            if (carrinho[i].id === p.id && carrinho[i].validade.validade === p.validade.validade) {
                a = true;
                if ((p.quantidade_comprada + carrinho[i].quantidade_comprada) > p.validade.quantidade) {
                    msg.erro("Nao temos essa quantidade");
                    return;
                }
                if ((p.quantidade_comprada + carrinho[i].quantidade_comprada) > limite && limite > 0) {
                    msg.erro("Voce esta ultrapassando o limite de compra");
                    return;
                }
            }
        }

        if (!a) {
            carrinho[carrinho.length] = p;
        }
        carrinhoService.setCarrinho(carrinho, function(r) {

            if (r.sucesso) {

                msg.alerta("Adicionado com sucesso");

                $("#indicadorAdd").css('visibility', 'initial');

            } else {

                msg.erro("Falha ao adicionar o produto");

            }

        })

    }


    $scope.setProduto = function(produto) {
        $scope.prod = produto;
        produtoService.getValidades($scope.meses_validade_curta, produto, function(v) {
            produto.validades = v;
        })
    }

    $scope.addLevel = function(op, filtro) {
        op.selecionada++;
        op.selecionada = op.selecionada % 2;

        for (var i = 0; i < filtro.opcoes.length; i++) {
            if (filtro.opcoes[i].selecionada > 0 && filtro.opcoes[i].id !== op.id) {
                filtro.opcoes[i].selecionada = 0;
            }
        }

        $scope.produtos.attList();
    }

    $scope.resetarFiltro = function() {

        for (var i = 0; i < $scope.produtos.filtro.length; i++) {
            var f = $scope.produtos.filtro[i];
            if (f._classe === 'FiltroTextual') {
                f.valor = "";
            } else if (f._classe === 'FiltroOpcional') {
                for (var j = 0; j < f.opcoes.length; j++) {
                    f.opcoes[j].selecionada = 0;
                }
            }
        }

        $scope.produtos.attList();

    }

    $scope.dividir = function(produtos, qtd) {

        var k = Math.ceil((produtos.length) / qtd);

        var m = [];

        for (var a = 0; a < qtd; a++) {
            m[a] = [];
            for (var i = a * k; i < (a + 1) * k && i < produtos.length; i++) {
                for (var j = 0; j < produtos[i].length; j++) {
                    m[a][m[a].length] = produtos[i][j];
                }
            }
        }

        return m;

    }

})
rtc.controller("crtTarefaSimplificada", function($scope, $sce, clienteService, tarefaSimplificadaService, produtoService, baseService, uploadService, tipoTarefaService) {

    $scope.tarefas = createAssinc(tarefaSimplificadaService, 1, 10, 4);
    assincFuncs(
        $scope.tarefas,
        "t", ["prioridade", "id", "descricao", "id_tipo_tarefa", "momento"]);
    $scope.tarefas.attList();

    $scope.sincronizar = function(usuario, tarefa) {

        for (var i = 0; i < tarefa.usuarios.length; i++) {
            if (tarefa.usuarios[i].id === usuario.id) {
                tarefa.usuarios[i].minutos_orcamento = usuario.minutos_orcamento;
            }
        }

    }

    $scope.clientes = createAssinc(clienteService, 1, 3, 4);
    assincFuncs(
        $scope.clientes,
        "cliente", ["codigo", "razao_social"], "filtroClientes");

    $scope.produtos = createAssinc(produtoService, 1, 5, 4);
    assincFuncs(
        $scope.produtos,
        "produto", ["codigo", "nome", "disponivel"], "filtroProdutos");

    $scope.filtro = 0;

    $scope.filtro_funcionarios = "";

    $scope.filtros = [
        { id: 0, nome: "Sem filtro", sql: "" },
        { id: 1, nome: "Pausadas", sql: "(t.id IN (SELECT tt.id_tarefa FROM andamento_tarefa_simplificada tt INNER JOIN (SELECT MAX(ttt.id) as 'id' FROM andamento_tarefa_simplificada ttt GROUP BY ttt.id_tarefa) kk ON kk.id=tt.id WHERE tt.tipo=1))" },
        { id: 2, nome: "Concluidas", sql: "(t.id IN (SELECT tt.id_tarefa FROM andamento_tarefa_simplificada tt INNER JOIN (SELECT MAX(ttt.id) as 'id' FROM andamento_tarefa_simplificada ttt GROUP BY ttt.id_tarefa) kk ON kk.id=tt.id WHERE tt.tipo=2))" },
        { id: 3, nome: "Em andamento", sql: "(t.id IN (SELECT tt.id_tarefa FROM andamento_tarefa_simplificada tt INNER JOIN (SELECT MAX(ttt.id) as 'id' FROM andamento_tarefa_simplificada ttt GROUP BY ttt.id_tarefa) kk ON kk.id=tt.id WHERE tt.tipo=0))" },
        { id: 4, nome: "Nao inciadas", sql: "(t.id NOT IN (SELECT tt.id_tarefa FROM andamento_tarefa_simplificada tt GROUP BY tt.id_tarefa) and t.orcamento=false)" },
        { id: 5, nome: "Orcamento", sql: "(t.id NOT IN (SELECT tt.id_tarefa FROM andamento_tarefa_simplificada tt GROUP BY tt.id_tarefa) and t.orcamento=true)" }
    ]



    $scope.changeFiltro = function() {

        tarefaSimplificadaService.filtro_base = $scope.filtros[$scope.filtro].sql;

        if ($scope.filtro_funcionarios !== "") {

            if (tarefaSimplificadaService.filtro_base !== "") {

                tarefaSimplificadaService.filtro_base += " AND ";

            }

            tarefaSimplificadaService.filtro_base += "(t.id IN (SELECT t.id_tarefa FROM usuario_tarefa_simplificada t INNER JOIN usuario u ON t.id_usuario=u.id WHERE u.nome like '%" + $scope.filtro_funcionarios + "%'))";

        }

        $scope.tarefas.attList();

    }

    $scope.tipos_andamento = [
        { id: 0, nome: "Inicio" },
        { id: 1, nome: "Pausa" },
        { id: 2, nome: "Fim" }
    ]

    $scope.getFim = function(tarefa) {

        for (var i = 0; i < tarefa.andamentos.length; i++) {
            if (tarefa.andamentos[i].tipo === 2) {
                return new Date(parseFloat(tarefa.andamentos[i].momento + "")).toLocaleString();
            }
        }

        return 0;

    }

    $scope.getInicio = function(tarefa) {

        for (var i = 0; i < tarefa.andamentos.length; i++) {
            if (tarefa.andamentos[i].tipo === 0) {
                return new Date(parseFloat(tarefa.andamentos[i].momento + "")).toLocaleString();
            }
        }

        return 0;

    }

    $scope.nova_tarefa = {};
    $scope.tarefa = {};

    $scope.tipos_tarefa = [];

    $scope.novo_andamento = {};
    $scope.andamento = {};

    $scope.novo_produto_tarefa_simplificada = {};

    tarefaSimplificadaService.getProdutoTarefaSimplificada(function(p) {
        $scope.novo_produto_tarefa_simplificada = p.produto;
    })

    $scope.setCliente = function(c) {

        $scope.tarefa.cliente = c;

    }

    $scope.quantidade = 0;
    $scope.addProduto = function(p) {

        if ($scope.quantidade == 0) {
            return;
        }

        if ($scope.quantidade > p.disponivel) {
            //msg.erro("Quantidade maior que disponivel");
            //return;
        }

        var pt = angular.copy($scope.novo_produto_tarefa_simplificada);

        pt.tarefa = $scope.tarefa;
        pt.produto = p;
        pt.quantidade = $scope.quantidade;
        pt.valor = p.valor_base;

        $scope.tarefa.produtos[$scope.tarefa.produtos.length] = pt;

    }

    $scope.removeProduto = function(p) {

        var np = [];

        for (var i = 0; i < $scope.tarefa.produtos.length; i++) {
            var pi = $scope.tarefa.produtos[i];
            if (pi.id !== p.id) {
                np[np.length] = pi;
            }
        }

        $scope.tarefa.produtos = np;

    }

    $("#flArquivos").change(function() {

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo");

            } else {

                var doc = angular.copy($scope.documento);

                for (var i = 0; i < arquivos.length; i++) {

                    $scope.tarefa.arquivos[$scope.tarefa.arquivos.length] = arquivos[i];

                }

                baseService.merge($scope.tarefa, function(r) {

                    if (r.sucesso) {

                        msg.alerta("Operacao efetuada com sucesso");

                    } else {

                        msg.erro("Falha ao efetuar operacao2");

                    }

                })
            }

        })

    })

    $scope.initUpload = function(tarefa) {

        $scope.tarefa = tarefa;

        $('#flArquivos').click();

    }

    $scope.novoAndamento = function(tarefa) {

        $scope.andamento = angular.copy($scope.novo_andamento);
        $scope.andamento.tarefa = tarefa;
        $scope.andamento.usuario = tarefa.usuarios[0];

        var possiveis = $scope.getTiposPossiveis($scope.andamento);
        $scope.andamento.tipo = possiveis[0].id;

    }

    tarefaSimplificadaService.getAndamentoTarefaSimplificada(function(a) {

        $scope.novo_andamento = a.andamento;

    })

    $scope.getQuantidadeHoras = function(tarefa) {

        var us = [];

        for (var i = 0; i < tarefa.andamentos.length; i++) {
            var a = tarefa.andamentos[i];
            var j = 0;
            for (; j < us.length; j++) {
                var uu = us[j];
                if (uu.id === a.usuario.id) {
                    break;
                }
            }
            if (j === us.length) {
                var nu = angular.copy(a.usuario);
                nu["horas"] = 0;
                nu["andamentos"] = [];
                us[us.length] = nu;
            }

            var u = us[j];
            u.andamentos[u.andamentos.length] = a;

            var k = u.andamentos.length - 1;
            while (k > 0 && u.andamentos[k].momento < u.andamentos[k - 1].momento) {
                var l = u.andamentos[k];
                u.andamentos[k] = u.andamentos[k - 1];
                u.andamentos[k - 1] = l;
            }

        }

        for (var i = 0; i < us.length; i++) {
            var u = us[i];
            for (var j = 0;
                (j + 1) < u.andamentos.length; j += 2) {
                u.horas += u.andamentos[j + 1].momento - u.andamentos[j].momento;
            }
        }

        return us;

    }

    $scope.valorOrcamento = function(tarefa) {

        var total = 0;


        if (tarefa.produtos !== null) {
            for (var i = 0; i < tarefa.produtos.length; i++) {
                var p = tarefa.produtos[i];
                total = p.quantidade * p.valor;
            }
        }

        for (var i = 0; i < tarefa.usuarios.length; i++) {
            var u = tarefa.usuarios[i];
            total += (((u.faixa_salarial / 22) / 8) / 60) * (u.minutos_orcamento);
        }

        return total.toFixed(2).split(".").join(",");


    }

    $scope.valor = function(tarefa) {

        var total = 0;

        if (tarefa.produtos !== null) {
            for (var i = 0; i < tarefa.produtos.length; i++) {
                var p = tarefa.produtos[i];
                total = p.quantidade * p.valor;
            }
        }

        var us = $scope.getQuantidadeHoras(tarefa);

        for (var i = 0; i < us.length; i++) {
            var u = us[i];
            total += ((u.faixa_salarial / 22) / 8) * (u.horas / (1000 * 60 * 60));
        }

        return total.toFixed(2).split(".").join(",");

    }


    $scope.horasGastas = function(tarefa) {

        var us = $scope.getQuantidadeHoras(tarefa);

        var str = "";

        for (var i = 0; i < us.length; i++) {
            var u = us[i];
            str += u.id + " - " + u.nome + ", utilizou " + (u.horas / (1000 * 60)).toFixed(0) + " minutos <br>";
        }

        return $sce.trustAsHtml(str);

    }

    $scope.setTarefa = function(tarefa) {

        $scope.tarefa = tarefa;

        if ($scope.tarefa.tipo === null) {

            $scope.tarefa.tipo = $scope.tipos_tarefa[0];

        }

        tarefaSimplificadaService.getProdutos($scope.tarefa, function(p) {
            if (p.sucesso) {
                $scope.tarefa.produtos = p.produtos;
            }
        })

        equalize($scope.tarefa, "tipo", $scope.tipos_tarefa);

        $scope.getUsuariosPossiveis($scope.tarefa);

    }

    $scope.novaTarefa = function() {

        if ($scope.tarefa.id !== 0 || $scope.tarefa.orcamento) {

            $scope.tarefa = angular.copy($scope.nova_tarefa);
            $scope.setTarefa($scope.tarefa);

        }

    }

    $scope.novoOrcamento = function() {

        if ($scope.tarefa.id !== 0 || !$scope.tarefa.orcamento) {

            $scope.tarefa = angular.copy($scope.novo_orcamento);
            $scope.setTarefa($scope.tarefa);

        }

    }

    $scope.getStatus = function(tarefa) {

        if (tarefa.andamentos.length === 0) {

            if (tarefa.orcamento) {

                return -2;

            }

            return -1;

        }

        return tarefa.andamentos[tarefa.andamentos.length - 1].tipo;

    }

    tipoTarefaService.getTiposTarefa(function(t) {

        $scope.tipos_tarefa = t.tipos_tarefa;

    })

    $scope.novo_orcamento = {};

    tarefaSimplificadaService.getTarefaSimplificada(function(t) {

        $scope.nova_tarefa = t.tarefa;

        $scope.novo_orcamento = angular.copy(t.tarefa);
        $scope.novo_orcamento.orcamento = true;

    })

    $scope.usuarios_possiveis = [];

    $scope.getUsuariosPossiveis = function(tarefa) {

        tarefaSimplificadaService.getUsuariosPossiveis(tarefa.tipo, function(u) {

            $scope.usuarios_possiveis = u.usuarios;

            if (tarefa.orcamento) {

                for (var i = 0; i < tarefa.usuarios.length; i++) {

                    var u = tarefa.usuarios[i];

                    for (var j = 0; j < $scope.usuarios_possiveis.length; j++) {

                        var up = $scope.usuarios_possiveis[j];

                        if (u.id === up.id) {

                            up.minutos_orcamento = u.minutos_orcamento;

                        }

                    }

                }

            }

        })

    }

    $scope.contem = function(usuario, tarefa) {

        for (var i = 0; i < tarefa.usuarios.length; i++) {
            if (tarefa.usuarios[i].id === usuario.id) {
                return true;
            }
        }

        return false;

    }

    $scope.minutosParaReal = function(usuario) {

        return ((((usuario.faixa_salarial / 22) / 8) / 60) * usuario.minutos_orcamento).toFixed(2).split(".").join(",");

    }

    $scope.addUsuario = function(usuario, tarefa) {

        for (var i = 0; i < tarefa.usuarios.length; i++) {
            if (tarefa.usuarios[i].id === usuario.id) {
                msg.erro("Esse usuario ja esta na tarefa");
                return;
            }
        }

        tarefa.usuarios[tarefa.usuarios.length] = angular.copy(usuario);

    }

    $scope.removeUsuario = function(usuario, tarefa) {

        var nu = [];

        for (var i = 0; i < tarefa.usuarios.length; i++) {
            if (tarefa.usuarios[i].id !== usuario.id) {
                nu[nu.length] = tarefa.usuarios[i];
            }
        }

        tarefa.usuarios = nu;

    }

    $scope.removeArquivo = function(arquivo, tarefa) {

        var na = [];

        for (var i = 0; i < tarefa.arquivos.length; i++) {
            if (tarefa.arquivos[i] !== arquivo) {
                na[na.length] = tarefa.arquivos[i];
            }
        }

        tarefa.arquivos = na;

        baseService.merge(tarefa, function(r) {

            if (!r.sucesso) {

                msg.erro("Falha ao efetuar operacao");

            }

        })

    }

    $scope.removeAndamento = function(andamento, tarefa) {

        baseService.delete(andamento, function(r) {

            if (r.sucesso) {

                var na = [];

                for (var i = 0; i < tarefa.andamentos.length; i++) {
                    if (tarefa.andamentos[i].id !== andamento.id) {
                        na[na.length] = tarefa.andamentos[i];
                    }
                }

                tarefa.andamentos = na;

                msg.alerta("OperaÃÂ§ÃÂ£o efetuada com sucesso");


            } else {

                msg.erro("Ocorreu um problema ao executar a operaÃÂ§ÃÂ£o");

            }

        })

    }


    $scope.getTiposPossiveis = function(andamento) {

        return $scope.tipos_andamento;
        //---- imediato;
        var possiveis = [];

        var ultimo_andamento = null;

        if (andamento.tarefa.andamentos.length > 0) {
            ultimo_andamento = andamento.tarefa.andamentos[andamento.tarefa.andamentos.length - 1];
        }

        if (ultimo_andamento === null) {

            possiveis = [$scope.tipos_andamento[0]];

            return possiveis;

        } else if (ultimo_andamento.tipo === 0) {


            possiveis = [$scope.tipos_andamento[1], $scope.tipos_andamento[2]];

            return possiveis;

        } else {

            possiveis = [$scope.tipos_andamento[0]];

            return possiveis;

        }

    }

    $scope.mergeAndamento = function(andamento) {

        baseService.merge(andamento, function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso");
                $scope.tarefas.attList();

            } else {

                msg.erro("Falha ao efetuar operacao1");

            }

        })

    }

    $scope.deleteTarefa = function(tarefa) {

        baseService.delete(tarefa, function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso");
                $scope.tarefas.attList();

            } else {

                msg.erro("Falha ao efetuar operacao");

            }

        })

    }

    $scope.mergeTarefa = function(tarefa) {

        if (tarefa.usuarios.length === 0) {

            msg.erro("A tarefa precisa ter pelo menos um usuario");
            return;

        }

        baseService.merge(tarefa, function(r) {

            if (r.sucesso) {

                var nt = r.o;

                tarefa.id = nt.id;
                tarefa.produtos = nt.produtos;

                msg.alerta("Operacao efetuada com sucesso");
                $scope.tarefas.attList();

            } else {

                msg.erro("Falha ao efetuar operacao");

            }

        })

    }


})


rtc.controller("crtPedidosReserva", function($scope, pedidoReservaService, logService, tabelaService, baseService, produtoService, sistemaService, statusPedidoSaidaService, formaPagamentoService, transportadoraService, clienteService, produtoPedidoReservaService) {

    $scope.pedidos = createAssinc(pedidoReservaService, 1, 10, 10);
    $scope.pedidos.attList();
    assincFuncs(
        $scope.pedidos,
        "pedido_reserva", ["id", "cliente.razao_social", "data", "frete", "usuario.nome"]);

    produtoService.vencidos = false;
    $scope.produtos = createAssinc(produtoService, 1, 3, 4);

    assincFuncs(
        $scope.produtos,
        "produto", ["codigo", "nome", "disponivel"], "filtroProdutos");

    $scope.transportadoras = createAssinc(transportadoraService, 1, 3, 4);

    assincFuncs(
        $scope.transportadoras,
        "transportadora", ["codigo", "razao_social"], "filtroTransportadoras");

    $scope.clientes = createAssinc(clienteService, 1, 3, 4);

    $scope.carregando = false;

    $scope.inverterPrecos = function() {

        $scope.atualizaCustos();

        var p = $scope.pedido;

        var total = 0;

        for (var i = 0; i < p.produtos.length; i++) {
            total += p.produtos[i].quantidade * p.produtos[i].valor_base;
        }

        for (var i = 0; i < p.produtos.length; i++) {

            var pro = p.produtos[i];
            var vun = pro.valor_base + pro.frete + pro.juros + pro.icms + pro.ipi;

            var perc = pro.quantidade * pro.valor_base / total;

            var frete = (p.frete * perc) / pro.quantidade;
            vun -= frete;

            var ipi = 1 + (pro.ipi / (vun - pro.ipi));

            vun -= pro.ipi;

            var icms = ((vun - pro.icms) / (vun));

            vun -= pro.icms;

            var juros = 1 + (pro.juros / (vun - pro.juros));

            var fat = juros / icms * ipi;

            pro.valor_base = parseFloat(((pro.valor_base - frete) / fat).toFixed(3));


        }

        $scope.atualizaCustos();

    }

    assincFuncs(
        $scope.clientes,
        "cliente", ["codigo", "razao_social"], "filtroClientes");


    $scope.meses_validade_curta = 3;

    $scope.pedido_novo = {};

    $scope.produto_pedido_novo = {};

    $scope.pedido = {};

    $scope.fretes = [];

    $scope.qtd = 0;

    $scope.produto = {};

    $scope.logisticas = [];

    $scope.logs = [];

    $scope.retorno_cobranca = ""

    sistemaService.getLogisticas(function(rr) {

        $scope.logisticas = rr.logisticas;

    })


    produtoPedidoReservaService.getProdutoPedido(function(pp) {

        $scope.produto_pedido_novo = pp.produto_pedido;

    })


    $scope.gerarCobranca = function() {

        pedidoReservaService.gerarCobranca($scope.pedido, function(r) {

            if (r.sucesso) {
                $("#retCob").html("Cobranca gerada com sucesso. <hr> " + r.retorno);
            } else {
                $("#retCob").html("Problema ao gerar cobranca");
            }

        })

    }


    $scope.getPesoBrutoPedido = function() {

        var tot = 0;

        for (var i = 0; i < $scope.pedido.produtos.length; i++) {

            var p = $scope.pedido.produtos[i];

            tot += (p.produto.peso_bruto) * p.quantidade;

        }

        return tot;

    }

    $scope.getTotalPedido = function() {

        var tot = 0;

        for (var i = 0; i < $scope.pedido.produtos.length; i++) {

            var p = $scope.pedido.produtos[i];

            tot += (p.valor_base + p.icms + p.ipi + p.juros + p.frete) * p.quantidade;

        }

        return tot;

    }

    $scope.formas_pagamento = {};


    $scope.setTransportadora = function(trans) {

        $scope.pedido.transportadora = trans;
        $scope.atualizaCustos();

    }

    $scope.setCliente = function(cli) {

        $scope.pedido.cliente = cli;
        $scope.atualizaCustos();

    }


    $scope.addProduto = function(produto) {
        var pp = angular.copy($scope.produto_pedido_novo);
        pp.produto = produto;
        pp.pedido = $scope.pedido;
        pp.valor_base = produto.valor_base;
        pp.quantidade = $scope.qtd;

        var a = false;
        for (var j = 0; j < $scope.pedido.produtos.length; j++) {

            var pr = $scope.pedido.produtos[j];

            if (pr.produto.id === pp.produto.id) {

                pr.quantidade += pp.quantidade;
                a = true;

            }

        }

        if (!a) {
            $scope.pedido.produtos[$scope.pedido.produtos.length] = pp;
        }



        $scope.atualizaCustos();

    }

    $scope.removerProduto = function(produto) {

        var dt = new Date().getTime();
        dt += $scope.meses_validade_curta * 30 * 24 * 60 * 60 * 1000;

        remove($scope.pedido.produtos, produto);

        if (produto.validade_minima > dt) {
            for (var i = 0; i < $scope.pedido.produtos.length; i++) {

                var p = $scope.pedido.produtos[i];

                if (p.validade_minima > produto.validade_minima && p.produto.id === produto.produto.id) {

                    remove($scope.pedido.produtos, p);
                    i--;

                }
            }
        }

        $scope.atualizaCustos();

    }

    $scope.mergePedido = function() {

        $scope.carregando = true;

        var p = $scope.pedido;

        if (p.cliente == null) {
            msg.erro("Pedido sem cliente.");
            return;
        }

        if (p.transportadora == null) {
            msg.erro("Pedido sem transportadora.");
            return;
        }

        if (p.forma_pagamento == null) {
            msg.erro("Pedido sem forma de pagamento.");
            return;
        }


        baseService.merge(p, function(r) {
            if (r.sucesso) {
                $scope.pedido = r.o;
                if ($scope.pedido.logistica !== null) {
                    equalize($scope.pedido, "logistica", $scope.logisticas);
                }
                equalize($scope.pedido, "forma_pagamento", $scope.formas_pagamento);

                msg.alerta("Operacao efetuada com sucesso");

                if (typeof $scope.pedido["retorno"] !== 'undefined') {

                    msg.alerta($scope.pedido["retorno"]);

                }

            } else {
                $scope.pedido = r.o;
                if ($scope.pedido.logistica !== null) {
                    equalize($scope.pedido, "logistica", $scope.logisticas);
                }
                equalize($scope.pedido, "forma_pagamento", $scope.formas_pagamento);
                msg.erro("Ocorreu o seguinte problema: " + r.mensagem);
            }
            $scope.carregando = false;
        });

    }

    $scope.setFrete = function(fr) {

        $scope.pedido.frete = fr.valor + fr.transportadora.despacho;
        $scope.pedido.transportadora = fr.transportadora;
        $scope.atualizaCustos();

    }

    $scope.setProduto = function(produto) {

        produtoService.getValidades($scope.meses_validade_curta, produto, function(v) {

            produto.validades = v;

        })

    }

    $scope.calculoPronto = function() {

        if ($scope.pedido.cliente != null && $scope.pedido.produtos != null) {
            if ($scope.pedido.produtos.length > 0) {
                return true;
            }
        }
        return false;

    }


    $scope.getFretes = function() {

        var pesoTotal = 0;
        var valorTotal = 0;

        for (var i = 0; i < $scope.pedido.produtos.length; i++) {
            var p = $scope.pedido.produtos[i];
            valorTotal += (p.valor_base + p.juros + p.icms) * p.quantidade;
            pesoTotal += p.produto.peso_bruto * p.quantidade;
        }
        if ($scope.pedido.logistica === null) {
            tabelaService.getFretes(null, { cidade: $scope.pedido.cliente.endereco.cidade, valor: valorTotal, peso: pesoTotal }, function(f) {

                $scope.fretes = f.fretes;

            })
        } else {

            tabelaService.getFretes($scope.pedido.logistica, { cidade: $scope.pedido.cliente.endereco.cidade, valor: valorTotal, peso: pesoTotal }, function(f) {

                $scope.fretes = f.fretes;

            })
        }

    }

    $scope.atualizaCustos = function() {

        pedidoReservaService.atualizarCustos($scope.pedido, function(np) {

            $scope.pedido = np.o;

            equalize($scope.pedido, "forma_pagamento", $scope.formas_pagamento);

            if ($scope.pedido.logistica !== null) {
                equalize($scope.pedido, "logistica", $scope.logisticas);
            }

        })

    }

    pedidoReservaService.getPedido(function(ped) {
        ped.pedido.produtos = [];
        $scope.pedido_novo = ped.pedido;

    })

    $scope.novoPedido = function() {

        if ($scope.pedido.id === 0) {
            $scope.setPedido(angular.copy($scope.pedido));
        } else {
            $scope.setPedido(angular.copy($scope.pedido_novo));
        }
    }

    $scope.resetarPedido = function() {

        $scope.pedido.transportadora = null;
        $scope.pedido.produtos = [];

        if ($scope.pedido.logistica === null) {
            produtoService.filtro_base = "produto.id_logistica=0";
            transportadoraService.empresa = $scope.pedido.empresa;
        } else {
            produtoService.filtro_base = "produto.id_logistica=" + $scope.pedido.logistica.id;
            transportadoraService.empresa = $scope.pedido.logistica;
        }

        $scope.produtos.attList();
        $scope.transportadoras.attList();

    }

    $scope.setPedido = function(pedido) {

        $scope.pedido = pedido;

        if (pedido.logistica !== null) {

            equalize($scope.pedido, "logistica", $scope.logisticas);

        }

        if ($scope.pedido.logistica === null) {
            produtoService.filtro_base = "produto.id_logistica=0";
            transportadoraService.empresa = $scope.pedido.empresa;
        } else {
            produtoService.filtro_base = "produto.id_logistica=" + $scope.pedido.logistica.id;
            transportadoraService.empresa = $scope.pedido.logistica;
        }

        if ($scope.pedido.id === 0) {

            formaPagamentoService.getFormasPagamento($scope.pedido, function(f) {

                $scope.formas_pagamento = f.formas;
                $scope.pedido.forma_pagamento = $scope.formas_pagamento[0];

            });

            return;

        }

        pedidoReservaService.getProdutos(pedido, function(p) {

            pedido.produtos = p.produtos;

            formaPagamentoService.getFormasPagamento($scope.pedido, function(f) {
                $scope.formas_pagamento = f.formas;
                equalize(pedido, "forma_pagamento", $scope.formas_pagamento);
            })

            var ic = $("#myIframe").contents();

            ic.find("#logoEmpresa img").remove();
            ic.find("#logoEmpresa").append($("#logo").clone().addClass("product-image"));
            ic.find("#infoEmpresa").html(pedido.empresa.nome + ", " + pedido.empresa.endereco.cidade.nome + "-" + pedido.empresa.endereco.cidade.estado.sigla);
            ic.find("#infoEmpresa2").html(pedido.empresa.endereco.bairro + ", " + pedido.empresa.endereco.cep.valor + " - " + pedido.empresa.telefone.numero);

            ic.find("#idPedido").html($scope.pedido.id);
            ic.find("#nomeUsuario").html($scope.pedido.usuario.nome);
            ic.find("#nomeCliente").html($scope.pedido.cliente.razao_social);
            ic.find("#cnpjCliente").html($scope.pedido.cliente.cnpj.valor);
            ic.find("#ruaCliente").html($scope.pedido.cliente.endereco.rua);
            ic.find("#cidadeCliente").html($scope.pedido.cliente.endereco.cidade.nome);
            ic.find("#emailCliente").html($scope.pedido.cliente.email.endereco);

            ic.find("#transportadora").html($scope.pedido.transportadora.razao_social);
            ic.find("#cnpjTransportadora").html($scope.pedido.transportadora.cnpj.valor);
            ic.find("#emailTransportadora").html($scope.pedido.transportadora.email.endereco);

            var telefones = "";

            for (var i = 0; i < $scope.pedido.transportadora.telefones.length; i++) {
                telefones += $scope.pedido.transportadora.telefones[i].numero + "<br>";
            }

            ic.find("#telefoneTransportadora").html(telefones);
            ic.find("#cidadeEstadoTransportadora").html($scope.pedido.transportadora.endereco.cidade.nome + " - " + $scope.pedido.transportadora.endereco.cidade.estado.sigla);

            var suframa = "Sem suframa";

            if ($scope.pedido.cliente.suframa) {
                suframa = $scope.pedido.cliente.inscricao_suframa;
            }

            ic.find("#suframa").html(suframa);

            var p = ic.find("#produto").each(function() {
                p = $(this);
            });

            p.hide();

            ic.find("#produtos").find("tr").each(function() {
                if (typeof $(this).data("gerado") !== 'undefined') {
                    $(this).remove();
                }
            });

            var p = p.clone();

            var icms = 0;
            var base = 0;
            var total = 0;
            for (var i = 0; i < $scope.pedido.produtos.length; i++) {

                p = p.clone();

                var pro = $scope.pedido.produtos[i];
                icms += pro.icms * pro.quantidade;
                base += pro.base_calculo * pro.quantidade;
                p.find("[data-tipo='nome']").html(pro.produto.nome);
                p.find("[data-tipo='valor']").html((pro.valor_base + pro.frete + pro.juros + pro.icms).toFixed(2));
                p.find("[data-tipo='quantidade']").html(pro.quantidade);
                p.find("[data-tipo='validade']").html(toDate(pro.validade_minima));
                p.find("[data-tipo='total']").html(((pro.valor_base + pro.frete + pro.ipi + pro.juros + pro.icms) * pro.quantidade).toFixed(2));
                p.data("gerado", true);

                ic.find("#produtos").append(p);
                p.show();

                total += (pro.valor_base + pro.frete + pro.juros + pro.ipi + pro.icms) * pro.quantidade;

            }
            var alicota = (icms * 100 / base);

            ic.find("#prazo").html(pedido.prazo);
            ic.find("#alicota").html(alicota.toFixed(0));
            ic.find("#icms").html(icms.toFixed(2));

            ic.find("#tipoFrete").html(pedido.frete_incluso ? 'CIF' : 'FOB');
            ic.find("#nomeTransportadora").html(pedido.transportadora.razao_social);
            ic.find("#contato").html(pedido.transportadora.email.endereco);
            ic.find("#valorFrete").html(pedido.frete);

            ic.find("#observacoes").html(pedido.observacoes);
            ic.find("#nomeUsuario2").html(pedido.usuario.nome);

        })


    }

    $scope.deletePedido = function() {
        $scope.carregando = true;
        baseService.delete($scope.pedido, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.pedido = angular.copy($scope.novo_pedido);
                $scope.pedidos.attList();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
            $scope.carregando = false;
        });

    }


})
rtc.controller("crtAprovacaoConsignado", function($scope, aprovacaoConsignadoService, baseService) {

    $scope.aprovacoes = createAssinc(aprovacaoConsignadoService, 1, 10, 4);
    assincFuncs(
        $scope.aprovacoes,
        "empresa", ["id", "nome", "cnpj", "produto.nome", "produto.valor_base", "produto.disponivel"]);
    $scope.aprovacoes.attList();

    $scope.aprovar = function(aprovacao) {


        baseService.merge(aprovacao, function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso");
                $scope.aprovacoes.attList();

            } else {

                msg.erro("Ocorreu um problema");

            }

        })

    }


})
rtc.controller("crtRepresentaProduto", function($scope, produtoService, cidadeService, empresaService, sistemaService, uploadService, baseService) {

    $scope.garantia = new Date().getTime();

    //produtoService.filtro_base = "produto.empresa_vendas=0";
    $scope.produtos_av = createAssinc(produtoService, 1, 7, 4);
    assincFuncs(
        $scope.produtos_av,
        "produto", ["codigo", "nome"], "filtroProdutos2");
    $scope.produtos_av.attList();


    $scope.usuario = null;

    $scope.estados = [];
    $scope.cidades = [];


    $scope.selecionar = function(c) {
        c.selecionada = !c.selecionada;
    }

    $scope.regioes = [
        { id: 0, nome: "Norte", estados: ["AM", "RO", "AP", "PR", "AC", "RO", "RR"], cidades: [], filtro: "" },
        { id: 1, nome: "Centro-Oeste", estados: ["MT", "GO", "MS"], cidades: [], filtro: "" },
        { id: 2, nome: "Regiao Nordeste", estados: ["MR", "CE", "PI", "RS", "RN", "PE", "BA", "SE"], cidades: [], filtro: "" },
        { id: 3, nome: "Sudeste", estados: ["SP", "MG", "ES", "RJ"], cidades: [], filtro: "" },
        { id: 4, nome: "Sul", estados: ["PR", "SC", "RG"], cidades: [], filtro: "" }
    ];

    $scope.liberadoc = true;
    $scope.aceitou_contrato = true;


    $scope.selecionarTudo = function(regiao) {

        for (var i = 0; i < regiao.cidades.length; i++) {
            if (regiao.cidades[i].aparecer) {
                regiao.cidades[i].selecionada = !regiao.cidades[i].selecionada;
            }
        }

    }

    $scope.selecionarPais = function() {

        for (var i = 0; i < $scope.regioes.length; i++) {

            var r = $scope.regioes[i];

            for (var j = 0; j < r.cidades.length; j++) {

                var c = r.cidades[j];

                c.selecionada = !c.selecionada;

            }

        }

    }

    var getCidadesSelecionadas = function() {

        var ret = [];

        for (var i = 0; i < $scope.regioes.length; i++) {

            var r = $scope.regioes[i];

            for (var j = 0; j < r.cidades.length; j++) {

                var c = r.cidades[j];

                if (c.selecionada) {

                    ret[ret.length] = c.cidade.id;

                }

            }

        }

        return ret;

    }

    $scope.filtro = function(regiao) {

        var f = regiao.filtro.toUpperCase();

        for (var i = 0; i < regiao.cidades.length; i++) {

            var c = regiao.cidades[i];
            c.aparecer = c.cidade.nome.toUpperCase().indexOf(f) >= 0 || c.cidade.estado.sigla.toUpperCase().indexOf(f) >= 0;

        }

    }

    $scope.empresa = null;
    sistemaService.getUsuario(function(u) {

        $scope.usuario = u.usuario;
        $scope.empresa = $scope.usuario.empresa;

        $scope.aceitou_contrato = u.usuario.contrato_fornecedor;

        cidadeService.getElementos(function(p) {

            var estados = [];
            var cidades = p.elementos;
            $scope.cidades = cidades;

            lbl:
                for (var i = 0; i < cidades.length; i++) {
                    var c = cidades[i];

                    for (var q = 0; q < $scope.regioes.length; q++) {
                        var r = $scope.regioes[q];
                        for (var l = 0; l < r.estados.length; l++) {
                            var e = r.estados[l];
                            if (e === c.estado.sigla) {
                                r.cidades[r.cidades.length] = { cidade: c, selecionada: false, aparecer: true };
                            }
                        }
                    }


                    for (var j = 0; j < estados.length; j++) {
                        if (estados[j].id === c.estado.id) {
                            estados[j].cidades[estados[j].cidades.length] = c;
                            c.estado = estados[j];
                            continue lbl;
                        }
                    }



                    c.estado["cidades"] = [c];
                    estados[estados.length] = c.estado;
                }

            $scope.estados = estados;

            equalize($scope.empresa.endereco, "cidade", $scope.cidades);
            if (typeof $scope.empresa.endereco.cidade !== 'undefined') {
                $scope.estado = $scope.empresa.endereco.cidade.estado;
            } else {
                $scope.empresa.endereco.cidade = $scope.cidades[0];
                $scope.estado = $scope.empresa.endereco.cidade.estado;
            }

        })

    })




    $scope.mergeUsuario = function(usuario) {

        if (usuario.senha !== usuario.confirmar_senha) {
            msg.erro("Confirmacao de senha incorreta");
            return;
        }
        usuario.contrato_fornecedor = true;
        baseService.merge(usuario, function(r) {

            if (r.sucesso) {
                baseService.merge(usuario.empresa, function(rr) {
                    if (rr.sucesso) {
                        msg.alerta("Operacao Efetuada com sucesso");
                        $scope.liberadoc = true;
                    } else {
                        msg.erro("Estamos em manutencao no momento tente mais tarde");
                    }
                })
            } else {
                msg.erro("Ocorreu um problema: " + r.mensagem);
            }
        })

    }


    $scope.aceitar = function() {

        $scope.aceitou_contrato = !$scope.aceitou_contrato;

    }


    var consignados = angular.copy(produtoService);
    consignados.filtro_base = "produto.empresa_vendas>0 AND produto.cr=1";

    $scope.produtos_consignados = createAssinc(consignados, 1, 7, 4);
    assincFuncs(
        $scope.produtos_consignados,
        "produto", ["codigo", "nome"], "filtroProdutos");
    $scope.produtos_consignados.attList();

    $scope.empresa_av = null;
    $scope.produto_novo_av = null;
    $scope.produto_av = null;

    $scope.carregando_av = false;
    $scope.produtos_possiveis_av = [];

    $scope.travado_av = false;

    $scope.quantidade_av = 0;

    $scope.empresa_selecionada = null;

    $scope.virtuais = [];

    empresaService.getVirtuais(function(v) {

        $scope.virtuais = v.virtuais;
        $scope.empresa_selecionada = v.virtuais[0];

    })

    empresaService.getEmpresa(function(e) {

        $scope.empresa_av = e.empresa;

        produtoService.getProduto(function(p) {

            $scope.produto_novo_av = p.produto;
            $scope.novoProduto();

        })

    });

    var k = setInterval(function() {

        $("#flImg").each(function() {


            $(this).change(function() {

                uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

                    if (!sucesso) {

                        msg.erro("Falha ao subir arquivo");

                    } else {

                        var doc = angular.copy($scope.documento);

                        for (var i = 0; i < arquivos.length; i++) {

                            if (i === 0) {
                                $scope.produto_av.imagem = arquivos[i];
                            } else {
                                $scope.produto_av.mais_fotos[i - 1] = arquivos[i];
                            }
                        }

                        msg.alerta("Upload feito com sucesso");
                    }

                })

            })

            clearInterval(k);

        })


    }, 1000);


    $scope.novoProduto = function() {

        $scope.produto_av = angular.copy($scope.produto_novo_av);
        $scope.produto_av.empresa = $scope.empresa_av;
    }

    $scope.atualizarPossibilidades = function() {

        $scope.carregando_av = true;
        produtoService.getProdutosFiltro("produto.nome like '%" + $scope.produto_av.nome + "%'", function(p) {

            $scope.produtos_possiveis_av = p.produtos;
            $scope.carregando_av = false;

        })

    }

    $scope.liberado = function() {

        if ($scope.travado_av) {

            return true;

        } else {

            if ($scope.produto_av.nome == null || $scope.produto_av.fabricante == null || $scope.produto_av.ativo == null || $scope.produto_av.valor_base == 0) {

                return false;

            }

            if ($scope.produto_av.nome.length > 2 && $scope.produto_av.fabricante.length > 2 && $scope.produto_av.ativo.length > 2) {

                return true;

            }

        }

        return false;

    }

    $scope.selecionarPossibilidade = function(p) {

        $scope.produto_av = p;
        $scope.produto_av.empresa = $scope.empresa_av;
        $scope.travado_av = true;

    }

    $scope.selecionarPossibilidadeSemEstoque = function(p) {

        if (p.id === 0) {
            p.estoque = 0;
            p.disponivel = 0;
        }

        $scope.produto_av = p;
        $scope.produto_av.empresa = $scope.empresa_av;
        $scope.travado_av = true;

    }

    $scope.destravar = function() {

        $scope.novoProduto();
        $scope.travado_av = false;

    }

    $scope.deconsignar = function(produto) {

        sistemaService.deconsignarProduto(produto, function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso.");
                $scope.produtos_consignados.attList();
                $scope.produtos_av.attList();

            } else {

                msg.erro("Ocorreu um problema ao efetuar essa operacao");

            }

        })

    }

    $scope.finalizar = function() {

        if (($scope.produto_av.valor_base / $scope.produto_av.custo) < 1.02) {
            msg.erro("A porcentagem minima é de 2%");
            return;
        }

        $scope.produto_av.cr = 1;
        sistemaService.consignarProduto($scope.produto_av, $scope.empresa_selecionada, getCidadesSelecionadas(), function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso.");
                $scope.destravar();
                $scope.produtos_consignados.attList();
                $scope.produtos_av.attList();

            } else {

                msg.erro("Ocorreu um problema ao efetuar essa operacao");

            }

        })

    }

})
rtc.controller("crtConsignaProduto", function($scope, produtoService, cidadeService, empresaService, sistemaService, uploadService, baseService) {

    $scope.garantia = new Date().getTime();

    //produtoService.filtro_base = "produto.empresa_vendas=0";
    $scope.produtos_av = createAssinc(produtoService, 1, 7, 4);
    assincFuncs(
        $scope.produtos_av,
        "produto", ["codigo", "nome"], "filtroProdutos2");
    $scope.produtos_av.attList();

    $scope.aceitarConsignacao = function() {

        $scope.aceitou_contrato_consignacao = !$scope.aceitou_contrato_consignacao;

        $scope.usuario.contrato_consigna = $scope.aceitou_contrato_consignacao;

        sistemaService.aceitarConsignacao($scope.usuario, function(u) {




        })

    }


    $scope.usuario = null;

    $scope.estados = [];
    $scope.cidades = [];


    $scope.selecionar = function(c) {
        c.selecionada = !c.selecionada;
    }

    $scope.regioes = [
        { id: 0, nome: "Norte", estados: ["AM", "RO", "AP", "PR", "AC", "RO", "RR"], cidades: [], filtro: "" },
        { id: 1, nome: "Centro-Oeste", estados: ["MT", "GO", "MS"], cidades: [], filtro: "" },
        { id: 2, nome: "Regiao Nordeste", estados: ["MR", "CE", "PI", "RS", "RN", "PE", "BA", "SE"], cidades: [], filtro: "" },
        { id: 3, nome: "Sudeste", estados: ["SP", "MG", "ES", "RJ"], cidades: [], filtro: "" },
        { id: 4, nome: "Sul", estados: ["PR", "SC", "RG"], cidades: [], filtro: "" }
    ];

    $scope.liberadoc = false;
    $scope.aceitou_contrato = false;


    $scope.selecionarTudo = function(regiao) {

        for (var i = 0; i < regiao.cidades.length; i++) {
            if (regiao.cidades[i].aparecer) {
                regiao.cidades[i].selecionada = !regiao.cidades[i].selecionada;
            }
        }

    }

    $scope.selecionarPais = function() {

        for (var i = 0; i < $scope.regioes.length; i++) {

            var r = $scope.regioes[i];

            for (var j = 0; j < r.cidades.length; j++) {

                var c = r.cidades[j];

                c.selecionada = !c.selecionada;

            }

        }

    }

    var getCidadesSelecionadas = function() {

        var ret = [];

        for (var i = 0; i < $scope.regioes.length; i++) {

            var r = $scope.regioes[i];

            for (var j = 0; j < r.cidades.length; j++) {

                var c = r.cidades[j];

                if (c.selecionada) {

                    ret[ret.length] = c.cidade.id;

                }

            }

        }

        return ret;

    }

    $scope.filtro = function(regiao) {

        var f = regiao.filtro.toUpperCase();

        for (var i = 0; i < regiao.cidades.length; i++) {

            var c = regiao.cidades[i];
            c.aparecer = c.cidade.nome.toUpperCase().indexOf(f) >= 0 || c.cidade.estado.sigla.toUpperCase().indexOf(f) >= 0;

        }

    }

    $scope.empresa = null;
    sistemaService.getUsuario(function(u) {

        $scope.usuario = u.usuario;
        $scope.empresa = $scope.usuario.empresa;

        $scope.liberadoc = u.usuario.empresa_verificada;
        $scope.aceitou_contrato = u.usuario.contrato_fornecedor;
        $scope.aceitou_contrato_consignacao = u.usuario.contrato_consigna;

        cidadeService.getElementos(function(p) {

            var estados = [];
            var cidades = p.elementos;
            $scope.cidades = cidades;

            lbl:
                for (var i = 0; i < cidades.length; i++) {
                    var c = cidades[i];

                    for (var q = 0; q < $scope.regioes.length; q++) {
                        var r = $scope.regioes[q];
                        for (var l = 0; l < r.estados.length; l++) {
                            var e = r.estados[l];
                            if (e === c.estado.sigla) {
                                r.cidades[r.cidades.length] = { cidade: c, selecionada: false, aparecer: true };
                            }
                        }
                    }


                    for (var j = 0; j < estados.length; j++) {
                        if (estados[j].id === c.estado.id) {
                            estados[j].cidades[estados[j].cidades.length] = c;
                            c.estado = estados[j];
                            continue lbl;
                        }
                    }



                    c.estado["cidades"] = [c];
                    estados[estados.length] = c.estado;
                }

            $scope.estados = estados;

            equalize($scope.empresa.endereco, "cidade", $scope.cidades);
            if (typeof $scope.empresa.endereco.cidade !== 'undefined') {
                $scope.estado = $scope.empresa.endereco.cidade.estado;
            } else {
                $scope.empresa.endereco.cidade = $scope.cidades[0];
                $scope.estado = $scope.empresa.endereco.cidade.estado;
            }

        })

    })




    $scope.mergeUsuario = function(usuario) {

        if (usuario.senha !== usuario.confirmar_senha) {
            msg.erro("Confirmacao de senha incorreta");
            return;
        }
        usuario.contrato_fornecedor = true;
        baseService.merge(usuario, function(r) {

            if (r.sucesso) {
                baseService.merge(usuario.empresa, function(rr) {
                    if (rr.sucesso) {
                        msg.alerta("Operacao Efetuada com sucesso");
                        $scope.liberadoc = true;
                    } else {
                        msg.erro("Estamos em manutencao no momento tente mais tarde");
                    }
                })
            } else {
                msg.erro("Ocorreu um problema: " + r.mensagem);
            }
        })

    }




    $scope.aceitar = function() {

        $scope.aceitou_contrato = !$scope.aceitou_contrato;

        $scope.usuario.contrato_fornecedor = $scope.aceitou_contrato;

        sistemaService.aceitarRepresentacao($scope.usuario, function(u) {


        })

    }


    var consignados = angular.copy(produtoService);
    consignados.filtro_base = "produto.empresa_vendas>0 AND produto.cr=0";

    $scope.produtos_consignados = createAssinc(consignados, 1, 7, 4);
    assincFuncs(
        $scope.produtos_consignados,
        "produto", ["codigo", "nome"], "filtroProdutos");
    $scope.produtos_consignados.attList();

    $scope.empresa_av = null;
    $scope.produto_novo_av = null;
    $scope.produto_av = null;

    $scope.carregando_av = false;
    $scope.produtos_possiveis_av = [];

    $scope.travado_av = false;

    $scope.aceitou_contrato_consignacao = false;

    $scope.quantidade_av = 0;

    $scope.empresa_selecionada = null;

    $scope.virtuais = [];

    empresaService.getVirtuais(function(v) {

        $scope.virtuais = v.virtuais;
        $scope.empresa_selecionada = v.virtuais[0];

    })

    empresaService.getEmpresa(function(e) {

        $scope.empresa_av = e.empresa;

        produtoService.getProduto(function(p) {

            $scope.produto_novo_av = p.produto;
            $scope.novoProduto();

        })

    });

    var k = setInterval(function() {

        $("#flImg").each(function() {


            $(this).change(function() {

                uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

                    if (!sucesso) {

                        msg.erro("Falha ao subir arquivo");

                    } else {

                        var doc = angular.copy($scope.documento);

                        for (var i = 0; i < arquivos.length; i++) {

                            if (i === 0) {
                                $scope.produto_av.imagem = arquivos[i];
                            } else {
                                $scope.produto_av.mais_fotos[i - 1] = arquivos[i];
                            }
                        }

                        msg.alerta("Upload feito com sucesso");
                    }

                })

            })

            clearInterval(k);

        })


    }, 1000);


    $scope.novoProduto = function() {

        $scope.produto_av = angular.copy($scope.produto_novo_av);
        $scope.produto_av.validade = new Date().getTime();
        $scope.produto_av.empresa = $scope.empresa_av;
    }

    $scope.atualizarPossibilidades = function() {

        $scope.carregando_av = true;
        produtoService.getProdutosFiltro("produto.nome like '%" + $scope.produto_av.nome + "%'", function(p) {

            $scope.produtos_possiveis_av = p.produtos;
            $scope.carregando_av = false;

        })

    }

    $scope.liberado = function() {

        if ($scope.travado_av) {

            return true;

        } else {

            if ($scope.produto_av.nome == null || $scope.produto_av.fabricante == null || $scope.produto_av.ativo == null || $scope.produto_av.valor_base == 0) {

                return false;

            }

            if ($scope.produto_av.nome.length > 2 && $scope.produto_av.fabricante.length > 2 && $scope.produto_av.ativo.length > 2) {

                return true;

            }

        }

        return false;

    }

    $scope.selecionarPossibilidade = function(p) {

        $scope.produto_av = p;
        $scope.produto_av.validade = new Date().getTime();
        $scope.produto_av.empresa = $scope.empresa_av;
        $scope.travado_av = true;

    }

    $scope.selecionarPossibilidadeSemEstoque = function(p) {

        if (p.id === 0) {
            p.estoque = 0;
            p.disponivel = 0;
        }


        $scope.produto_av = p;
        $scope.produto_av.validade = new Date().getTime();
        $scope.produto_av.empresa = $scope.empresa_av;
        $scope.travado_av = true;

    }

    $scope.destravar = function() {

        $scope.novoProduto();
        $scope.travado_av = false;

    }

    $scope.deconsignar = function(produto) {

        sistemaService.deconsignarProduto(produto, function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso.");
                $scope.produtos_consignados.attList();
                $scope.produtos_av.attList();

            } else {

                msg.erro("Ocorreu um problema ao efetuar essa operacao");

            }

        })

    }

    $scope.finalizar = function() {

        if (($scope.produto_av.valor_base / $scope.produto_av.custo) < 1.02) {
            msg.erro("A porcentagem minima é de 2%");
            return;
        }
        $scope.produto_av.cr = 0;
        sistemaService.consignarRealmenteProduto($scope.produto_av, $scope.empresa_selecionada, getCidadesSelecionadas(), function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso.");
                $scope.destravar();
                $scope.produtos_consignados.attList();
                $scope.produtos_av.attList();

            } else {

                msg.erro("Ocorreu um problema ao efetuar essa operacao");

            }

        })

    }

})
rtc.controller("crtProdutoEncomenda", function($scope, produtoService, empresaService, sistemaService) {

    $scope.produtos_av = createAssinc(produtoService, 1, 7, 4);
    assincFuncs(
        $scope.produtos_av,
        "produto", ["codigo", "nome"], "filtroProdutos2");
    $scope.produtos_av.attList();

    $scope.empresa_av = null;
    $scope.produto_novo_av = null;
    $scope.produto_av = null;

    $scope.carregando_av = false;
    $scope.produtos_possiveis_av = [];

    $scope.travado_av = false;

    $scope.quantidade_av = 0;

    empresaService.getEmpresa(function(e) {

        $scope.empresa_av = e.empresa;

        produtoService.getProduto(function(p) {

            $scope.produto_novo_av = p.produto;
            $scope.novoProduto();

        })

    });

    $scope.novoProduto = function() {

        $scope.produto_av = angular.copy($scope.produto_novo_av);
        $scope.produto_av.empresa = $scope.empresa_av;
    }

    $scope.atualizarPossibilidades = function() {

        $scope.carregando_av = true;
        produtoService.getProdutosFiltro("produto.nome like '%" + $scope.produto_av.nome + "%'", function(p) {

            $scope.produtos_possiveis_av = p.produtos;
            $scope.carregando_av = false;

        })

    }

    $scope.liberado = function() {

        if ($scope.travado_av) {

            return true;

        } else {

            if ($scope.produto_av.nome == null || $scope.produto_av.fabricante == null || $scope.produto_av.ativo == null) {

                return false;

            }

            if ($scope.produto_av.nome.length > 2 && $scope.produto_av.fabricante.length > 2 && $scope.produto_av.ativo.length > 2) {

                return true;

            }

        }

        return false;

    }

    $scope.selecionarPossibilidade = function(p) {

        $scope.produto_av = p;
        $scope.produto_av.empresa = $scope.empresa_av;
        $scope.travado_av = true;

    }

    $scope.selecionarPossibilidadeSemEstoque = function(p) {

        if (p.disponivel > 0) {

            msg.erro("Existe o produto em estoque nao e possivel encomenda-lo");
            window.open("comprar.php");

        }

        $scope.produto_av = p;
        $scope.produto_av.empresa = $scope.empresa_av;
        $scope.travado_av = true;

    }

    $scope.destravar = function() {

        $scope.novoProduto();
        $scope.travado_av = false;

    }

    $scope.finalizar = function() {

        if ($scope.quantidade_av == 0) {
            msg.alerta("A quantidade nao pode ser 0");
            return;
        }

        /*
        if($scope.produto_av.custo == 0){
            msg.alerta("O Valor nao pode ser 0");
            return;
        }
        */

        sistemaService.addCarrinhoEncomendaCadastrando($scope.produto_av, $scope.quantidade_av, function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso. O produto ja se encontra no seu carrinho");
                $scope.destravar();

            } else {

                msg.erro("Ocorreu um problema ao efetuar essa operacao");

            }

        })

    }

})
rtc.controller("crtPardal", function($scope, $sce, $timeout, pardalService) {

    $scope.texto = "";
    $scope.conversa = [];




    $scope.enviar = function() {

        if ($scope.texto !== "") {
            $scope.conversa[$scope.conversa.length] = {
                tipo: 1,
                texto: $sce.trustAsHtml($scope.texto)
            };
        }

        pardalService.enviar($scope.texto, function(r) {

            if (r.sucesso) {

                $scope.conversa[$scope.conversa.length] = {
                    tipo: 0,
                    texto: $sce.trustAsHtml(r.fala)
                };

            }

        })

        $scope.texto = "";

    }


    pardalService.reset(function() {
        $scope.enviar();
        $("#txtEp").focus();
    });

})
rtc.controller("crtProtocolos", function($scope, protocoloService, empresaService, usuarioService, sistemaService, tipoProtocoloService, baseService, pedidoService, clienteService, transportadoraService, cotacaoEntradaService, pedidoEntradaService) {

    $scope.protocolos = createAssinc(protocoloService, 1, 3, 10);
    assincFuncs(
        $scope.protocolos,
        "p", ["id", "titulo", "inicio", "fim", "iniciado_por", "e.nome", "e.id", "tp.nome", "tp.prioridade"]);
    $scope.protocolos.attList();

    $scope.pedidos = createAssinc(pedidoService, 1, 3, 5);
    assincFuncs(
        $scope.pedidos,
        "pedido", ["id", "cliente.razao_social", "data"], "filtroPedido");


    $scope.clientes = createAssinc(clienteService, 1, 3, 5);
    assincFuncs(
        $scope.clientes,
        "cliente", ["id", "razao_social"], "filtroCliente");

    $scope.transportadoras = createAssinc(transportadoraService, 1, 3, 5);
    assincFuncs(
        $scope.transportadoras,
        "transportadora", ["id", "razao_social"], "filtroTransportadora");

    $scope.cotacoes = createAssinc(cotacaoEntradaService, 1, 3, 5);
    assincFuncs(
        $scope.cotacoes,
        "cotacao_entrada", ["id", "fornecedor.nome"], "filtroCotacao");

    $scope.pedidosEntrada = createAssinc(pedidoEntradaService, 1, 3, 5);
    assincFuncs(
        $scope.pedidosEntrada,
        "pedido_entrada", ["id", "fornecedor.nome"], "filtroPedidoEntrada");

    $scope.usuarios = createAssinc(usuarioService, 1, 7, 5);
    assincFuncs(
        $scope.usuarios,
        "usuario", ["id", "nome"], "filtroUsuario");


    $scope.empresas = [];

    usuarioService.filtro_base = "usuario.id>0";

    $scope.usuarioService = usuarioService;

    empresaService.getGrupoEmpresarial(function(g) {

        $scope.empresas = g.grupo;
        $scope.usuarioService.empresa = $scope.empresas[0];

        $scope.usuarios.attList();

    })


    $scope.addUsuario = function(u) {

        for (var i = 0; i < $scope.protocolo.usuarios.length; i++) {

            var uu = $scope.protocolo.usuarios[i];

            if (uu.id === u.id) {

                return;

            }

        }

        $scope.protocolo.usuarios[$scope.protocolo.usuarios.length] = u;

    }

    $scope.removeUsuario = function(u) {

        var nu = [];

        for (var i = 0; i < $scope.protocolo.usuarios.length; i++) {
            var u2 = $scope.protocolo.usuarios[i];
            if (u2.id !== u.id) {
                nu[nu.length] = u2;
            }
        }

        $scope.protocolo.usuarios = nu;

    }

    $scope.isSelecionado = function(u) {

        for (var i = 0; i < $scope.protocolo.usuarios.length; i++) {

            var usuario = $scope.protocolo.usuarios[i];

            if (usuario.id === u.id) {

                return true;

            }

        }

        return false;

    }



    $scope.tipos_protocolo = [];

    $scope.protocolo_novo = {};
    $scope.protocolo = {};

    $scope.mensagem_protocolo_novo = {};
    $scope.mensagem_protocolo = {};

    $scope.terminar = function(protocolo) {

        protocoloService.terminar(protocolo, function(t) {

            if (t.sucesso) {

                msg.alerta("Terminado com sucesso");
                $scope.protocolos.attList();

            } else {

                msg.erro("Ocorreu um problema");

            }

        })

    }

    $scope.novaMensagem = function() {

        $scope.mensagem = angular.copy($scope.mensagem_protocolo_novo);

    }

    $scope.setEntidade = function(entidade) {

        $scope.protocolo.tipo_entidade = entidade._classe;
        $scope.protocolo.id_entidade = entidade.id;

    }

    $scope.mergeMensagem = function(protocolo) {

        var msgg = $scope.mensagem;
        msgg.protocolo = protocolo;

        baseService.merge(msgg, function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso");
                protocolo.chat[protocolo.chat.length] = r.o;

            } else {

                msg.erro("Ocorreu um problema");

            }

        })

    }

    $scope.novoProtocolo = function() {

        if ($scope.protocolo.id === 0) {

            $scope.setProtocolo($scope.protocolo);

        } else {

            $scope.protocolo = angular.copy($scope.protocolo_novo);
            $scope.setProtocolo($scope.protocolo);

        }


        sistemaService.getUsuario(function(u) {


            $scope.protocolo.usuarios[$scope.protocolo.usuarios.length] = u.usuario;

        })

    }



    $scope.setProtocolo = function(protocolo) {

        $scope.protocolo = protocolo;

        if (protocolo.tipo === null) {

            protocolo.tipo = $scope.tipos_protocolo[0];

        } else {

            equalize($scope.protocolo, "tipo", $scope.tipos_protocolo);

        }

    }

    $scope.mergeProtocolo = function() {

        if ($scope.protocolo.usuarios.length <= 2) {

            msg.erro("Um protocolo deve ter pelo menos 3 envolvidos");
            return;

        }

        baseService.merge($scope.protocolo, function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso");
                $scope.protocolo = r.o;
                $scope.protocolos.attList();

                $scope.novoProtocolo();


            } else {

                msg.erro("Falha ao executar operacao");

            }

        });




    }

    $scope.deleteProtocolo = function() {

        baseService.delete($scope.protocolo, function(t) {

            if (t.sucesso) {

                msg.alerta("Operacao efetuada com sucesso");
                $scope.protocolos.attList();

            } else {

                msg.erro("Falha ao executar operacao");

            }

        })

    }

    protocoloService.getMensagemProtocolo(function(m) {

        $scope.mensagem_protocolo_novo = m.mensagem;

    })



    tipoProtocoloService.getTiposProtocolo(function(t) {

        $scope.tipos_protocolo = t.tipos;

        protocoloService.getProtocolo(function(p) {

            $scope.protocolo_novo = p.protocolo;

            $scope.novoProtocolo();

        })


    })


})
rtc.controller("crtRespostaCotacaoGrupal", function($scope, cotacaoGrupalService) {

    $scope.respostas = [];
    $scope.carregando = true;
    $scope.cotacao = null;
    $scope.respondida = false;

    cotacaoGrupalService.getRespostasCotacaoGrupal(rtc["id_cotacao"], rtc["id_fornecedor"], rtc["id_empresa"], function(r) {

        $scope.respostas = r.respostas;
        $scope.cotacao = r.cotacao;
        $scope.carregando = false;

    })

    $scope.excluirProduto = function(r) {

        r.quantidade = -1;

    }

    $scope.getTotalCotacao = function() {

        var valor = 0;

        for (var i = 0; i < $scope.respostas.length; i++) {

            var r = $scope.respostas[i];

            if (r.quantidade < 0) {
                continue;
            }

            valor += r.produto.produto.quantidade_unidade * r.quantidade * r.valor;

        }

        return valor;

    }

    $scope.responder = function() {

        var resp = angular.copy($scope.respostas);

        for (var i = 0; i < resp.length; i++) {

            resp[i].valor *= resp[i].produto.produto.quantidade_unidade;

            resp[i].valor = parseFloat(resp[i].valor.toFixed(2));


        }

        cotacaoGrupalService.responder(resp, function(r) {

            if (r.sucesso) {

                $scope.respondida = true;
                msg.alerta("Resposta enviada com sucesso, o RTC agradece.");
                window.location.reload();

            } else {

                msg.erro("Ocorreu um problema, tente mais tarde");

            }

        });

    }

})
rtc.controller("crtMovimentoEstoque", function($scope, $timeout, movimentosProdutoService, produtoService, empresaService) {

    $scope.isLogistica = false;

    $scope.estaEmpresa = null;
    $scope.empresa = null;
    $scope.empresas = [];

    empresaService.getEmpresa(function(e) {

        $scope.estaEmpresa = e.empresa;
        $scope.empresa = e.empresa;
        $scope.isLogistica = e.empresa._classe === "Logistica";

        if ($scope.isLogistica) {
            empresaService.getEmpresasClientes(function(es) {

                $scope.empresas = es.clientes;
                $scope.empresas[$scope.empresas.length] = $scope.empresa;

            })
        }

    })

    $scope.trocaEmpresa = function() {
        produtoService["empresa"] = $scope.empresa.id;

        if ($scope.empresa.id === $scope.estaEmpresa.id) {
            produtoService["filtro_base"] = undefined;
        } else {
            produtoService["filtro_base"] = "produto.id_logistica=" + $scope.estaEmpresa.id;
        }

        $scope.produtos.attList();
    }


    $scope.gerando = false;

    $scope.inicio = new Date().getTime() - (30 * 24 * 60 * 60 * 1000);
    $scope.fim = new Date().getTime();

    $scope.produtos_selecionados = [];
    $scope.produtos = createAssinc(produtoService, 1, 3, 4);

    $scope.relatorio = [];

    assincFuncs(
        $scope.produtos,
        "produto", ["codigo", "nome", "disponivel"], "filtroProdutos");

    $scope.getCor = function(mov) {


        var baixo = true;

        if (mov.influencia_reserva > 0 || mov.influencia_estoque > 0) {
            baixo = false;
        }

        if (baixo) {

            if (mov.influencia_estoque !== mov.influencia_reserva) {
                return 'Orange';
            }
            return 'Red';

        } else {

            if (mov.influencia_estoque !== mov.influencia_reserva) {
                return 'Blue';
            }
            return 'Green';

        }

    }

    $scope.gerarRelatorio = function() {

        $scope.gerando = true;
        $('#mdlRelatorio').modal('show');

        var filtro = "UNIX_TIMESTAMP(!data_emissao!)*1000 > " + $scope.inicio + " AND UNIX_TIMESTAMP(!data_emissao!)*1000 < " + $scope.fim;

        if ($scope.isLogistica && $scope.estaEmpresa.id === $scope.empresa.id) {
            filtro += " AND pr.id_empresa=" + $scope.estaEmpresa.id;
        }

        var inn = "";

        for (var i = 0; i < $scope.produtos_selecionados.length; i++) {
            var p = $scope.produtos_selecionados[i];
            if (inn === "") {
                inn = "(" + p.id;
            } else {
                inn += "," + p.id;
            }
        }

        if (inn !== "") {
            inn += ")";
            filtro += " AND pr.id IN " + inn;
        }

        $scope.relatorio = [];
        movimentosProdutoService.getMovimentos(filtro, function(r) {

            var m = r.movimentos;

            lbl:
                for (var i = 0; i < m.length; i++) {
                    var mov = m[i];
                    for (var j = 0; j < $scope.relatorio.length; j++) {
                        var item = $scope.relatorio[j];
                        if (mov.id_produto === item.id_produto) {
                            item.movimentos[item.movimentos.length] = mov;
                            continue lbl;
                        }
                    }
                    var item = {
                        id_produto: mov.id_produto,
                        nome_produto: mov.nome_produto,
                        armazen: mov.armazen,
                        movimentos: [mov],
                        estoque_atual: mov.estoque_atual,
                        disponivel_atual: mov.disponivel_atual
                    };
                    $scope.relatorio[$scope.relatorio.length] = item;
                }

            $scope.gerando = false;

        })

    }

    $scope.addProduto = function(p) {

        for (var i = 0; i < $scope.produtos_selecionados.length; i++) {
            if ($scope.produtos_selecionados[i].id === p.id) {
                msg.erro("Esse produto ja esta adicionado");
                return;
            }
        }

        $scope.produtos_selecionados[$scope.produtos_selecionados.length] = p;

    }

    $scope.removeProduto = function(p) {

        var nv = [];

        for (var i = 0; i < $scope.produtos_selecionados.length; i++) {
            if ($scope.produtos_selecionados[i].id !== p.id) {
                nv[nv.length] = $scope.produtos_selecionados[i];
            }
        }

        $scope.produtos_selecionados = nv;

    }

})
rtc.controller("crtAnaliseCotacao", function($scope, $sce, analiseCotacaoService) {

    $scope.analises = {};
    $scope.elementos = [];


    analiseCotacaoService.getElementos(function(t) {

        $scope.elementos = t.elementos;
        $scope.analises = createList($scope.elementos, 1, 10, "nome_produto");

    })

    $scope.passar = function(analise) {

        analiseCotacaoService.passar(analise, function(r) {

            if (r.sucesso) {

                msg.alerta("Produto vistado com sucesso");

                var ne = [];

                for (var i = 0; i < $scope.elementos.length; i++) {
                    if ($scope.elementos[i].id !== analise.id) {
                        ne[ne.length] = $scope.elementos[i];
                    }
                }

                $scope.elementos = ne;

                $scope.analises = createList($scope.elementos, 1, 10, "nome_produto");

            } else {

                msg.erro("Problema ao vistar produto, tente novamente mais tarde");

            }

        })


    }

    $scope.recusar = function(analise) {

        analiseCotacaoService.recusar(analise, function(r) {

            if (r.sucesso) {

                msg.alerta("Produto recusado com sucesso");

                var ne = [];

                for (var i = 0; i < $scope.elementos.length; i++) {
                    if ($scope.elementos[i].id !== analise.id) {
                        ne[ne.length] = $scope.elementos[i];
                    }
                }

                $scope.elementos = ne;

                $scope.analises = createList($scope.elementos, 1, 10, "nome_produto");

            } else {

                msg.erro("Problema ao recusar produto, tente novamente mais tarde");

            }

        })


    }

    $scope.aprovar = function(analise) {

        analise.custo_atual = analise.valor;

        analiseCotacaoService.aprovar(analise, function(r) {

            if (r.sucesso) {

                msg.alerta("Produto aprovado com sucesso");

            } else {

                msg.erro("Problema ao aprovar produto, tente novamente mais tarde");

            }

        })

    }

    $scope.campanha = function(analise) {

        var dias = 1;

        if (typeof analise["dias_campanha"] !== 'undefined') {
            dias = analise["dias_campanha"];
        }

        analiseCotacaoService.campanha(analise, dias, function(r) {

            if (r.sucesso) {

                msg.alerta("Produto colocado na campanha por " + dias + " dias, com sucesso");

            } else {

                msg.erro("Problema ao colocar produto, tente novamente mais tarde");

            }

        })

    }

})
rtc.controller("crtCarrinhoEncomendaFinal", function($scope, sistemaService, carrinhoEncomendaService, transportadoraService, tabelaService, encomendaService) {

    $scope.possibilidades = [
        { id: 0, prazo: 0, parcelas: 1, nome: "Antecipado" },
        { id: 1, prazo: 30, parcelas: 1, nome: null },
        { id: 2, prazo: 60, parcelas: 1, nome: null },
        { id: 3, prazo: 90, parcelas: 1, nome: null },
        { id: 4, prazo: 30, parcelas: 2, nome: null },
        { id: 5, prazo: 60, parcelas: 2, nome: null },
        { id: 6, prazo: 90, parcelas: 2, nome: null },
        { id: 7, prazo: 60, parcelas: 3, nome: null },
        { id: 8, prazo: 90, parcelas: 3, nome: null },
    ];

    $scope.atualizando_custo = false;
    $scope.encomendas = [];
    $scope.encomenda_contexto = null;
    $scope.carrinho = [];

    carrinhoEncomendaService.getCarrinho(function(c) {

        $scope.carrinho = c.carrinho;

    })

    $scope.setFrete = function(frete) {


        $scope.encomenda_contexto.transportadora = frete.transportadora;
        $scope.encomenda_contexto.frete = parseFloat(frete.valor.toFixed(2));
        $scope.atualizaCustos($scope.encomenda_contexto);


    }

    $scope.getFretes = function(encomenda) {

        $scope.encomenda_contexto = encomenda;

        var empresa = encomenda.empresa;

        //gambiarra
        if (empresa.id === 1734) {

            empresa = { id: 1735, _classe: "Empresa" };

        }

        //---- parametros de frete

        var pesoTotal = 0;
        var valorTotal = 0;

        for (var i = 0; i < encomenda.produtos.length; i++) {
            var p = encomenda.produtos[i];
            valorTotal += (p.valor_base_inicial + p.ipi_inicial + p.icms_inicial + p.juros_inicial) * p.quantidade;
            pesoTotal += p.produto.peso_bruto * p.quantidade;
        }

        if (isNaN(pesoTotal)) {

            pesoTotal = 200;

        }

        //------------------------

        tabelaService.getFretes(empresa, { cidade: encomenda.cliente.endereco.cidade, valor: valorTotal, peso: pesoTotal }, function(f) {

            $scope.fretes = f.fretes;

        })

    }


    $scope.abrirTransportadoras = function(e) {

        $scope.setEncomendaContexto(e);

        var local = e.empresa;

        //GAMBIARRA
        if (local.id === 1734) {

            local = { id: 1735 };

        }

        transportadoraServiceEmpresa.empresa = local;
        $scope.transportadorasEmpresa.attList();

        $("#transportadoras").modal("show");

    }

    $scope.getTotalInicial = function(encomenda) {

        var total = 0;

        for (var i = 0; i < encomenda.produtos.length; i++) {
            total += encomenda.produtos[i].quantidade * (encomenda.produtos[i].valor_base_inicial + encomenda.produtos[i].ipi_inicial + encomenda.produtos[i].juros_inicial);
        }

        return total;

    }

    $scope.getTotalFinal = function(encomenda) {

        var total = 0;

        for (var i = 0; i < encomenda.produtos.length; i++) {
            total += encomenda.produtos[i].quantidade * (encomenda.produtos[i].valor_base_final + encomenda.produtos[i].ipi_final + encomenda.produtos[i].juros_final);
        }

        return total;

    }

    $scope.finalizarEncomenda = function(encomenda) {
        encomenda.status_finalizacao = { valor: "Aguarde... O Sistema esta fechando sua encomenda", classe: "btn-primary", final: false };

        sistemaService.finalizarEncomendaParceiros(encomenda, function(r) {

            var novo_carrinho = [];

            lbl:
                for (var i = 0; i < $scope.carrinho.length; i++) {

                    var it = $scope.carrinho[i];

                    for (var j = 0; j < encomenda.produtos.length; j++) {

                        var p = encomenda.produtos[j];

                        if (it.id === p.produto.id) {

                            continue lbl;

                        }

                    }

                    novo_carrinho[novo_carrinho.length] = it;

                }

            $scope.carrinho = novo_carrinho;
            var p = r.o;
            carrinhoEncomendaService.setCarrinho($scope.carrinho, function(s) {

                encomenda.cobranca_gerada = true;
                encomenda.status_finalizacao = { valor: "A Encomenda foi realizada com sucesso !, verifique a confirmacao em seu email", classe: "btn btn-warning", final: true };

            })

        })

    }


    $scope.setFrete = function(fr) {

        $scope.encomenda_contexto.frete = fr.valor + fr.transportadora.despacho;
        $scope.encomenda_contexto.transportadora = fr.transportadora;
        $scope.atualizaCustos($scope.encomenda_contexto);

    }

    $scope.setTransportadora = function(t) {

        $scope.encomenda_contexto.transportadora = t;

    }


    carrinhoEncomendaService.getEncomendasResultantes(function(r) {

        if (r.sucesso) {

            $scope.encomendas = r.encomendas;

            for (var i = 0; i < r.encomendas.length; i++) {

                r.encomendas[i].identificador = i;
                r.encomendas[i].possibilidades_frete = [];
                r.encomendas[i].status_finalizacao = null;
                r.encomendas[i].prazo_parcelas = $scope.possibilidades[0];
            }

        }

    });

    $scope.setEncomendaContexto = function(encomenda) {

        $scope.encomenda_contexto = encomenda;

    }


    $scope.remover = function(produto) {

        var nc = [];
        for (var i = 0; i < $scope.carrinho.length; i++) {
            if ($scope.carrinho[i].id === produto.produto.id) {
                continue;
            }
            nc[nc.length] = $scope.carrinho[i];
        }

        carrinhoEncomendaService.setCarrinho(nc, function() {

            location.reload();

        })

    }

    $scope.attPrazoParcelas = function(encomenda) {

        encomenda.prazo = encomenda.prazo_parcelas.prazo;
        encomenda.parcelas = encomenda.prazo_parcelas.parcelas;

        $scope.atualizaCustos(encomenda);

    }

    $scope.atualizaCustos = function(encomenda) {
        $scope.atualizando_custo = true;
        var i = 0;
        for (; i < $scope.encomendas.length; i++) {
            if ($scope.encomendas[i] === encomenda) {
                break;
            }
        }

        encomendaService.atualizarCustos(encomenda, function(np) {

            $scope.encomendas[i] = np.o;
            $scope.encomendas[i].identificador = i;
            $scope.encomendas[i].status_finalizacao = null;
            equalize($scope.encomendas[i], "prazo_parcelas", $scope.possibilidades);
            $scope.encomenda_contexto = $scope.encomendas[i];
            $scope.atualizando_custo = false;
        })

    }


})
rtc.controller("crtEncomendaParceiros", function($scope, transportadoraService, produtoService, encomendaParceiroService, sistemaService, carrinhoEncomendaService) {

    $scope.locais = [];
    $scope.produto = null;

    $scope.carregando_encomenda = true;
    $scope.loaders = [{ id: 0 }, { id: 1 }, { id: 2 }, { id: 3 }, { id: 4 }, { id: 5 }];

    $scope.produtos = createFilterList(encomendaParceiroService, 3, 6, 10);
    $scope.produtos["posload"] = function(els) {

        $scope.carregando_encomenda = false;

    }
    $scope.produtos.attList();



    $scope.qtd = 0;
    $scope.prod = null;
    $scope.val = null;

    var carrinho = [];

    carrinhoEncomendaService.getCarrinho(function(c) {

        carrinho = c.carrinho;

    })


    $scope.addCarrinho = function(produto) {

        $scope.prod = produto;

        $scope.qtd = parseFloat(window.prompt("Quantidade"));
        if (isNaN($scope.qtd)) {
            msg.erro("Quantidade incorreta");
            return;
        }

        $scope.qtd = parseInt(($scope.qtd + ""));


        var p = angular.copy($scope.prod);
        p.quantidade_comprada = $scope.qtd;

        if (p.limite > 0) {
            if (p.quantidade_comprada > p.limite) {
                msg.erro("Essa quantidade ultrapassa o limite para este produto");
                return;
            }
        }

        var a = false;
        for (var i = 0; i < carrinho.length; i++) {
            if (carrinho[i].id === p.id) {

                if (p.limite > 0) {
                    if (carrinho[i].quantidade_comprada + $scope.qtd > p.limite) {
                        msg.erro("Essa quantidade ultrapassa o limite para este produto");
                        return;
                    }
                }

                carrinho[i].quantidade_comprada += $scope.qtd;
                a = true;
                break;
            }
        }

        if (!a) {
            carrinho[carrinho.length] = p;
        }
        carrinhoEncomendaService.setCarrinho(carrinho, function(r) {

            if (r.sucesso) {

                msg.confirma("Adicionado com sucesso. Deseja finalizar ?", function() {
                    window.location = 'carrinho_encomenda.php';
                });


            } else {

                msg.erro("Falha ao adicionar o produto");

            }

        })

    }


    $scope.setProduto = function(produto) {
        $scope.prod = produto;
    }

    $scope.addLevel = function(op, filtro) {
        op.selecionada++;
        op.selecionada = op.selecionada % 2;

        for (var i = 0; i < filtro.opcoes.length; i++) {
            if (filtro.opcoes[i].selecionada > 0 && filtro.opcoes[i].id !== op.id) {
                filtro.opcoes[i].selecionada = 0;
            }
        }

        $scope.produtos.attList();
    }

    $scope.resetarFiltro = function() {

        for (var i = 0; i < $scope.produtos.filtro.length; i++) {
            var f = $scope.produtos.filtro[i];
            if (f._classe === 'FiltroTextual') {
                f.valor = "";
            } else if (f._classe === 'FiltroOpcional') {
                for (var j = 0; j < f.opcoes.length; j++) {
                    f.opcoes[j].selecionada = 0;
                }
            }
        }

        $scope.produtos.attList();

    }

    $scope.dividir = function(produtos, qtd) {

        var k = Math.ceil((produtos.length) / qtd);

        var m = [];

        for (var a = 0; a < qtd; a++) {
            m[a] = [];
            for (var i = a * k; i < (a + 1) * k && i < produtos.length; i++) {
                for (var j = 0; j < produtos[i].length; j++) {
                    m[a][m[a].length] = produtos[i][j];
                }
            }
        }

        return m;

    }

})
rtc.controller("crtEncomendas", function($scope, transportadoraService, cotacaoGrupalService, tabelaService, empresaService, sistemaService, encomendaService, logService, baseService, produtoService, sistemaService, statusEncomendaService, clienteService, produtoEncomendaService) {

    $scope.encomendas = createAssinc(encomendaService, 1, 10, 10);
    $scope.encomendas.attList();
    assincFuncs(
        $scope.encomendas,
        "encomenda", ["id", "cliente.razao_social", "data", "id_status", "usuario.nome"]);
    /*    
     $scope.cotacoes_grupais = createAssinc(cotacaoGrupalService, 1, 10, 10);
     $scope.cotacoes_grupais.attList();
     assincFuncs(
     $scope.cotacoes_grupais,
     "c",
     ["id", "cliente.razao_social", "data", "id_status", "usuario.nome"]);
     */
    $scope.produtos = createAssinc(produtoService, 1, 3, 4);

    assincFuncs(
        $scope.produtos,
        "produto", ["codigo", "nome", "disponivel"], "filtroProdutos");

    $scope.clientes = createAssinc(clienteService, 1, 3, 4);

    $scope.carregando = false;

    $scope.logisticas = [null];
    $scope.logistica = null;

    $scope.empresa = null;

    $scope.transportadoras = createAssinc(transportadoraService, 1, 3, 4);

    assincFuncs(
        $scope.transportadoras,
        "transportadora", ["codigo", "razao_social"], "filtroTransportadoras");


    $scope.setTransportadora = function(t) {

        $scope.encomenda.transportadora = t;

    }


    $scope.setLogistica = function() {

        if ($scope.logistica.id !== $scope.empresa.id) {
            produtoService.filtro_base = "(produto.id_logistica=" + $scope.logistica.id + ")";
        } else {
            produtoService.filtro_base = "(produto.id_logistica=0)";
        }

        $scope.encomenda.produtos = [];

        $scope.produtos.attList();

    }

    sistemaService.getLogisticas(function(rr) {

        empresaService.getEmpresa(function(e) {

            $scope.empresa = e.empresa;
            $scope.logistica = e.empresa;

            $scope.logisticas = [$scope.empresa].concat(rr.logisticas);

            $scope.setLogistica();

        })


    })



    assincFuncs(
        $scope.clientes,
        "cliente", ["codigo", "razao_social"], "filtroClientes");

    $scope.status_encomenda = [];

    $scope.status_excluido = {};

    $scope.encomenda_novo = {};

    $scope.produto_encomenda_novo = {};

    $scope.encomenda = {};

    $scope.qtd = 0;

    $scope.valor_inicial = 0;
    $scope.valor_final = 0;

    $scope.$watch(function() {

        if ($scope.encomenda !== null) {
            for (var i = 0; i < $scope.encomenda.produtos.length; i++) {
                var p = $scope.encomenda.produtos[i];
                if (p.valor_base_inicial > p.valor_base_final) {
                    p.valor_base_inicial = p.valor_base_final;
                }
            }
        }

    })

    $scope.produto = {};

    $scope.logs = [];

    $scope.getLogs = function() {

        logService.getLogs($scope.encomenda, function(l) {

            $scope.logs = l.logs;

            $("#shLogs").children("*").each(function() {
                $(this).remove();
            })

            for (var i = 0; i < $scope.logs.length; i++) {

                var l = $scope.logs[i];

                $("<div></div>").css('width', '100%').css('display', 'block').css('border-bottom', '1px solid Gray').css('padding', '10px').html(l.usuario + " / " + toTime(l.momento) + " / " + l.obs).appendTo($("#shLogs"));

            }

        })


    }

    $scope.calculoPronto = function() {

        if ($scope.encomenda.cliente != null && $scope.encomenda.produtos != null) {
            if ($scope.encomenda.produtos.length > 0) {
                return true;
            }
        }
        return false;

    }

    $scope.setFrete = function(fr) {

        $scope.encomenda.frete = parseFloat((fr.valor + fr.transportadora.despacho).toFixed(2));
        $scope.encomenda.transportadora = fr.transportadora;
        $scope.atualizaCustos();

    }

    $scope.fretes = [];

    $scope.getFretes = function() {

        var pesoTotal = 0;
        var valorTotal = 0;

        for (var i = 0; i < $scope.encomenda.produtos.length; i++) {
            var p = $scope.encomenda.produtos[i];
            valorTotal += (p.valor_base_inicial) * p.quantidade;
            pesoTotal += p.produto.peso_bruto * p.quantidade;
        }

        tabelaService.getFretes($scope.logistica, { cidade: $scope.encomenda.cliente.endereco.cidade, valor: valorTotal, peso: pesoTotal }, function(f) {

            $scope.fretes = f.fretes;

        })

    }

    $scope.getPesoBrutoEncomenda = function() {

        var tot = 0;

        for (var i = 0; i < $scope.encomenda.produtos.length; i++) {

            var p = $scope.encomenda.produtos[i];

            tot += (p.produto.peso_bruto) * p.quantidade;

        }

        return tot;

    }

    $scope.getTotalInicial = function() {

        var tot = 0;

        for (var i = 0; i < $scope.encomenda.produtos.length; i++) {

            var p = $scope.encomenda.produtos[i];

            tot += (p.valor_base_inicial + p.icms_inicial + p.ipi_inicial + p.juros_inicial) * p.quantidade;

        }

        return tot;

    }

    $scope.getTotalFinal = function() {

        var tot = 0;

        for (var i = 0; i < $scope.encomenda.produtos.length; i++) {

            var p = $scope.encomenda.produtos[i];

            tot += (p.valor_base_final + p.icms_final + p.ipi_final + p.juros_final) * p.quantidade;

        }

        return tot;

    }


    statusEncomendaService.getStatus(function(st) {

        $scope.status_encomenda = st.status;

    })

    $scope.setCliente = function(cli) {

        $scope.encomenda.cliente = cli;
        $scope.atualizaCustos();

    }

    produtoEncomendaService.getProdutoEncomenda(function(pp) {

        $scope.produto_encomenda_novo = pp.produto_encomenda;

    })

    $scope.addProduto = function(produto) {

        if ($scope.valor_final < $scope.valor_inicial) {
            msg.erro("O Valor final nao pode ser menor do que o inicial");
            return;
        }

        if ($scope.qtd == 0) {
            msg.alerta("A quantidade nao pode ser 0");
            return;
        }

        if ($scope.valor_final == 0 || $scope.valor_inicial == 0) {
            msg.alerta("O Valor nao pode ser 0");
            return;
        }

        var pp = angular.copy($scope.produto_encomenda_novo);
        pp.produto = produto;
        pp.encomenda = $scope.encomenda;
        pp.valor_base_inicial = $scope.valor_inicial;
        pp.valor_base_final = $scope.valor_final;
        pp.quantidade = $scope.qtd;

        if (produto.valor_cotacao > 0) {

            pp.valor_base_inicial = produto.valor_cotacao;
            pp.valor_base_final = produto.valor_cotacao;

        } else if (produto.valor_base > 0) {

            pp.valor_base_inicial = produto.valor_base;
            pp.valor_base_final = produto.valor_base * 1.10;

        }

        var a = false;
        for (var j = 0; j < $scope.encomenda.produtos.length; j++) {

            var pr = $scope.encomenda.produtos[j];

            if (pr.produto.id === pp.produto.id) {

                pr.quantidade += pp.quantidade;
                a = true;
                break;
            }

        }

        if (!a) {
            $scope.encomenda.produtos[$scope.encomenda.produtos.length] = pp;
        }

        $scope.atualizaCustos();

    }

    $scope.removerProduto = function(produto) {

        remove($scope.encomenda.produtos, produto);

        $scope.atualizaCustos();

    }

    $scope.mergeEncomenda = function() {

        $scope.carregando = true;

        var p = $scope.encomenda;

        if (p.cliente == null) {
            msg.erro("Encomenda sem cliente.");
            return;
        }


        if (p.status == null) {
            msg.erro("Encomenda sem status.");
            return;
        }



        baseService.merge(p, function(r) {
            if (r.sucesso) {
                $scope.encomenda = r.o;
                equalize($scope.encomenda, "status", $scope.status_encomenda);

                msg.alerta("Operacao efetuada com sucesso");

                if (typeof $scope.encomenda["retorno"] !== 'undefined') {

                    msg.alerta($scope.encomenda["retorno"]);

                }

            } else {
                $scope.encomenda = r.o;
                equalize($scope.encomenda, "status", $scope.status_encomenda);
                msg.erro("Ocorreu o seguinte problema: " + r.mensagem);
            }
            $scope.carregando = false;
        });

    }

    $scope.atualizaCustos = function() {

        encomendaService.atualizarCustos($scope.encomenda, function(np) {
            $scope.encomenda = np.o;
            equalize($scope.encomenda, "status", $scope.status_encomenda);

        })

    }

    encomendaService.getEncomenda(function(ped) {

        ped.encomenda.produtos = [];
        $scope.encomenda_novo = ped.encomenda;

    })

    $scope.novoEncomenda = function() {

        $scope.setEncomenda(angular.copy($scope.encomenda_novo));

    }

    $scope.resetarEncomenda = function() {

        $scope.encomenda.produtos = [];

        $scope.produto0s.attList();

    }

    $scope.setEncomenda = function(encomenda) {

        $scope.encomenda = encomenda;

        if ($scope.encomenda.id === 0) {

            $scope.encomenda.status = $scope.status_encomenda[0];

            return;

        }

        encomendaService.getProdutos(encomenda, function(p) {

            encomenda.produtos = p.produtos;

            for (var i = 0; i < encomenda.produtos.length; i++) {
                encomenda.produtos[i].encomenda = encomenda;
            }


            //======================================================


            var pedido = encomenda;
            $scope.pedido = pedido;

            var ic = $("#myIframe").contents();

            ic.find("#logoEmpresa img").remove();
            ic.find("#logoEmpresa").append($("#logo").clone().addClass("product-image"));
            ic.find("#infoEmpresa").html(pedido.empresa.nome + ", " + pedido.empresa.endereco.cidade.nome + "-" + pedido.empresa.endereco.cidade.estado.sigla);
            ic.find("#infoEmpresa2").html(pedido.empresa.endereco.bairro + ", " + pedido.empresa.endereco.cep.valor + " - " + pedido.empresa.telefone.numero);

            ic.find("#idPedido").html($scope.pedido.id);
            ic.find("#nomeUsuario").html($scope.pedido.usuario.nome);
            ic.find("#nomeCliente").html($scope.pedido.cliente.razao_social);
            ic.find("#cnpjCliente").html($scope.pedido.cliente.cnpj.valor);
            ic.find("#ruaCliente").html($scope.pedido.cliente.endereco.rua);
            ic.find("#cidadeCliente").html($scope.pedido.cliente.endereco.cidade.nome);
            ic.find("#emailCliente").html($scope.pedido.cliente.email.endereco);

            if ($scope.pedido.transportadora !== null) {

                ic.find("#transportadora").html($scope.pedido.transportadora.razao_social);
                ic.find("#cnpjTransportadora").html($scope.pedido.transportadora.cnpj.valor);
                ic.find("#emailTransportadora").html($scope.pedido.transportadora.email.endereco);

                ic.find("#cidadeEstadoTransportadora").html($scope.pedido.transportadora.endereco.cidade.nome + " - " + $scope.pedido.transportadora.endereco.cidade.estado.sigla);

            }


            var suframa = "Sem suframa";

            if ($scope.pedido.cliente.suframa) {
                suframa = $scope.pedido.cliente.inscricao_suframa;
            }

            ic.find("#suframa").html(suframa);

            var p = ic.find("#produto").each(function() {
                p = $(this);
            });

            p.hide();

            ic.find("#produtos").find("tr").each(function() {
                if (typeof $(this).data("gerado") !== 'undefined') {
                    $(this).remove();
                }
            });

            var p = p.clone();

            var icms = 0;
            var base = 0;
            var total = 0;

            for (var i = 0; i < $scope.pedido.produtos.length; i++) {

                p = p.clone();

                var pro = $scope.pedido.produtos[i];
                icms += pro.icms_inicial * pro.quantidade;
                base += pro.base_calculo_inicial * pro.quantidade;
                p.find("[data-tipo='nome']").html(pro.produto.nome);

                p.find("[data-tipo='valor']").html((pro.valor_base_inicial + pro.frete_inicial + pro.juros_inicial + pro.icms_inicial).toFixed(2));
                p.find("[data-tipo='quantidade']").html(pro.quantidade);
                p.find("[data-tipo='validade']").html("-------------");
                p.find("[data-tipo='total']").html(((pro.valor_base_inicial + pro.frete_inicial + pro.ipi_inicial + pro.juros_inicial + pro.icms_inicial) * pro.quantidade).toFixed(2));
                p.data("gerado", true);

                ic.find("#produtos").append(p);
                p.show();

                total += (pro.valor_base_inicial + pro.frete_inicial + pro.juros_inicial + pro.ipi_inicial + pro.icms_inicial) * pro.quantidade;

            }
            var alicota = (icms * 100 / base);

            ic.find("#prazo").html(pedido.prazo);
            ic.find("#alicota").html(alicota.toFixed(0));
            ic.find("#icms").html(icms.toFixed(2));

            ic.find("#tipoFrete").html(pedido.frete_incluso ? 'CIF' : 'FOB');
            ic.find("#nomeTransportadora").html(pedido.transportadora.razao_social);
            ic.find("#contato").html(pedido.transportadora.email.endereco);
            ic.find("#valorFrete").html(pedido.frete);

            ic.find("#observacoes").html(pedido.observacoes);
            ic.find("#nomeUsuario2").html(pedido.usuario.nome);



            //======================================================


            equalize(encomenda, "status", $scope.status_encomenda);

        })


    }

    $scope.deleteEncomenda = function() {
        $scope.carregando = true;
        baseService.delete($scope.encomenda, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.pedido = angular.copy($scope.novo_encomenda);
                $scope.pedidos.attList();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
            $scope.carregando = false;
        });

    }


})
rtc.controller("crtSeparacao", function($scope, pedidoService, loteService, sistemaService) {


    var formatar = function(valor, digitos) {
        var v = valor;
        while (v.length < digitos) {
            v = "0" + v;
        }
        return v;
    }

    var descrever = function(retirada) {

        var k = retirada.length - 2;

        if (k === 1) {
            return "PALET INTEIRO";
        } else if (k === 2) {
            return "CAIXA";
        } else if (k === 3) {
            return "UNIDADE DA CAIXA";
        }

        return "SUB UNIDADE DA CAIXA";

    }
    $scope.carregando = false;
    $scope.pedido = null;
    $scope.codigo = "";
    $scope.itens = [];

    $scope.relatorio_separacao = "";

    $scope.gerarRelatorio = function() {

        sistemaService.gerarRelatorioSeparacao($scope.pedido, $scope.itens, function(r) {

            if (r.sucesso) {

                $scope.relatorio_separacao = r.relatorio;
                $('#mdlRelatorio').modal('show');

            } else {

                msg.alerta(r.mensagem);

            }
        })

    }

    $scope.imprimirItens = function() {

        var etiquetas = [];
        for (var i = 0; i < $scope.itens.length; i++) {
            var item = $scope.itens[i];

            var etiqueta = {
                id: item.id_lote,
                id_produto: item.id_produto,
                nome_produto: item.nome_produto,
                validade: item.validade,
                codigo: item.codigo,
                empresa: "Agro Fauna Tecnologia"
            };
            etiquetas[etiquetas.length] = etiqueta;
        }

        var buffer = 50;
        var buff = [];

        for (var i = 0; i < etiquetas.length; i++) {
            var k = parseInt(i / buffer);
            if (i % buffer === 0) {
                buff[k] = [];
            }
            buff[k][buff[k].length] = etiquetas[i];
        }

        for (var i = 0; i < buff.length; i++) {
            loteService.getEtiquetas(buff[i], function(a) {
                if (a.sucesso) {

                    window.open(projeto + "/php/uploads/" + a.arquivo);

                } else {

                    msg.erro("Ocorreu um problema de servidor, tente mais tarde");
                }
            });
        }

    }



    pedidoService.getPedidoEspecifico(rtc.id_empresa, rtc.id_pedido, function(pedido) {

        $scope.pedido = pedido.pedido;

        $scope.itens = [];
        for (var i = 0; i < $scope.pedido.produtos.length; i++) {
            var p = $scope.pedido.produtos[i];

            if (!p.produto.sistema_lotes) {
                continue;
            }
            for (var j = 0; j < p.retiradas.length; j++) {
                var r = p.retiradas[j];
                var codigo = formatar(r[0] + "", 7);
                for (var k = 3; k < r.length; k++) {
                    codigo += formatar(r[k] + "", 4);
                }

                var validade = new Date(parseFloat(p.validade_minima + "")).toLocaleString();
                validade = validade.split(" ")[0];

                var item = {
                    id_produto: p.produto.id,
                    id_pedido_produto: p.id,
                    id_lote: r[0],
                    nome_produto: p.produto.nome,
                    quantidade: r[1],
                    codigo: codigo,
                    validade: validade,
                    descricao: descrever(r),
                    codigo_bipado: ""
                }
                $scope.itens[$scope.itens.length] = item;
            }
        }

        sistemaService.popularEnderecamento($scope.itens, function(i) {

            $scope.itens = i.itens;

        })

    })


    $scope.bipe = function() {

        var achou = false;
        for (var i = 0; i < $scope.itens.length; i++) {
            if ($scope.itens[i].codigo === $scope.codigo) {
                $scope.itens[i].codigo_bipado = $scope.codigo;
                achou = true;
                break;
            }
        }

        if (!achou) {
            for (var i = 0; i < $scope.itens.length; i++) {
                if ($scope.itens[i].codigo === $scope.codigo) {
                    $scope.itens[i].codigo_bipado = $scope.codigo;
                    achou = true;
                    break;
                }
            }
        }

        $scope.codigo = "";

    }

    $scope.podeFinalizar = function() {

        var a = true;

        for (var i = 0; i < $scope.itens.length; i++) {
            if ($scope.itens[i].codigo_bipado === "") {
                a = false;
                break;
            }
        }

        return a && !$scope.carregando;

    }

    $scope.finalizarSeparacao = function() {
        $scope.carregando = true;
        sistemaService.finalizarSeparacao($scope.pedido, function(r) {

            if (r.sucesso) {

                msg.alerta("Finalizada com sucesso. Redirecionando a tela de tarefas");
                window.location = "tarefas.php";

            } else {

                msg.erro("Ocorreu um problema ao finalizar a separa��o");

            }

            $scope.carregando = false;

        })

    }


})
rtc.controller("crtAnaliseCredito", function($scope, clienteService, sistemaService) {

    $scope.cliente = null;
    $scope.faturamento_anual = 0;
    $scope.meses_faturamento = {};
    $scope.ativo = 0;
    $scope.circulante = 0;
    $scope.passivo = 0;
    $scope.m = 0;
    $scope.exigivelLongoPrazo = 0;
    $scope.endividamentoGeral = 0;
    $scope.composicaoEndividamento = 0;
    $scope.ac = 0;
    $scope.ae = 0;
    $scope.ag = 0;
    $scope.aj = 0;
    $scope.al = 0;
    $scope.indiceCoberturaJuros = 0;
    $scope.retornoSobreVendas = 0;
    $scope.margemBruta = 0;
    $scope.cartaFianca = 0;
    $scope.impostoRenda = 0;
    $scope.estoqueConsignado = 0;
    $scope.hipoteca = 0;
    $scope.epo = false;
    $scope.rde = false;
    $scope.fb = false;
    $scope.pco = false;
    $scope.rco = false;
    $scope.ccs = false;
    $scope.her = false;
    $scope.tempoRamoAtual = 0;
    $scope.pee = false;
    $scope.ane = false;
    $scope.toc = false;
    $scope.cpr = false;
    $scope.pch = false;
    $scope.rai = false;
    $scope.trs = false;
    $scope.scoreR = 0;
    $scope.leR = false;
    $scope.mediaFaturamentoR = 0;
    $scope.cfR = false;
    $scope.cfQR = 0;
    $scope.slR = false;
    $scope.limiteMensal = 0;
    $scope.clienteDesdeR = 0;
    $scope.liR = false;
    $scope.liQR = 0;
    $scope.limiteMensalR2 = 0;
    $scope.sllR = false;
    $scope.resultado = 0;

    clienteService.getClienteEspecifico(rtc.id_empresa, rtc.id_cliente, function(cli) {

        $scope.cliente = cli.cliente;

    })

    $scope.podeFinalizar = function() {

        return !isNaN($scope.resultado);

    }

    $scope.finalizarAnalise = function() {

        msg.confirma("Tem certeza que deseja finalizar com limite de R$ " + $scope.resultado + ", para o cliente " + $scope.cliente.razao_social, function() {

            sistemaService.setLimiteCredito($scope.resultado, rtc.id_cliente, rtc.id_empresa, rtc.id_pedido, function(r) {

                if (r.sucesso) {

                    msg.alerta("Limite analisado com sucesso. " + (rtc.id_pedido > 0 ? 'Redirecionando novamente a tela de tarefas' : ''));

                    window.location = "tarefas.php";

                } else {

                    msg.erro("Ocorreu um problema.");

                }

            })

        });

    }

    $scope.precario = 0;

    $scope.finalizarAnalisePrecaria = function() {

        msg.confirma("Tem certeza que deseja finalizar com limite de R$ " + $scope.precario + ", para o cliente " + $scope.cliente.razao_social, function() {

            sistemaService.setLimiteCredito($scope.precario, rtc.id_cliente, rtc.id_empresa, rtc.id_pedido, function(r) {

                if (r.sucesso) {

                    msg.alerta("Limite analisado com sucesso. " + (rtc.id_pedido > 0 ? 'Redirecionando novamente a tela de tarefas' : ''));

                    window.location = "tarefas.php";

                } else {

                    msg.erro("Ocorreu um problema.");

                }

            })

        });

    }

    var calcular = function() {

        var limite = 0;

        var porcentagens = [10, 20, 25, 30, 35];

        var data = new Date();

        var porcentagem = porcentagens[1];


        $scope.faturamento_anual = 0;

        for (a in $scope.meses_faturamento) {
            $scope.faturamento_anual += $scope.meses_faturamento[a];
        }

        var media_faturamento = $scope.faturamento_anual;


        var fat_perc = media_faturamento * porcentagem / 100;

        $scope.precario = fat_perc;


        var perc_bal = fat_perc * 0.1;

        var endividamento_geral = ($scope.m + $scope.exigivelLongoPrazo) / $scope.ativo;



        if (!isNaN(endividamento_geral)) {
            $scope.endividamentoGeral = (endividamento_geral * 100).toFixed(2) + " | " + ((1 - endividamento_geral) * 0.4 * perc_bal).toFixed(2);
        } else {
            $scope.endividamentoGeeral = 0;
        }

        endividamento_geral = (1 - endividamento_geral);

        if (isNaN(endividamento_geral)) {
            endividamento_geral = 0;
        }



        limite += perc_bal * 0.4 * endividamento_geral;

        var composicao_endividamento = $scope.m / ($scope.m + $scope.exigivelLongoPrazo);

        $scope.composicaoEndividamento = (composicao_endividamento * 100).toFixed(2) + " | " + ((1 - composicao_endividamento) * perc_bal * 0.2).toFixed(2);

        composicao_endividamento = 1 - composicao_endividamento;

        if (isNaN(composicao_endividamento)) {
            composicao_endividamento = 0;
        }

        limite += perc_bal * 0.2 * composicao_endividamento;


        var retorno_sobre_vendas = $scope.al / $scope.ac;

        if (isNaN(retorno_sobre_vendas)) {
            retorno_sobre_vendas = 0;
        }

        $scope.retornoSobreVendas = (retorno_sobre_vendas * 100).toFixed(2);

        limite += perc_bal * 0.2 * retorno_sobre_vendas;

        var indice_cobertura_juros = $scope.aj / $scope.ag;

        if (isNaN(indice_cobertura_juros)) {
            indice_cobertura_juros = 0;
        }

        $scope.indiceCoberturaJuros = (indice_cobertura_juros * 100).toFixed(2);

        limite += perc_bal * 0.1 * indice_cobertura_juros;

        var margem_bruta = $scope.ae / $scope.ac;

        if (isNaN(margem_bruta)) {
            margem_bruta = 0;
        }

        $scope.margemBruta = (margem_bruta * 100).toFixed(2);

        limite += perc_bal * 0.3 * margem_bruta;


        var fat_in = $scope.impostoRenda / media_faturamento;
        fat_in--;
        if (isNaN(fat_in))
            fat_in = 0;

        var perc_fin = 1 + parseInt(fat_in);

        var cons_in = $scope.estoqueConsignado / media_faturamento;
        cons_in--;
        if (isNaN(cons_in))
            cons_in = 0;

        perc_fin += parseInt(cons_in);

        var hip = $scope.hipoteca / media_faturamento;
        hip--;
        if (isNaN(hip))
            hip = 0;

        perc_fin += parseInt(hip);

        var porc_fin = porcentagens[Math.min(porcentagens.length - 1, 1 + perc_fin)];

        var diff = Math.max(porc_fin - porcentagem, 0);

        limite += media_faturamento * diff / 100;

        var estf = media_faturamento * 0.03;

        if ($scope.eop) {
            limite += estf * 0.4;
        }

        if ($scope.rde) {
            limite += estf * 0.4;
        }

        if ($scope.fb) {
            limite += estf * 0.2;
        }

        estf = media_faturamento * 0.02;

        if ($scope.pco) {
            limite += estf * 0.2;
        }

        if ($scope.rco) {
            limite += estf * 0.2;
        }


        if ($scope.ccs) {
            limite += estf * 0.1;
        }

        if ($scope.her) {
            limite += estf * 0.1;
        }

        estf = media_faturamento * 0.03;

        var tra = $scope.tempoRamoAtual;

        if (tra > 5) {
            limite += estf * 0.5;
        } else if (tra > 2) {
            limite += estf * 0.3;
        }

        if ($scope.pee) {
            limite += estf * 0.2;
        }

        if ($scope.ane) {
            limite += estf * 0.1;
        }

        if ($scope.toc) {
            limite += estf * 0.1;
        }

        estf = media_faturamento * 0.02;

        if ($scope.cpr) {
            limite += estf * 0.2;
        }

        if ($scope.pch) {
            limite += estf * 0.4;
        }

        if ($scope.rai) {
            limite += estf * 0.4;
        }

        if ($scope.trs) {
            limite += estf * 0.1;
        }

        var limite_final = limite;



        //===============================================



        $scope.mediaFaturamentoR = (media_faturamento).toFixed(2);

        var limite_solicitado = limite_final * 0.1;
        limite_final *= 0.8;

        var score = $scope.scoreR;
        if (isNaN(score))
            score = 0;

        limite_solicitado *= score / 900;

        if ($scope.leR) {
            limite_solicitado *= 0.95;
        }

        if ($scope.cfR) {

            var rf = $scope.cfQR;
            if (isNaN(rf))
                rf = 0;

            rf /= 12;
            rf /= 10;

            limite_solicitado = (limite_solicitado + rf) / 2;
            limite_solicitado = Math.max(0, limite_solicitado);

        }

        $scope.limiteMensal = (limite_solicitado + limite_final).toFixed(2);

        var solicitacaoLimite = $scope.slR;

        if (solicitacaoLimite == 0) {

            limite_solicitado *= 0.9;

        }



        var dif = Math.abs($scope.clienteDesdeR - (data.getYear() + 1900));
        if (!isNaN(dif)) {
            if (dif > 10) {
                limite_solicitado *= 1.03;
            } else if (dif > 5) {
                limite_solicitado *= 1.01;
            }
        }

        if ($scope.liR) {
            limite_solicitado *= 1.03;
        }


        $scope.limiteMensalR2 = (limite_solicitado + limite_final).toFixed(2);

        if ($scope.sllR) {
            limite_solicitado *= 1.04;
        }

        limite_final += limite_solicitado;

        limite_final += $scope.cartaFianca;


        if (limite_final > fat_perc) {

            limite_final = fat_perc;

        }

        limite_final += $scope.hipoteca;

        //===============================================

        var fd = limite_final * 100 / media_faturamento;

        var class_cli = 0;

        for (; porcentagens[class_cli] < fd && class_cli < porcentagens.length; class_cli++)
        ;

        $scope.resultado = limite_final.toFixed(2);


    }

    $scope.$watch(function(newValue, oldValue) {

        calcular();

    });

})
rtc.controller("crtAcompanharAtividades", function($scope, usuarioService, tarefaService) {

    $scope.tarefas = [];
    $scope.carregando = true;

    $scope.observacao_tarefa = { observacao: "", porcentagem: 1, _classe: "ObservacaoTarefa" };

    $scope.addObservacao = function(tarefa) {

        if ($scope.observacao_tarefa.observacao === "") {
            msg.alerta("Digite uma observacao");
            return;
        }

        $scope.observacao_tarefa.observacao = formatTextArea($scope.observacao_tarefa.observacao);

        var c = tarefa.porcentagem_conclusao;
        var dif = $scope.observacao_tarefa.porcentagem - c;
        $scope.observacao_tarefa.porcentagem = dif;
        var tar = angular.copy(tarefa);
        tarefaService.addObservacao(tar, $scope.observacao_tarefa, function(f) {

            if (f.sucesso) {

                tarefa.id = f.o.tarefa.id;
                tarefa.observacoes[tarefa.observacoes.length] = $scope.observacao_tarefa;

                msg.alerta("Operacao efetuada com sucesso");

                $scope.observacao_tarefa = { observacao: "", porcentagem: 1, _classe: "ObservacaoTarefa" };

            } else {

                msg.erro("Falha ao efetuar operacao");

            }


        })

    }

    usuarioService.getTarefasSolicitadas(function(tt) {

        var usuarios = [];
        var grupos = [];

        lbl:
            for (var i = 0; i < tt.tarefas.length; i++) {
                var t = tt.tarefas[i];
                for (var j = 0; j < usuarios.length; j++) {
                    if (usuarios[j] === t.id_usuario) {
                        grupos[j][grupos[j].length] = t;
                        continue lbl;
                    }
                }
                usuarios[usuarios.length] = t.id_usuario;
                grupos[grupos.length] = [t];
            }
        for (var i = 0; i < grupos.length; i++) {
            grupos[i] = {
                id_usuario: grupos[i][0].id_usuario,
                nome_usuario: grupos[i][0].nome_usuario,
                id_empresa: grupos[i][0].id_empresa,
                nome_empresa: grupos[i][0].nome_empresa,
                lista: createList(grupos[i], 1, 7, "titulo")
            };
        }

        $scope.tarefas = createList(grupos, 1, 7, "nome_usuario");
        $scope.carregando = false;


    })

})
rtc.controller("crtFechamentoCaixa", function($scope, movimentoService, notaService, baseService, fechamentoCaixaService, bancoService, movimentosFechamentoService) {

    $scope.fechamentos = createAssinc(fechamentoCaixaService, 1, 5, 10);
    assincFuncs(
        $scope.fechamentos,
        "fechamento_caixa", ["id", "valor", "data", "banco.codigo", "banco.nome", "banco.saldo"]);
    $scope.fechamentos.attList();

    $scope.movimentos = createAssinc(movimentosFechamentoService, 1, 12, 15);
    assincFuncs(
        $scope.movimentos,
        "movimento", ["data", "id", "valor", "juros", "descontos", "saldo_anterior", "operacao.nome", "historico.nome", "visto"]);

    $scope.bancos = [];

    $scope.banco = null;
    $scope.fechamento = null;
    $scope.nota = null;

    $scope.carregando = false;
    $scope.relatorio = "";
    $scope.gerarRelatorio = function() {

        $scope.carregando = true;
        bancoService.getRelatorioFechamento($scope.banco, function(r) {

            $scope.relatorio = r.relatorio;
            $scope.carregando = false;
            $("#mdlRelatorio").modal("show");

        })

    }

    $scope.podeFechar = function(f) {

        if (Math.abs(f.banco.saldo - f.valor) < 0.1) {
            return true;
        }
        return false;

    }

    $scope.setVisto = function(mov) {

        movimentoService.setVisto(mov, function(s) {
            if (s.sucesso) {

            } else {
                msg.erro("Problema ao vistar");
            }
        });

    }

    $scope.getTotalNota = function() {

        var total = 0;

        for (var i = 0; i < $scope.nota.produtos.length; i++) {

            var p = $scope.nota.produtos[i];

            total += p.valor_total;

        }

        return total;

    }

    $scope.getNota = function(mov) {

        if (mov.vencimento === null) {
            msg.alerta("A nao foi encontrada nota");
            return;
        }

        if (mov.vencimento.nota === null) {
            msg.alerta("A nao foi encontrada nota");
            return;
        }

        $scope.setNota(mov.vencimento.nota);

    }

    $scope.setNota = function(nota) {

        $scope.nota = nota;
        $scope.nota.calcular_valores = false;

        $scope.nota.data_emissao_texto = toTime($scope.nota.data_emissao);


        notaService.getProdutos(nota, function(p) {

            nota.produtos = p.produtos;

            notaService.getVencimentos(nota, function(v) {

                nota.vencimentos = v.vencimentos;

                for (var i = 0; i < nota.vencimentos.length; i++) {

                    nota.vencimentos[i].data_texto = toDate(nota.vencimentos[i].data);

                }

            })

            $("#nota").modal('show');

        })

    }

    $scope.setBanco = function(banco) {

        $scope.banco = banco;

        bancoService.getFechamento($scope.banco, function(f) {

            $scope.fechamento = f.fechamento;

        })

        movimentosFechamentoService.banco = $scope.banco;
        $scope.movimentos.attList();

    }

    fechamentoCaixaService.getBancosFechar(function(e) {

        $scope.bancos = e.bancos;

        if ($scope.bancos.length > 0) {

            $scope.setBanco($scope.bancos[0]);

        }

    })

    $scope.mergeFechamento = function() {

        baseService.merge($scope.fechamento, function(s) {

            if (s.sucesso) {

                msg.alerta("Banco " + $scope.banco.nome + ", fechado com sucesso até a data atual, o sistema ira atualizar a pagina automaticamente.");
                document.location.reload();

            } else {

                msg.erro('Houve um problema ao efetuar a operacao: '.s.mensagem);

            }


        })

    }

})
rtc.controller("crtRelacaoCliente", function($scope, relacaoClienteService, baseService) {


    $scope.relacaoCliente = null;
    $scope.contatos = [];

    $scope.contato_novo = null;
    $scope.contato = null;

    $scope.clientes = createAssinc(relacaoClienteService, 1, 5, 10);
    assincFuncs(
        $scope.clientes,
        "cliente", ["codigo", "email.endereco", "razao_social", "cnpj", "cpf", "empresa.nome"]);
    $scope.clientes.attList();


    $scope.atividade = null;

    relacaoClienteService.getContato(function(c) {

        $scope.contato_novo = c.contato;
        $scope.contato = angular.copy(c.contato);

    })

    $scope.novoContato = function() {

        $scope.contato = angular.copy($scope.contato_novo);

    }

    $scope.mergeContato = function(c) {

        c.descricao = formatTextArea(c.descricao);
        c.relacao = $scope.relacaoCliente;

        baseService.merge(c, function(r) {
            if (r.sucesso) {

                $scope.contato = r.o;

                msg.alerta("Operacao efetuada com sucesso.");


            } else {
                msg.erro("Problema ao efetuar operacao. ");
            }
        });

    }

    relacaoClienteService.getAtividadeUsuarioClienteAtual(function(r) {

        $scope.atividade = r.atividade;

    })

    $scope.setRelacaoCliente = function(r) {

        $scope.relacaoCliente = r;

        relacaoClienteService.getContatos(r, function(c) {

            $scope.contatos = c.contatos;

        })

    }

})
rtc.controller("crtCobranca", function($scope, $timeout, tarefaService) {

    $scope.cobrancas = [];
    $scope.cobrar = false;

    var att = function() {
        tarefaService.getTarefasAtivas(function(t) {

            $scope.cobrancas = t.tarefas;
            if ($scope.cobrancas.length > 0) {
                $scope.cobrar = true;
            } else {
                $scope.cobrar = false;
            }

        })
    }


    $timeout(function() {
        att();
    }, 10000);

})
rtc.controller("crtTarefas", function($scope, $sce, tarefaService, observacaoTarefaService, usuarioService, tipoTarefaService, empresaService) {

    $scope.tarefas = {};

    $scope.empresas = [];
    $scope.empresa = null;

    $scope.tipos_tarefa = [];
    $scope.tipos_tarefa_usuario = [];

    $scope.tipo_tarefa_usuario = null;
    $scope.tipo_tarefa = null;

    $scope.recorrencia = 0;

    $scope.usuario = null;

    $scope.obs_padrao = "";

    $scope.empresarial = false;
    $scope.tarefa_novo = null;
    $scope.tarefa = null;
    $scope.lista_tarefas = [];

    $scope.tarefa_principal = null;

    $scope.observacao_tarefa = {};


    $scope.toHTML = function(h) {

        return $sce.trustAsHtml(h);

    }


    $scope.todos = function() {

        usuarioService.filtro_base = "";
        $scope.usuarios.attList();

    }

    $scope.start = function(tarefa) {

        tarefaService.start(tarefa, function(r) {
            tarefa.start = r.o.start;
            tarefa.intervalos_execucao = r.o.intervalos_execucao;
        });

    }

    $scope.pause = function(tarefa) {

        tarefaService.pause(tarefa, function(r) {
            tarefa.start = r.o.start;
            tarefa.intervalos_execucao = r.o.intervalos_execucao;
        });

    }

    $scope.finish = function(tarefa) {

        tarefaService.finish(tarefa, function(r) {

            var ts = [];

            for (var i = 0; i < $scope.lista_tarefas.length; i++) {
                var t = $scope.lista_tarefas[i];
                if (t.id !== tarefa.id) {
                    ts[ts.length] = t;
                }
            }

            $scope.tarefas = createList(ts, 1, 7, "descricao");
            $scope.tarefa_principal = ts[0];
            $scope.lista_tarefas = ts;

        });

    }

    $scope.novaObservacaoTarefa = function() {

        observacaoTarefaService.getObservacaoTarefa(function(o) {

            $scope.observacao_tarefa = o.observacao_tarefa;
            $scope.observacao_tarefa.observacao = $scope.observacao_padrao;

        })

    }

    $scope.pf = function(tarefa) {

        if (typeof tarefa.tipo_tarefa["porcentagem_fixa"] !== 'undefined') {
            var base = 0;
            for (var i = 0; i < $scope.tarefa.observacoes.length; i++) {
                base += $scope.tarefa.observacoes[i].porcentagem;
            }

            $scope.observacao_tarefa.porcentagem = base + tarefa.tipo_tarefa["porcentagem_fixa"];
            return tarefa.tipo_tarefa["porcentagem_fixa"];
        } else {
            return 0;
        }

    }

    $scope.addObservacao = function() {

        if ($scope.observacao_tarefa.observacao === "") {
            msg.alerta("Digite uma observacao");
            return;
        }

        $scope.observacao_tarefa.observacao = formatTextArea($scope.observacao_tarefa.observacao);

        var c = $scope.tarefa.porcentagem_conclusao;
        var dif = $scope.observacao_tarefa.porcentagem; // - c
        $scope.observacao_tarefa.porcentagem = dif;

        tarefaService.addObservacao($scope.tarefa, $scope.observacao_tarefa, function(f) {

            if (f.sucesso) {

                $scope.tarefa = f.o.tarefa;

                msg.alerta("Operacao efetuada com sucesso");

                if (tarefa.porcentagem_conclusao >= 100) {

                    var ts = [];

                    for (var i = 0; i < $scope.lista_tarefas.length; i++) {
                        var t = $scope.lista_tarefas[i];
                        if (t.id !== $scope.tarefa.id) {
                            ts[ts.length] = t;
                        }
                    }

                    $scope.tarefas = createList(ts, 1, 7, "descricao");
                    $scope.tarefa_principal = ts[0];
                    $scope.lista_tarefas = ts;

                }

                observacaoTarefaService.getObservacaoTarefa(function(o) {

                    $scope.observacao_tarefa = o.observacao_tarefa;

                })

            } else {

                msg.erro("Falha ao efetuar operacao, Verifique o seu Cargo com a Equipe de T.I");

            }


        })

    }

    $scope.usuarios = createAssinc(usuarioService, 1, 5, 10);
    assincFuncs(
        $scope.usuarios,
        "usuario", ["id", "email_usu.endereco", "nome", "cpf", "rg", "login"], "filtroUsuarios");

    empresaService.getGrupoEmpresarial(function(f) {

        $scope.empresas = f.grupo;

        if ($scope.empresas.length > 0) {

            $scope.setEmpresa($scope.empresas[0]);

        }

    })


    $scope.num = function(vector) {

        var a = [];

        for (var i = 0; i < vector.length; i++) {

            a[a.length] = i;

        }

        return a;

    }

    $scope.setTarefa = function(tarefa) {

        $scope.tarefa = tarefa;
        observacaoTarefaService.getObservacaoTarefa(function(o) {

            $scope.observacao_tarefa = o.observacao_tarefa;

            tipoTarefaService.getObservacaoPadrao($scope.tarefa, function(t) {

                $scope.observacao_padrao = t.observacao.split("<br>").join("\n");

                $scope.observacao_tarefa.observacao = $scope.observacao_padrao;

            })

            tarefaService.getOpcoes($scope.tarefa, function(r) {

                $scope.tarefa.opcoes = r.opcoes;


            })

        })
    }

    tipoTarefaService.getTiposTarefaUsuario(function(t) {

        $scope.tipos_tarefa_usuario = t.tipos_tarefa;

        if (t.tipos_tarefa.length > 0) {
            $scope.tipo_tarefa_usuario = t.tipos_tarefa[0];
        }

    })

    tarefaService.getTarefasAtivas(function(t) {

        $scope.tarefas = createList(t.tarefas, 1, 7, "descricao");
        $scope.tarefa_principal = t.tarefas[0];
        $scope.lista_tarefas = t.tarefas;

    })

    tarefaService.getTarefa(function(t) {

        $scope.tarefa = t.tarefa;
        $scope.tarefa_novo = angular.copy(t.tarefa);

    })

    $scope.novaTarefa = function() {

        $scope.tarefa = angular.copy($scope.tarefa_novo);

    }

    $scope.setUsuario = function(u) {

        $scope.usuario = u;

    }

    $scope.setTipoTarefaUsuario = function(t) {

        $scope.tipo_tarefa_usuario = t;

    }

    $scope.setTipoTarefa = function(t) {

        $scope.tipo_tarefa = t;
        usuarioService.empresa = $scope.empresa;

        var filtro = "usuario.id_cargo IN (";

        var a = false;
        for (var i = 0; i < t.cargos.length; i++) {
            if (a)
                filtro += ",";
            filtro += t.cargos[i].id;
            a = true;
        }

        if (!a) {
            filtro += '0';
        }

        filtro += ")";

        if (!a) {

            filtro += " AND false";

        }

        usuarioService.filtro_base = filtro;
        $scope.usuario = null;

        $scope.tarefa.prioridade = $scope.tipo_tarefa.prioridade;

    }

    $scope.setEmpresa = function(emp) {

        $scope.empresa = emp;

        tipoTarefaService.empresa = $scope.empresa;
        tipoTarefaService.getTiposTarefa(function(t) {

            $scope.tipos_tarefa = t.tipos_tarefa;
            if ($scope.tipos_tarefa.length > 0) {
                $scope.setTipoTarefa($scope.tipos_tarefa[0]);
            }

        })

    }

    $scope.salvarTarefaUsuario = function() {

        $scope.tarefa.titulo = formatTextArea($scope.tarefa.titulo);
        $scope.tarefa.descricao = formatTextArea($scope.tarefa.descricao);
        $scope.tarefa.tipo_tarefa = $scope.tipo_tarefa_usuario;
        $scope.tarefa.realocavel = false;

        tarefaService.atribuirTarefaUsuarioSessao($scope.tarefa, function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso");

                tarefaService.getTarefasAtivas(function(t) {

                    $scope.tarefas = createList(t.tarefas, 1, 5, "descricao");
                    $scope.tarefa_principal = t.tarefas[0];

                })

            } else {

                msg.erro("Problema ao efetuar operacao");

            }

        })

    }

    $scope.salvarTarefa = function() {

        $scope.tarefa.titulo = formatTextArea($scope.tarefa.titulo);
        $scope.tarefa.descricao = formatTextArea($scope.tarefa.descricao);

        $scope.tarefa.tipo_tarefa = $scope.tipo_tarefa;
        $scope.tarefa.realocavel = $scope.empresarial;

        if ($scope.empresarial) {

            tarefaService.atribuirTarefaEmpresa($scope.empresa, $scope.tarefa, function(r) {

                if (r.sucesso) {

                    msg.alerta("Operacao efetuada com sucesso");

                    tarefaService.getTarefasAtivas(function(t) {

                        $scope.tarefas = createList(t.tarefas, 1, 5, "descricao");

                    })

                } else {

                    msg.erro("Problema ao efetuar operacao");

                }

            })

        } else {

            tarefaService.atribuirTarefaUsuario($scope.usuario, $scope.tarefa, function(r) {

                if (r.sucesso) {

                    msg.alerta("Operacao efetuada com sucesso");

                } else {

                    msg.erro("Problema ao efetuar operacao");

                }

            })

        }

    }


})


rtc.controller("crtGerenciador2", function($scope, $interval, gerenciadorService, gerenciadorEmailService) {


    $scope.filtroEmail = new Date().getTime();

    $scope.emails = null;

    $scope.servidor = null;

    gerenciadorEmailService.getEnviosEmails($scope.filtroEmail, function(e) {

        $scope.emails = createList(e.envios, 1, 15);


    })

    $scope.listaEnvios = createAssinc(gerenciadorEmailService, 1, 10, 6);
    assincFuncs(
        $scope.listaEnvios,
        "emails_lista", ["id", "email"], "filtro");
    $scope.listaEnvios.attList();

    gerenciadorEmailService.getLogsServidor($scope.filtroEmail, function(e) {

        $scope.servidor = createList(e.logs, 1, 15);


    })

    $scope.trocaFiltro = function() {

        gerenciadorEmailService.getEnviosEmails($scope.filtroEmail, function(e) {

            $scope.emails = createList(e.envios, 1, 15);


        })

        gerenciadorEmailService.getLogsServidor($scope.filtroEmail, function(e) {

            $scope.servidor = createList(e.logs, 1, 15);


        })


    }


});

rtc.controller("crtGerenciador", function($scope, $interval, gerenciadorService, gerenciadorEmailService) {

    $scope.ativos = null;
    $scope.gerenciadorUsuarios = null;
    $scope.gerenciadorEstat = null;

    $scope.qtdAcessos = 0;
    $scope.maximoUsuariosOnline = 0;
    $scope.pontosGrafico = [];
    $scope.intervaloEstat = 3600000; //1 hora inicialmente;

    $scope.pontosGraficoInfoUsu = [];
    $scope.intervaloInfoUsu = 3600000;
    $scope.selecionado = null;
    $scope.informacoes = null;

    $scope.numero_empresas = 0;

    $scope.grupo = false;
    $scope.total = true;

    $scope.filtroEmail = new Date().getTime() - 24 * 60 * 60 * 1000;


    $scope.emails = null;

    $scope.servidor = null;

    gerenciadorEmailService.getEnviosEmails($scope.filtroEmail, function(e) {

        $scope.emails = createList(e.envios, 1, 15);


    })

    $scope.listaEnvios = createAssinc(gerenciadorEmailService, 1, 10, 6);
    assincFuncs(
        $scope.listaEnvios,
        "emails_lista", ["id", "email"], "filtro");
    $scope.listaEnvios.attList();

    gerenciadorEmailService.getLogsServidor($scope.filtroEmail, function(e) {

        $scope.servidor = createList(e.logs, 1, 15);


    })

    $scope.trocaFiltro = function() {

        gerenciadorEmailService.getEnviosEmails($scope.filtroEmail, function(e) {

            $scope.emails = createList(e.envios, 1, 15);


        })

        gerenciadorEmailService.getLogsServidor($scope.filtroEmail, function(e) {

            $scope.servidor = createList(e.logs, 1, 15);


        })


    }


    $scope.outrasEmpresas = function() {

        $scope.grupo = false;
        $scope.total = false;
        $scope.attGrupo();

    }

    $scope.isOutrasEmpresas = function() {

        return !$scope.grupo && !$scope.total;

    }

    $scope.isGrupoEmpresarial = function() {

        return $scope.grupo && !$scope.total;

    }

    $scope.isTotal = function() {

        return $scope.total;

    }

    $scope.grupoEmpresarial = function() {

        $scope.grupo = true;
        $scope.total = false;
        $scope.attGrupo();

    }

    $scope.total = function() {

        $scope.grupo = false;
        $scope.total = true;
        $scope.attGrupo();

    }

    $scope.attGrupo = function() {


        $scope.gerenciadorUsuarios.grupo = $scope.grupo;
        $scope.gerenciadorEstat.grupo = $scope.grupo;
        $scope.gerenciadorUsuarios.total = $scope.total;
        $scope.gerenciadorEstat.total = $scope.total;

        $scope.ativos.attList();
        $scope.attEstat();
        $scope.attInfoUsu();


    }

    $scope.selecionar = function(atv) {

        $scope.selecionado = atv;
        $scope.attInfoUsu();


    }

    gerenciadorService.getGerenciador(function(g) {

        $scope.gerenciadorUsuarios = g.gerenciador;
        $scope.gerenciadorEstat = angular.copy(g.gerenciador);
        gerenciadorService.gerenciador = $scope.gerenciadorUsuarios;

        $scope.ativos = createAssinc(gerenciadorService, 1, 10, 6);
        $scope.ativos["posload"] = function(els) {

            if ($scope.selecionado === null) {
                $scope.selecionar(els[0]);
            } else {
                for (var i = 0; i < els.length; i++) {
                    if (els[i].id === $scope.selecionado.id) {
                        $scope.selecionado = els[i];
                    }
                }
            }

        }
        assincFuncs(
            $scope.ativos,
            "a", ["u.id", "u.nome", "e.id", "e.nome", "e.cnpj"], "filtro", false);

        $scope.ativos.attList();

        $scope.attEstat();

    })

    $scope.reduzirIntervaloEstat = function() {

        $scope.intervaloEstat = parseInt($scope.intervaloEstat / 2);

        $scope.attEstat();

    }

    $scope.attUsuarios = function() {

        $scope.ativos.attList();
        $scope.attInfoUsu();

    }

    $scope.attInfoUsu = function() {

        gerenciadorService.getNumeroEmpresas($scope.gerenciadorEstat, function(q) {

            $scope.numero_empresas = q.qtd;

        })

        gerenciadorService.getAtividadeUsuario($scope.selecionado, $scope.intervaloInfoUsu, function(p) {

            $scope.pontosGraficoInfoUsu = [];

            for (var i = 0; i < p.pontos.length; i++) {

                var momento = $scope.intervaloInfoUsu * i + $scope.gerenciadorUsuarios.periodo_inicial;
                var momentoFinal = momento + $scope.intervaloInfoUsu;
                var titulo = toTime(momento).split(" ")[1] + " a " + toTime(momentoFinal).split(" ")[1];

                $scope.pontosGraficoInfoUsu[$scope.pontosGraficoInfoUsu.length] = { nome: titulo, valor: p.pontos[i] };

            }


        })

        gerenciadorService.getInformacoesUsuario($scope.selecionado, function(inf) {

            $scope.informacoes = inf;
            $scope.informacoes.logs = createList($scope.informacoes.logs, 1, 5, "log.descricao");

        })


    }

    $scope.reduzirIntervaloInfoUsu = function() {

        $scope.intervaloEstat = parseInt($scope.intervaloInfoUsu / 2);

        $scope.attInfoUsu();

    }

    $scope.aumentarIntervaloInfoUsu = function() {

        $scope.intervaloEstatInfoUsu = parseInt($scope.intervaloEstat * 2);

        $scope.attInfoUsu();

    }

    $scope.aumentarIntervaloEstat = function() {

        $scope.intervaloEstat = Math.min(parseInt($scope.intervaloEstat * 2), 86400000);

        $scope.attEstat();

    }

    var interval = false;

    $scope.attEstat = function() {

        gerenciadorService.getCount('', function(r) {
            $scope.qtdAcessos = r.qtd;
        }, $scope.gerenciadorEstat);

        gerenciadorService.getMaximoUsuariosOnline($scope.gerenciadorEstat, function(r) {
            $scope.maximoUsuariosOnline = r.qtd;
        })

        gerenciadorService.getTempo_Usuarios($scope.gerenciadorEstat, $scope.intervaloEstat, function(p) {

            $scope.pontosGrafico = [];

            for (var i = 0; i < p.pontos.length; i++) {

                var momento = $scope.intervaloEstat * i + $scope.gerenciadorEstat.periodo_inicial;
                var momentoFinal = momento + $scope.intervaloEstat;
                var titulo = toTime(momento).split(" ").join("<br>") + " a " + toTime(momentoFinal).split(" ")[1];

                $scope.pontosGrafico[$scope.pontosGrafico.length] = { nome: titulo, valor: p.pontos[i] };

            }


        })

        if (!interval) {

            $interval(function() {

                $scope.ativos.attList();
                $scope.attEstat();
                $scope.attInfoUsu();

            }, 30000);

            interval = true;

        }

    }





});
rtc.controller("crtAtividade", function($scope, $timeout, $interval, atividadeService) {

    atividadeService.sinal();

    $interval(function() {
        atividadeService.sinal();
    }, 60000);

    $(document).click(function(e) {

        var x = e.clientX;
        var y = e.clientY;

        atividadeService.cliqueComum("Clique (" + x + "," + y + ")");

    })


    $(document).find("input[type=search]").each(function() {
        $(this).change(function() {
            atividadeService.pesquisar("Digitou: " + $(this).val());
        })
    })



});
rtc.controller("crtBanners", function($scope, bannerService, campanhaService, uploadService, empresaService, baseService) {

    $scope.banners = createAssinc(bannerService, 1, 5, 10);

    assincFuncs(
        $scope.banners,
        "banner", ["id", "data_inicial", "data_final", "tipo"]);

    $scope.campanhas = createAssinc(campanhaService, 1, 10, 10);
    assincFuncs(
        $scope.campanhas,
        "campanha", ["id", "nome", "inicio", "fim"], "filtroCampanhas");

    $scope.banner_novo = {};
    $scope.banner = {};
    $scope.bannerService = bannerService;

    $scope.clientes = [];

    $scope.setOrdem = function(banner) {

        bannerService.setOrdem(banner, function(r) {

            if (!r.sucesso) {

                msg.erro("Ocorreu um problema");

            }

        })

    }

    $scope.trocaEmpresa = function() {

        bannerService.getBanner(function(p) {
            $scope.banner_novo = p.banner;
        })

        $scope.novoBanner = function() {
            $scope.banner = angular.copy($scope.banner_novo);
        }

        campanhaService.empresa = bannerService.empresa;
        $scope.banners.attList();

    }

    empresaService.getEmpresasClientes(function(c) {

        $scope.clientes = c.clientes;
        if ($scope.clientes.length > 0) {
            bannerService.empresa = $scope.clientes[0];
            $scope.trocaEmpresa();
        }

    })

    $scope.data_atual = new Date().getTime();

    $scope.tipos_banner = ["Frontal", "Lateral", "Email Marketing", "Entrada Boas Vindas", "Email Modulo 0", "Cobranca Emocional", "Modulo 2", "Banner Lateral Inicio", "Redes Sociais", "Texto do Rodape", "Black Friday"];

    $("#uploaderHTML").change(function() {

        var arquivos = $(this).prop("files");

        for (var i = 0; i < arquivos.length; i++) {
            var sp = arquivos[i].name.split(".");
            if (sp[sp.length - 1] !== "html") {
                msg.alerta("Arquivo: " + arquivos[i].name + ", invalido");
                return;
            }
        }


        uploadService.upload(arquivos, function(arqs, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo");

            } else {

                $scope.arquivos = arqs;

                for (var i = 0; i < arquivos.length; i++) {
                    var reader = new FileReader();
                    reader["ii"] = i;
                    reader.onload = function(arquivo) {

                        var html = arquivo.target.result;

                        var json = DOMToJson(html);

                        json = JSON.stringify(json);


                        uploadService.uploadStr([json], function(arqs2, sucesso2) {

                            if (sucesso2) {

                                msg.alerta("Upload efetuado com sucesso");
                                $scope.banner.json = arqs2[0];

                            } else {

                                msg.alerta("Falha ao subir banner");

                            }


                        })




                    };
                    reader.readAsText(arquivos[i]);
                }

            }

        })

    });

    bannerService.getBanner(function(p) {
        $scope.banner_novo = p.banner;
    })

    $scope.novoBanner = function() {
        $scope.banner = angular.copy($scope.banner_novo);
    }

    $scope.setCampanha = function(campanha) {

        $scope.banner.campanha = campanha;

    }

    $scope.deleteCampanha = function() {

        $scope.banner.campanha = null;

    }

    $scope.setBanner = function(banner) {

        $scope.banner = banner;
        banner.html = "";

        bannerService.getHTML(banner, function(h) {

            banner.html = window.atob(h.html);

            $("#html_" + banner.id).html(banner.html);

        })

    }

    $scope.mergeBanner = function() {

        if ($scope.banner.json == null) {
            msg.erro("Realize o upload do arquivo");
            return;
        }
        $scope.banner.html = "";

        baseService.merge($scope.banner, function(r) {
            if (r.sucesso) {
                $scope.banner = r.o;

                msg.alerta("Operacao efetuada com sucesso");
                $scope.banners.attList();

            } else {
                msg.erro("Problema ao efetuar operacao. ");
            }
        });

    }
    $scope.deleteBanner = function() {
        $scope.banner.html = "";

        baseService.delete($scope.banner, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.banners.attList();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
        });
    }

})
rtc.controller("crtRelatorio", function($scope, relatorioService) {

    $scope.relatorios = [];
    $scope.gerado = null;
    $scope.carregando = false;

    $scope.modos = ["Igual a", "Maior que", "Menor que"];
    $scope.mn = [0, 1, 2];

    $scope.filhos = [];

    if (typeof rtc["relatorio"] !== 'undefined') {

        $scope.relatorio = rtc["relatorio"];


    }

    $scope.addOrdem = function(campo) {
        campo.ordem++;
    }

    $scope.removeOrdem = function(campo) {
        campo.ordem = Math.max(0, campo.ordem - 1);
    }

    $scope.inverteGroup = function(campo) {
        campo.agrupado = !campo.agrupado;
    }

    $scope.detalhes = function(item) {


        relatorioService.getFilhos(item, function(f) {

            $scope.filhos = f.filhos;
            $("#mdlFilhos").modal("show");

        })

    }
    $scope.xsd = "";
    $scope.gerarXsd = function() {
        $scope.carregando = true;
        $scope.prepararRelatorio();
        relatorioService.getXsd(function(x) {

            $scope.xsd = projeto + "/php/uploads/" + x.arquivo;
            $("#mdlXsd").modal('show');
            $scope.carregando = false;
        });

    }
    $scope.pdf = "";
    $scope.gerarPdf = function() {
        $scope.carregando = true;
        $scope.prepararRelatorio();
        relatorioService.getPdf(function(x) {

            $scope.pdf = x.pdf;
            $("#mdlPdf").modal('show');
            $scope.carregando = false;
        });

    }


    $scope.prepararRelatorio = function() {

        var order = "";
        var order_fields = [];
        for (var i = 0; i < $scope.relatorio.campos.length; i++) {

            var campo = $scope.relatorio.campos[i];

            if (campo.ordem > 0) {
                var j = 0
                for (; j < order_fields.length; j++) {
                    if (campo.ordem >= order_fields[j].ordem) {
                        for (var k = order_fields.length - 1; k >= j; k--) {
                            order_fields[k + 1] = order_fields[k];
                        }
                        break;
                    }
                }
                order_fields[j] = campo;
            }

            if (campo.possiveis.length > 0) {

                var sub = "";
                for (var j = 0; j < campo.possiveis.length; j++) {
                    var p = campo.possiveis[j];

                    if (p.selecionado) {

                        if (sub !== "") {
                            sub += " OR ";
                        }

                        sub += "k." + campo.nome + "='" + p.termo + "'";
                    }
                }
                if (sub !== "") {
                    campo.filtro = "(" + sub + ") ";
                } else {
                    campo.filtro = "";
                }
            } else if (campo.tipo === 'T') {


                campo.filtro = "k." + campo.nome + " like '%" + campo.texto + "%' ";

            } else if (campo.tipo === 'N') {

                if (campo.numero !== 0) {


                    campo.filtro = "k." + campo.nome;

                    if (campo.modo === 0) {
                        campo.filtro += "=";
                    } else if (campo.modo === 1) {
                        campo.filtro += ">";
                    } else if (campo.modo === 2) {
                        campo.filtro += "<";
                    }

                    campo.filtro += campo.numero + " ";

                } else {
                    campo.filtro = "";
                }

            } else if (campo.tipo === 'D') {


                campo.filtro = "(k." + campo.nome + " >= FROM_UNIXTIME(" + campo.inicio + "/1000) AND k." + campo.nome + " <= FROM_UNIXTIME(" + campo.fim + "/1000)) ";

            } else if (campo.tipo === 'DF') {


                campo.filtro = "(k." + campo.nome + " = FROM_UNIXTIME(" + campo.inicio + "/1000,'%Y-%m-%d') OR k." + campo.nome + "=FROM_UNIXTIME(" + campo.inicio + "/1000,'%d/%m/%Y')) ";

            }

        }

        for (var i = 0; i < order_fields.length; i++) {
            if (i > 0) {
                order += ",";
            }
            order += "k." + order_fields[i].nome;
        }

        $scope.relatorio.order = order;


        relatorioService.relatorio = $scope.relatorio;

    }

    $scope.getDadosAdcionais = function(linha) {

        if ($scope.relatorio.tem_dados_adcionais) {

            relatorioService.getDadosAdcionais(linha, function(d) {

                $("#dados_add").html(d.dados);
                $("#mdlDadosAdcionais").modal('show');

            })

        }

    }

    $scope.gerarRelatorio = function() {

        $scope.carregando = true;
        $scope.prepararRelatorio();

        $scope.gerado = createAssinc(relatorioService, 1, 20, 6);
        $scope.gerado.posload = function(els) {
            $scope.carregando = false;
        }
        $scope.gerado.attList();


        $("#mdlRelatorio").modal("show");

    }

    $scope.init = function() {

        var r = $scope.relatorio;

        for (var i = 0; i < r.campos.length; i++) {

            var campo = r.campos[i];
            if (typeof campo["ordem"] === 'undefined') {
                campo.ordem = 0;
            }
            if (campo.possiveis.length > 0) {

                for (var j = 0; j < campo.possiveis.length; j++) {

                    campo.possiveis[j] = { termo: campo.possiveis[j], selecionado: true };

                }

            } else if (campo.tipo === 'T') {

                campo.texto = "";

            } else if (campo.tipo === 'N') {

                campo.modo = 0;
                campo.numero = 0;

            } else if (campo.tipo === 'D') {

                if (typeof campo["inicio"] === 'undefined') {
                    campo.inicio = new Date().getTime();
                    campo.fim = new Date().getTime() + (24 * 60 * 60 * 1000);
                }

            }

        }

    }


    relatorioService.getRelatorios(function(f) {

        $scope.relatorios = f.relatorios;

        if (typeof $scope["relatorio"] !== 'undefined') {

            for (var i = 0; i < $scope.relatorios.length; i++) {
                if (($scope.relatorios[i].id + "") === ($scope.relatorio + "")) {
                    $scope.relatorio = $scope.relatorios[i];
                    $scope.init();
                    break;
                }
            }

        }

    })




})
rtc.controller("crtEmpresaConfig", function($scope, $timeout, empresaService, sistemaService, cidadeService, baseService, uploadService) {

    $scope.empresa_atual = null;
    $scope.empresas_clientes = [];
    empresaService.getEmpresasClientes(function(e) {
        $scope.empresas_clientes = e.clientes;
        empresaService.getEmpresa(function(e) {
            var a = null;
            for (var i = 0; i < $scope.empresas_clientes.length; i++) {
                if ($scope.empresas_clientes[i].id === e.empresa.id) {
                    a = $scope.empresas_clientes[i];
                    break;
                }
            }
            if (a === null) {
                a = e.empresa;
                $scope.empresas_clientes[$scope.empresas_clientes.length] = a;
            }
            $scope.empresa_atual = a;
        })
    })
    $scope.trocaEmpresa = function() {

        $scope.setEmpresa($scope.empresa_atual);

    }

    $scope.empresa = null;
    $scope.filiais = [];
    $scope.parametros_emissao = null;
    $scope.status = null;
    $scope.estados = [];
    $scope.cidades = [];
    $scope.estado = null;
    $scope.marketings = [];
    $scope.marketing = null;

    $scope.adm = null;
    $scope.adms = [];

    $scope.tabelaLogistica = null;



    $scope.certificado_comerc = '';

    $("#uploaderCertificadoComerc").change(function() {

        var ext = ['pdf', 'txt', 'doc', 'jpg', 'png'];
        var pre_arquivos = $(this).prop("files");
        var arquivos = [];
        var e = [];

        var total_size = 0;

        for (var i = 0; i < pre_arquivos.length; i++) {
            for (var j = 0; j < ext.length; j++) {
                if (ext[j] === pre_arquivos[i].name.split('.')[pre_arquivos[i].name.split('.').length - 1]) {
                    arquivos[arquivos.length] = pre_arquivos[i];
                    e[e.length] = pre_arquivos[i].name.split('.')[pre_arquivos[i].name.split('.').length - 1];
                    total_size += pre_arquivos[i].size;
                    break;
                }
            }
        }

        if (arquivos.length === 0) {
            msg.alerta("O Certificado esta em forma invalido, tente tirar uma foto");
            return;
        }

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo");

            } else {

                for (var i = 0; i < arquivos.length; i++) {

                    $scope.certificado_comerc = arquivos[i];

                }

                msg.alerta("Upload feito com sucesso");
            }

        })

    })

    $("#uploaderCertificadoDigital").change(function() {

        var ext = ['pfx'];
        var pre_arquivos = $(this).prop("files");
        var arquivos = [];
        var e = [];

        var total_size = 0;

        for (var i = 0; i < pre_arquivos.length; i++) {
            for (var j = 0; j < ext.length; j++) {
                if (ext[j] === pre_arquivos[i].name.split('.')[pre_arquivos[i].name.split('.').length - 1]) {
                    arquivos[arquivos.length] = pre_arquivos[i];
                    e[e.length] = pre_arquivos[i].name.split('.')[pre_arquivos[i].name.split('.').length - 1];
                    total_size += pre_arquivos[i].size;
                    break;
                }
            }
        }

        if (arquivos.length === 0) {
            msg.alerta("O Certificado deve ser do tipo PFX A1");
            return;
        }

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo");

            } else {

                for (var i = 0; i < arquivos.length; i++) {

                    $scope.parametros_emissao.certificado = arquivos[i];

                }

                msg.alerta("Upload feito com sucesso");
            }

        })

    })

    $scope.setMarketing = function(mkt) {

        $scope.marketing = mkt;


    }

    $scope.setAdm = function(adm) {

        $scope.adm = adm;


    }

    $scope.setEmpresa = function(e) {

        if (e.tipo_empresa === 1) {

            empresaService.getTabelaLogistica(e, function(r) {

                if (r.sucesso) {

                    $scope.tabelaLogistica = r.tabela;

                }

            })

        } else {

            $scope.tabelaLogistica = null;

        }


        $scope.empresa = e;

        $scope.filiais = [];
        $scope.filiais[$scope.filiais.length] = e;

        empresaService.getFiliais(function(rr) {

            for (var i = 0; i < rr.filiais.length; i++) {
                if (rr.filiais[i].id === $scope.empresa.id)
                    continue;
                $scope.filiais[$scope.filiais.length] = rr.filiais[i];

            }

        })

        sistemaService.getMarketings(function(r) {

            $scope.marketings = r.marketings;

            empresaService.getMarketing($scope.empresa, function(m) {

                $scope.marketing = m.marketing;

                if ($scope.marketing !== null) {
                    equalize($scope, "marketing", $scope.marketings);
                }
                $scope.marketings[$scope.marketings.length] = null;

            })

        })

        sistemaService.getAdms(function(r) {

            $scope.adms = r.adms;

            empresaService.getAdm($scope.empresa, function(m) {

                $scope.adm = m.adm;

                if ($scope.adm !== null) {
                    equalize($scope, "adm", $scope.adms);
                }
                $scope.adms[$scope.adms.length] = null;

            })

        })


        empresaService.getParametrosEmissao($scope.empresa, function(e) {

            $scope.parametros_emissao = e.parametros_emissao;
            if ($scope.empresa !== null) {
                equalize($scope.empresa.endereco, "cidade", $scope.cidades);
                if (typeof $scope.empresa.endereco.cidade !== 'undefined') {
                    $scope.estado = $scope.empresa.endereco.cidade.estado;
                } else {
                    $scope.empresa.endereco.cidade = $scope.cidades[0];
                    $scope.estado = $scope.empresa.endereco.cidade.estado;
                }
            }

            empresaService.getStatusParametroEmissao($scope.parametros_emissao, function(s) {

                $scope.status = s.status;


            })

        })


    }

    empresaService.getEmpresa(function(r) {

        $scope.setEmpresa(r.empresa);

    })

    $scope.mergeEmpresa = function() {

        if ($scope.empresa.endereco.cidade == null) {
            msg.erro("Empresa sem cidade.");
            return;
        }

        var boas_vindas = false;

        if (typeof rtc["boas_vindas"] !== 'undefined') {

            boas_vindas = true;

        }


        if (boas_vindas) {

            if ($scope.certificado_comerc === '') {

                msg.erro("Precisamos do seu certificado para permitir o prosseguimento da operacao.");

                return;

            }
        }

        baseService.merge($scope.empresa, function(r) {
            if (r.sucesso) {

                $scope.empresa = r.o;

                if ($scope.tabelaLogistica != null) {

                    baseService.merge($scope.tabelaLogistica, function(rr) {

                    })

                }

                if (boas_vindas) {

                    if ($scope.certificado_comerc === '') {

                        msg.erro("Precisamos do seu certificado para permitir o prosseguimento da operacao.");

                        return;

                    }

                    empresaService.setCadastroAtualizadoBoasVindas($scope.empresa, function(rr) {

                        if (rr.sucesso) {

                            msg.alerta("Cadastro confirmado com sucesso, redirecionando para a finalizacao da compra");
                            $timeout(function() {

                                window.location.replace('carrinho-de-compras.php');

                            }, 1000);


                        } else {

                            msg.erro("Ocorreu um problema, tente mais tarde");

                        }

                    })

                    return;
                }

                empresaService.setMarketing($scope.empresa, $scope.marketing, function(rr) {


                })

                empresaService.setAdm($scope.empresa, $scope.adm, function(rr) {


                })

                equalize($scope.empresa.endereco, "cidade", $scope.cidades);
                if (typeof $scope.empresa.endereco.cidade !== 'undefined') {
                    $scope.estado = $scope.empresa.endereco.cidade.estado;
                } else {
                    $scope.empresa.endereco.cidade = $scope.cidades[0];
                    $scope.estado = $scope.empresa.endereco.cidade.estado;
                }

                baseService.merge($scope.parametros_emissao, function(rr) {
                    if (rr.sucesso) {
                        $scope.parametros_emissao = rr.o;

                        empresaService.getStatusParametroEmissao($scope.parametros_emissao, function(s) {

                            $scope.status = s.status;

                        })



                        msg.alerta("Operacao efetuada com sucesso. Relogue para surtir as alteracoes");




                    } else {
                        msg.erro("Problema ao efetuar operacao.#");
                    }
                });

            } else {
                msg.erro("Problema ao efetuar operacao. ");
            }
        });

    }

    cidadeService.getElementos(function(p) {
        var estados = [];
        var cidades = p.elementos;
        $scope.cidades = cidades;

        lbl:
            for (var i = 0; i < cidades.length; i++) {
                var c = cidades[i];
                for (var j = 0; j < estados.length; j++) {
                    if (estados[j].id === c.estado.id) {
                        estados[j].cidades[estados[j].cidades.length] = c;
                        c.estado = estados[j];
                        continue lbl;
                    }
                }
                c.estado["cidades"] = [c];
                estados[estados.length] = c.estado;
            }

        $scope.estados = estados;
        if ($scope.empresa !== null) {
            equalize($scope.empresa.endereco, "cidade", $scope.cidades);
            if (typeof $scope.empresa.endereco.cidade !== 'undefined') {
                $scope.estado = $scope.empresa.endereco.cidade.estado;
            } else {
                $scope.empresa.endereco.cidade = $scope.cidades[0];
                $scope.estado = $scope.empresa.endereco.cidade.estado;
            }
        }
    })

})



rtc.controller("crtCarrinhoFinal", function($scope, sistemaService, tabelaService, carrinhoService, pedidoService, formaPagamentoService, transportadoraService) {

    $scope.transportadoras = createAssinc(transportadoraService, 1, 3, 4);
    $scope.transportadoras.attList();
    assincFuncs(
        $scope.transportadoras,
        "transportadora", ["codigo", "razao_social"], "filtroTransportadoras");


    var transportadoraServiceEmpresa = angular.copy(transportadoraService);
    $scope.transportadorasEmpresa = createAssinc(transportadoraServiceEmpresa, 1, 3, 4);
    $scope.transportadorasEmpresa.attList();
    assincFuncs(
        $scope.transportadorasEmpresa,
        "transportadora", ["codigo", "razao_social"], "filtroTransportadorasEmpresa");


    $scope.atualizando_custo = false;
    $scope.pedidos = [];
    $scope.pedido_contexto = null;
    $scope.carrinho = [];
    $scope.carregando_frete = false;
    $scope.fretes = [];
    $scope.possibilidades = [
        { id: 0, prazo: 0, parcelas: 1, nome: "Antecipado" },
        { id: 1, prazo: 30, parcelas: 1, nome: null },
        { id: 2, prazo: 60, parcelas: 1, nome: null },
        { id: 3, prazo: 90, parcelas: 1, nome: null },
        { id: 4, prazo: 30, parcelas: 2, nome: null },
        { id: 5, prazo: 60, parcelas: 2, nome: null },
        { id: 6, prazo: 90, parcelas: 2, nome: null },
        { id: 7, prazo: 60, parcelas: 3, nome: null },
        { id: 8, prazo: 90, parcelas: 3, nome: null },
    ];

    carrinhoService.getCarrinho(function(c) {

        $scope.carrinho = c.carrinho;

    })

    $scope.pedfrete = null;

    $scope.voltarFrete = function() {

        $scope.pedfrete.frete_incluso = true;

    }

    $scope.tiqueFob = function(pedido) {

        pedido.frete_incluso = false;
        $scope.pedfrete = pedido;
        if (!pedido.frete_incluso) {

            $("#mdlFob").modal("show");

        }

    }

    $scope.abrirTransportadoras = function(pedido) {

        $scope.setPedidoContexto(pedido);

        var local = pedido.empresa;

        if (pedido.logistica !== null) {

            local = pedido.logistica;

        }

        transportadoraServiceEmpresa.empresa = local;
        $scope.transportadorasEmpresa.attList();

        $("#transportadoras").modal("show");

    }

    $scope.setFreteRedespacho = function(p) {

        $('#mdlPossibilidadesFrete').modal('hide');

        var total = 0;

        for (var i = 0; i < p.length; i++) {
            total += p[i].valor;
        }

        $scope.pedido_contexto.frete = parseFloat(total.toFixed(2));
        $scope.pedido_contexto.transportadora = p[0].transportadora;
        $scope.pedido_contexto.etapa_frete = 0;

        var fretes_intermediarios = [];
        for (var i = p.length - 2; i >= 1; i--) {
            if ($scope.pedido_contexto.etapa_frete === 0) {
                $scope.pedido_contexto.etapa_frete = i;
            }
            var fi = {
                _classe: "FreteIntermediario",
                id: 0,
                ordem: i,
                valor: p[i].valor,
                transportadora: p[i].transportadora,
                pedido: $scope.pedido_contexto,
                id_empresa_destino: p[i].local.id,
                local: p[i].local
            };
            fretes_intermediarios[fretes_intermediarios.length] = fi;
        }

        $scope.pedido_contexto.fretes_intermediarios = fretes_intermediarios;

        $scope.atualizaCustos($scope.pedido_contexto);

    }


    $scope.getNomeLocal = function(ponto) {

        if (typeof ponto.local['nome'] === 'undefined') {

            return ponto.local.razao_social;

        }

        return ponto.local.nome;

    }

    $scope.getTotal = function(pedido) {

        var total = 0;

        for (var i = 0; i < pedido.produtos.length; i++) {
            total += pedido.produtos[i].quantidade * (pedido.produtos[i].valor_base + pedido.produtos[i].frete + pedido.produtos[i].ipi + pedido.produtos[i].juros + pedido.produtos[i].icms);
        }

        return total;

    }

    $scope.finalizarPedido = function(pedido) {
        pedido.status_finalizacao = { valor: "Aguarde... O Sistema esta fechando seu pedido", classe: "btn-primary", final: false };

        sistemaService.finalizarCompraParceiros(pedido, function(r) {

            if (!r.sucesso) {

                msg.erro(r.mensagem);
                return;

            }

            var novo_carrinho = [];

            lbl:
                for (var i = 0; i < $scope.carrinho.length; i++) {

                    var it = $scope.carrinho[i];

                    for (var j = 0; j < pedido.produtos.length; j++) {

                        var p = pedido.produtos[j];

                        if (it.id === p.produto.id) {

                            continue lbl;

                        }

                    }

                    novo_carrinho[novo_carrinho.length] = it;

                }

            $scope.carrinho = novo_carrinho;
            var p = r.o;
            carrinhoService.setCarrinho($scope.carrinho, function(s) {

                pedido.status_finalizacao = { valor: "O Sistema esta gerando a cobranca, aguarde mais um pouco...", classe: "btn-outline-success", final: false };
                pedidoService.gerarCobranca(p, function(r) {
                    $("#finalizarCompraModal").modal('show');
                    if (r.sucesso) {
                        $("#finalizarCompra").html("Cobranca gerada com sucesso. <hr> " + r.retorno);
                    } else {
                        $("#finalizarCompra").html("Compra finalizada porem houve um problema ao gerar a cobranca");
                    }

                    pedido.cobranca_gerada = true;
                    pedido.status_finalizacao = { valor: "O Pedido foi realizado com sucesso !, verifique a confirmacao em seu email", classe: "btn btn-warning", final: true };
                })


            })



        })

    }


    $scope.getFormasPagamento = function(pedido) {

        formaPagamentoService.getFormasPagamento(pedido, function(f) {

            pedido.formas_pagamento = f.formas;

            if (pedido.forma_pagamento !== null) {

                equalize(pedido, "forma_pagamento", pedido.formas_pagamento);
            } else {
                pedido.forma_pagamento = f.formas_pagamento[0];
            }
        })

    }

    $scope.setTransportadora = function(t) {

        $scope.pedido_contexto.transportadora = t;
        $scope.atualizaCustos($scope.pedido_contexto);

    }


    $scope.setFretePedido = function(pedido, frete) {

        $scope.pedido_contexto = pedido;
        $scope.setFrete(frete);

    }

    $scope.setFrete = function(frete) {


        $scope.pedido_contexto.transportadora = frete.transportadora;
        $scope.pedido_contexto.etapa_frete = 0;
        $scope.pedido_contexto.fretes_intermediarios = [];
        $scope.pedido_contexto.frete = parseFloat(frete.valor.toFixed(2));
        $scope.atualizaCustos($scope.pedido_contexto);


    }

    $scope.cf = false;

    $scope.getFretes = function(pedido) {

        $scope.pedido_contexto = pedido;

        var empresa = pedido.empresa;

        if (pedido.logistica !== null) {

            empresa = pedido.logistica;

        }

        //---- parametros de frete

        var pesoTotal = 0;
        var valorTotal = 0;

        for (var i = 0; i < pedido.produtos.length; i++) {
            var p = pedido.produtos[i];
            valorTotal += (p.valor_base + p.ipi + p.icms + p.juros) * p.quantidade;
            pesoTotal += p.produto.peso_bruto * p.quantidade;
        }

        //------------------------

        if (!$scope.cf) {

            tabelaService.getFretes(empresa, { cidade: pedido.cliente.endereco.cidade, valor: valorTotal, peso: pesoTotal }, function(f) {

                $scope.fretes = f.fretes;
                pedido.fretes = f.fretes;

                $scope.cf = false;

            })

            $scope.cf = true;

        }

        return true;

    }

    $scope.getPossibilidadesFrete = function(pedido) {

        $scope.carregando_frete = true;
        $('#mdlPossibilidadesFrete').modal('show');
        $scope.setPedidoContexto(pedido);

        pedidoService.getPossibilidadesFreteIntermediario(pedido, function(f) {

            pedido.possibilidades_frete = f.possibilidades;
            $scope.carregando_frete = false;

        })

    }

    $scope.getTotalGlobal = function() {

        var total = 0;

        for (var i = 0; i < $scope.pedidos.length; i++) {

            var pedido = $scope.pedidos[i];

            for (var j = 0; j < pedido.produtos.length; j++) {

                var produto = pedido.produtos[j];

                total += produto.quantidade * (produto.valor_base + produto.juros + produto.frete + produto.ipi + produto.icms);

            }

        }

        return total;

    }


    $scope.getProdutos = function() {


        var produtos = [];
        for (var i = 0; i < $scope.pedidos.length; i++) {

            var pedido = $scope.pedidos[i];

            for (var j = 0; j < pedido.produtos.length; j++) {

                var produto = pedido.produtos[j];

                produtos[produtos.length] = produto;

            }

        }



        return produtos;

    }

    carrinhoService.getPedidosResultantes(function(r) {

        if (r.sucesso) {

            $scope.pedidos = r.pedidos;

            for (var i = 0; i < r.pedidos.length; i++) {

                r.pedidos[i].identificador = i;
                r.pedidos[i].possibilidades_frete = [];
                r.pedidos[i].status_finalizacao = null;
                r.pedidos[i].prazo_parcelas = $scope.possibilidades[0];
                equalize(r.pedidos[i], "forma_pagamento", r.pedidos[i].formas_pagamento);
            }

            produtos = null;

        }

    });

    $scope.setPedidoContexto = function(pedido) {

        $scope.pedido_contexto = pedido;

    }

    $scope.atualizaCustosResetandoFrete = function(pedido) {

        pedido.transportadora = null;
        pedido.frete = 0;
        pedido.etapa_frete = 0;
        pedido.fretes_intermediarios = [];

        $scope.atualizaCustos(pedido);

    }

    $scope.remover = function(produto) {

        var nc = [];
        for (var i = 0; i < $scope.carrinho.length; i++) {
            if ($scope.carrinho[i].id === produto.produto.id) {
                continue;
            }
            nc[nc.length] = $scope.carrinho[i];
        }

        carrinhoService.setCarrinho(nc, function() {

            location.reload();

        })

    }

    $scope.retirouPromocao = function(produto) {

        if (typeof produto["retirou_promocao"] === 'undefined') {

            return 0;

        }

        return produto.retirou_promocao;

    }

    $scope.attPrazoParcelas = function(pedido) {

        pedido.prazo = pedido.prazo_parcelas.prazo;
        pedido.parcelas = pedido.prazo_parcelas.parcelas;

        if (pedido.prazo > 3) {

            $("#mdlPagamento").modal("show");

        }

        $scope.atualizaCustos(pedido);

    }

    $scope.atualizaCustos = function(pedido) {
        $scope.atualizando_custo = true;
        var i = 0;
        for (; i < $scope.pedidos.length; i++) {
            if ($scope.pedidos[i] === pedido) {
                break;
            }
        }

        pedidoService.atualizarCustos(pedido, function(np) {

            $scope.pedidos[i] = np.o;
            $scope.pedidos[i].identificador = i;
            $scope.pedidos[i].status_finalizacao = null;

            $scope.getFormasPagamento($scope.pedidos[i]);
            equalize($scope.pedidos[i], "prazo_parcelas", $scope.possibilidades);
            $scope.pedido_contexto = $scope.pedidos[i];
            $scope.atualizando_custo = false;
        })

    }


})
rtc.controller("crtCarrinho", function($scope, sistemaService, carrinhoService) {

    $scope.carrinho = [];

    $scope.attCarrinho = function() {

        carrinhoService.getCarrinho(function(c) {

            $scope.carrinho = c.carrinho;

        })

    }

    $scope.attCarrinho();

    $scope.removerProduto = function(produto) {

        remove($scope.carrinho, produto);

        carrinhoService.setCarrinho($scope.carrinho, function(r) {

            if (r.sucesso) {

                msg.alerta("Removido com sucesso");

            } else {

                msg.erro("Falha ao remover do carrinho");

            }

        })

    }

})
rtc.controller("crtEmpresa", function($scope, $timeout, $interval, tipoProtocoloService, atividadeService, usuarioService, empresaService, protocoloService, baseService) {

    $timeout(function() {

        atividadeService.sinal();

    }, 10000);

    $interval(function() {
        atividadeService.sinal();
    }, 60000);


    $scope.empresa = null;
    $scope.filiais = [];
    $scope.carregando_empresa = true;

    $scope.protocolos_ativos = [];

    $scope.tipos_protocolo = [];


    var fnac = function(s) {

        msg.confirma("Solicitacao para alteracao de formula em produto: " + s.observacao, function() {

            usuarioService.aceitarSolicitacao(s, function(rr) {

                if (rr.sucesso) {



                }

            })


        })


    }

    $interval(function() {

        usuarioService.getSolicitacoes(function(r) {

            for (var i = 0; i < r.solicitacoes.length; i++) {

                var s = r.solicitacoes[i];

                fnac(s);

            }

        })

    }, 10000)


    $scope.setContrato = function(i) {

        $scope.empresa.aceitou_contrato = i;

        empresaService.setContrato($scope.empresa, function() {

            msg.alerta("Operacao efetuada com sucesso");

        })

    }


    $scope.mensagem_protocolo = {};
    $scope.suportes = [];

    //------------------------------
    $scope.novo_mensagem_suporte = {};


    //------------------------------


    $scope.enviar = function(protocolo) {

        var m = angular.copy($scope.mensagem_protocolo);
        m.mensagem = protocolo.obs;
        m.protocolo = protocolo;
        baseService.merge(m, function(r) {

            if (r.sucesso) {

                protocolo.obs = "";

            } else {

                msg.erro("Ocorreu um problema");

            }

        })

    }

    $scope.removerUsuario = function(p, u) {

        var us = [];

        for (var i = 0; i < p.usuarios.length; i++) {
            if (p.usuarios[i].id !== u.id) {
                us[us.length] = p.usuarios[i];
            }
        }

        p.usuarios = us;

    }

    $scope.reprovar = function(p) {

        protocoloService.reprovar(p, function(s) {

            if (s.sucesso) {

                var na = [];

                for (var i = 0; i < $scope.protocolos_aprovacao.length; i++) {

                    if ($scope.protocolos_aprovacao[i].id !== p.id) {

                        na[na.length] = $scope.protocolos_aprovacao[i];

                    }

                }

                $scope.protocolos_aprovacao = na;

            } else {

                msg.erro("Problema ao reprovar");

            }


        });

    }

    $scope.aprovar = function(p) {

        protocoloService.aprovar(p, function(s) {

            if (s.sucesso) {

                var na = [];

                for (var i = 0; i < $scope.protocolos_aprovacao.length; i++) {

                    if ($scope.protocolos_aprovacao[i].id !== p.id) {

                        na[na.length] = $scope.protocolos_aprovacao[i];

                    }

                }

                $scope.protocolos_aprovacao = na;

            } else {

                msg.erro("Problema ao aprovar");

            }


        });

    }

    $scope.getEmpresas = function() {
        empresaService.getEmpresa(function(r) {


            $scope.empresa = r.empresa;

            $scope.filiais = [];
            $scope.filiais[$scope.filiais.length] = r.empresa;

            empresaService.getFiliais(function(rr) {

                for (var i = 0; i < rr.filiais.length; i++) {
                    if (rr.filiais[i].id === $scope.empresa.id)
                        continue;
                    $scope.filiais[$scope.filiais.length] = rr.filiais[i];

                }
                $scope.carregando_empresa = false;

            })

        })
        $("#mdlTrocaEmpresa").modal("show");
    }

    $scope.setEmpresa = function(empresa) {
        loading.show();
        empresaService.setEmpresa(empresa, function(r) {
            loading.close();
            if (r.sucesso) {
                if (r.aceito) {
                    window.location = 'index_em_branco.php';
                } else {
                    msg.alerta("Voce nao tem acesso a empresa " + empresa.nome);
                }
            }

        });
    }

})
rtc.controller("crtCompraParceiros", function($scope, $timeout, produtoService, compraParceiroService, sistemaService, carrinhoService) {

    $scope.tv = function(produto) {

        return typeof produto.produtos[0]["validades"] !== 'undefined';

    }

    $scope.locais = [];
    $scope.produto = null;

    $scope.carregando_compra = true;
    $scope.loaders = [{ id: 0 }, { id: 1 }, { id: 2 }, { id: 3 }, { id: 4 }, { id: 5 }];

    $scope.mais_vendidos = [];

    $scope.produtos = createFilterList(compraParceiroService, 3, 6, 10);
    $scope.produtos["posload"] = function(elementos) {
        $scope.carregando_compra = false;
        sistemaService.getMesesValidadeCurta(function(p) {
            var produtos = [];
            for (var i = 0; i < elementos.length; i++) {
                for (var j = 0; j < elementos[i].produtos.length; j++) {
                    produtos[produtos.length] = elementos[i].produtos[j];
                }
            }

            if (typeof rtc["empresa_base"] === 'undefined') {

                produtoService.remessaGetValidades(p.meses_validade_curta, produtos, function() {});

            } else {


                compraParceiroService.getMaisVendidos(rtc["empresa_base"], 12, function(m) {

                    $scope.mais_vendidos = m.mais_vendidos;

                    for (var i = 0; i < $scope.mais_vendidos.length; i++) {
                        for (var j = 0; j < $scope.mais_vendidos[i].produtos.length; j++) {
                            produtos[produtos.length] = $scope.mais_vendidos[i].produtos[j];
                        }
                    }

                    produtoService.remessaGetValidades(p.meses_validade_curta, produtos, function() {});


                })


            }


        });
    }

    $scope.produtos.attList();



    $scope.nl = function(grupo) {
        var m = 0;
        for (var i = 0; i < grupo.produtos.length; i++) {
            if (grupo.produtos[i].validades.length > m) {
                m = grupo.produtos[i].validades.length;
            }
        }
        var ret = [];
        for (var i = 0; i < m; i++) {
            ret[i] = i;
        }
        return ret;
    }

    $scope.maisLocais = function(produto) {

        var principal = $scope.gp(produto);

        $scope.produto = produto;

        $scope.locais = [];

        for (var i = 0; i < produto.produtos.length; i++) {
            var p = produto.produtos[i];
            if (p !== principal) {
                for (var k = 0; k < p.validades.length; k++) {
                    $scope.locais[$scope.locais.length] = { local: p, validade: p.validades[k] };
                }
            }
        }

        $("#locaisProduto").modal('show');

    }

    $scope.gp = function(grupo) {

        var mi = -1;
        var mq = 0;

        for (var i = 0; i < grupo.produtos.length; i++) {
            var produto = grupo.produtos[i];
            var qtd = 0;
            for (var k = 0; k < produto.validades.length; k++) {
                qtd += produto.validades[k].quantidade;
            }
            if (mi < 0 || mq < qtd) {
                mq = qtd;
                mi = i;
            }
        }

        return grupo.produtos[mi];

    }

    $scope.temPromocao = function(produto) {

        var pr = $scope.gp(produto);

        for (var j = 0; j < pr.validades.length; j++) {
            if (pr.validades[j].oferta) return true;
        }


        return false;

    }

    $scope.qtd = 0;
    $scope.prod = null;
    $scope.val = null;
    $scope.meses_validade_curta = 3;

    var carrinho = [];


    carrinhoService.getCarrinho(function(c) {

        carrinho = c.carrinho;

    })

    sistemaService.getMesesValidadeCurta(function(p) {

        $scope.meses_validade_curta = p.meses_validade_curta;

    })

    $scope.produto_adicionado = null;
    $scope.validade_adicionada = null;

    $scope.qtdcx = 0;
    $scope.qtdun = 0;

    $scope.grade = 1;

    $scope.adcionando = false;

    $scope.addCarrinhoNormal = function(produto, validade) {

        $scope.prod = produto;

        $scope.qtd = parseFloat(window.prompt("Quantidade"));
        if (isNaN($scope.qtd)) {
            msg.erro("Quantidade incorreta");
            return;
        }

        $scope.qtd = parseInt(($scope.qtd + ""));


        var p = angular.copy($scope.prod);
        p.quantidade_comprada = $scope.qtd;

        $scope.val = validade;

        if ($scope.qtd > $scope.val.quantidade) {
            $scope.adcionando = false;
            msg.erro("Nao temos essa quantidade");
            return;

        }

        carrinhoService.getCarrinho(function(c) {

            carrinho = c.carrinho;

            var p = angular.copy($scope.prod);
            p.validade = $scope.val;
            p.quantidade_comprada = $scope.qtd;

            var limite = p.validade.limite;
            var a = false;
            for (var i = 0; i < carrinho.length; i++) {
                if (carrinho[i].id === p.id && carrinho[i].validade.validade === p.validade.validade) {
                    a = true;
                    if ((p.quantidade_comprada + carrinho[i].quantidade_comprada) > p.validade.quantidade) {
                        msg.erro("Nao temos essa quantidade em estoque");
                        return;
                    }
                    if ((p.quantidade_comprada + carrinho[i].quantidade_comprada) > limite && limite > 0) {
                        msg.erro("Limite de compra ecxedido");
                        return;
                    }

                    carrinho[i].quantidade_comprada += p.quantidade_comprada;
                    break;
                }
            }

            if (!a) {
                carrinho[carrinho.length] = p;
            }
            carrinhoService.setCarrinho(carrinho, function(r) {

                if (r.sucesso) {

                    $('#mdlAdicionar').modal("hide");

                    $("#indicadorAdd").css('visibility', 'initial');

                    $("#carrinhoFacil").dropdown('toggle');
                    $timeout(function() {
                        angular.element($('#carrinhoFacil')).triggerHandler('click');
                    });
                } else {

                    msg.erro("Falha ao adicionar o produto");

                }

                $scope.adcionando = false;

            })


        });


    }

    $scope.finalizaAddCarrinho = function() {


        $scope.adcionando = true;

        $scope.prod = $scope.produto_adicionado;

        $scope.qtd = (parseInt($scope.qtdcx + "") * parseInt($scope.grade + "")) + parseInt($scope.qtdun + "");

        if (isNaN($scope.qtd)) {
            $scope.adcionando = false;

            msg.alerta("Não temos essa quantidade em estoque. Por favor, digite uma quantidade menor.");

            return;

        }


        $scope.val = $scope.validade_adicionada;

        if ($scope.qtd > $scope.val.quantidade) {
            $scope.adcionando = false;
            msg.alerta("Não temos essa quantidade em estoque. Por favor, digite uma quantidade menor.");
            return;

        }


        carrinhoService.getCarrinho(function(c) {

            carrinho = c.carrinho;

            var p = angular.copy($scope.prod);
            p.validade = $scope.val;
            p.quantidade_comprada = $scope.qtd;

            var limite = p.validade.limite;
            var a = false;
            for (var i = 0; i < carrinho.length; i++) {
                if (carrinho[i].id === p.id && carrinho[i].validade.validade === p.validade.validade) {
                    a = true;
                    if ((p.quantidade_comprada + carrinho[i].quantidade_comprada) > p.validade.quantidade) {
                        msg.erro("Nao temos essa quantidade em estoque");
                        return;
                    }
                    if ((p.quantidade_comprada + carrinho[i].quantidade_comprada) > limite && limite > 0) {
                        msg.erro("Limite de compra ecxedido");
                        return;
                    }

                    carrinho[i].quantidade_comprada += p.quantidade_comprada;
                    break;
                }
            }

            if (!a) {
                carrinho[carrinho.length] = p;
            }
            carrinhoService.setCarrinho(carrinho, function(r) {

                if (r.sucesso) {

                    $('#mdlAdicionar').modal("hide");

                    $("#indicadorAdd").css('visibility', 'initial');

                    $("#carrinhoFacil").dropdown('toggle');
                    $timeout(function() {
                        angular.element($('#carrinhoFacil')).triggerHandler('click');
                    });
                } else {

                    msg.erro("Falha ao adicionar o produto");

                }

                $scope.adcionando = false;

            })


        });



    }

    $scope.addCarrinho = function(produto, validade) {

        $scope.qtdcx = 0;
        $scope.qtdun = 0;

        $scope.produto_adicionado = produto;
        $scope.validade_adicionada = validade;

        var caixa = Math.max($scope.produto_adicionado.grade.gr[0], 1);
        $scope.grade = caixa;

        $("#mdlAdicionar").modal("show");




    }


    $scope.setProduto = function(produto) {
        $scope.prod = produto;
        produtoService.getValidades($scope.meses_validade_curta, produto, function(v) {
            produto.validades = v;
        })
    }

    $scope.addLevel = function(op, filtro) {
        op.selecionada++;
        op.selecionada = op.selecionada % 2;

        for (var i = 0; i < filtro.opcoes.length; i++) {
            if (filtro.opcoes[i].selecionada > 0 && filtro.opcoes[i].id !== op.id) {
                filtro.opcoes[i].selecionada = 0;
            }
        }

        $scope.produtos.attList();
    }

    $scope.resetarFiltro = function() {

        for (var i = 0; i < $scope.produtos.filtro.length; i++) {
            var f = $scope.produtos.filtro[i];
            if (f._classe === 'FiltroTextual') {
                f.valor = "";
            } else if (f._classe === 'FiltroOpcional') {
                for (var j = 0; j < f.opcoes.length; j++) {
                    f.opcoes[j].selecionada = 0;
                }
            }
        }

        $scope.produtos.attList();

    }

    $scope.dividir = function(produtos, qtd) {

        var k = Math.ceil((produtos.length) / qtd);

        var m = [];

        for (var a = 0; a < qtd; a++) {
            m[a] = [];
            for (var i = a * k; i < (a + 1) * k && i < produtos.length; i++) {
                for (var j = 0; j < produtos[i].length; j++) {
                    m[a][m[a].length] = produtos[i][j];
                }
            }
        }

        return m;

    }

})
rtc.controller("crtExpediente", function($scope, $timeout, usuarioService, ausenciaService, expedienteService) {

    $scope.usuarios = createAssinc(usuarioService, 1, 3, 4);
    $scope.usuarios.posload = function(e) {
        if (e.length > 0) {
            $timeout(function() {

                $scope.setUsuario(e[0]);

            }, 500)
        }
    }
    $scope.usuarios.attList();
    assincFuncs(
        $scope.usuarios,
        "usuario", ["id", "email_usu.endereco", "nome", "cpf", "rg", "login"], "filtroUsuarios");

    $scope.usuario = null;
    $scope.ausencias = [];
    $scope.expedientes = [];

    $scope.dias = [
        { id: 0, nome: "Dom" },
        { id: 1, nome: "Seg" },
        { id: 2, nome: "Ter" },
        { id: 3, nome: "Qua" },
        { id: 4, nome: "Qui" },
        { id: 5, nome: "Sex" },
        { id: 6, nome: "Sab" }
    ];

    $scope.ausencia_novo = {};

    $scope.expediente_novo = {};

    ausenciaService.getAusencia(function(a) {
        $scope.ausencia_novo = a.ausencia;
    })

    expedienteService.getExpediente(function(e) {
        $scope.expediente_novo = e.expediente;
    })

    $scope.setUsuario = function(usuario) {

        $scope.usuario = usuario;

        ausenciaService.getAusencias($scope.usuario, function(a) {

            $scope.usuario.ausencias = a.ausencias;
            $scope.ausencias = createList(a.ausencias, 1, 5);

        })

        expedienteService.getExpedientes($scope.usuario, function(e) {

            $scope.usuario.expedientes = e.expedientes;
            $scope.expedientes = createList(e.expedientes, 1, 14);

        })

        $scope.getTempo = function(t) {

            var h = parseInt(t);
            var m = ((t % 1) * 60).toFixed(0);

            return h + "h:" + m + "m";

        }

    }

    $scope.removeExpediente = function(ee) {

        var ne = [];

        for (var i = 0; i < $scope.usuario.expedientes.length; i++) {
            var e = $scope.usuario.expedientes[i];
            if (e !== ee) {
                ne[ne.length] = e;
            }
        }

        $scope.usuario.expedientes = ne;
        $scope.expedientes = createList($scope.usuario.expedientes, 1, 14);

    }

    $scope.removeAusencia = function(aa) {

        var na = [];

        for (var i = 0; i < $scope.usuario.ausencias.length; i++) {
            var a = $scope.usuario.ausencias[i];
            if (a !== aa) {
                na[na.length] = a;
            }
        }

        $scope.usuario.ausencias = na;
        $scope.ausencias = createList($scope.usuario.ausencias, 1, 5);

    }

    $scope.addAusencia = function() {

        $scope.usuario.ausencias[$scope.usuario.ausencias.length] = angular.copy($scope.ausencia_novo);
        $scope.ausencias.attList();

    }

    $scope.addExpediente = function() {

        $scope.usuario.expedientes[$scope.usuario.expedientes.length] = angular.copy($scope.expediente_novo);
        $scope.expedientes.attList();

    }

    $scope.confirmarExpedientes = function() {


        expedienteService.setExpedientes($scope.usuario, $scope.usuario.expedientes, function(f) {

            if (f.sucesso) {

                msg.alerta("Operacao confirmada com sucesso");

            } else {

                msg.erro("Problema ao confirmar operacoes");

            }

        })

    }

    $scope.confirmarAusencias = function() {

        ausenciaService.setAusencias($scope.usuario, $scope.usuario.ausencias, function(f) {

            if (f.sucesso) {

                msg.alerta("Operacao confirmada com sucesso");

            } else {

                msg.erro("Problema ao confirmar operacoes");

            }

        })

    }


})
rtc.controller("crtOrganograma", function($scope, usuarioService) {

    $scope.usuarios = createAssinc(usuarioService, 1, 3, 10);
    $scope.usuarios.attList();
    assincFuncs(
        $scope.usuarios,
        "usuario", ["id", "email_usu.endereco", "nome", "cpf", "rg", "login"], "filtroUsuarios");

})


rtc.controller("crtCadastroEmpresaBase", function($scope, empresaService, usuarioService, cidadeService, baseService, telefoneService, cargoService, tipoTarefaService) {

    cargoService.empresa = rtc["empresa_base"];

    $scope.usuario = null;

    usuarioService.getUsuario(function(u) {

        $scope.usuario = u.usuario;

        $scope.usuario.empresa = rtc["empresa_base"];

        $scope.usuario.email.endereco = "";

        cargoService.getCargos(function(c) {

            $scope.usuario.cargo = c.cargos[0];


        })

    })

    $scope.estados = [];
    $scope.cidades = [];

    $scope.estado = null;

    cidadeService.getElementos(function(p) {
        var estados = [];
        var cidades = p.elementos;
        $scope.cidades = cidades;

        lbl:
            for (var i = 0; i < cidades.length; i++) {
                var c = cidades[i];
                for (var j = 0; j < estados.length; j++) {
                    if (estados[j].id === c.estado.id) {
                        estados[j].cidades[estados[j].cidades.length] = c;
                        c.estado = estados[j];
                        continue lbl;
                    }
                }
                c.estado["cidades"] = [c];
                estados[estados.length] = c.estado;
            }



        $scope.estados = estados;

        $scope.estado = estados[0];
        $scope.usuario.endereco.cidade = $scope.estado.cidades[0];
    })

    $scope.mergeUsuario = function() {

        baseService.merge($scope.usuario, function(s) {

            if (s.sucesso) {

                msg.sucesso("Cadastro efetuado com sucesso, voce sera redirecionado a tela de login");
                window.location = 'login.php';

            } else {


                msg.erro("Ocorreu uma falha");

            }

        })


    }

});

rtc.controller("crtUsuarios", function($scope, $timeout, tipoProtocoloService, empresaService, usuarioService, permissaoService, cidadeService, baseService, telefoneService, cargoService, tipoTarefaService) {

    var cache = [];

    $scope.empresa_atual = null;
    $scope.empresas_clientes = [];
    empresaService.getEmpresasClientes(function(e) {
        $scope.empresas_clientes = e.clientes;
        empresaService.getEmpresa(function(e) {
            var a = null;
            for (var i = 0; i < $scope.empresas_clientes.length; i++) {
                if ($scope.empresas_clientes[i].id === e.empresa.id) {
                    a = $scope.empresas_clientes[i];
                    break;
                }
            }
            if (a === null) {
                a = e.empresa;
                $scope.empresas_clientes[$scope.empresas_clientes.length] = a;
            }
            $scope.empresa_atual = a;
        })
    })



    $scope.tipos_tarefa_usuario = [];

    $scope.permissoes_abaixo = [];

    $scope.permissoes_permitidas = [];

    usuarioService.getPermissoesPermitidas(function(a) {

        $scope.permissoes_permitidas = a.permissoes;


    })




    $scope.transferir = function(u) {
        if ($scope.igualar_aprovador === 0) {
            $scope.usuario.permissoes = [];

            for (var i = 0; i < u.permissoes.length; i++) {
                $scope.usuario.permissoes[$scope.usuario.permissoes.length] = angular.copy(u.permissoes[i]);
            }

            var nc = [];
            for (var i = 0; i < cache.length; i++) {
                if (cache[i].usuario.id !== $scope.usuario.id) {
                    nc[nc.length] = cache[i];
                }
            }
            cache = nc;

            $scope.mergeUsuario();

        } else {

            $scope.tipo_protocolo.aprovador = u;

            baseService.merge($scope.tipo_protocolo, function(r) {

                if (r.sucesso) {

                    msg.alerta("Operacao efetuada com sucesso");

                } else {

                    msg.erro("Falha ao efetuar operacao");

                }

            })

        }

    }


    $scope.getPermissoesUsuario = function(usuario) {

        for (var j = 0; j < cache.length; j++) {
            if (cache[j].usuario === usuario) {
                return cache[j].cache;
            }
        }

        var p = [];

        for (var i = 0; i < $scope.permissoes_abaixo.length; i++) {

            var k = $scope.permissoes_abaixo[i];

            p[p.length] = { id: k.id, nome: k.nome, p: [k] };

        }

        for (var i = 0; i < usuario.permissoes.length; i++) {

            var k = usuario.permissoes[i];

            for (var j = 0; j < p.length; j++) {

                if (p[j].id === k.id) {

                    p[j].p[p[j].p.length] = k;
                    break;

                }

            }

        }

        cache[cache.length] = { usuario: usuario, cache: p };

        return p;

    }

    $scope.permitida = function(permissao, tipo) {

        for (var i = 0; i < $scope.permissoes_permitidas.length; i++) {

            if ($scope.permissoes_permitidas[i].id === permissao.id) {

                var t = ["alt", "in", "del", "cons"];

                return $scope.permissoes_permitidas[i][t[tipo]];

            }

        }

        return false;

    }

    usuarioService.getPermissoesAbaixo(function(a) {

        $scope.permissoes_abaixo = a.permissoes;

    })


    $scope.col = function(l) {

        var k = ["alt", "in", "del", "cons"];


        var perms = [];

        var t = 0;
        var tot = 0;

        for (var i = 0; i < $scope.usuario.permissoes.length; i++) {


            var p = $scope.usuario.permissoes[i];

            if (!$scope.permitida(p, l))
                continue;

            perms[perms.length] = p;

            if (p[k[l]]) {
                t++;
            }
            tot++;
            //p[k[l]] = !p[k[l]];

        }

        for (var i = 0; i < $scope.permissoes_abaixo.length; i++) {

            var p = $scope.permissoes_abaixo[i];

            if (!$scope.permitida(p, l))
                continue;

            perms[perms.length] = p;

            if (p[k[l]]) {
                t++;
            }
            tot++;
            //p[k[l]] = !p[k[l]];

        }

        if (t === tot) {

            for (var i = 0; i < perms.length; i++) {

                var p = perms[i];

                p[k[l]] = false;

            }

        } else {

            for (var i = 0; i < perms.length; i++) {

                var p = perms[i];

                p[k[l]] = true;

            }


        }



    }

    $scope.row = function(p, x) {

        var todas = true;

        if ($scope.permitida(p.p[x], 0))
            todas = todas && p.p[x].alt;


        if ($scope.permitida(p.p[x], 1))
            todas = todas && p.p[x].in;

        if ($scope.permitida(p.p[x], 2))
            todas = todas && p.p[x].del;

        if ($scope.permitida(p.p[x], 3))
            todas = todas && p.p[x].cons;


        p.p[x].alt = !todas;
        p.p[x].in = !todas;
        p.p[x].cons = !todas;
        p.p[x].del = !todas;


    }

    $scope.trocaEmpresa = function() {
        usuarioService.filtro_base = "usuario.id>=0";
        usuarioService.empresa = $scope.empresa_atual;
        permissaoService.empresa = $scope.empresa_atual;
        cargoService.empresa = $scope.empresa_atual;
        tipoTarefaService.empresa = $scope.empresa_atual;
        $scope.init();
        $scope.usuarios.attList();
    }


    $scope.usuarios = createAssinc(usuarioService, 1, 5, 10);
    $scope.usuarios.posload = function(e) {
        if (e.length > 0) {
            $timeout(function() {

                $scope.setUsuario(e[0]);

            }, 500)
        }
    }
    $scope.usuarios.attList();
    assincFuncs(
        $scope.usuarios,
        "usuario", ["id", "email_usu.endereco", "nome", "cpf", "rg", "login"]);


    $scope.usuarios2 = createAssinc(angular.copy(usuarioService), 1, 3, 4);
    $scope.usuarios2.attList();
    assincFuncs(
        $scope.usuarios2,
        "usuario", ["id"], "filtroUsuario2");


    $scope.usuario_novo = {};
    $scope.usuario = {};
    $scope.estado = {};

    $scope.tipo_tarefa = null;
    $scope.tipo_tarefa_novo = {};

    $scope.tipos_tarefa = [];

    $scope.tipo_protocolo = {};

    $scope.igualar_aprovador = 0;

    $scope.tipos_protocolo = [];


    var attTiposTarefa = function() {

    }

    var attTiposProtocolo = function() {

    }

    $scope.igualarCargo = function() {

        var cargo = $scope.usuario.cargo;

        if (cargo !== null) {

            cargoService.getPermissoes(cargo, function(r) {

                var permissoes = r.permissoes;

                for (var i = 0; i < $scope.usuario.permissoes.length; i++) {

                    var px = $scope.usuario.permissoes[i];

                    for (var j = 0; j < permissoes.length; j++) {

                        var py = permissoes[j];

                        if (px.id === py.id) {

                            px.in = py.in;
                            px.alt = py.alt;
                            px.del = py.del;
                            px.cons = py.cons;
                            break;
                        }

                    }

                }

                $scope.mergeUsuario();

            })

        }

    }

    $scope.cargo_permissoes = {};

    $scope.setCargo = function(cargo) {

        $scope.cargo_permissoes = cargo;

        cargoService.getPermissoes(cargo, function(r) {

            cargo.permissoes = r.permissoes;

        })

    }

    $scope.init = function() {

        tipoTarefaService.getTipoTarefa(function(t) {

            $scope.tipo_tarefa_novo = t.tipo_tarefa;

        })

        attTiposTarefa = function() {
            tipoTarefaService.getTiposTarefa(function(t) {

                if ($scope.tipo_tarefa === null) {
                    if (t.tipos_tarefa.length > 0) {
                        $scope.setTipoTarefa(t.tipos_tarefa[0]);
                    } else {
                        $scope.tipo_tarefa = {};
                    }
                }

                $scope.tipos_tarefa = createList(t.tipos_tarefa, 1, 5, "nome");

            })
        }

        attTiposProtocolo = function() {
            tipoProtocoloService.getTiposProtocolo(function(t) {

                if ($scope.tipo_protocolo === null) {
                    if (t.tipos_protocolo.length > 0) {
                        $scope.setTipoProtocolo(t.tipos[0]);
                    } else {
                        $scope.tipo_protocolo = {};
                    }
                }

                $scope.tipos_protocolo = createList(t.tipos, 1, 5, "nome");

            })
        }

        attTiposTarefa();
        attTiposProtocolo();

        cargoService.getCargo(function(c) {

            $scope.cargo = c.cargo;
            $scope.cargo_novo = angular.copy(c.cargo);

        })

        var attCargos = function() {
            cargoService.getCargos(function(c) {
                $scope.cargos = c.cargos;
                $scope.lstCargos = createList(angular.copy(c.cargos), 1, 5, "nome");
                if ($scope.usuario !== null) {
                    $scope.setUsuario($scope.usuario);
                }
            })
        }

        attCargos();

        permissaoService.getPermissoes(function(p) {
            $scope.permissoes = p.permissoes;
        })

        usuarioService.getUsuario(function(p) {
            $scope.usuario_novo = p.usuario;
        })
        telefoneService.getTelefone(function(p) {
            $scope.telefone_novo = p.telefone;
            $scope.telefone = angular.copy($scope.telefone_novo);
        })

    }

    $scope.init();




    $scope.cargos = [];
    $scope.lstCargos = {};

    $scope.cargo = {};
    $scope.cargo_novo = {};

    $scope.email = {};

    $scope.data_atual = new Date().getTime();

    $scope.telefone_novo = {};
    $scope.telefone = {};

    $scope.permissoes = [];

    $scope.estados = [];
    $scope.cidades = [];



    $scope.cargo_tipo_tarefa = [];

    $scope.addCargo = function(cargo) {

        for (var i = 0; i < $scope.tipo_tarefa.cargos.length; i++) {
            if ($scope.tipo_tarefa.cargos[i].id === cargo.id) {
                msg.erro("Esse cargo ja esta relacionado com essa tarefa");
                return;
            }
        }

        $scope.tipo_tarefa.cargos[$scope.tipo_tarefa.cargos.length] = cargo;
        $scope.setTipoTarefa($scope.tipo_tarefa);

    }

    $scope.removeCargoTarefa = function(cargo) {

        var nc = [];

        for (var i = 0; i < $scope.tipo_tarefa.cargos.length; i++) {
            if ($scope.tipo_tarefa.cargos[i].id !== cargo.id) {
                nc[nc.length] = $scope.tipo_tarefa.cargos[i];
            }
        }

        $scope.tipo_tarefa.cargos = nc;

        $scope.setTipoTarefa($scope.tipo_tarefa);

    }

    $scope.setTipoTarefa = function(tt) {

        $scope.tipo_tarefa = tt;
        $scope.cargos_tipo_tarefa = createList(tt.cargos, 1, 3, "nome");

    }

    $scope.setTipoProtocolo = function(tt) {

        $scope.igualar_aprovador = 1;
        $scope.tipo_protocolo = tt;

    }



    cidadeService.getElementos(function(p) {
        var estados = [];
        var cidades = p.elementos;
        $scope.cidades = cidades;

        lbl:
            for (var i = 0; i < cidades.length; i++) {
                var c = cidades[i];
                for (var j = 0; j < estados.length; j++) {
                    if (estados[j].id === c.estado.id) {
                        estados[j].cidades[estados[j].cidades.length] = c;
                        c.estado = estados[j];
                        continue lbl;
                    }
                }
                c.estado["cidades"] = [c];
                estados[estados.length] = c.estado;
            }

        $scope.estados = estados;
    })
    $scope.novoUsuario = function() {

        $scope.usuario = angular.copy($scope.usuario_novo);

    }

    $scope.removeTelefone = function(tel) {

        remove($scope.usuario.telefones, tel);

    }
    $scope.addTelefone = function() {
        $scope.usuario.telefones[$scope.usuario.telefones.length] = $scope.telefone;
        $scope.telefone = angular.copy($scope.telefone_novo);
    }

    $scope.marginTop = 0;
    $scope.setUsuario = function(usuario) {

        $scope.usuario = usuario;

        equalize(usuario, "cargo", $scope.cargos);

        var dv = $("#dvUsuarios");
        var tr = $("#tr_" + $scope.usuario.id);

        $scope.marginTop = tr.offset().top - dv.offset().top;


        equalize(usuario.endereco, "cidade", $scope.cidades);
        if (typeof usuario.endereco.cidade !== 'undefined') {
            $scope.estado = usuario.endereco.cidade.estado;
        } else {
            usuario.endereco.cidade = $scope.cidades[0];
            $scope.estado = usuario.endereco.cidade.estado;
        }

        usuarioService.getTiposTarefaUsuario($scope.usuario, function(r) {

            $scope.tipos_tarefa_usuario = r.tipos_tarefa_usuario;

        })

    }


    $scope.mergeTipoTarefaUsuario = function(tt) {

        baseService.merge(tt, function(r) {

            if (r.sucesso) {

                msg.alerta("Salvo com sucesso");

            } else {

                msg.erro("Problema ao salvar");

            }


        })

    }

    $scope.novoTipoTarefa = function() {

        $scope.tipo_tarefa = angular.copy($scope.tipo_tarefa_novo);
        $scope.setTipoTarefa($scope.tipo_tarefa);

    }

    $scope.novoTipoProtocolo = function() {

        if ($scope.tipo_protocolo === null) {

            msg.erro("Preencha corretamente os dados");
            return;

        }

        $scope.tipo_protocolo.id = 0;
        $scope.tipo_protocolo._classe = "TipoProtocolo";

        $scope.mergeTipoProtocolo($scope.tipo_protocolo);

        attTiposProtocolo();

    }

    $scope.mergeTipoProtocolo = function(tt) {
        if (tt.nome === "") {
            msg.erro("Digite o nome");
            return;
        }

        if (isNaN(tt.prioridade) || tt.prioridade === 0) {
            msg.erro("Digite a prioridade correta");
            return;
        }

        baseService.merge(tt, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
            } else {
                msg.erro("Problema ao efetuar operacao. " + r.mensagem);
            }
        });
    }

    $scope.mergeTipoTarefa = function(tt) {
        if (tt.nome === "") {
            msg.erro("Digite o nome");
            return;
        }
        baseService.merge(tt, function(r) {
            if (r.sucesso) {
                $scope.tipo_tarefa = r.o;
                msg.alerta("Operacao efetuada com sucesso");
                attTiposTarefa();
            } else {
                msg.erro("Problema ao efetuar operacao. " + r.mensagem);
            }
        });
    }

    $scope.deleteTipoProtocolo = function(tt) {
        baseService.delete(tt, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                attTiposProtocolo();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
        });
    }

    $scope.deleteTipoTarefa = function(tt) {
        baseService.delete(tt, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                attTiposTarefa();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
        });
    }

    $scope.setPermissoesCargo = function(cargo) {

        cargoService.setPermissoes(cargo, cargo.permissoes, function(r) {

            if (r.sucesso) {

                msg.alerta("Permissoes colocadas com sucesso");

            } else {

                msg.erro("Problema ao colocar permissoes");

            }

        })

    }

    $scope.mergeCargo = function(cargo) {
        if (cargo.nome === "") {
            msg.erro("Digite o nome");
            return;
        }
        baseService.merge(cargo, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.cargo = angular.copy($scope.cargo_novo);
                attCargos();
            } else {
                msg.erro("Problema ao efetuar operacao. " + r.mensagem);
            }
        });
    }

    $scope.deleteCargo = function(cargo) {
        baseService.delete(cargo, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                attCargos();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
        });
    }

    $scope.mergeUsuario = function() {

        if ($scope.usuario.endereco.cidade == null) {
            msg.erro("Usuario sem cidade.");
            return;
        }

        baseService.merge($scope.usuario, function(r) {
            if (r.sucesso) {
                $scope.usuario = r.o;

                usuarioService.setPermissoesAbaixo($scope.permissoes_abaixo, function(t) {

                    if (t.sucesso) {

                        msg.alerta("Operacao efetuada com sucesso");
                        $scope.setUsuario($scope.usuario);
                        $scope.usuarios.attList();

                    } else {

                        msg.erro("Ocorreu um problema na operacao");

                    }

                })

            } else {
                msg.erro("Problema ao efetuar operacao. " + r.mensagem);
            }
        });



    }

    $scope.dispensar = function() {
        usuarioService.dispensar($scope.usuario, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.usuarios.attList();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
        })
    }

    $scope.deleteUsuario = function() {
        baseService.delete($scope.usuario, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.usuarios.attList();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
        });
    }

    $scope.removeUsuario = function(tel) {

        remove($scope.usuario.telefones, tel);

    }
    $scope.addUsuario = function() {
        $scope.usuario.telefones[$scope.usuario.telefones.length] = $scope.telefone;
        $scope.telefone = angular.copy($scope.telefone_novo);
    }

})

rtc.controller("crtEntrada", function($scope, sistemaService, uploadService) {

    $scope.xmls = [];

    $scope.pedidos = [];

    $scope.arquivos = [];

    var buscarPedido = function(xml, i) {

        sistemaService.getPedidoEntradaSemelhante(xml, function(p) {

            if (p.sucesso) {

                var pedidos = p.pedidos;

                if (pedidos.length == 0) {

                    msg.erro("Nao foi encontrado nenhum pedido de compra referente a essa Nota");

                } else {

                    var p = pedidos[0];
                    p.nota.xml = $scope.arquivos[i];
                    p.notas_logisticas[p.notas_logisticas.length] = p.nota;

                    $scope.pedidos = [p];

                }

            } else {

                msg.erro(p.mensagem);

            }

        })

    }

    $scope.removeOperacao = function(op) {

        remove($scope.pedidos[0].notas_logisticas, op);

    }

    $scope.finalizarNotas = function(pedido) {

        sistemaService.finalizarNotas(pedido, function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso");
                window.location = 'lotes.php';
                $scope.pedidos = [];

            } else {

                msg.erro("Problema ao efetuar operacao " + r.mensagem);

            }

        })

    }

    $("#flXML").change(function() {

        var arquivos = $(this).prop("files");

        for (var i = 0; i < arquivos.length; i++) {
            var sp = arquivos[i].name.split(".");
            if (sp[sp.length - 1] != "xml") {
                msg.alerta("Arquivo: " + arquivos[i].name + ", invalido");
                $("#grpArquivos").removeClass("has-success").addClass("has-error");
                return;
            }
        }


        uploadService.upload(arquivos, function(arqs, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo");

            } else {

                $scope.arquivos = arqs;

                for (var i = 0; i < arquivos.length; i++) {
                    var reader = new FileReader();
                    reader["ii"] = i;
                    reader.onload = function(arquivo) {


                        var json = xmlToJson(arquivo.target.result);

                        buscarPedido(json, this.ii);

                    };
                    reader.readAsText(arquivos[i]);
                }

            }

        })

    });


})
rtc.controller("crtProdutoClienteLogistic", function($scope, produtoClienteLogisticService) {

    $scope.produtos = createAssinc(produtoClienteLogisticService, 1, 10, 10);
    $scope.produtos.attList();
    assincFuncs(
        $scope.produtos,
        "produto", ["id_universal", "nome", "empresa.nome"]);

    $scope.to = function(num) {
        var k = [];
        for (var i = 0; i < num; i++) {
            k[i] = i;
        }
        return k;
    }


})
rtc.controller("crtMovimentos", function($scope, movimentoService, sistemaService, notaService, bancoService, baseService) {

    $scope.movimentos = createAssinc(movimentoService, 1, 10, 10);
    $scope.movimentos.attList();
    assincFuncs(
        $scope.movimentos,
        "movimento", ["id", "valor", "nota.ficha", "juros", "descontos", "data", "banco.nome", "saldo_anterior", "operacao.nome", "historico.nome"]);

    $scope.bancos = createAssinc(bancoService, 1, 3, 10);

    assincFuncs(
        $scope.bancos,
        "banco", ["id", "codigo", "nome", "conta", "agencia", "saldo"], "filtroBanco");


    notaService.filtro_base = "nota.emitida=true AND nota.cancelada=false";
    $scope.notas = createAssinc(notaService, 1, 10, 10);

    assincFuncs(
        $scope.notas,
        "nota", ["ficha", "numero", "saida", "data_emissao", "cliente.razao_social", "fornecedor.nome"], "filtroNota");

    $scope.movimento_novo = {};
    $scope.movimento = {};

    $scope.data_atual = new Date().getTime();

    movimentoService.getMovimento(function(m) {

        $scope.movimento_novo = m.movimento;
        $scope.movimento = angular.copy(m.movimento);

    })

    sistemaService.getOperacoes(function(o) {

        $scope.operacoes = o.operacoes;

    })

    sistemaService.getHistoricos(function(h) {

        $scope.historicos = h.historicos;

    })

    $scope.getVencimentos = function(nota) {

        notaService.getVencimentos(nota, function(v) {

            nota.vencimentos = v.vencimentos;

        })

    }

    $scope.corretorSaldo = function(m) {

        movimentoService.corretorSaldo(m, function(v) {

            msg.alerta("Saldo corrigido a partir desse movimento");
            $scope.movimentos.attList();

        })

    }

    $scope.novoMovimento = function() {

        $scope.movimento = angular.copy($scope.movimento_novo);
        $scope.movimento.data_texto = toTime($scope.movimento.data);
        $scope.movimento.historico = $scope.historicos[0];
        $scope.movimento.operacao = $scope.operacoes[0];


    }

    $scope.criarEstorno = function(movimento) {

        $scope.novoMovimento();

        var m = $scope.movimento;
        m.data = movimento.data;
        m.data_texto = toTime(m.data);
        m.banco = movimento.banco;
        m.estorno = movimento.id;
        m.valor = movimento.valor + movimento.juros - movimento.descontos;
        m.vencimento = movimento.vencimento;
        m.juros = 0;
        m.descontos = 0;

    }

    $scope.setMovimento = function(movimento) {

        $scope.movimento = movimento;
        $scope.movimento.data_texto = toTime($scope.movimento.data);
        equalize(movimento, "operacao", $scope.operacoes);
        equalize(movimento, "historico", $scope.historicos);

    }

    $scope.setBanco = function(banco) {

        $scope.movimento.banco = banco;

    }

    $scope.setVencimento = function(vencimento) {

        $scope.movimento.vencimento = vencimento;
        vencimento.movimento = $scope.movimento;

    }

    $scope.mergeMovimento = function() {

        if ($scope.movimento.banco == null) {
            msg.erro("Movimento sem banco");
            return;
        }

        if ($scope.movimento.operacao == null) {
            msg.erro("Movimento sem operacao");
            return;
        }

        if ($scope.movimento.historico == null) {
            msg.erro("Movimento sem historico");
            return;
        }
        /*
         $scope.movimento.data = fromTime($scope.movimento.data_texto);
         
         if ($scope.movimento.data < 0) {
         
         msg.alerta("Data do movimento incorreta");
         return;
         
         }
         */

        baseService.insert($scope.movimento, function(r) {
            if (r.sucesso) {
                $scope.movimento = r.o;
                equalize($scope.movimento, "operacao", $scope.operacoes);
                equalize($scope.movimento, "historico", $scope.historicos);
                msg.alerta("Operacao efetuada com sucesso");
                $scope.movimentos.attList();
            } else {
                msg.erro("Problema ao efetuar operacao. " + r.mensagem);
            }
        });

    }
    $scope.deleteMovimento = function() {
        baseService.delete($scope.movimento, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.movimentos.attList();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
        });
    }

    $scope.removeDocumento = function(documento) {
        remove($scope.fornecedor.documentos, documento);
    }

})

rtc.controller("crtNotas", function($scope, logService, notaService, empresaService, baseService, produtoService, produtoNotaService, vencimentoService, sistemaService, formaPagamentoService, transportadoraService, clienteService, fornecedorService, uploadService) {

    $scope.empresas__clientes = [];
    $scope.empresa__cliente = null;

    empresaService.getEmpresa(function(e) {

        $scope.empresas__clientes[$scope.empresas__clientes.length] = e.empresa;
        $scope.empresa__cliente = e.empresa;
        empresaService.getEmpresasClientes(function(c) {
            for (var i = 0; i < c.clientes.length; i++) {
                $scope.empresas__clientes[$scope.empresas__clientes.length] = c.clientes[i];
            }
            $scope.alterarEmpresa();
        })


    })

    $scope.hv = function(nota, nome) {

        if (nota.visto.indexOf(nome) >= 0) {

            return false;

        }

        return true;

    }

    $scope.vistar = function(nota) {

        notaService.vistar(nota, function(n) {

            if (n.sucesso) {

                nota.visto = n.o.visto;

            }

        })

    }

    var pr = false;
    $scope.alterarEmpresa = function() {

        notaService.empresa = $scope.empresa__cliente;
        produtoService.empresa = $scope.empresa__cliente.id;
        transportadoraService.empresa = $scope.empresa__cliente;
        clienteService.empresa = $scope.empresa__cliente;
        fornecedorService.empresa = $scope.empresa__cliente;

        $scope.notas = createAssinc(notaService, 1, 10, 10);

        assincFuncs(
            $scope.notas,
            "nota", ["ficha", "numero", "transportadora.razao_social", "saida", "id_pedido", "data_emissao", "cliente.razao_social", "fornecedor.nome"]);


        $scope.notas["posload"] = function(elementos) {
            for (var i = 0; i < elementos.length; i++) {
                elementos[i]["cartas_correcao"] = [];
            }
        }

        $scope.produtos = createAssinc(produtoService, 1, 3, 4);

        assincFuncs(
            $scope.produtos,
            "produto", ["codigo", "nome", "disponivel"], "filtroProdutos");

        $scope.transportadoras = createAssinc(transportadoraService, 1, 3, 4);

        assincFuncs(
            $scope.transportadoras,
            "transportadora", ["codigo", "razao_social"], "filtroTransportadoras");

        $scope.clientes = createAssinc(clienteService, 1, 3, 4);

        assincFuncs(
            $scope.clientes,
            "cliente", ["codigo", "razao_social"], "filtroClientes");

        $scope.fornecedores = createAssinc(fornecedorService, 1, 3, 4);

        assincFuncs(
            $scope.fornecedores,
            "fornecedor", ["codigo", "nome"], "filtroFornecedores");

        $scope.empresas_clientes = [];
        $scope.empresa = null;
        $scope.empresa_produtos = null;

        $scope.operacao_sefaz = 0;

        $scope.selecao_minuta = [];


        $scope.selecionar = function(r) {

            var k = $scope.isSelecionada(r);

            if (k < 0) {
                $scope.selecao_minuta[$scope.selecao_minuta.length] = r;
            } else {
                for (var i = k; k < $scope.selecao_minuta.length - 1; i++) {
                    $scope.selecao_minuta[i] = $scope.selecao_minuta[i + 1];
                }
                $scope.selecao_minuta.length--;
            }

        }

        $scope.isSelecionada = function(r) {

            for (var i = 0; i < $scope.selecao_minuta.length; i++) {
                if ($scope.selecao_minuta[i].id === r.id) {
                    return i;
                }
            }
            return -1;

        }

        $scope.emitirMinutas = function() {


            if ($scope.selecao_minuta.length > 0) {

                var id_empresa = encode64SPEC($scope.selecao_minuta[0].empresa.id + "");

                var notas = "";

                for (var i = 0; i < $scope.selecao_minuta.length; i++) {

                    if (i > 0) {
                        notas += ";";
                    }

                    notas += $scope.selecao_minuta[i].id;

                }

                notas = encode64SPEC(notas);

                window.open(projeto + "/minuta.php?id_empresa=" + id_empresa + "&notas=" + notas);

            }

        }


        $scope.emitir = function(nota) {
            $scope.operacao_sefaz = 20;
            notaService.emitir(nota, function(r) {
                if (r.sucesso) {
                    $scope.operacao_sefaz = 0;
                    msg.alerta(r.retorno_sefaz);
                } else {
                    msg.erro("Ocorreu um problema ao efetuar a operacao");
                }
            })
        }

        $scope.corrigir = function(nota) {


            $scope.observacao_sefaz = formatTextArea(nota.observacao_sefaz);

            if ($scope.observacao_sefaz.length < 16) {
                msg.erro("Digite uma observacao maior");
                return;
            }
            $scope.operacao_sefaz = 10;
            notaService.corrigir(nota, $scope.observacao_sefaz, function(r) {
                if (r.sucesso) {
                    $scope.operacao_sefaz = 0;
                    if (r.retorno_sefaz === true) {
                        msg.alerta("Carta de correcao efetuada com sucesso");
                    } else {
                        msg.alerta("Carta de correcao efetuada com sucesso");
                    }
                } else {
                    msg.erro("Ocorreu um problema ao efetuar a operacao");
                }
            })
        }

        $scope.manifestar = function(nota) {
            $scope.operacao_sefaz = 10;
            notaService.manifestar(nota, function(r) {
                if (r.sucesso) {
                    $scope.operacao_sefaz = 0;
                    if (r.retorno_sefaz === true) {
                        msg.alerta("Nota manifestada com sucesso");
                    } else {
                        msg.alerta("Falha ao manifestar nota");
                    }
                } else {
                    msg.erro("Ocorreu um problema ao efetuar a operacao");
                }
            })
        }

        $scope.cancelar = function(nota) {
            $scope.observacao_sefaz = formatTextArea(nota.observacao_sefaz);
            if ($scope.observacao_sefaz.length < 16) {
                msg.erro("Digite uma observacao maior");
                return;
            }
            $scope.operacao_sefaz = 10;
            notaService.cancelar(nota, $scope.observacao_sefaz, function(r) {
                if (r.sucesso) {
                    $scope.operacao_sefaz = 0;
                    if (r.retorno_sefaz === true) {
                        msg.alerta("Nota cancelada com sucesso");
                    } else {
                        msg.alerta("Falha ao cancelar nota");
                    }
                } else {
                    msg.erro("Ocorreu um problema ao efetuar a operacao");
                }
            })
        }

        $scope.uploadXML = function(k) {
            $("#" + k).change(function() {

                uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

                    if (!sucesso) {

                        msg.erro("Falha ao subir arquivo");

                    } else {

                        for (var i = 0; i < arquivos.length; i++) {

                            $scope.nota.xml = arquivos[i];

                        }

                        msg.alerta("Upload feito com sucesso");
                    }

                })

            }).click();
        }
        $scope.uploadDANFE = function(k) {
            $("#" + k).change(function() {

                uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

                    if (!sucesso) {

                        msg.erro("Falha ao subir arquivo");

                    } else {

                        for (var i = 0; i < arquivos.length; i++) {

                            $scope.nota.danfe = arquivos[i];

                        }

                        msg.alerta("Upload feito com sucesso");
                    }

                })

            }).click();
        }

        $scope.nova_novo = {};

        $scope.produto_nota_novo = {};

        $scope.produto_nota = {};

        $scope.vencimento_novo = {};

        $scope.vencimento = {};

        $scope.nota = {};

        $scope.produto = {};

        $scope.formas_pagamento = {};




        $scope.setTransportadora = function(trans) {

            $scope.nota.transportadora = trans;

        }


        $scope.getTotalNota = function() {

            var total = 0;

            for (var i = 0; i < $scope.nota.produtos.length; i++) {

                var p = $scope.nota.produtos[i];

                total += p.valor_total;

            }

            return total;

        }

        $scope.calcular = function() {

            for (var i = 0; i < $scope.nota.produtos.length; i++) {
                var p = $scope.nota.produtos[i];
                p.valor_total = p.valor_unitario * p.quantidade;
            }

            if ($scope.nota.calcular_valores) {
                notaService.calcularImpostosAutomaticamente($scope.nota, function(n) {

                    $scope.nota = n.o;
                    $scope.nota.calcular_valores = true;
                    equalize($scope.nota, "forma_pagamento", $scope.formas_pagamento);

                })
            }

        }

        $scope.setCliente = function(cli) {

            $scope.nota.cliente = cli;

        }

        $scope.setFornecedor = function(forn) {

            $scope.nota.fornecedor = forn;

        }

        vencimentoService.getVencimento(function(v) {

            $scope.vencimento = v.vencimento;
            $scope.vencimento.data_texto = toDate(v.vencimento.data);

            $scope.vencimento_novo = angular.copy($scope.vencimento);

        })

        produtoNotaService.getProdutoNota(function(pp) {

            $scope.produto_nota_novo = pp.produto_nota;
            $scope.produto_nota = angular.copy(pp.produto_nota);

        })

        $scope.setProduto = function(produto) {

            $scope.produto_nota.produto = produto;
            $scope.addProduto();

        }

        $scope.addProduto = function(produto) {


            $scope.nota.produtos[$scope.nota.produtos.length] = $scope.produto_nota;
            $scope.produto_nota.nota = $scope.nota;
            $scope.produto_nota = angular.copy($scope.produto_nota_novo);
            $scope.calcular();

        }

        $scope.removerProduto = function(produto) {

            remove($scope.nota.produtos, produto);

        }

        $scope.mergeNota = function() {

            var n = $scope.nota;

            if (n.cliente == null && n.saida) {
                msg.erro("Nota de saida sem cliente.");
                return;
            }

            if (n.transportadora == null) {
                msg.erro("Nota sem transportadora.");
                return;
            }

            if (n.fornecedor == null && !n.saida) {
                msg.erro("Nota de entrada sem fornecedor.");
                return;
            }

            if (n.forma_pagamento == null) {
                msg.erro("Nota sem forma de pagamento");
                return;
            }

            for (var i = 0; i < $scope.nota.vencimentos.length; i++) {
                $scope.nota.vencimentos[i].data = fromDate($scope.nota.vencimentos[i].data_texto);
                if ($scope.nota.vencimentos[i].data < 0) {
                    msg.alerta("Data do " + (i + 1) + "?? vencimento, incorreta");
                    return;
                }
            }

            $scope.nota.data_emissao = fromTime($scope.nota.data_emissao_texto);
            if ($scope.nota.data_emissao < 0) {
                msg.alerta("Data de emissao incorreta");
                return;
            }

            baseService.merge(n, function(r) {
                if (r.sucesso) {
                    $scope.nota = r.o;
                    equalize($scope.nota, "forma_pagamento", $scope.formas_pagamento);
                    msg.alerta("Operacao efetuada com sucesso");
                    $scope.notas.attList();
                } else {
                    $scope.nota = r.o;
                    equalize($scope.nota, "forma_pagamento", $scope.formas_pagamento);
                    msg.erro("Ocorreu o seguinte problema: " + r.mensagem);
                }
            });

        }

        notaService.getNota(function(n) {

            n.nota.produtos = [];
            n.nota.xml = "";
            n.nota.danfe = "";
            $scope.nota_novo = angular.copy(n.nota);


            empresaService.getEmpresasClientes(function(e) {

                $scope.empresas_clientes = e.clientes;

                empresaService.getEmpresa(function(e) {

                    $scope.empresas_clientes[$scope.empresas_clientes.length] = e.empresa;

                    $scope.setEmpresa(e.empresa);

                })

            })

        })


        $scope.setEmpresaProdutos = function() {

            produtoService.empresa = $scope.empresa_produtos.id;

            if ($scope.empresa_produtos.id !== $scope.empresa.id) {

                produtoService.filtro_base = "produto.id_logistica=" + $scope.empresa.id;

            } else {

                produtoService.filtro_base = undefined;

            }

            $scope.produtos.attList();

        }

        $scope.setEmpresa = function(emp) {

            $scope.empresa = emp;
            $scope.empresa_produtos = emp;
            $scope.nota_novo.empresa = emp;
            //notaService.empresa = emp;
            $scope.notas.attList();

            //produtoService.empresa = emp;
            //transportadoraService.empresa = emp;
            //clienteService.empresa = emp;
            //fornecedorService.empresa = emp;

        }

        $scope.removeVencimento = function(v) {

            if (v.movimento !== null) {

                msg.erro("O vencimento tem um movimento relacionado e nao pode ser excluido");
                return;

            }

            remove($scope.nota.vencimentos, v);

        }

        $scope.addVencimento = function() {

            $scope.nota.vencimentos[$scope.nota.vencimentos.length] = $scope.vencimento;
            $scope.vencimento.nota = $scope.nota;
            $scope.vencimento = angular.copy($scope.vencimento_novo);

        }

        $scope.novoNota = function() {

            $scope.setNota(angular.copy($scope.nota_novo));

        }


        $scope.setNota = function(nota) {

            $scope.nota = nota;
            $scope.nota.calcular_valores = false;

            $scope.nota.data_emissao_texto = toTime($scope.nota.data_emissao);

            if ($scope.nota.id === 0) {

                $scope.nota.vencimentos = [];
                $scope.nota.produtos = [];

                formaPagamentoService.getFormasPagamento($scope.nota, function(f) {

                    $scope.formas_pagamento = f.formas;
                    $scope.nota.forma_pagamento = $scope.formas_pagamento[0];
                    loading.close();

                });

                $scope.calcular();

                return;

            }

            $scope.imprimirCarta = function(carta) {

                window.open(projeto + "/carta_correcao.php?nota=" + carta.nota.id + "&carta=" + carta.id + "&id_empresa=" + nota.empresa.id);

            }

            logService.getLogs(nota, function(l) {

                nota.logs = l.logs;

            })

            notaService.getCartasCorrecao(nota, function(c) {

                nota.cartas_correcao = c.cartas;

            })

            notaService.getProdutos(nota, function(p) {

                nota.produtos = p.produtos;

                notaService.getVencimentos(nota, function(v) {

                    nota.vencimentos = v.vencimentos;

                    for (var i = 0; i < nota.vencimentos.length; i++) {

                        nota.vencimentos[i].data_texto = toDate(nota.vencimentos[i].data);

                    }

                    formaPagamentoService.getFormasPagamento(nota, function(f) {
                        $scope.formas_pagamento = f.formas;
                        equalize(nota, "forma_pagamento", $scope.formas_pagamento);
                        $scope.calcular();
                    })

                })

            })

        }

        $scope.deleteNota = function() {

            baseService.delete($scope.nota, function(r) {
                if (r.sucesso) {
                    msg.alerta("Operacao efetuada com sucesso");
                    $scope.nota = angular.copy($scope.novo_pedido);
                    $scope.notas.attList();
                } else {
                    msg.erro("Problema ao efetuar operacao");
                }
            });

        }

    }

})

rtc.controller("crtBancos", function($scope, bancoService, empresaService, baseService) {


    $scope.empresa_atual = null;
    $scope.empresas_clientes = [];
    empresaService.getEmpresasClientes(function(e) {
        $scope.empresas_clientes = e.clientes;
        empresaService.getEmpresa(function(e) {
            var a = null;
            for (var i = 0; i < $scope.empresas_clientes.length; i++) {
                if ($scope.empresas_clientes[i].id === e.empresa.id) {
                    a = $scope.empresas_clientes[i];
                    break;
                }
            }
            if (a === null) {
                a = e.empresa;
                $scope.empresas_clientes[$scope.empresas_clientes.length] = a;
            }
            $scope.empresa_atual = a;
        })
    })
    $scope.trocaEmpresa = function() {
        bancoService.empresa = $scope.empresa_atual;
        $scope.bancos.attList();
        $scope.init();
    }

    $scope.bancos = createAssinc(bancoService, 1, 3, 10);
    $scope.bancos.attList();
    assincFuncs(
        $scope.bancos,
        "banco", ["id", "codigo", "nome", "conta", "agencia", "saldo"]);

    $scope.banco_novo = {};
    $scope.banco = {};
    $scope.estado = {};

    $scope.data_atual = new Date().getTime();

    $scope.init = function() {

        bancoService.getBanco(function(p) {

            $scope.banco_novo = p.banco;

        })

    }

    $scope.init();

    $scope.novoBanco = function() {

        $scope.banco = angular.copy($scope.banco_novo);

    }

    $scope.setBanco = function(banco) {

        $scope.banco = banco;
    }

    $scope.mergeBanco = function() {

        baseService.merge($scope.banco, function(r) {
            if (r.sucesso) {
                $scope.banco = r.o;


                msg.alerta("Operacao efetuada com sucesso");
                $scope.setBanco($scope.banco);
                $scope.bancos.attList();



            } else {
                msg.erro("Problema ao efetuar operacao. ");
            }
        });

    }
    $scope.deleteBanco = function() {
        baseService.delete($scope.banco, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.bancos.attList();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
        });
    }

})
rtc.controller("crtCotacoesEntrada", function($scope, cotacaoGrupalService, cotacaoEntradaService, empresaService, transportadoraService, tabelaService, baseService, produtoService, sistemaService, statusCotacaoEntradaService, fornecedorService, produtoCotacaoEntradaService) {

    $scope.cortar = function(texto, num) {

        if (texto.length < num) {

            return texto;

        }

        return texto.substring(0, num) + "...";

    }

    $scope.cotacoesGrupais = createAssinc(cotacaoGrupalService, 1, 10, 10);
    $scope.cotacoesGrupais.attList();
    assincFuncs(
        $scope.cotacoesGrupais,
        "c", ["id"]);
    $scope.cotacoesGrupais.posload = function() {

        setTimeout(function() {
            loading.redux();
        }, 200);

    }

    $scope.empresas = [];
    $scope.local_retirada = null;

    $scope.localEntregaEspec = function(local) {

        var e = local;


        for (var i = 0; i < $scope.empresas.length; i++) {
            if ($scope.empresas[i].codigo === e) {
                e = $scope.empresas[i];
                break;
            }
        }

        var valor = "Local de Entrega: " + e.nome + "<br>CNPJ: " + e.cnpj.valor + "<br>Estado: " +
            e.endereco.cidade.estado.sigla + "<br>Cidade: " + e.endereco.cidade.nome +
            "<br>Bairro: " + e.endereco.bairro + "<br>Rua: " +
            e.endereco.rua + "<br>Numero: " + e.endereco.numero + "<br>CEP: " + e.endereco.cep.valor;

        $scope.cotacao.observacao = valor;


    }

    $scope.localEntrega = function() {

        var e = $scope.local_retirada;

        var valor = "Local de Entrega: " + e.nome + "<br>CNPJ: " + e.cnpj.valor + "<br>Estado: " +
            e.endereco.cidade.estado.sigla + "<br>Cidade: " + e.endereco.cidade.nome +
            "<br>Bairro: " + e.endereco.bairro + "<br>Rua: " +
            e.endereco.rua + "<br>Numero: " + e.endereco.numero + "<br>CEP: " + e.endereco.cep.valor;

        $scope.cotacao.observacao = valor;


    }

    empresaService.getGrupoEmpresarial(function(g) {

        $scope.empresas = g.grupo;

        for (var i = 0; i < $scope.empresas.length; i++) {
            $scope.empresas[i].codigo = i;
        }

        $scope.local_retirada = g.grupo[0];
        $scope.localEntrega();
    })

    $scope.getFornecedores = function(c) {

        var forns = "";
        for (var i = 0; i < c.fornecedores.length; i++) {

            var f = c.fornecedores[i].nome;

            if (i > 0 && forns.length + f.length > 40) {
                var qtd = (c.fornecedores.length - i - 1);
                if (qtd > 1) {
                    forns += " E outros " + qtd + "...";
                } else {
                    forns += " Mais um..."
                }
                break;
            }

            forns += f + "; ";

        }

        return forns;

    }

    $scope.getQuantidadeRespostas = function(c) {

        var f = angular.copy(c.fornecedores);
        var qtd = 0;
        for (var i = 0; i < c.produtos.length; i++) {
            for (var j = 0; j < c.produtos[i].respostas.length; j++) {
                var r = c.produtos[i].respostas[j];
                for (var k = 0; k < f.length; k++) {
                    if (f[k] === null) {
                        continue;
                    }
                    if (f[k].id === r.fornecedor.id) {
                        qtd++;
                        f[k] = null;
                    }
                }
            }
        }

        return qtd;

    }

    $scope.grupal1_normal0 = false;

    $scope.cotacaoGrupal = {};


    $scope.setCotacaoGrupal = function(c) {

        $scope.cotacaoGrupal = c;
        $scope.grupal1_normal0 = true;

    }

    $scope.cotacoes = createAssinc(cotacaoEntradaService, 1, 10, 10);
    $scope.cotacoes.attList();
    assincFuncs(
        $scope.cotacoes,
        "cotacao_entrada", ["id", "fornecedor.nome", "id_status", "data", "usuario.nome"]);
    $scope.cotacoes.posload = function() {

        setTimeout(function() {
            loading.redux();
        }, 200);

    }

    if (typeof rtc["id_cotacao"] !== 'undefined' && typeof rtc['id_empresa'] !== 'undefined') {

        produtoService.empresa = rtc['id_empresa'];

    }

    $scope.produtos = createAssinc(produtoService, 1, 6, 4);

    assincFuncs(
        $scope.produtos,
        "produto", ["codigo", "nome", "disponivel"], "filtroProdutos");
    $scope.produtos.posload = function() {

        setTimeout(function() {
            loading.redux();
        }, 200);

    }

    $scope.fornecedores = createAssinc(fornecedorService, 1, 6, 4);

    assincFuncs(
        $scope.fornecedores,
        "fornecedor", ["codigo", "nome"], "filtroFornecedores");

    $scope.fornecedores.posload = function() {

        setTimeout(function() {
            loading.redux();
        }, 200);

    }

    $scope.status_cotacao = [];

    $scope.cotacao_novo = {};

    $scope.produto_cotacao_novo = {};

    $scope.cotacao = {};

    $scope.qtd = 0;

    $scope.frete = 0;

    $scope.valor = 0;

    $scope.produto = {};

    $scope.fretes = [];

    $scope.podeFormarPedido = function() {

        return $scope.cotacao.status.id == 2;

    }

    $scope.transp = null;

    $scope.formarPedido = function(transportadora) {

        cotacaoEntradaService.formarPedido($scope.cotacao, transportadora, $scope.frete, function(f) {

            if (f.sucesso) {

                $scope.cotacao = f.o.cotacao;
                equalize($scope.cotacao, "status", $scope.status_cotacao);
                msg.alerta("Operacao efetuada com sucesso, altere os detahes do pedido gerado.");

            } else {

                msg.erro("Problema ao efetuar operacao");

            }


        })

    }

    statusCotacaoEntradaService.getStatus(function(st) {

        $scope.status_cotacao = st.status;

    })


    $scope.setFornecedor = function(forn) {

        $scope.pedido.fornecedor = forn;


    }

    produtoCotacaoEntradaService.getProdutoCotacao(function(pp) {

        $scope.produto_cotacao_novo = pp.produto_cotacao;

    })

    $scope.enviandoEmail = false;
    $scope.enviarEmailsCotacaoGrupal = function() {
        $scope.enviandoEmail = true;
        cotacaoGrupalService.enviarEmails($scope.cotacaoGrupal, function(r) {

            if (r.sucesso) {

                msg.alerta("Os emails foram enviados com sucesso");

                $scope.cotacaoGrupal.enviada = true;

            } else {

                msg.erro("Ocorreu um problema, tente mais tarde");

            }
            $scope.enviandoEmail = false;

        })


    }

    $scope.getTotalCotacao = function() {

        var tot = 0;

        for (var i = 0; i < $scope.cotacao.produtos.length; i++) {

            var p = $scope.cotacao.produtos[i];

            tot += (p.valor) * p.quantidade;

        }

        return tot;

    }

    $scope.produto_cotacao_grupal_novo = {};
    $scope.cotacao_grupal_novo = {};

    cotacaoGrupalService.getCotacaoGrupal(function(c) {

        $scope.cotacao_grupal_novo = c.cotacao_grupal;

    })

    cotacaoGrupalService.getProdutoCotacaoGrupal(function(p) {

        $scope.produto_cotacao_grupal_novo = p.produto_cotacao_grupal;

    })

    $scope.getTotalCotacaoGrupal = function() {

        var totais = [];

        for (var i = 0; i < $scope.cotacaoGrupal.produtos.length; i++) {

            var p = $scope.cotacaoGrupal.produtos[i];
            lbl:
                for (var j = 0; j < p.respostas.length; j++) {

                    var r = p.respostas[j];

                    var subTotal = r.valor * r.quantidade;

                    for (var k = 0; k < totais.length; k++) {

                        if (totais[k].fornecedor.id === r.fornecedor.id) {

                            totais[k].total += subTotal;
                            continue lbl;
                        }

                    }

                    var total = {
                        fornecedor: r.fornecedor,
                        total: subTotal
                    }

                    totais[totais.length] = total;

                }

        }

        var str = "";

        if (totais.length === 0) {

            str = "NinguÃÂ©m respondeu ainda, nÃÂ£o ÃÂ© possÃÂ­vel calcular total";

        } else {

            for (var i = 0; i < totais.length; i++) {

                str += totais[i].fornecedor.nome + ": R$ " + totais[i].total.toFixed(2).split('.').join(',') + ";   ";

            }

        }

        return str;

    }

    $scope.getRespostas = function(produto) {

        var fornecedores = angular.copy(produto.cotacao.fornecedores);

        lbl:
            for (var i = 0; i < produto.respostas.length; i++) {
                var f = produto.respostas[i].fornecedor;
                for (var j = 0; j < fornecedores.length; j++) {
                    if (fornecedores[j].id === f.id) {
                        continue lbl;
                    }
                }
                fornecedores[fornecedores.length] = f;
            }

        var respostas = [];

        for (var i = 0; i < fornecedores.length; i++) {

            var resp = null;
            for (var j = 0; j < produto.respostas.length; j++) {
                var resposta = produto.respostas[j];
                if (resposta.fornecedor.id === fornecedores[i].id) {
                    resp = resposta;
                }
            }

            if (resp !== null) {
                respostas[respostas.length] = resp;
            } else {

                var resp = {
                    fornecedor: fornecedores[i],
                    momento: 0
                }

                respostas[respostas.length] = resp;

            }

        }

        return respostas;

    }

    $scope.novaCotacaoGrupal = function() {

        $scope.cotacaoGrupal = angular.copy($scope.cotacao_grupal_novo);
        $scope.grupal1_normal0 = true;

    }

    $scope.addProduto = function(produto) {

        if ($scope.qtd == 0) {

            $("#txtPQtd").focus().css("border", "2px solid Red");
            return;

        } else {

            $("#txtPQtd").focus().css("border", "1px solid Gray");

        }

        if ($scope.valor == 0) {

            $("#txtPValor").focus().css("border", "2px solid Red");
            return;

        } else {

            $("#txtPValor").focus().css("border", "1px solid Gray");

        }

        if ($scope.grupal1_normal0) {

            var pp = angular.copy($scope.produto_cotacao_grupal_novo);
            pp.produto = produto;
            pp.cotacao = $scope.cotacaoGrupal;
            pp.quantidade = $scope.qtd;

            $scope.cotacaoGrupal.produtos[$scope.cotacaoGrupal.produtos.length] = pp;

            msg.confirma("Deseja colocar os fornecedores que fornecem esse produto ?", function() {

                cotacaoGrupalService.getFornecedores(produto, function(f) {

                    lbl: for (var i = 0; i < f.fornecedores.length; i++) {

                        for (var j = 0; j < $scope.cotacaoGrupal.fornecedores.length; j++) {
                            if ($scope.cotacaoGrupal.fornecedores[j].id === f.fornecedores[i].id || $scope.cotacaoGrupal.fornecedores[j].cnpj.valor === f.fornecedores[i].cnpj.valor) {
                                continue lbl;
                            }
                        }

                        $scope.cotacaoGrupal.fornecedores[$scope.cotacaoGrupal.fornecedores.length] = f.fornecedores[i];

                    }

                })

            })

        } else {
            var pp = angular.copy($scope.produto_cotacao_novo);
            pp.produto = produto;
            pp.cotacao = $scope.cotacao;
            pp.valor = $scope.valor;
            pp.quantidade = $scope.qtd;

            for (var j = 0; j < $scope.cotacao.produtos.length; j++) {

                var pr = $scope.cotacao.produtos[j];

                if (pr.produto.id === pp.produto.id) {

                    pr.quantidade += pp.quantidade;
                    $("#produtos").modal("hide");
                    return;

                }

            }

            pp.valor_unitario = pp.valor / pp.produto.quantidade_unidade;

            $scope.cotacao.produtos[$scope.cotacao.produtos.length] = pp;
        }


        $("#produtos").modal("hide");

    }

    $scope.removerProduto = function(produto) {
        if ($scope.grupal1_normal0) {
            remove($scope.cotacaoGrupal.produtos, produto);
        } else {
            remove($scope.cotacao.produtos, produto);
        }
    }

    $scope.mergeCotacao = function() {

        if (!$scope.grupal1_normal0) {
            var p = $scope.cotacao;

            if (typeof rtc["id_cotacao"] !== 'undefined' && typeof rtc['id_empresa'] !== 'undefined') {

                $scope.cotacao.status = $scope.status_cotacao[1];

            }

            if (p.fornecedor == null) {
                msg.erro("Cotacao sem fornecedor.");
                return;
            }

            if (p.status == null) {
                msg.erro("Cotacao sem status.");
                return;
            }
            if (p["observacao"] == null || typeof p["observacao"] === 'undefined') {
                p["observacao"] = "";
            }

        } else {
            $scope.cotacaoGrupal.observacoes = formatTextArea($scope.cotacaoGrupal.observacoes);
        }

        baseService.merge($scope.grupal1_normal0 === 1 ? $scope.cotacaoGrupal : $scope.cotacao, function(r) {
            if (r.sucesso) {
                if ($scope.grupal1_normal0) {
                    $scope.cotacaoGrupal = r.o;
                    $scope.cotacoesGrupais.attList();
                } else {
                    $scope.cotacao = r.o;
                    equalize($scope.cotacao, "status", $scope.status_cotacao);
                    $scope.cotacoes.attList();
                }
                msg.alerta("Operacao efetuada com sucesso");
            } else {
                $scope.cotacao = r.o;
                equalize($scope.cotacao, "status", $scope.status_cotacao);
                msg.erro("Ocorreu o seguinte problema: " + r.mensagem);
            }
        });

    }

    $scope.calculoPronto = function() {

        if ($scope.cotacao.fornecedor != null && $scope.cotacao.produtos != null) {
            if ($scope.cotacao.produtos.length > 0) {
                return true;
            }
        }
        return false;

    }


    $scope.getFretes = function() {

        var pesoTotal = 0;
        var valorTotal = 0;

        for (var i = 0; i < $scope.cotacao.produtos.length; i++) {
            var p = $scope.cotacao.produtos[i];
            valorTotal += (p.valor_base) * p.quantidade;
            pesoTotal += p.produto.peso_bruto * p.quantidade;
        }
        tabelaService.getFretes(null, { cidade: $scope.cotacao.fornecedor.endereco.cidade, valor: valorTotal, peso: pesoTotal }, function(f) {

            $scope.fretes = f.fretes;
        })

    }


    cotacaoEntradaService.getCotacao(function(ped) {

        ped.cotacao.produtos = [];
        $scope.cotacao_novo = ped.cotacao;

    })

    $scope.temCotacao = function() {

        return typeof $scope.cotacao["id"] !== 'undefined';

    }

    if (typeof rtc["id_cotacao"] !== 'undefined' && typeof rtc['id_empresa'] !== 'undefined') {

        cotacaoEntradaService.getCotacaoEspecifica(rtc["id_cotacao"], rtc["id_empresa"], function(f) {
            if (f.cotacoes.length > 0) {
                $scope.cotacao = f.cotacoes[0];
                $scope.setCotacao($scope.cotacao);
            }
        })

    }

    $scope.novoCotacao = function() {

        $scope.setCotacao(angular.copy($scope.cotacao_novo));

    }

    $scope.attValorUnitario = function(produto) {

        produto.valor = produto.valor_unitario * produto.produto.quantidade_unidade;

    }

    $scope.attValor = function(produto) {

        produto.valor_unitario = produto.valor / produto.produto.quantidade_unidade;

    }

    $scope.setCotacao = function(cotacao) {

        $scope.cotacao = cotacao;
        $scope.grupal1_normal0 = false;

        if ($scope.cotacao.id === 0) {

            $scope.cotacao.status = $scope.status_cotacao[0];

            return;

        }

        cotacaoEntradaService.getProdutos(cotacao, function(p) {

            cotacao.produtos = p.produtos;

            for (var i = 0; i < cotacao.produtos.length; i++) {
                cotacao.produtos[i].passar_pedido = false;
            }

            equalize(cotacao, "status", $scope.status_cotacao);

            var ic = $("#myIframe").contents();

            ic.find("#logoEmpresa img").remove();
            ic.find("#logoEmpresa").append($("#logo").clone().addClass("product-image"));
            ic.find("#infoEmpresa").html(cotacao.empresa.nome + ", " + cotacao.empresa.endereco.cidade.nome + "-" + cotacao.empresa.endereco.cidade.estado.sigla);
            ic.find("#infoEmpresa2").html(cotacao.empresa.endereco.bairro + ", " + cotacao.empresa.endereco.cep.valor + " - " + cotacao.empresa.telefone.numero);
            ic.find("#observacoes").html(cotacao.observacao);

            ic.find("#idPedido").html($scope.cotacao.id);
            ic.find("#nomeUsuario").html($scope.cotacao.usuario.nome);
            ic.find("#nomeCliente").html($scope.cotacao.fornecedor.nome);
            ic.find("#cnpjCliente").html($scope.cotacao.fornecedor.cnpj.valor);
            ic.find("#ruaCliente").html($scope.cotacao.fornecedor.endereco.rua);
            ic.find("#cidadeCliente").html($scope.cotacao.fornecedor.endereco.cidade.nome);


            var p = ic.find("#produto").each(function() {
                p = $(this);
            });

            p.hide();

            ic.find("#produtos").find("tr").each(function() {
                if (typeof $(this).data("gerado") !== 'undefined') {
                    $(this).remove();
                }
            });

            var p = p.clone();

            var icms = 0;
            var base = 0;
            var total = 0;
            for (var i = 0; i < $scope.cotacao.produtos.length; i++) {

                p = p.clone();

                var pro = $scope.cotacao.produtos[i];

                pro.valor_unitario = pro.valor / pro.produto.quantidade_unidade;

                icms += pro.icms;
                base += pro.base_calculo;
                p.find("[data-tipo='nome']").html(pro.produto.nome);
                p.find("[data-tipo='valor']").html(($scope.cotacao.tratar_em_litros ? (pro.valor / pro.produto.quantidade_unidade) : pro.valor).toFixed(2));
                p.find("[data-tipo='quantidade']").html(($scope.cotacao.tratar_em_litros ? (pro.quantidade * pro.produto.quantidade_unidade) : pro.quantidade).toFixed(2));
                p.find("[data-tipo='validade']").html('-----');
                p.find("[data-tipo='total']").html(((pro.valor) * pro.quantidade).toFixed(2));
                p.data("gerado", true);

                ic.find("#produtos").append(p);
                p.show();

                total += (pro.valor) * pro.quantidade;

            }
            var alicota = (icms * 100 / base).toFixed(2);

            ic.find("#prazo").html(cotacao.prazo);
            ic.find("#alicota").html('----');
            ic.find("#icms").html('-----');

            ic.find("#tipoFrete").html(cotacao.frete_incluso ? 'CIF' : 'FOB');
            ic.find("#nomeTransportadora").html(cotacao.transportadora.razao_social);
            ic.find("#contato").html(cotacao.transportadora.email.endereco);
            ic.find("#valorFrete").html(cotacao.frete);

            ic.find("#observacoes").html(cotacao.observacoes);
            ic.find("#nomeUsuario2").html(cotacao.usuario.nome);

        })


    }



    $scope.removeFornecedor = function(forn) {

        var nf = [];

        for (var i = 0; i < $scope.cotacaoGrupal.fornecedores.length; i++) {

            var f = $scope.cotacaoGrupal.fornecedores[i];

            if (f.id !== forn.id) {
                nf[nf.length] = f;
            }

        }

        $scope.cotacaoGrupal.fornecedores = nf;

    }

    $scope.setFornecedor = function(forn) {

        if ($scope.grupal1_normal0) {
            $scope.cotacaoGrupal.fornecedores[$scope.cotacaoGrupal.fornecedores.length] = forn;
        } else {
            $scope.cotacao.fornecedor = forn;
        }

        $("#clientes").modal("hide")

    }

    $scope.deleteCotacao = function() {

        baseService.delete($scope.grupal1_normal0 ? $scope.cotacaoGrupal : $scope.cotacao, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.cotacao = angular.copy($scope.novo_cotacao);
                $scope.cotacoes.attList();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
        });

    }



})


rtc.controller("crtPedidosEntrada", function($scope, pedidoEntradaService, empresaService, tabelaService, baseService, produtoService, sistemaService, statusPedidoEntradaService, transportadoraService, fornecedorService, produtoPedidoEntradaService) {

    $scope.cortar = function(texto, num) {

        if (texto.length < num) {

            return texto;

        }

        return texto.substring(0, num) + "...";

    }

    $scope.pedidos = createAssinc(pedidoEntradaService, 1, 15, 4);
    assincFuncs(
        $scope.pedidos,
        "pedido_entrada", ["id", "fornecedor.nome", "id_status", "frete", "prazo", "data"]);
    $scope.pedidos.attList();

    $scope.pedidos.posload = function() {
        setTimeout(function() {
            loading.redux();
        }, 200);
    }

    $scope.produtos = createAssinc(produtoService, 1, 6, 4);
    $scope.empresas = [];
    $scope.local_retirada = null;
    assincFuncs(
        $scope.produtos,
        "produto", ["codigo", "nome", "disponivel"], "filtroProdutos");

    $scope.produtos.posload = function() {
        setTimeout(function() {
            loading.redux();
        }, 200);
    }

    $scope.transportadoras = createAssinc(transportadoraService, 1, 6, 4);

    assincFuncs(
        $scope.transportadoras,
        "transportadora", ["codigo", "razao_social"], "filtroTransportadoras");

    $scope.transportadoras.posload = function() {
        setTimeout(function() {
            loading.redux();
        }, 200);
    }



    $scope.fornecedores = createAssinc(fornecedorService, 1, 6, 4);

    assincFuncs(
        $scope.fornecedores,
        "fornecedor", ["codigo", "nome"], "filtroFornecedores");

    $scope.fornecedores.posload = function() {
        setTimeout(function() {
            loading.redux();
        }, 200);
    }


    $scope.meses_validade_curta = 3;

    $scope.status_pedido = [];

    $scope.pedido_novo = {};

    $scope.produto_pedido_novo = {};

    $scope.pedido = {};

    $scope.fretes = [];

    $scope.qtd = 0;

    $scope.valor = 0;

    $scope.produto = {};


    $scope.localEntregaEspec = function(local) {

        var e = local;


        for (var i = 0; i < $scope.empresas.length; i++) {
            if ($scope.empresas[i].codigo === e) {
                e = $scope.empresas[i];
                break;
            }
        }

        $scope.local_retirada = e;

        var valor = "";
        $scope.pedido.observacoes = valor;


    }

    $scope.localEntrega = function() {

        var e = $scope.local_retirada;

        var valor = "";

        $scope.pedido.observacoes = valor;


    }

    empresaService.getGrupoEmpresarial(function(g) {

        $scope.empresas = g.grupo;

        for (var i = 0; i < $scope.empresas.length; i++) {
            $scope.empresas[i].codigo = i;
        }

        $scope.local_retirada = g.grupo[0];
        $scope.localEntrega();
    })


    $scope.getPesoBrutoPedido = function() {

        var tot = 0;

        for (var i = 0; i < $scope.pedido.produtos.length; i++) {

            var p = $scope.pedido.produtos[i];

            tot += (p.produto.peso_bruto) * p.quantidade;

        }

        return tot;

    }

    $scope.getTotalPedido = function() {

        var tot = 0;

        for (var i = 0; i < $scope.pedido.produtos.length; i++) {

            var p = $scope.pedido.produtos[i];

            tot += (p.valor) * p.quantidade;

        }

        return tot;

    }

    statusPedidoEntradaService.getStatus(function(st) {

        $scope.status_pedido = st.status;

    })

    $scope.setTransportadora = function(trans) {

        $scope.pedido.transportadora = trans;
        $scope.atualizaCustos();

    }

    $scope.setFornecedor = function(forn) {

        $scope.pedido.fornecedor = forn;
        $("#clientes").modal("hide");

    }

    produtoPedidoEntradaService.getProdutoPedido(function(pp) {

        $scope.produto_pedido_novo = pp.produto_pedido;

    })

    $scope.addProduto = function(produto) {

        if ($scope.qtd == 0) {

            $("#txtPQtd").css("border", "2px solid Red").focus();
            return;

        } else {

            $("#txtPQtd").css("border", "1px solid Gray");

        }

        if ($scope.valor == 0) {

            $("#txtPValor").css("border", "2px solid Red").focus();
            return;

        } else {

            $("#txtPValor").css("border", "1px solid Gray");

        }

        var pp = angular.copy($scope.produto_pedido_novo);
        pp.produto = produto;
        pp.pedido = $scope.pedido;
        pp.valor = $scope.valor;
        pp.quantidade = $scope.qtd;

        for (var j = 0; j < $scope.pedido.produtos.length; j++) {

            var pr = $scope.pedido.produtos[j];

            if (pr.produto.id === pp.produto.id) {

                pr.quantidade += pp.quantidade;
                $("#produtos").modal("hide");
                return;

            }

        }

        pp.valor_unitario = pp.valor / pp.produto.quantidade_unidade;

        $scope.pedido.produtos[$scope.pedido.produtos.length] = pp;

        $("#produtos").modal("hide");

    }

    $scope.removerProduto = function(produto) {

        remove($scope.pedido.produtos, produto);

    }

    $scope.mergePedido = function() {

        var p = $scope.pedido;

        if (p.fornecedor == null) {
            msg.erro("Pedido sem fornecedor.");
            return;
        }

        if (p.transportadora == null) {
            msg.erro("Pedido sem transportadora.");
            return;
        }

        if (p.status == null) {
            msg.erro("Pedido sem status.");
            return;
        }
        p.observacoes = formatTextArea(p.observacoes);

        baseService.merge(p, function(r) {
            if (r.sucesso) {
                $scope.pedido = r.o;
                equalize($scope.pedido, "status", $scope.status_pedido);
                msg.alerta("Operacao efetuada com sucesso");
            } else {
                $scope.pedido = r.o;
                equalize($scope.pedido, "status", $scope.status_pedido);
                msg.erro("Ocorreu o seguinte problema: " + r.mensagem);
            }
        });

    }


    $scope.calculoPronto = function() {

        if ($scope.pedido.fornecedor != null && $scope.pedido.produtos != null) {
            if ($scope.pedido.produtos.length > 0) {
                return true;
            }
        }
        return false;

    }

    $scope.setFrete = function(fr) {

        $scope.pedido.frete = fr.valor + fr.transportadora.despacho;
        $scope.pedido.transportadora = fr.transportadora;
        $scope.atualizaCustos();

    }

    $scope.getFretes = function() {

        var pesoTotal = 0;
        var valorTotal = 0;

        for (var i = 0; i < $scope.pedido.produtos.length; i++) {
            var p = $scope.pedido.produtos[i];
            valorTotal += (p.valor) * p.quantidade;
            pesoTotal += p.produto.peso_bruto * p.quantidade;
        }

        tabelaService.getFretes($scope.local_retirada, { cidade: $scope.pedido.fornecedor.endereco.cidade, valor: valorTotal, peso: pesoTotal }, function(f) {

            $scope.fretes = f.fretes;

        })

    }


    pedidoEntradaService.getPedido(function(ped) {

        ped.pedido.produtos = [];
        $scope.pedido_novo = ped.pedido;

    })

    $scope.novoPedido = function() {

        $scope.setPedido(angular.copy($scope.pedido_novo));

    }

    $scope.attValorUnitario = function(produto) {

        produto.valor = produto.valor_unitario * produto.produto.quantidade_unidade;

    }

    $scope.attValor = function(produto) {

        produto.valor_unitario = produto.valor / produto.produto.quantidade_unidade;

    }

    $scope.setPedido = function(pedido) {

        $scope.pedido = pedido;
        $scope.localEntrega();
        if ($scope.pedido.id === 0) {

            $scope.pedido.status = $scope.status_pedido[0];

            return;

        }

        $scope.localEntregaEspec(pedido.entrega);

        pedidoEntradaService.getProdutos(pedido, function(p) {

            pedido.produtos = p.produtos;
            equalize(pedido, "status", $scope.status_pedido);

            var ic = $("#myIframe").contents();

            ic.find("#logoEmpresa img").remove();
            ic.find("#logoEmpresa").append($("#logo").clone().addClass("product-image"));
            ic.find("#infoEmpresa").html(pedido.empresa.nome + ", " + pedido.empresa.endereco.cidade.nome + "-" + pedido.empresa.endereco.cidade.estado.sigla);
            ic.find("#infoEmpresa2").html(pedido.empresa.endereco.bairro + ", " + pedido.empresa.endereco.cep.valor + " - " + pedido.empresa.telefone.numero);

            ic.find("#idPedido").html($scope.pedido.id);
            ic.find("#nomeUsuario").html($scope.pedido.usuario.nome);
            ic.find("#nomeCliente").html($scope.pedido.fornecedor.nome);
            ic.find("#cnpjCliente").html($scope.pedido.fornecedor.cnpj.valor);
            ic.find("#ruaCliente").html($scope.pedido.fornecedor.endereco.rua);
            ic.find("#cidadeCliente").html($scope.pedido.fornecedor.endereco.cidade.nome);


            var p = ic.find("#produto").each(function() {
                p = $(this);
            });

            p.hide();

            ic.find("#produtos").find("tr").each(function() {
                if (typeof $(this).data("gerado") !== 'undefined') {
                    $(this).remove();
                }
            });

            var p = p.clone();

            var icms = 0;
            var base = 0;
            var total = 0;
            for (var i = 0; i < $scope.pedido.produtos.length; i++) {

                p = p.clone();

                var pro = $scope.pedido.produtos[i];

                pro.valor_unitario = pro.valor / pro.produto.quantidade_unidade;

                icms += pro.icms;
                base += pro.base_calculo;
                p.find("[data-tipo='nome']").html(pro.produto.nome);
                p.find("[data-tipo='valor']").html((pro.valor / pro.produto.quantidade_unidade).toFixed(2));
                p.find("[data-tipo='quantidade']").html((pro.quantidade * pro.produto.quantidade_unidade).toFixed(2));
                p.find("[data-tipo='validade']").html('-----');
                p.find("[data-tipo='total']").html(((pro.valor) * pro.quantidade).toFixed(2));
                p.data("gerado", true);

                ic.find("#produtos").append(p);
                p.show();

                total += (pro.valor) * pro.quantidade;

            }
            var alicota = (icms * 100 / base).toFixed(2);

            ic.find("#prazo").html(pedido.prazo);
            ic.find("#alicota").html('----');
            ic.find("#icms").html('-----');

            ic.find("#tipoFrete").html(pedido.frete_incluso ? 'CIF' : 'FOB');
            ic.find("#nomeTransportadora").html(pedido.transportadora.razao_social);
            ic.find("#contato").html(pedido.transportadora.email.endereco);
            ic.find("#valorFrete").html(pedido.frete);

            ic.find("#observacoes").html(pedido.observacoes);
            ic.find("#nomeUsuario2").html(pedido.usuario.nome);

        })


    }

    $scope.deletePedido = function() {

        baseService.delete($scope.pedido, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.pedido = angular.copy($scope.novo_pedido);
                $scope.pedidos.attList();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
        });

    }



})


rtc.controller("crtAcompanharPedidos", function($scope, acompanharPedidoService, logService, tabelaService, baseService, produtoService, sistemaService, statusPedidoSaidaService, formaPagamentoService, transportadoraService, clienteService, produtoPedidoService) {

    $scope.pedidos = createAssinc(acompanharPedidoService, 1, 10, 10);
    $scope.pedidos.attList();
    assincFuncs(
        $scope.pedidos,
        "pedido", ["id", "empresa.nome", "data", "frete", "id_status", "usuario.nome"]);


    $scope.gerarCobranca = function() {

        pedidoService.gerarCobranca($scope.pedido, function(r) {

            if (r.sucesso) {
                $("#retCob").html("Cobranca gerada com sucesso. <hr> " + r.retorno);
            } else {
                $("#retCob").html("Problema ao gerar cobranca");
            }

        })

    }

    sistemaService.getLogisticas(function(rr) {

        $scope.logisticas = rr.logisticas;

    })

    $scope.getLogsPedido = function(pedido) {

        logService.getLogs(pedido, function(l) {

            $scope.logs = l.logs;

            $("#shLogs").children("*").each(function() {
                $(this).remove();
            })

            for (var i = 0; i < $scope.logs.length; i++) {

                var l = $scope.logs[i];

                $("<div></div>").css('width', '100%').css('display', 'block').css('border-bottom', '1px solid Gray').css('padding', '10px').html(l.usuario + " / " + toTime(l.momento) + " / " + l.obs).appendTo($("#shLogs"));

            }

        })
    }

    $scope.getLogs = function() {

        logService.getLogs($scope.pedido, function(l) {

            $scope.logs = l.logs;

            $("#shLogs").children("*").each(function() {
                $(this).remove();
            })

            for (var i = 0; i < $scope.logs.length; i++) {

                var l = $scope.logs[i];

                $("<div></div>").css('width', '100%').css('display', 'block').css('border-bottom', '1px solid Gray').css('padding', '10px').html(l.usuario + " / " + toTime(l.momento) + " / " + l.obs).appendTo($("#shLogs"));

            }

        })
    }

    $scope.getPesoBrutoPedido = function() {

        var tot = 0;

        for (var i = 0; i < $scope.pedido.produtos.length; i++) {

            var p = $scope.pedido.produtos[i];

            tot += (p.produto.peso_bruto) * p.quantidade;

        }

        return tot;

    }

    $scope.getTotalPedido = function() {

        var tot = 0;

        for (var i = 0; i < $scope.pedido.produtos.length; i++) {

            var p = $scope.pedido.produtos[i];

            tot += (p.valor_base + p.icms + p.ipi + p.juros + p.frete) * p.quantidade;

        }

        return tot;

    }

    $scope.formas_pagamento = {};

    statusPedidoSaidaService.getStatus(function(st) {

        $scope.status_pedido = st.status;

    })

    $scope.setPedido = function(pedido) {

        $scope.pedido = pedido;

        if (pedido.logistica !== null) {

            equalize($scope.pedido, "logistica", $scope.logisticas);

        }

        if ($scope.pedido.logistica === null) {
            produtoService.filtro_base = "produto.id_logistica=0";
            transportadoraService.empresa = $scope.pedido.empresa;
        } else {
            produtoService.filtro_base = "produto.id_logistica=" + $scope.pedido.logistica.id;
            transportadoraService.empresa = $scope.pedido.logistica;
        }

        if ($scope.pedido.id === 0) {

            $scope.pedido.status = $scope.status_pedido[0];

            formaPagamentoService.getFormasPagamento($scope.pedido, function(f) {

                $scope.formas_pagamento = f.formas;
                $scope.pedido.forma_pagamento = $scope.formas_pagamento[0];

            });

            return;

        }

        acompanharPedidoService.getProdutos(pedido, function(p) {

            pedido.produtos = p.produtos;
            equalize(pedido, "status", $scope.status_pedido);

            formaPagamentoService.getFormasPagamento($scope.pedido, function(f) {
                $scope.formas_pagamento = f.formas;
                equalize(pedido, "forma_pagamento", $scope.formas_pagamento);
            })

            var ic = $("#myIframe").contents();

            ic.find("#logoEmpresa img").remove();
            ic.find("#logoEmpresa").append($("#logo").clone().addClass("product-image"));
            ic.find("#infoEmpresa").html(pedido.empresa.nome + ", " + pedido.empresa.endereco.cidade.nome + "-" + pedido.empresa.endereco.cidade.estado.sigla);
            ic.find("#infoEmpresa2").html(pedido.empresa.endereco.bairro + ", " + pedido.empresa.endereco.cep.valor + " - " + pedido.empresa.telefone.numero);

            ic.find("#idPedido").html($scope.pedido.id);
            ic.find("#nomeUsuario").html($scope.pedido.usuario.nome);
            ic.find("#nomeCliente").html($scope.pedido.cliente.razao_social);
            ic.find("#cnpjCliente").html($scope.pedido.cliente.cnpj.valor);
            ic.find("#ruaCliente").html($scope.pedido.cliente.endereco.rua);
            ic.find("#cidadeCliente").html($scope.pedido.cliente.endereco.cidade.nome);


            var p = ic.find("#produto").each(function() {
                p = $(this);
            });

            p.hide();

            ic.find("#produtos").find("tr").each(function() {
                if (typeof $(this).data("gerado") !== 'undefined') {
                    $(this).remove();
                }
            });

            var p = p.clone();

            var icms = 0;
            var base = 0;
            var total = 0;
            for (var i = 0; i < $scope.pedido.produtos.length; i++) {

                p = p.clone();

                var pro = $scope.pedido.produtos[i];
                icms += pro.icms;
                base += pro.base_calculo;
                p.find("[data-tipo='nome']").html(pro.produto.nome);
                p.find("[data-tipo='valor']").html((pro.valor_base + pro.frete + pro.juros + pro.icms).toFixed(2));
                p.find("[data-tipo='quantidade']").html(pro.quantidade);
                p.find("[data-tipo='validade']").html(toDate(pro.validade_minima));
                p.find("[data-tipo='total']").html(((pro.valor_base + pro.frete + pro.ipi + pro.juros + pro.icms) * pro.quantidade).toFixed(2));
                p.data("gerado", true);

                ic.find("#produtos").append(p);
                p.show();

                total += (pro.valor_base + pro.frete + pro.juros + pro.ipi + pro.icms) * pro.quantidade;

            }

            var alicota = (icms * 100 / base).toFixed(2);

            ic.find("#prazo").html(pedido.prazo);
            ic.find("#alicota").html(alicota);
            ic.find("#icms").html(icms);

            ic.find("#tipoFrete").html(pedido.frete_incluso ? 'CIF' : 'FOB');
            ic.find("#nomeTransportadora").html(pedido.transportadora.razao_social);
            ic.find("#contato").html(pedido.transportadora.email.endereco);
            ic.find("#valorFrete").html(pedido.frete);

            ic.find("#observacoes").html(pedido.observacoes);
            ic.find("#nomeUsuario2").html(pedido.usuario.nome);

        })

    }

    $scope.deletePedido = function() {

        baseService.delete($scope.pedido, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.pedido = angular.copy($scope.novo_pedido);
                $scope.pedidos.attList();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
        });

    }

})


rtc.controller("crtPedidos", function($scope, pedidoService, logService, tabelaService, baseService, produtoService, sistemaService, statusPedidoSaidaService, formaPagamentoService, transportadoraService, clienteService, produtoPedidoService) {

    $scope.pedidos = createAssinc(pedidoService, 1, 10, 10);
    assincFuncs(
        $scope.pedidos,
        "pedido", ["id", "k.produtos", "cliente.razao_social", "data", "transportadora.razao_social", "frete", "id_status", "usuario.nome"]);
    $scope.pedidos.attList();


    produtoService.vencidos = false;
    $scope.produtos = createAssinc(produtoService, 1, 3, 4);

    $scope.orcamento = false;

    $scope.gerarReceita = function() {


        var id_pedido = encode64SPEC($scope.pedido.id + "");


        window.open(projeto + "/receita.php?p=" + id_pedido);



    }

    $scope.setOrcamento = function() {

        $scope.orcamento = !$scope.orcamento;

        if ($scope.orcamento) {

            pedidoService.filtro_base = "pedido.id_status=11";
            $scope.pedidos.attList();

        } else {

            pedidoService.filtro_base = "";
            $scope.pedidos.attList();

        }

    }

    assincFuncs(
        $scope.produtos,
        "produto", ["codigo", "nome", "disponivel"], "filtroProdutos");

    $scope.transportadoras = createAssinc(transportadoraService, 1, 3, 4);

    assincFuncs(
        $scope.transportadoras,
        "transportadora", ["codigo", "razao_social"], "filtroTransportadoras");

    $scope.clientes = createAssinc(clienteService, 1, 3, 4);

    $scope.carregando = false;

    $scope.inverterPrecos = function() {

        var p = $scope.pedido;

        var total = 0;

        for (var i = 0; i < p.produtos.length; i++) {
            total += p.produtos[i].quantidade * p.produtos[i].valor_base;
        }

        for (var i = 0; i < p.produtos.length; i++) {

            var pro = p.produtos[i];
            var vun = pro.valor_base + pro.frete + pro.juros + pro.icms + pro.ipi;

            var perc = pro.quantidade * pro.valor_base / total;

            var frete = (p.frete * perc) / pro.quantidade;
            vun -= frete;

            var ipi = 1 + (pro.ipi / (vun - pro.ipi));

            vun -= pro.ipi;

            var icms = ((vun - pro.icms) / (vun));

            vun -= pro.icms;

            var juros = 1 + (pro.juros / (vun - pro.juros));

            var fat = juros / icms * ipi;

            pro.valor_base = parseFloat(((pro.valor_base - frete) / fat).toFixed(3));


        }

        $scope.atualizaCustos();

    }

    assincFuncs(
        $scope.clientes,
        "cliente", ["codigo", "razao_social"], "filtroClientes");


    $scope.meses_validade_curta = 3;

    $scope.status_pedido = [];

    $scope.status_excluido = {};

    $scope.pedido_novo = {};

    $scope.produto_pedido_novo = {};

    $scope.pedido = {};

    $scope.fretes = [];

    $scope.qtd = 0;

    $scope.produto = {};

    $scope.logisticas = [];

    $scope.logs = [];

    $scope.retorno_cobranca = ""

    sistemaService.getLogisticas(function(rr) {

        $scope.logisticas = rr.logisticas;

    })


    $scope.gerarCobranca = function() {

        pedidoService.gerarCobranca($scope.pedido, function(r) {

            if (r.sucesso) {
                $("#retCob").html("Cobranca gerada com sucesso. <hr> " + r.retorno);
            } else {
                $("#retCob").html("Problema ao gerar cobranca");
            }

        })

    }

    $scope.getLogs = function() {

        logService.getLogs($scope.pedido, function(l) {

            $scope.logs = l.logs;

            $("#shLogs").children("*").each(function() {
                $(this).remove();
            })

            for (var i = 0; i < $scope.logs.length; i++) {

                var l = $scope.logs[i];

                $("<div></div>").css('width', '100%').css('display', 'block').css('border-bottom', '1px solid Gray').css('padding', '10px').html(l.usuario + " / " + toTime(l.momento) + " / " + l.obs).appendTo($("#shLogs"));

            }

        })


    }

    $scope.getPesoBrutoPedido = function() {

        var tot = 0;

        for (var i = 0; i < $scope.pedido.produtos.length; i++) {

            var p = $scope.pedido.produtos[i];

            tot += (p.produto.peso_bruto) * p.quantidade;

        }

        return tot;

    }

    $scope.getTotalPedido = function() {

        var tot = 0;

        for (var i = 0; i < $scope.pedido.produtos.length; i++) {

            var p = $scope.pedido.produtos[i];

            console.log(p);

            tot += (p.valor_base + p.icms + p.ipi + p.juros + p.frete) * p.quantidade;

        }

        return tot;

    }

    $scope.formas_pagamento = {};




    $scope.status_filtro = [{
        id: -1,
        nome: "Sem filtro"
    }];

    $scope.status_filtro_selecionado = $scope.status_filtro[0];
    $scope.filtro_produto = "";

    $scope.atualizaFiltro = function() {

        var s = $scope.status_filtro_selecionado;

        var f = "";

        if (s.id >= 0) {

            f = "(pedido.id_status=" + s.id + ")";

        }

        if ($scope.filtro_produto !== "") {

            if (f !== "") {
                f += " AND ";
            }

            f += "(pedido.id IN (SELECT pp.id_pedido FROM produto_pedido_saida pp INNER JOIN produto p ON p.id=pp.id_produto WHERE p.nome like '%" + $scope.filtro_produto + "%'))";

        }

        pedidoService.filtro_base = f;

        $scope.pedidos.attList();

    }

    statusPedidoSaidaService.getStatus(function(st) {

        $scope.status_pedido = st.status;

        for (var i = 0; i < $scope.status_pedido.length; i++) {

            $scope.status_filtro[$scope.status_filtro.length] = $scope.status_pedido[i];

        }

    })

    $scope.setTransportadora = function(trans) {

        $scope.pedido.transportadora = trans;
        $scope.atualizaCustos();

    }

    $scope.setCliente = function(cli) {

        $scope.pedido.cliente = cli;
        $scope.atualizaCustos();

    }

    produtoPedidoService.getProdutoPedido(function(pp) {

        $scope.produto_pedido_novo = pp.produto_pedido;

    })

    $scope.addProduto = function(produto, validade) {

        var validades = [angular.copy(validade)];

        for (var i = 0; i < validades[0].validades.length; i++) {

            validades[0].quantidade -= validades[0].validades[i].quantidade;

        }

        var quantidades = [Math.min($scope.qtd, (validade.limite > 0) ? validade.limite : $scope.qtd)];

        while (validades[validades.length - 1].quantidade < quantidades[quantidades.length - 1]) {

            var v = validades[validades.length - 1];

            quantidades[quantidades.length] = quantidades[quantidades.length - 1] - v.quantidade;

            quantidades[quantidades.length - 2] = v.quantidade;

            var v0 = validades[0];

            if (v0.validades.length < validades.length) {

                msg.erro("Sem estoque suficiente");
                return;

            }

            validades[validades.length] = v0.validades[validades.length - 1];

        }

        lbl:
            for (var i = 0; i < validades.length; i++) {

                if (quantidades[i] === 0)
                    continue;


                var pp = angular.copy($scope.produto_pedido_novo);
                pp.produto = produto;
                pp.pedido = $scope.pedido;
                pp.validade_minima = validades[i].validade;
                pp.valor_base = validade.valor;
                pp.quantidade = quantidades[i];

                for (var j = 0; j < $scope.pedido.produtos.length; j++) {

                    var pr = $scope.pedido.produtos[j];

                    if (pr.produto.id === pp.produto.id && pr.validade_minima === pp.validade_minima) {

                        pr.quantidade += pp.quantidade;
                        continue lbl;

                    }

                }

                $scope.pedido.produtos[$scope.pedido.produtos.length] = pp;

            }



        $scope.atualizaCustos();

    }

    $scope.removerProduto = function(produto) {

        var dt = new Date().getTime();
        dt += $scope.meses_validade_curta * 30 * 24 * 60 * 60 * 1000;

        remove($scope.pedido.produtos, produto);

        if (produto.validade_minima > dt) {
            for (var i = 0; i < $scope.pedido.produtos.length; i++) {

                var p = $scope.pedido.produtos[i];

                if (p.validade_minima > produto.validade_minima && p.produto.id === produto.produto.id) {

                    remove($scope.pedido.produtos, p);
                    i--;

                }
            }
        }

        $scope.atualizaCustos();

    }

    $scope.mergePedido = function() {



        var p = $scope.pedido;

        if (p.cliente == null) {
            msg.erro("Pedido sem cliente.");
            return;
        }

        if (p.transportadora == null) {
            msg.erro("Pedido sem transportadora.");
            return;
        }

        if (p.status == null) {
            msg.erro("Pedido sem status.");
            return;
        }

        if (p.forma_pagamento == null) {
            msg.erro("Pedido sem forma de pagamento.");
            return;
        }

        $scope.carregando = true;

        baseService.merge(p, function(r) {
            if (r.sucesso) {
                $scope.pedido = r.o;
                if ($scope.pedido.logistica !== null) {
                    equalize($scope.pedido, "logistica", $scope.logisticas);
                }
                equalize($scope.pedido, "status", $scope.status_pedido);
                equalize($scope.pedido, "forma_pagamento", $scope.formas_pagamento);

                msg.alerta("Operacao efetuada com sucesso");

                if (typeof $scope.pedido["retorno"] !== 'undefined') {

                    msg.alerta($scope.pedido["retorno"]);

                }

            } else {
                $scope.pedido = r.o;
                if ($scope.pedido.logistica !== null) {
                    equalize($scope.pedido, "logistica", $scope.logisticas);
                }
                equalize($scope.pedido, "status", $scope.status_pedido);
                equalize($scope.pedido, "forma_pagamento", $scope.formas_pagamento);
                msg.erro("Ocorreu o seguinte problema: " + r.mensagem);
            }
            $scope.carregando = false;
        });

    }

    $scope.setFrete = function(fr) {

        $scope.pedido.frete = fr.valor + fr.transportadora.despacho;
        $scope.pedido.transportadora = fr.transportadora;
        $scope.atualizaCustos();

    }

    $scope.setProduto = function(produto) {

        produtoService.getValidades($scope.meses_validade_curta, produto, function(v) {

            produto.validades = v;

        })

    }

    $scope.calculoPronto = function() {

        if ($scope.pedido.cliente != null && $scope.pedido.produtos != null) {
            if ($scope.pedido.produtos.length > 0) {
                return true;
            }
        }
        return false;

    }


    $scope.getFretes = function() {

        var pesoTotal = 0;
        var valorTotal = 0;

        for (var i = 0; i < $scope.pedido.produtos.length; i++) {
            var p = $scope.pedido.produtos[i];
            valorTotal += (p.valor_base + p.juros + p.icms) * p.quantidade;
            pesoTotal += p.produto.peso_bruto * p.quantidade;
        }
        if ($scope.pedido.logistica === null) {
            tabelaService.getFretes(null, { cidade: $scope.pedido.cliente.endereco.cidade, valor: valorTotal, peso: pesoTotal }, function(f) {

                $scope.fretes = f.fretes;

            })
        } else {

            tabelaService.getFretes($scope.pedido.logistica, { cidade: $scope.pedido.cliente.endereco.cidade, valor: valorTotal, peso: pesoTotal }, function(f) {

                $scope.fretes = f.fretes;

            })
        }

    }

    $scope.atualizaCustos = function() {

        pedidoService.atualizarCustos($scope.pedido, function(np) {

            $scope.pedido = np.o;

            equalize($scope.pedido, "status", $scope.status_pedido);
            equalize($scope.pedido, "forma_pagamento", $scope.formas_pagamento);

            if ($scope.pedido.logistica !== null) {
                equalize($scope.pedido, "logistica", $scope.logisticas);
            }

        })

    }

    pedidoService.getPedido(function(ped) {

        ped.pedido.produtos = [];
        $scope.pedido_novo = ped.pedido;

    })

    $scope.novoPedido = function() {

        $scope.setPedido(angular.copy($scope.pedido_novo));

    }

    $scope.resetarPedido = function() {

        $scope.pedido.transportadora = null;
        $scope.pedido.produtos = [];

        if ($scope.pedido.logistica === null) {
            produtoService.filtro_base = "produto.id_logistica=0";
            transportadoraService.empresa = $scope.pedido.empresa;
        } else {
            produtoService.filtro_base = "produto.id_logistica=" + $scope.pedido.logistica.id;
            transportadoraService.empresa = $scope.pedido.logistica;
        }

        $scope.produtos.attList();
        $scope.transportadoras.attList();

    }

    $scope.setPedido = function(pedido) {

        $scope.pedido = pedido;

        if (pedido.logistica !== null) {

            equalize($scope.pedido, "logistica", $scope.logisticas);

        }

        if ($scope.pedido.logistica === null) {
            produtoService.filtro_base = "produto.id_logistica=0";
            transportadoraService.empresa = $scope.pedido.empresa;
        } else {
            produtoService.filtro_base = "produto.id_logistica=" + $scope.pedido.logistica.id;
            transportadoraService.empresa = $scope.pedido.logistica;
        }

        if ($scope.pedido.id === 0) {

            $scope.pedido.status = $scope.status_pedido[0];

            formaPagamentoService.getFormasPagamento($scope.pedido, function(f) {

                $scope.formas_pagamento = f.formas;
                $scope.pedido.forma_pagamento = $scope.formas_pagamento[0];

            });

            return;

        }

        pedidoService.getProdutos(pedido, function(p) {

            pedido.produtos = p.produtos;
            equalize(pedido, "status", $scope.status_pedido);

            formaPagamentoService.getFormasPagamento($scope.pedido, function(f) {
                $scope.formas_pagamento = f.formas;
                equalize(pedido, "forma_pagamento", $scope.formas_pagamento);
            })

            var ic = $("#myIframe").contents();

            ic.find("#logoEmpresa img").remove();
            ic.find("#logoEmpresa").append($("#logo").clone().addClass("product-image"));
            ic.find("#infoEmpresa").html(pedido.empresa.nome + ", " + pedido.empresa.endereco.cidade.nome + "-" + pedido.empresa.endereco.cidade.estado.sigla);
            ic.find("#infoEmpresa2").html(pedido.empresa.endereco.bairro + ", " + pedido.empresa.endereco.cep.valor + " - " + pedido.empresa.telefone.numero);

            ic.find("#idPedido").html($scope.pedido.id);
            ic.find("#nomeUsuario").html($scope.pedido.usuario.nome);
            ic.find("#nomeCliente").html($scope.pedido.cliente.razao_social);
            ic.find("#cnpjCliente").html($scope.pedido.cliente.cnpj.valor);
            ic.find("#ruaCliente").html($scope.pedido.cliente.endereco.rua);
            ic.find("#cidadeCliente").html($scope.pedido.cliente.endereco.cidade.nome);
            ic.find("#emailCliente").html($scope.pedido.cliente.email.endereco);

            ic.find("#transportadora").html($scope.pedido.transportadora.razao_social);
            ic.find("#cnpjTransportadora").html($scope.pedido.transportadora.cnpj.valor);
            ic.find("#emailTransportadora").html($scope.pedido.transportadora.email.endereco);

            var telefones = "";

            for (var i = 0; i < $scope.pedido.transportadora.telefones.length; i++) {
                telefones += $scope.pedido.transportadora.telefones[i].numero + "<br>";
            }

            ic.find("#telefoneTransportadora").html(telefones);
            ic.find("#cidadeEstadoTransportadora").html($scope.pedido.transportadora.endereco.cidade.nome + " - " + $scope.pedido.transportadora.endereco.cidade.estado.sigla);

            var suframa = "Sem suframa";

            if ($scope.pedido.cliente.suframa) {
                suframa = $scope.pedido.cliente.inscricao_suframa;
            }

            ic.find("#suframa").html(suframa);

            var p = ic.find("#produto").each(function() {
                p = $(this);
            });

            p.hide();

            ic.find("#produtos").find("tr").each(function() {
                if (typeof $(this).data("gerado") !== 'undefined') {
                    $(this).remove();
                }
            });

            var p = p.clone();

            var icms = 0;
            var base = 0;
            var total = 0;
            for (var i = 0; i < $scope.pedido.produtos.length; i++) {

                p = p.clone();

                var pro = $scope.pedido.produtos[i];
                icms += pro.icms * pro.quantidade;
                base += pro.base_calculo * pro.quantidade;
                p.find("[data-tipo='nome']").html(pro.produto.nome);
                p.find("[data-tipo='valor']").html((pro.valor_base + pro.frete + pro.juros + pro.icms).toFixed(2));
                p.find("[data-tipo='quantidade']").html(pro.quantidade);
                p.find("[data-tipo='validade']").html(toDate(pro.validade_minima));
                p.find("[data-tipo='total']").html(((pro.valor_base + pro.frete + pro.ipi + pro.juros + pro.icms) * pro.quantidade).toFixed(2));
                p.data("gerado", true);

                ic.find("#produtos").append(p);
                p.show();

                total += (pro.valor_base + pro.frete + pro.juros + pro.ipi + pro.icms) * pro.quantidade;

            }
            var alicota = (icms * 100 / base);

            ic.find("#prazo").html(pedido.prazo);
            ic.find("#alicota").html(alicota.toFixed(0));
            ic.find("#icms").html(icms.toFixed(2));

            ic.find("#tipoFrete").html(pedido.frete_incluso ? 'CIF' : 'FOB');
            ic.find("#nomeTransportadora").html(pedido.transportadora.razao_social);
            ic.find("#contato").html(pedido.transportadora.email.endereco);
            ic.find("#valorFrete").html(pedido.frete);

            ic.find("#observacoes").html(pedido.observacoes);
            ic.find("#nomeUsuario2").html(pedido.usuario.nome);

        })


    }

    $scope.deletePedido = function() {
        $scope.carregando = true;
        baseService.delete($scope.pedido, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.pedido = angular.copy($scope.novo_pedido);
                $scope.pedidos.attList();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
            $scope.carregando = false;
        });

    }


})

rtc.controller("crtListaPreco", function($scope, listaPrecoProdutoService, listaPrecoPragaService, listaPrecoCulturaService) {

    $scope.produtos = createAssinc(listaPrecoProdutoService, 1, 3, 10);
    $scope.produtos.attList();
    assincFuncs(
        $scope.produtos,
        "produto", ["codigo", "nome", "estoque", "disponivel", "transito", "valor_base", "ativo", "classe_risco"]);

    $scope.culturas = createAssinc(listaPrecoCulturaService, 1, 3, 5);
    $scope.culturas.attList();
    assincFuncs(
        $scope.culturas,
        "cultura", ["id", "nome"], "filtroCultura");

    $scope.pragas = createAssinc(listaPrecoPragaService, 1, 3, 5);
    $scope.pragas.attList();
    assincFuncs(
        $scope.pragas,
        "praga", ["id", "nome"], "filtroPraga");

    $scope.produto = null;
    $scope.cultura = null;
    $scope.praga = null;

    $scope.setCultura = function(cultura) {

        $scope.cultura = cultura;
        listaPrecoPragaService.cultura = cultura;
        listaPrecoProdutoService.cultura = cultura;

        $scope.pragas.attList();
        $scope.culturas.attList();
        $scope.produtos.attList();

    }

    $scope.setPraga = function(praga) {

        $scope.praga = praga;
        listaPrecoCulturaService.praga = praga;
        listaPrecoProdutoService.praga = praga;

        $scope.pragas.attList();
        $scope.culturas.attList();
        $scope.produtos.attList();

    }

    $scope.setProduto = function(produto) {

        $scope.produto = produto;
        listaPrecoCulturaService.produto = produto;
        listaPrecoPragaService.produto = produto;


        $scope.produtos.attList();
        $scope.pragas.attList();
        $scope.culturas.attList();


    }

})
rtc.controller("crtCampanhas", function($scope, campanhaService, baseService, produtoAlocalService, produtoService, sistemaService) {

    $scope.campanhas = createAssinc(campanhaService, 1, 10, 10);
    assincFuncs(
        $scope.campanhas,
        "campanha", ["id", "nome", "inicio", "fim", "prazo", "parcelas"]);
    $scope.campanhas.attList();

    $scope.produtos = createAssinc(produtoAlocalService, 1, 3, 4);
    assincFuncs(
        $scope.produtos,
        "produto", ["codigo", "nome", "estoque", "disponivel"], "filtroProdutos");

    $scope.produtos2 = createAssinc(produtoAlocalService, 1, 3, 4);
    assincFuncs(
        $scope.produtos2,
        "produto", ["codigo", "nome", "estoque", "disponivel"], "filtroProdutos2");

    $scope.agora = new Date().getTime();
    $scope.campanha = { inicio: $scope.agora, fim: $scope.agora, id: 0 };
    $scope.campanha_nova = {};

    $scope.criacao_campanhas = [];
    $scope.cc = null;

    $scope.usuario = null;

    sistemaService.getUsuario(function(u) {

        $scope.usuario = u.usuario;

    })

    $scope.produto_campanha_novo = {};

    $scope.teste = new Date().getTime();
    $scope.teste2 = new Date().getTime() + (10 * 24 * 60 * 60 * 1000);
    $scope.teste3 = function() {
        alert("teste4");
    }

    $scope.produto = {};
    $scope.produto_campanha_validade = {};

    $scope.meses_validade_curta = 3;

    var data = new Date();
    var dia = 1000 * 60 * 60 * 24;

    campanhaService.getProdutoCampanha(function(p) {

        $scope.produto_campanha_novo = p.produto_campanha;

    })

    $scope.quantidadeNumero = function(campanha, cc) {

        var qtd = 0;

        var numero = 0;
        for (var i = 0; i < campanha.campanhas.length; i++) {
            if (campanha.campanhas[i] === cc) {
                numero = campanha.campanhas[i].id;
                break;
            }
        }

        for (var i = 0; i < campanha.produtos.length; i++) {

            var p = campanha.produtos[i];

            if (p.numeracao === numero) {

                qtd++;

            }

        }

        return qtd;

    }

    $scope.setAutoValidade = function(v) {

        $scope.produto_campanha_validade.validade = v.validade;
        $scope.produto_campanha_validade.quantidade_validade = v.quantidade;
    }

    $scope.setProdutoValidade = function(produto_campanha) {

        $scope.produto = produto_campanha.produto;
        $scope.produto_campanha_validade = produto_campanha;

        $scope.getValidades($scope.produto);


    }

    $scope.selecionarValor = function(produto, v) {

        var k = !v.selecionado;

        for (var i = 0; i < produto.valores.length; i++) {
            produto.valores[i].selecionado = false;
        }
        produto.valor_editavel.selecionado = false;

        v.selecionado = k;

    }

    $scope.selecionarValorBoasVindas = function(produto, v) {

        var k = !v.selecionado;

        for (var i = 0; i < produto.valores_boas_vindas.length; i++) {
            produto.valores_boas_vindas[i].selecionado = false;
        }
        produto.valor_boas_vindas_editavel.selecionado = false;

        v.selecionado = k;

    }

    var okc = false;
    campanhaService.getCampanha(function(p) {

        $scope.campanha_nova = p.campanha;
        $scope.campanha = p.campanha;
        okc = true;
        $scope.setDataCampanha();


    })

    $scope.getNumeracaoAlfabetica = function(numero) {

        var c = "A B C D E F G H I J K L M N O P Q R S T U V W X Y Z";

        c = c.split(" ");
        var r = "";
        do {
            r = c[numero % c.length] + r;
            numero = (numero - (numero % c.length)) / c.length;
        } while (numero > 0)

        return r;
    }

    $scope.addNumeracao = function(prod) {

        prod.numeracao++;

        var c = prod.campanha.campanhas;
        var add = true;

        for (var i = 0; i < c.length; i++) {
            if (c[i].id === prod.numeracao) {
                add = false;
                break;
            }
        }
        if (add) {
            c = prod.campanha;

            c.campanhas[c.campanhas.length] = {
                inicio: c.inicio,
                fim: c.fim,
                nome: "Campanha " + $scope.getNumeracaoAlfabetica(prod.numeracao),
                id: prod.numeracao,
                prazo: 0,
                parcelas: 1
            };
        }
        var c = prod.campanha.campanhas;

        lbl:
            for (var i = 0; i < c.length; i++) {

                for (var j = 0; j < prod.campanha.produtos.length; j++) {

                    if (prod.campanha.produtos[j].numeracao === c[i].id) {

                        continue lbl;

                    }

                }

                c[i] = null;

                for (var a = i; a < c.length - 1; a++) {
                    c[a] = c[a + 1];
                }
                c.length--;

            }

    }



    $scope.removeNumeracao = function(prod) {

        prod.numeracao--;

        prod.numeracao = Math.max(0, prod.numeracao);

        var c = prod.campanha.campanhas;
        var add = true;

        for (var i = 0; i < c.length; i++) {
            if (c[i].id === prod.numeracao) {
                add = false;
                break;
            }
        }
        if (add) {
            c = prod.campanha;

            c.campanhas[c.campanhas.length] = {
                inicio: c.inicio,
                fim: c.fim,
                nome: "Campanha " + $scope.getNumeracaoAlfabetica(prod.numeracao),
                id: prod.numeracao,
                prazo: 0,
                parcelas: 1
            };
        }
        var c = prod.campanha.campanhas;

        lbl:
            for (var i = 0; i < c.length; i++) {

                for (var j = 0; j < prod.campanha.produtos.length; j++) {

                    if (prod.campanha.produtos[j].numeracao === c[i].id) {

                        continue lbl;

                    }

                }

                c[i] = null;

                for (var a = i; a < c.length - 1; a++) {
                    c[a] = c[a + 1];
                }
                c.length--;

            }

    }

    $scope.getNumeracaoCor = function(numero) {

        var c = ['DarkRed', 'DarkGreen', 'DarkGray', 'DarkBlue', 'Purple', 'DarkOrange', 'SteelBlue'];

        return c[numero % c.length];

    }

    $scope.asalvar = [];

    var salvarCampanha = function(obj, campanha) {

        if ($scope.asalvar.length > 0) {

            $scope.asalvar[$scope.asalvar.length] = campanha;

        } else {

            $scope.asalvar[$scope.asalvar.length] = campanha;

            var mgc = function(camp) {

                baseService.merge(camp, function(r) {

                    for (var i = 0; i < $scope.asalvar.length - 1; i++) {
                        $scope.asalvar[i] = $scope.asalvar[i + 1];
                    }
                    $scope.asalvar.length--;

                    if ($scope.asalvar.length > 0) {
                        mgc($scope.asalvar[0]);
                    }

                    if (r.sucesso) {
                        obj.atual++;
                    } else {
                        obj.erro++;
                    }
                    loading.setProgress(obj.atual * 100 / obj.total);
                    if (obj.total == (obj.erro + obj.atual)) {
                        msg.alerta("Campanhas cadastradas" + (obj.erro > 0 ? ". Porem contem erros" : " com exito"));

                        $scope.campanhas.attList();
                    }
                });

            }

            mgc($scope.asalvar[0]);

        }

    }

    $scope.terminarCadastro = function() {

        var r = [];

        var str_erro = "";

        for (var i = 0; i < $scope.campanha.campanhas.length; i++) {

            var c = $scope.campanha.campanhas[i];

            var camp = angular.copy($scope.campanha_nova);
            camp.nome = c.nome;
            camp.prazo = c.prazo;
            camp.parcelas = c.parcelas;
            camp.inicio = c.inicio;
            camp.fim = c.fim;
            camp.produtos = [];

            for (var j = 0; j < $scope.campanha.produtos.length; j++) {

                var p = $scope.campanha.produtos[j];

                if (p.validade < 0) {

                    str_erro += "O Produto " + p.produto.nome + ", esta sem validade selecionada \n";
                    continue;

                }

                if (p.numeracao !== c.id) {

                    continue;

                }


                var prod = angular.copy($scope.produto_campanha_novo);
                prod.produto = p.produto;
                prod.campanha = camp;
                prod.limite = p.limite;
                prod.limite_boas_vindas = p.limite_boas_vindas;
                prod.valor = -1;
                prod.validade = p.validade;
                prod.de = p.de;
                prod.compra0_encomenda1 = p.compra0_encomenda1;

                for (var k = 0; k < p.valores.length; k++) {
                    if (p.valores[k].selecionado) {
                        prod.valor = p.valores[k].valor;
                        break;
                    }
                }

                for (var k = 0; k < p.valores.length; k++) {
                    if (p.valores_boas_vindas[k].selecionado) {
                        prod.valor_boas_vindas = p.valores_boas_vindas[k].valor;
                        break;
                    }
                }

                if (p.valor_editavel.selecionado) {

                    prod.valor = p.valor_editavel.valor;

                }

                if (p.valor_boas_vindas_editavel.selecionado) {

                    prod.valor_boas_vindas = p.valor_boas_vindas_editavel.valor;

                }

                if (isNaN(prod.valor) && isNaN(prod.valor_boas_vindas)) {
                    str_erro += "O produto " + prod.produto.nome + ", esta com um valor incorreto \n";
                    return;
                }

                if (prod.valor <= 0 || prod.valor_boas_vindas <= 0) {
                    str_erro += "O produto " + prod.produto.nome + ", esta sem valor selecionado \n";
                    continue;

                }

                if (prod.valor > 0) {

                    camp.produtos[camp.produtos.length] = prod;

                }

            }

            if (camp.produtos.length > 0) {

                r[r.length] = camp;

            }

        }

        if (str_erro !== "") {

            msg.erro(str_erro);
            return;

        }

        var obj = { total: r.length, atual: 0, erro: 0 };

        $scope.asalvar = [];
        for (var i = 0; i < r.length; i++) {
            salvarCampanha(obj, r[i]);
        }

        $scope.campanha.terminada = true;

    }

    $scope.millis = [];


    $scope.setDataCampanha = function() {

        if (!okc)
            return;
        $scope.setCampanhaCriacao($scope.agora);

    }

    $scope.removeProdutoCamp = function(campanha, produto) {

        if (campanha.produtos.length === 1) {
            msg.alerta("A Campanha nao pode ficar sem produtos");
            return;
        }

        var np = [];
        for (var i = 0; i < campanha.produtos.length; i++) {
            if (campanha.produtos[i] !== produto) {
                np[np.length] = campanha.produtos[i];
            }
        }
        campanha.produtos = np;

        $scope.addNumeracao(campanha.produtos[0]);
        $scope.removeNumeracao(campanha.produtos[0]);
        var pg = campanha.lista.pagina;
        campanha.lista = createList(campanha.produtos, 1, 500, "produto.nome");
        campanha.lista.pagina = pg;
        campanha.lista.attList();
    }


    $scope.addProdutoCamp = function(campanha, produto) {

        var produto_campanha = angular.copy($scope.produto_campanha_novo);
        produto_campanha.produto = produto;
        produto_campanha.validade = -1;
        produto_campanha.campanha = campanha;
        produto_campanha.valores = [{ valor: produto.valor_base, selecionado: false }];
        produto_campanha.valores_boas_vindas = [{ valor: produto.valor_base, selecionado: false }];

        produto_campanha.valor_editavel = { valor: produto.valor_base, selecionado: false };
        produto_campanha.valor_boas_vindas_editavel = { valor: parseFloat((produto.valor_base * 0.95).toFixed(2)), selecionado: false };

        produto_campanha.numeracao = -1;

        for (var j = 0; j < 3; j++) {
            produto_campanha.valores[j + 1] = { valor: (produto_campanha.valores[j].valor * 0.96).toFixed(2), selecionado: false };
            produto_campanha.valores_boas_vindas[j + 1] = { valor: (produto_campanha.valores_boas_vindas[j].valor * 0.95).toFixed(2), selecionado: false };
        }

        campanha.produtos[campanha.produtos.length] = produto_campanha;

        $scope.addNumeracao(produto_campanha);

        campanha.lista.attList();

    }


    var inl = false;
    $scope.setCampanhaCriacao = function(millis) {

        if (inl)
            return;

        var campanha = null;
        inl = true;
        for (var i = 0; i < $scope.millis.length; i++) {
            if ($scope.millis[i] === millis) {
                campanha = $scope.criacao_campanhas[i];
                break;
            }
        }

        if (campanha === null) {
            var ms = new Date(parseFloat(millis + ""));
            ms.setHours(0);
            ms.setMinutes(0);
            ms.setSeconds(1);
            var c = angular.copy($scope.campanha_nova);
            c.campanhas = [{
                inicio: ms.getTime(),
                fim: ms.getTime() + dia - 1000,
                nome: "Campanha A",
                id: 0,
                prazo: 0,
                parcelas: 1
            }]
            c.inicio = ms.getTime() + dia * i;
            c.fim = ms.getTime() + (dia * (i + 1));
            c.nome = "Nova campanha";

            c.numero = i;
            while (new Date(parseFloat(c.fim + "")).getDay() == 0 || new Date(parseFloat(c.fim + "")).getDay() == 6) {
                c.fim += dia;
            }
            c.terminada = false;
            $scope.criacao_campanhas[$scope.criacao_campanhas.length] = c;
            $scope.millis[$scope.millis.length] = millis;
            campanha = c;
        }

        $scope.c = campanha;

        if (campanha.produtos.length === 0) {

            var campanhas_feitas = [];

            campanhaService.getProdutosDia(new Date(parseFloat(millis + "")).getDay(), parseFloat(millis + ""), function(prods) {

                var selecionados = prods.produtos[1];

                var feita = selecionados.length > 0;

                if (feita) {
                    c.campanhas = [];
                }

                var produtos = prods.produtos[0];

                for (var i = 0; i < produtos.length; i++) {

                    var produto = produtos[i];

                    var sel = null;

                    for (var j = 0; j < selecionados.length; j++) {
                        if (parseInt(selecionados[j][0] + "") === parseInt(produto.codigo + "")) {
                            sel = selecionados[j];
                            break;
                        }
                    }

                    if (sel === null && feita) {
                        continue;
                    }

                    var nnn = 0;

                    if (feita) {

                        var tem = -1;
                        for (var k = 0; k < campanhas_feitas.length; k++) {
                            if (campanhas_feitas[k].nome === sel[4]) {
                                tem = k;
                                break;
                            }
                        }

                        if (tem < 0) {

                            campanha.campanhas[campanha.campanhas.length] = {
                                inicio: sel[5],
                                fim: sel[6],
                                nome: sel[4],
                                id: campanha.campanhas.length,
                                prazo: 0,
                                parcelas: 1
                            };

                            campanhas_feitas[campanhas_feitas.length] = campanha.campanhas[campanha.campanhas.length - 1];
                            nnn = campanha.campanhas.length - 1;

                        } else {
                            ccc = campanhas_feitas[tem];
                            nnn = tem;
                        }

                    }


                    var produto_campanha = angular.copy($scope.produto_campanha_novo);
                    produto_campanha.produto = produto;
                    produto_campanha.validade = -1;
                    produto_campanha.campanha = campanha;
                    produto_campanha.valores = [{ valor: produto.valor_base, selecionado: false }];
                    produto_campanha.valores_boas_vindas = [{ valor: parseFloat((produto.valor_base * 0.95).toFixed(2)), selecionado: false }];

                    produto_campanha.valor_editavel = { valor: produto.valor_base, selecionado: false };
                    produto_campanha.valor_boas_vindas_editavel = { valor: parseFloat((produto.valor_base * 0.95).toFixed(2)), selecionado: false };
                    produto_campanha.numeracao = nnn;


                    if ($scope.usuario.empresa.tipo_empresa !== 3) {
                        for (var j = 0; j < 3; j++) {
                            produto_campanha.valores[j + 1] = { valor: (produto_campanha.valores[j].valor * 0.95).toFixed(2), selecionado: false };
                            produto_campanha.valores_boas_vindas[j + 1] = { valor: (produto_campanha.valores_boas_vindas[j].valor * 0.95).toFixed(2), selecionado: false };
                        }
                    } else {
                        produto_campanha.quantidade_validade = produto.estoque;
                        produto_campanha.validade = 1000;
                        produto_campanha.valores[0].selecionado = true;
                        produto_campanha.valores_boas_vindas[0].selecionado = true;
                    }

                    if (feita) {
                        var achou = false;
                        for (var k = 0; k < produto_campanha.valores.length; k++) {

                            var v = produto_campanha.valores[k];

                            var vv = parseFloat(v.valor + "");

                            if (Math.abs(vv - sel[1]) < 0.1) {
                                v.selecionado = true;
                                achou = true;
                                break;
                            }

                        }
                        if (!achou) {
                            produto_campanha.valor_editavel.selecionado = true;
                            produto_campanha.valor_editavel.valor = sel[1];
                        }
                    }

                    campanha.produtos[campanha.produtos.length] = produto_campanha;

                }

                campanha.lista = createList(campanha.produtos, 1, 500, "produto.nome");

                inl = false;

            })

        } else {
            inl = false;
        }

        $scope.campanha = campanha;

    }

    sistemaService.getMesesValidadeCurta(function(p) {

        $scope.meses_validade_curta = p.meses_validade_curta;

    })

    $scope.setCampanha = function(campanha) {

        $scope.campanha = campanha;

    }

    $scope.mergeCampanha = function() {
        baseService.merge($scope.campanha, function(r) {
            if (r.sucesso) {
                $scope.campanha = r.o;
                if (r.sucesso) {
                    msg.alerta("Operacao efetuada com sucesso");
                    $scope.campanhas.attList();
                } else {
                    msg.erro("Fornecedor alterado, por?�m ocorreu um problema ao subir os documentos");
                }
            } else {
                msg.erro("Problema ao efetuar operacao. ");
            }
        });
    }

    $scope.getValidades = function(produto) {

        produtoService.getValidades($scope.meses_validade_curta, produto, function(validades) {

            produto.validades = validades;

        })

    }

    $scope.addProdutoCampanha = function(produto, validade) {

        var pc = angular.copy($scope.produto_campanha_novo);
        pc.produto = produto;
        pc.campanha = $scope.campanha;
        $scope.campanha.produtos[$scope.campanha.produtos.length] = pc;
        pc.valor = produto.valor_base;
        pc.validade = validade.validade;
        pc.limite = validade.quantidade;

        msg.alerta("Adicionado com sucesso");

    }

    $scope.deleteProdutoCampanha = function(campanha, produto) {

        remove(campanha.produtos, produto);

    }

    $scope.deleteCampanha = function() {
        baseService.delete($scope.campanha, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.campanhas.attList();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
        });
    }

})
rtc.controller("crtLotes", function($scope, loteService, baseService) {


    $scope.lotes = createAssinc(loteService, 1, 10, 10);
    $scope.lotes.attList();
    assincFuncs(
        $scope.lotes,
        "lote", ["id", "produto.nome", "quantidade_real", "validade", "numero", "rua", "altura", "data_entrada", "codigo_fabricante"]);

    $scope.lote_novo = {};

    $scope.loteSemData = function(lote) {

        lote.validade = 1000;

    }

    $scope.loteComData = function(lote) {

        lote.validade = new Date().getTime();

    }

    $scope.lote = {};

    $scope.lotes_cadastro = [];

    $scope.pendencias = [];
    $scope.todas_pendencias = [];

    loteService.getLote(function(l) {

        $scope.lote_novo = l.lote;

    })

    $scope.deletarLote = function() {
        baseService.delete($scope.lote, function(r) {
            if (r.sucesso) {
                msg.alerta("Deletado com sucesso");
                $scope.lotes.attList();
            } else {
                msg.erro("Problema ao deletar");
            }
        });
    }

    $scope.setLote = function(lote, elemento) {

        $scope.lote = lote;


        $scope.lote.validade_texto = toDate($scope.lote.validade);


        loteService.getItem(lote, function(i) {

            $scope.lote.item = i.item;

            if (elemento != null) {

                $scope.formarArvore(lote, elemento);

            }

        })

    }

    $scope.atualizaPendencias = function() {

        loteService.getPendenciasCadastro('', function(p) {

            for (var i = 0; i < p.pendencias.length; i++) {
                p.pendencias[i].divisao = parseInt(p.pendencias[i].grade.str.split(',')[0]) * 48;
            }

            $scope.todas_pendencias = angular.copy(p.pendencias);
            $scope.pendencias = createList(p.pendencias, 1, 10, "nome_produto");
            $scope.pendencias.attList();

        })

        loteService.getPendenciasCadastroCompra('', function(p) {

            for (var i = 0; i < p.pendencias.length; i++) {
                p.pendencias[i].divisao = parseInt(p.pendencias[i].grade.str.split(',')[0]) * 48;
            }

            $scope.todas_pendencias_compra = angular.copy(p.pendencias);
            $scope.pendencias_compra = createList(p.pendencias, 1, 10, "nome_produto");
            $scope.pendencias_compra.attList();

        })

    }

    $scope.atualizaPendencias();

    var ml = function(obj, lote) {
        baseService.merge(lote, function(r) {
            if (r.sucesso) {
                obj.atual++;
                loading.setProgress(obj.atual * 100 / obj.total);
                if ((obj.atual + obj.erros) == obj.total) {
                    if (obj.erros == 0) {
                        msg.alerta("Lotes cadastrados com sucesso");
                    } else {
                        msg.alerta("Ocorreu problema no cadastro de alguns lotes");
                    }
                    $scope.lotes.attList();
                    $scope.atualizaPendencias();
                    $scope.lotes_cadastro = [];
                }
            } else {
                obj.erros++;
            }
        });
    }
    var kk = 0;
    var fa = function(els, lote) {
        var id = kk;
        kk++;
        if (els == null) {
            return $('<ul></ul>').html('ESGOTADO').css('border-color', 'DarkRed').css('color', 'DarkRed');
        }
        var n = "";
        for (var i = 0; i < els.numero.length; i++) {
            if (n != "")
                n += "-";
            n += els.numero[i];
        }
        n = "[" + n + "]";

        var e = $('<ul></ul>');

        e.data("item", els);
        e.data("lote", lote);

        e.attr('id', 'a' + id);


        if (els.filhos.length > 0) {

            e.append($('<i></i>').addClass('fas fa-plus-circle').attr('id', 'b' + id).click(function() {

                $(this).hide(100);
                $('#l' + id).show(100);
                $('#a' + id).children('li').show(100);

            })).append($('<i></i>').addClass('fas fa-minus-circle').attr('id', 'l' + id).click(function() {

                $(this).hide();
                $('#b' + id).show(100);
                $('#a' + id).children('li').hide(100);

            }).hide()).append($('<i></i>').addClass('fas fa-sitemap').click(function() {

                $scope.imprimirItens($(this).parent().data("item").filhos.filter(function(el) {
                    return el != null
                }), $(this).parent().data("lote"));

            }));

        }

        e.append($('<i></i>').addClass("fas fa-print").click(function() {

            $scope.imprimirItens([$(this).parent().data("item")], $(this).parent().data("lote"));

        }))

        e.append(n + " &nbsp Quantidade: <strong>" + els.quantidade + "</strong>")

        for (var i = 0; i < els.filhos.length; i++) {

            e.append($('<li></li>').hide().append(fa(els.filhos[i], lote)));

        }



        return e;
    }

    $scope.imprimirItens = function(itens, lote) {
        var etiquetas = [];
        for (var i = 0; i < itens.length; i++) {
            var cod = fix(lote.id + "", 7);
            for (var j = 1; j < itens[i].numero.length; j++) {
                cod += fix(itens[i].numero[j] + "", 4);
            }
            var etiqueta = {
                id: lote.id,
                id_produto: lote.produto.id,
                nome_produto: lote.produto.nome,
                codigo: cod,
                empresa: lote.produto.empresa.nome
            };
            /*
            etiqueta.validade = toDate(lote.validade);

            if(lote.validade == 1000){

                etiqueta.validade = "";

            }
            */

            etiquetas[etiquetas.length] = etiqueta;
        }

        var buffer = 20;
        var buff = [];

        for (var i = 0; i < etiquetas.length; i++) {
            var k = parseInt(i / buffer);
            if (i % buffer === 0) {
                buff[k] = [];
            }
            buff[k][buff[k].length] = etiquetas[i];
        }

        for (var i = 0; i < buff.length; i++) {
            loteService.getEtiquetas(buff[i], function(a) {
                if (a.sucesso) {

                    window.open(projeto + "/php/uploads/" + a.arquivo);
                } else {

                    msg.erro("Ocorreu um problema de servidor, tente mais tarde");
                }
            });
        }

    }


    $scope.formarArvore = function(lote, elemento) {

        var i = lote.item;

        $("#" + elemento).html('');

        $('#' + elemento).append('<strong>Legenda:</strong><br>').append($('<i></i>').addClass('fas fa-sitemap')).append(' Imprimir todos sub-itens, ').append($('<i></i>').addClass('fas fa-print')).append(' Imprimir item <hr>');

        $("#" + elemento).append(fa(i, lote));

    }
    $scope.mergeLotes = function() {
        var progresso = { atual: 0, total: $scope.lotes_cadastro.length, erros: 0 };
        for (var i = 0; i < $scope.lotes_cadastro.length; i++) {

            var l = $scope.lotes_cadastro[i];

            if (l.validade !== 1000) {
                l.validade = fromDate(l.validade_texto);
            }

            if (l.validade < 0) {
                progresso.erros++;
                continue;
            }

            ml(progresso, l);

        }
    }

    $scope.mergeLote = function() {

        if ($scope.lote.validade !== 1000) {
            $scope.lote.validade = fromDate($scope.lote.validade_texto);

            if ($scope.lote.validade < 0) {

                msg.erro("Validade incorreta");
                return;

            }
        }

        baseService.merge($scope.lote, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.lote = r.o;
                $scope.lotes.attList();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
        });
    }

    $scope.setPendenciaCompra = function(pendencia, palet) {

        if (palet <= 0) {

            msg.erro("A quantidade de palet deve ser maior do que 0");
            return;

        }

        if ((palet % pendencia.grade.gr[pendencia.grade.gr.length - 1]) != 0) {

            msg.erro("A quantidade de palet deve ser multipla de " + pendencia.grade.gr[pendencia.grade.gr.length - 1]);
            return;

        }

        var qtd = pendencia.quantidade;

        var produtoSimulado = { id: pendencia.id_produto, nome: pendencia.nome_produto };

        $scope.lotes_cadastro = [];

        while (qtd > 0) {

            var z = palet;

            qtd -= z;

            if (qtd < 0)
                z += qtd;

            var lote = angular.copy($scope.lote_novo);

            lote.grade = pendencia.grade;
            lote.quantidade_inicial = z;
            lote.quantidade_real = z;
            lote.produto = produtoSimulado;

            lote.validade_texto = toDate(lote.validade);

            lote.id_produto_pedido = pendencia.id_produto_pedido;

            $scope.lotes_cadastro[$scope.lotes_cadastro.length] = lote;

        }

    }

    $scope.setPendencia = function(pendencia, palet) {

        if (palet <= 0) {

            msg.erro("A quantidade de palet deve ser maior do que 0");
            return;

        }

        if ((palet % pendencia.grade.gr[pendencia.grade.gr.length - 1]) != 0) {

            msg.erro("A quantidade de palet deve ser multipla de " + pendencia.grade.gr[pendencia.grade.gr.length - 1]);
            return;

        }

        var qtd = pendencia.quantidade;

        var produtoSimulado = { id: pendencia.id_produto, nome: pendencia.nome_produto };

        $scope.lotes_cadastro = [];

        while (qtd > 0) {

            var z = palet;

            qtd -= z;

            if (qtd < 0)
                z += qtd;

            var lote = angular.copy($scope.lote_novo);

            lote.grade = pendencia.grade;
            lote.quantidade_inicial = z;
            lote.quantidade_real = z;
            lote.produto = produtoSimulado;


            lote.validade_texto = toDate(lote.validade);


            $scope.lotes_cadastro[$scope.lotes_cadastro.length] = lote;

        }

    }


})
rtc.controller("crtFornecedores", function($scope, fornecedorService, categoriaDocumentoService, documentoService, cidadeService, baseService, telefoneService, uploadService) {

    $scope.cortar = function(texto, num) {

        if (texto.length < num) {

            return texto;

        }

        return texto.substring(0, num) + "...";

    }

    $scope.fornecedores = createAssinc(fornecedorService, 1, 13, 4);
    $scope.fornecedores.attList();
    assincFuncs(
        $scope.fornecedores,
        "fornecedor", ["codigo", "nome", "email_fornecedor.endereco", "cnpj", "inscricao_estadual", "habilitado"]);

    $scope.fornecedores.posload = function() {

        setTimeout(function() {
            loading.redux();
        }, 1000)

    }

    $scope.fornecedor_novo = {};
    $scope.fornecedor = {};
    $scope.estado = {};

    $scope.email = {};

    $scope.data_atual = new Date().getTime();


    $scope.documento_novo = {};
    $scope.documento = {};

    $scope.telefone_novo = {};
    $scope.telefone = {};

    $scope.categorias_documento = [];
    $scope.estados = [];
    $scope.cidades = [];

    $("#uploaderDocumentoFornecedor").change(function() {

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo");

            } else {

                var doc = angular.copy($scope.documento);

                for (var i = 0; i < arquivos.length; i++) {

                    var d = angular.copy(doc);
                    $scope.documento = d;
                    d.link = arquivos[i];

                    $scope.addDocumento();

                }

                msg.alerta("Upload feito com sucesso");
            }

        })

    })

    fornecedorService.getFornecedor(function(p) {
        $scope.fornecedor_novo = p.fornecedor;
        $scope.fornecedor_novo["documentos"] = [];
    })
    categoriaDocumentoService.getElementos(function(p) {
        $scope.categorias_documento = p.elementos;
        $scope.documento.categoria = $scope.categorias_documento[0];
    })
    documentoService.getDocumento(function(p) {
        $scope.documento_novo = p.documento;
        $scope.documento = angular.copy($scope.documento_novo);
        $scope.documento.categoria = $scope.categorias_documento[0];
    })
    telefoneService.getTelefone(function(p) {
        $scope.telefone_novo = p.telefone;
        $scope.telefone = angular.copy($scope.telefone_novo);
    })

    cidadeService.getElementos(function(p) {
        var estados = [];
        var cidades = p.elementos;
        $scope.cidades = cidades;

        lbl:
            for (var i = 0; i < cidades.length; i++) {
                var c = cidades[i];
                for (var j = 0; j < estados.length; j++) {
                    if (estados[j].id === c.estado.id) {
                        estados[j].cidades[estados[j].cidades.length] = c;
                        c.estado = estados[j];
                        continue lbl;
                    }
                }
                c.estado["cidades"] = [c];
                estados[estados.length] = c.estado;
            }

        $scope.estados = estados;
    })

    $scope.novoFornecedor = function() {

        $scope.fornecedor = angular.copy($scope.fornecedor_novo);

    }

    $scope.setFornecedor = function(fornecedor) {

        $scope.fornecedor = fornecedor;

        fornecedorService.getDocumentos($scope.fornecedor, function(d) {
            $scope.fornecedor["documentos"] = d.documentos;
            for (var i = 0; i < d.documentos.length; i++) {
                equalize(d.documentos[i], "categoria", $scope.categorias_documento);
            }
        })

        equalize(fornecedor.endereco, "cidade", $scope.cidades);

        if (typeof fornecedor.endereco.cidade !== 'undefined') {
            $scope.estado = fornecedor.endereco.cidade.estado;
        } else {
            fornecedor.endereco.cidade = $scope.cidades[0];
            $scope.estado = fornecedor.endereco.cidade.estado;
        }

    }

    $scope.mergeFornecedor = function() {

        if ($scope.fornecedor.endereco.cidade == null) {
            msg.erro("Fornecedor sem cidade.");
            return;
        }

        baseService.merge($scope.fornecedor, function(r) {
            if (r.sucesso) {
                $scope.fornecedor = r.o;
                fornecedorService.setDocumentos($scope.fornecedor, $scope.fornecedor.documentos, function(rr) {

                    if (rr.sucesso) {

                        msg.alerta("Operacao efetuada com sucesso");
                        $scope.setFornecedor($scope.fornecedor);
                        $scope.fornecedores.attList();

                    } else {
                        msg.erro("Fornecedor alterado, por?ï¿½m ocorreu um problema ao subir os documentos");

                    }

                })


            } else {
                msg.erro("Problema ao efetuar operacao. ");
            }
        });

    }
    $scope.deleteFornecedor = function() {
        baseService.delete($scope.fornecedor, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.fornecedores.attList();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
        });
    }

    $scope.removeDocumento = function(documento) {
        remove($scope.fornecedor.documentos, documento);
    }

    $scope.addDocumento = function() {

        $scope.fornecedor.documentos[$scope.fornecedor.documentos.length] = $scope.documento;
        $scope.documento = angular.copy($scope.documento_novo);
        $scope.documento.categoria = $scope.categorias_documento[0];

    }
    $scope.removeTelefone = function(tel) {

        remove($scope.fornecedor.telefones, tel);

    }
    $scope.addTelefone = function() {
        $scope.fornecedor.telefones[$scope.fornecedor.telefones.length] = $scope.telefone;
        $scope.telefone = angular.copy($scope.telefone_novo);
    }

})
rtc.controller("crtTransportadoras", function($scope, clienteService, transportadoraService, regraTabelaService, tabelaService, categoriaDocumentoService, documentoService, cidadeService, baseService, telefoneService, uploadService) {

    $scope.transportadoras = createAssinc(transportadoraService, 1, 3, 10);
    $scope.transportadoras.attList();
    assincFuncs(
        $scope.transportadoras,
        "transportadora", ["codigo", "razao_social", "nome_fantasia", "despacho", "cnpj", "inscricao_estadual", "habilitada"]);

    $scope.clientes = createAssinc(clienteService, 1, 3, 4);
    $scope.clientes.attList();
    assincFuncs(
        $scope.clientes,
        "cliente", ["codigo", "razao_social"], "filtroClientes");

    $scope.transportadora_novo = {};
    $scope.transportadora = {};
    $scope.estado = {};
    $scope.cliente = {};
    $scope.email = {};

    $scope.tabela_nova = {};
    $scope.tabela = {};

    $scope.documento_novo = {};
    $scope.documento = {};

    $scope.tabela_selecionada = {};
    $scope.transportadora_tabela = {};

    $scope.resultado_individual = {};

    $scope.telefone_novo = {};
    $scope.telefone = {};

    $scope.regra_nova = {};
    $scope.regra = {};

    $scope.estado_teste = null;
    $scope.cidade_teste = null;
    $scope.valor_teste = 0;
    $scope.peso_teste = 0;

    $scope.categorias_documento = [];
    $scope.estados = [];
    $scope.cidades = [];

    $scope.fretes = [];


    $scope.a_nome_grupo = "";
    $scope.a_peso_minimo = 0;
    $scope.a_peso_maximo = 0;

    $scope.a_advalorem = 0;
    $scope.a_advalorem_minimo = 0;

    $scope.a_pedagio_fixo = 0;

    $scope.a_despacho_fixo = 0;

    $scope.a_gris = 0;
    $scope.a_gris_minimo = 0;

    $scope.a_taxakilosreal = 0;
    $scope.a_taxakilos = 0;
    $scope.a_taxakilos_minimo = 0;

    $scope.a_taxakilosreal_e = 0;
    $scope.a_taxakilos_e = 0;
    $scope.a_taxakilos_e_minimo = 0;

    $scope.a_frete_minimo = 0;
    $scope.condicional = "";

    $scope.resultante = "";

    $scope.tda = 0;
    $scope.taxa_suframa = 0;
    $scope.taxa_coleta = 0;
    $scope.frete_peso = 0;

    $scope.pfrete_peso = 0;

    $scope.assistente = function() {

        $scope.condicional = "";
        $scope.resultante = "";

        if ($scope.a_advalorem > 0) {

            if ($scope.resultante !== "") {
                $scope.resultante += "+";
            }

            var exp = "(!valor*" + ($scope.a_advalorem / 100) + ")";

            if ($scope.a_advalorem_minimo > 0) {
                exp = "MAX[" + exp + "," + $scope.a_advalorem_minimo + "]";
            }

            $scope.resultante += exp;

        }

        if ($scope.a_pedagio_fixo > 0) {

            if ($scope.resultante !== "") {
                $scope.resultante += "+";
            }


            $scope.resultante += $scope.a_pedagio_fixo;

        }

        if ($scope.taxa_coleta > 0) {

            if ($scope.resultante !== "") {
                $scope.resultante += "+";
            }


            $scope.resultante += $scope.taxa_coleta;

        }

        if ($scope.frete_peso > 0) {

            if ($scope.resultante !== "") {
                $scope.resultante += "+";
            }


            $scope.resultante += $scope.frete_peso;

        }

        if ($scope.taxa_suframa > 0) {

            if ($scope.resultante !== "") {
                $scope.resultante += "+";
            }


            $scope.resultante += $scope.taxa_suframa;

        }

        if ($scope.tda > 0) {

            if ($scope.resultante !== "") {
                $scope.resultante += "+";
            }


            $scope.resultante += $scope.tda;

        }

        if ($scope.a_despacho_fixo > 0) {

            if ($scope.resultante !== "") {
                $scope.resultante += "+";
            }


            $scope.resultante += $scope.a_despacho_fixo;

        }

        if ($scope.a_gris > 0) {

            if ($scope.resultante !== "") {
                $scope.resultante += "+";
            }

            var exp = "(!valor*" + ($scope.a_gris / 100) + ")";

            if ($scope.a_gris_minimo > 0) {
                exp = "MAX[" + exp + "," + $scope.a_gris_minimo + "]";
            }

            $scope.resultante += exp;

        }

        if ($scope.a_taxakilos > 0) {

            if ($scope.resultante !== "") {
                $scope.resultante += "+";
            }

            var exp = "(" + $scope.a_taxakilosreal + "*CIMA[!peso/" + $scope.a_taxakilos + "])";

            if ($scope.a_taxakilos_minimo > 0) {
                exp = "MAX[" + exp + "," + $scope.a_taxakilos_minimo + "]";
            }


            $scope.resultante += exp;

        }

        if ($scope.a_frete_minimo > 0) {

            if ($scope.resultante !== "") {
                $scope.resultante += "+";
            }

            $scope.resultante += $scope.a_frete_minimo;

        }

        if ($scope.a_taxakilos_e > 0) {

            if ($scope.resultante !== "") {
                $scope.resultante += "+";
            }

            var exp = "(" + $scope.a_taxakilosreal_e + "*MAX[0,(!peso-" + $scope.a_taxakilos_e + ")])";

            if ($scope.a_taxakilos_e_minimo > 0) {
                exp = "MAX[" + exp + "," + $scope.a_taxakilos_e_minimo + "]";
            }

            $scope.resultante += exp;

        }

        if ($scope.pfrete_peso > 0) {

            var k = "(";

            if ($scope.a_taxakilos > 0) {

                var exp = "(" + $scope.a_taxakilosreal + "*CIMA[!peso/" + $scope.a_taxakilos + "])";

                if ($scope.a_taxakilos_minimo > 0) {
                    exp = "MAX[" + exp + "," + $scope.a_taxakilos_minimo + "]";
                }

                k += exp;

            }

            if ($scope.frete_peso > 0) {

                if (k.length > 1) {
                    k += "+";
                }

                k += $scope.frete_peso + ")";

            }

            if (k.length > 1) {

                if ($scope.resultante !== "") {
                    $scope.resultante += "+";
                }

                k += ")";

                $scope.resultante += "(" + k + "*" + ($scope.pfrete_peso / 100) + ")";

            }

        }

        $scope.resultante = "(" + $scope.resultante + ")/!icms";

        //------

        if ($scope.a_nome_grupo !== "") {

            $scope.condicional += "GRUPO['!cliente.cidade','" + $scope.a_nome_grupo + "']";

        }

        if ($scope.a_peso_minimo > 0) {

            if ($scope.condicional !== "") {
                $scope.condicional += "&";
            }

            $scope.condicional += "!peso>" + $scope.a_peso_minimo;

        }

        if ($scope.a_peso_maximo > 0) {

            if ($scope.condicional !== "") {
                $scope.condicional += "&";
            }

            $scope.condicional += "!peso<" + $scope.a_peso_maximo;

        }




    }

    $scope.finalizaAssistente = function() {

        var r = angular.copy($scope.regra_nova);

        r.resultante = $scope.resultante;
        r.condicional = $scope.condicional;

        $scope.tabela_selecionada.regras[$scope.tabela_selecionada.regras.length] = r;

    }


    $("#uploaderDocumentoTransportadora").change(function() {

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo");

            } else {

                var doc = angular.copy($scope.documento);

                for (var i = 0; i < arquivos.length; i++) {

                    var d = angular.copy(doc);
                    $scope.documento = d;
                    d.link = arquivos[i];

                    $scope.addDocumento();

                }

                msg.alerta("Upload feito com sucesso");
            }

        })

    })

    transportadoraService.getTransportadora(function(p) {
        $scope.transportadora_novo = p.transportadora;
        $scope.transportadora_novo["documentos"] = [];
    })
    categoriaDocumentoService.getElementos(function(p) {
        $scope.categorias_documento = p.elementos;
        $scope.documento.categoria = $scope.categorias_documento[0];
    })
    documentoService.getDocumento(function(p) {
        $scope.documento_novo = p.documento;
        $scope.documento = angular.copy($scope.documento_novo);
        $scope.documento.categoria = $scope.categorias_documento[0];
    })
    telefoneService.getTelefone(function(p) {
        $scope.telefone_novo = p.telefone;
        $scope.telefone = angular.copy($scope.telefone_novo);
    })
    tabelaService.getTabela(function(p) {
        $scope.tabela_nova = p.tabela;
        $scope.tabela = angular.copy($scope.tabela_nova);
    })
    regraTabelaService.getRegraTabela(function(p) {
        $scope.regra_nova = p.regra_tabela;
        $scope.regra = angular.copy($scope.regra_nova);
    })

    $scope.addRegra = function() {

        $scope.tabela_selecionada.regras[$scope.tabela_selecionada.regras.length] = angular.copy($scope.regra_nova);

    }

    $scope.setCliente = function(cliente) {

        lbl: for (var i = 0; i < $scope.estados.length; i++) {
            if ($scope.estados[i].id === cliente.endereco.cidade.estado.id) {
                var e = $scope.estados[i];
                $scope.estado_teste = $scope.estados[i];
                for (var j = 0; j < e.cidades.length; j++) {
                    if (e.cidades[j].id === cliente.endereco.cidade.id) {
                        $scope.cidade_teste = e.cidades[j];
                        break lbl;
                    }
                }
            }
        }

            $scope.cliente = cliente;

    }

    $scope.attResultadoIndividual = function() {

        tabelaService.getValorTabela($scope.tabela_selecionada, { cidade: $scope.cidade_teste, valor: $scope.valor_teste, peso: $scope.peso_teste }, function(f) {

            $scope.resultado_individual = f.valor;

        })

    }

    $scope.attResultado = function() {

        tabelaService.getFretes(null, { cidade: $scope.cidade_teste, valor: $scope.valor_teste, peso: $scope.peso_teste }, function(f) {

            $scope.fretes = f.fretes;

        })

    }

    $scope.copiarRegra = function(regra) {

        var c = angular.copy(regra);
        c.id = 0;
        c.copia = regra.id;
        if (regra.copia > 0) {
            c.copia = regra.copia;
        }
        $scope.tabela_selecionada.regras[$scope.tabela_selecionada.regras.length] = c;

    }

    $scope.removerRegra = function(regra) {

        remove($scope.tabela_selecionada.regras, regra);

    }

    $scope.selecionarTabela = function(transp) {

        $scope.tabela_selecionada = transp.tabela;
        $scope.transportadora_tabela = transp;

    }

    cidadeService.getElementos(function(p) {
        var estados = [];
        var cidades = p.elementos;
        $scope.cidades = cidades;

        lbl:
            for (var i = 0; i < cidades.length; i++) {
                var c = cidades[i];
                for (var j = 0; j < estados.length; j++) {
                    if (estados[j].id === c.estado.id) {
                        estados[j].cidades[estados[j].cidades.length] = c;
                        c.estado = estados[j];
                        continue lbl;
                    }
                }
                c.estado["cidades"] = [c];
                estados[estados.length] = c.estado;
            }
        $scope.estado_teste = estados[0];
        $scope.cidade_teste = $scope.estado_teste.cidades[0];
        $scope.estados = estados;
    })

    $scope.selecionarRegra = function(regra) {

        $scope.regra = regra;

    }

    $scope.novoTransportadora = function() {

        $scope.transportadora = angular.copy($scope.transportadora_novo);

    }

    $scope.criarTabela = function(transp) {

        transp.tabela = $scope.tabela;
        $scope.tabela = angular.copy($scope.tabela_nova);

    }

    $scope.setTransportadora = function(transportadora) {

        $scope.transportadora = transportadora;

        transportadoraService.getDocumentos($scope.transportadora, function(d) {
            $scope.transportadora["documentos"] = d.documentos;
            for (var i = 0; i < d.documentos.length; i++) {
                equalize(d.documentos[i], "categoria", $scope.categorias_documento);
            }
        })

        equalize(transportadora.endereco, "cidade", $scope.cidades);
        if (typeof transportadora.endereco.cidade !== 'undefined') {
            $scope.estado = transportadora.endereco.cidade.estado;
        } else {
            transportadora.endereco.cidade = $scope.cidades[0];
            $scope.estado = transportadora.endereco.cidade.estado;
        }

    }

    $scope.mergeTabela = function(t) {

        baseService.merge(t, function(r) {

            if (!r.sucesso) {
                msg.erro("Falha na operacao");
            }

        })

    }

    $scope.mergeTransportadoraTabela = function() {

        if ($scope.transportadora_tabela.endereco.cidade == null) {
            msg.erro("Transportadora sem cidade.");
            return;
        }

        baseService.merge($scope.transportadora_tabela, function(r) {
            if (r.sucesso) {
                $scope.transportadora_tabela.tabela = r.o.tabela;
                $scope.transportadora_tabela = r.o;
                $scope.tabela_selecionada = r.o.tabela;
                if (r.sucesso) {
                    msg.alerta("Operacao efetuada com sucesso");
                } else {
                    msg.erro("Transportadora alterada, por?�m ocorreu um problema ao subir os documentos");

                }
            } else {
                msg.erro("Problema ao efetuar operacao. ");
            }
        });

    }

    $scope.mergeTransportadora = function() {

        if ($scope.transportadora.endereco.cidade == null) {
            msg.erro("Transportadora sem cidade.");
            return;
        }

        baseService.merge($scope.transportadora, function(r) {

            if (r.sucesso) {
                $scope.transportadora = r.o;
                transportadoraService.setDocumentos($scope.transportadora, $scope.transportadora.documentos, function(rr) {

                    if (rr.sucesso) {

                        msg.alerta("Operacao efetuada com sucesso");
                        $scope.setTransportadora($scope.transportadora);
                        $scope.transportadoras.attList();

                    } else {
                        msg.erro("Transportadora alterada, por?�m ocorreu um problema ao subir os documentos");

                    }

                })


            } else {

                msg.erro("Problema ao efetuar operacao. ");
            }
        });

    }
    $scope.deleteTransportadora = function() {
        baseService.delete($scope.transportadora, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.transportadoras.attList();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
        });
    }

    $scope.removeDocumento = function(documento) {
        remove($scope.transportadora.documentos, documento);
    }

    $scope.addDocumento = function() {

        $scope.transportadora.documentos[$scope.transportadora.documentos.length] = $scope.documento;
        $scope.documento = angular.copy($scope.documento_novo);
        $scope.documento.categoria = $scope.categorias_documento[0];

    }
    $scope.removeTelefone = function(tel) {

        remove($scope.transportadora.telefones, tel);

    }
    $scope.addTelefone = function() {
        $scope.transportadora.telefones[$scope.transportadora.telefones.length] = $scope.telefone;
        $scope.telefone = angular.copy($scope.telefone_novo);
    }

})
rtc.controller("crtClientesVendas", function($scope, clienteService, fornecedorService, clienteService, produtoService, usuarioService, baseService) {


    $scope.getValidades = function(produto) {

        produtoService.getValidades(3, produto, function(v) {

            produto.validades = v;

        })

    }

    $scope.salvarCliente = function(cliente) {

        baseService.merge(cliente, function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso");

            }

        })

    }

    $scope.salvarFornecedor = function(fornecedor) {

        baseService.merge(fornecedor, function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso");

            }

        })

    }

    $scope.setValidade = function(produto) {

        $scope.produto = produto;

        if ($scope.fornecedor != null) {

            var p = {
                id: 0,
                produto: produto,
                fornecedor: $scope.fornecedor,
                validade: 1000,
                preco1: "pv*1",
                comis1: "pv*0.002",
                preco2: "pv*0.98",
                comis2: "pv*0.002",
                preco3: "pv*0.97",
                comis3: "pv*0.0015",
                preco4: "pv*0.96",
                comis4: "pv*0.001",
                _classe: "ProdutoFornecedor"
            }

            $scope.fornecedor.produtos[$scope.fornecedor.produtos.length] = p;
        } else if ($scope.cliente != null) {

            console.log($scope);

            var p = {
                id: 0,
                produto: produto,
                cliente: $scope.cliente,
                preco1: "pv*1",
                comis1: "pv*0.002",
                preco2: "pv*0.98",
                comis2: "pv*0.002",
                preco3: "pv*0.97",
                comis3: "pv*0.0015",
                preco4: "pv*0.96",
                comis4: "pv*0.001",
                _classe: "ProdutoCliente"
            }


            $scope.cliente.produtos_cliente[$scope.cliente.produtos_cliente.length] = p;


        }

    }

    $scope.produtos = createAssinc(produtoService, 1, 3, 4);

    assincFuncs(
        $scope.produtos,
        "produto", ["codigo", "nome", "disponivel"], "filtroProdutos");



    usuarioService.filtro_base = "(usuario.id_cargo = -297)";

    $scope.vendedores = [];

    usuarioService.getElementos(0, 10000, "", "", function(e) {

        $scope.vendedores = e.elementos;
        $scope.vendedores[$scope.vendedores.length] = { id: 0, nome: "Sem Vendedor" };

        $scope.vendedor = $scope.vendedores[0];

    });

    $scope.produto = null;

    $scope.validade = null;

    $scope.fornecedor = null;

    $scope.vendedor = null;

    $scope.addVendedor = function(vendedor, fornecedor) {

        for (var i = 0; i < fornecedor.vendedores.length; i++) {

            var v = fornecedor.vendedores[i];

            if (vendedor.id == v.id)
                return;

        }


        fornecedor.vendedores[fornecedor.vendedores.length] = vendedor;


    }

    $scope.removeVendedor = function(vendedor, fornecedor) {

        var nv = [];

        for (var i = 0; i < fornecedor.vendedores.length; i++) {

            var v = fornecedor.vendedores[i];

            if (v.id === vendedor.id)
                continue;

            nv[nv.length] = v;

        }

        fornecedor.vendedores = nv;

    }

    $scope.setClienteSimples = function(cliente) {

        $scope.cliente = cliente;
        $scope.fornecedor = null;

        $("#produtos").modal("show");

        $scope.produtos.attList();

    }

    $scope.setFornecedorSimples = function(fornecedor) {

        $scope.fornecedor = fornecedor;
        $scope.cliente = null;

        $("#produtos").modal("show");

        $scope.produtos.attList();

    }

    $scope.cliente = null;

    $scope.setCliente = function(cliente) {

        clienteService.getProdutosCliente(cliente, function(r) {


            cliente.produtos_cliente = r.produtos;
            cliente.vendedores = rr.vendedores;

            $scope.cliente = cliente;

        })

    }

    $scope.setFornecedor = function(fornecedor) {

        fornecedorService.getProdutos(fornecedor, function(r) {


            fornecedorService.getVendedores(fornecedor, function(rr) {

                fornecedor.produtos = r.produtos;
                fornecedor.vendedores = rr.vendedores;

                $scope.fornecedor = fornecedor;


            })

        })

    }


    clienteService.filtro_base = "cliente.cnpj IN (SELECT cg.cnpj FROM clientes_grandes cg) AND cliente.cnpj NOT IN (SELECT fornecedor.cnpj FROM fornecedor WHERE fornecedor.excluido=false)";

    $scope.trocaFiltro = function() {

        if ($("#filtro").val() == "") {

            clienteService.filtro_base = "cliente.cnpj IN (SELECT cg.cnpj FROM clientes_grandes cg) AND cliente.cnpj NOT IN (SELECT fornecedor.cnpj FROM fornecedor WHERE fornecedor.excluido=false)";

        } else {

            clienteService.filtro_base = "cliente.cnpj NOT IN (SELECT fornecedor.cnpj FROM fornecedor WHERE fornecedor.excluido=false)";

        }

        $scope.clientes.attList();

    }


    $scope.trocaVendedor = function(cliente) {

        baseService.merge(cliente, function(r) {


        });

    }


    $scope.clientes = createAssinc(clienteService, 1, 20, 10);

    assincFuncs(
        $scope.clientes,
        "cliente", ["codigo", "email_cliente.endereco", "razao_social", "id_vendedor", "cobranca_emocional", "nome_fantasia", "inscricao_estadual", "cnpj", "cpf", "limite_credito", "termino_limite", "empresa.nome", "estado_cliente.sigla", "cidade_cliente.nome"]);

    $scope.clientes.attList();

    $scope.fornecedores = createAssinc(fornecedorService, 1, 10, 10);

    assincFuncs(
        $scope.fornecedores,
        "fornecedor", ["codigo", "email_fornecedor.endereco", "nome", "inscricao_estadual", "cnpj", "estado_fornecedor.sigla", "cidade_fornecedor.nome"]);

    $scope.fornecedores.attList();


})
rtc.controller("crtClientes", function($scope, produtoService, categoriaProspeccaoService, clienteService, sistemaService, empresaService, categoriaClienteService, categoriaDocumentoService, documentoService, cidadeService, baseService, telefoneService, uploadService) {

    $scope.clientes = createAssinc(clienteService, 1, 20, 10);

    assincFuncs(
        $scope.clientes,
        "cliente", ["codigo", "email_cliente.endereco", "razao_social", "cobranca_emocional", "nome_fantasia", "inscricao_estadual", "cnpj", "cpf", "limite_credito", "termino_limite", "empresa.nome"]);

    $scope.clientes.attList();

    $scope.produtos = createAssinc(produtoService, 1, 5, 4);
    $scope.produtos.attList();
    assincFuncs(
        $scope.produtos,
        "produto", ["codigo", "nome"], "filtro33");

    $scope.cliente_novo = {};
    $scope.cliente = {};
    $scope.estado = {};

    $scope.email = {};

    $scope.resetCredito = function(cliente) {

        clienteService.resetCredito(cliente, function(x) {

            cliente.inicio_limite = 1000;
            cliente.termino_limite = 1000;
            cliente.limite_credito = 0;

        })

    }

    $scope.data_atual = new Date().getTime();

    $scope.documento_novo = {};
    $scope.documento = {};

    $scope.telefone_novo = {};
    $scope.telefone = {};

    $scope.categorias_cliente = [];
    $scope.categorias_documento = [];
    $scope.estados = [];
    $scope.cidades = [];

    $scope.categoria_prospeccao = null;
    $scope.categorias_prospeccao = [];
    $scope.categorias_prospeccao_cliente = [];

    $scope.empresas_clientes = [];

    $scope.receita = "";

    $scope.obj = {};

    $scope.cobranca_emocional = false;

    $scope.removerProduto = function(p) {

        var np = [];


        for (var i = 0; i < $scope.cliente.produtos.length; i++) {

            if ($scope.cliente.produtos[i].id !== p.id) {

                np[np.length] = $scope.cliente.produtos[i];

            }

        }

        $scope.cliente.produtos = np;

    }

    $scope.selecionarProduto = function(p) {

        $scope.cliente.produtos[$scope.cliente.produtos.length] = p;

    }

    $scope.trancadoModulo0 = function(cliente) {

        if (typeof cliente["modulo0"] === 'undefined') {

            return true;

        }

        return cliente["modulo0"];

    }

    $scope.destrancarModulo0 = function(cliente) {

        empresaService.oferecerModulo0(cliente, function(r) {

            if (r.sucesso) {

                cliente["modulo0"] = false;

            }

        })

    }


    $scope.filtroCobrancaEmocional = function() {

        $scope.cobranca_emocional = !$scope.cobranca_emocional;

        if ($scope.cobranca_emocional) {

            clienteService.filtro_base = "cliente.cobranca_emocional=true";

        } else {

            clienteService.filtro_base = "";

        }

        $scope.clientes.attList();

    }

    $scope.setCobrancaEmocional = function(cliente) {

        cliente.cobranca_emocional = !cliente.cobranca_emocional;
        clienteService.setCobrancaEmocional(cliente, function(r) {
            if (!r.sucesso) {
                msg.erro("Ocorreu uma falha na operacao");
            }
        })

    }

    $scope.cliente_html = {};
    $scope.html = "";

    $scope.enviando_html = false;

    $scope.enviarHtmlModulo2 = function() {

        $scope.enviando_html = true;

        clienteService.enviarHtmlModulo2($scope.cliente_html, function(r) {

            if (r.sucesso) {

                msg.alerta("Html enviado com sucesso");
                $("#htmlModulo2").hide();

            } else {

                msg.erro("Ocorreu um problema, tente novamente mais tarde");

            }

            $scope.enviando_html = false;

        })


    }

    $scope.getHtmlModulo2 = function(cliente) {


        $scope.cliente_html = cliente;

        clienteService.getHtmlModulo2(cliente, function(r) {

            if (r.sucesso) {

                if (r.html === "") {

                    msg.erro("Nao existe nenhum email MKT para modulo 2 hoje, cobre a empresa de Marketing");

                } else {

                    var html = window.atob(r.html);

                    $scope.html = html;

                    $("#htmlModulo2").show();
                    $("#conteudoModulo2").html(html);

                }

            }

        })

    }

    $scope.enviarHtmlModulo0 = function() {

        $scope.enviando_html = true;

        clienteService.enviarHtmlModulo0($scope.cliente_html, function(r) {

            if (r.sucesso) {

                msg.alerta("Html enviado com sucesso");
                $("#htmlModulo0").hide();

            } else {

                msg.erro("Ocorreu um problema, tente novamente mais tarde");

            }

            $scope.enviando_html = false;

        })


    }

    $scope.getHtmlModulo0 = function(cliente) {


        $scope.cliente_html = cliente;

        clienteService.getHtmlModulo0(cliente, function(r) {

            if (r.sucesso) {

                if (r.html === "") {

                    msg.erro("Nao existe nenhum email MKT para modulo 0 hoje, cobre a empresa de Marketing");

                } else {

                    var html = window.atob(r.html);

                    $scope.html = html;

                    $("#htmlModulo0").show();
                    $("#conteudoModulo0").html(html);

                }

            }

        })

    }

    $scope.enviarHtmlBoasVindas = function() {

        $scope.enviando_html = true;

        clienteService.enviarHtmlBoasVindas($scope.cliente_html, function(r) {

            if (r.sucesso) {

                msg.alerta("Html enviado com sucesso");
                $("#htmlBoasVindas").hide();

            } else {

                msg.erro("Ocorreu um problema, tente novamente mais tarde");

            }

            $scope.enviando_html = false;

        })


    }

    $scope.getHtmlBoasVindas = function(cliente) {


        $scope.cliente_html = cliente;

        clienteService.getHtmlBoasVindas(cliente, function(r) {

            if (r.sucesso) {

                if (r.html === "") {

                    msg.erro("Nao existe nenhum email MKT para boas vindas hoje, cobre a empresa de Marketing");

                } else {

                    var html = window.atob(r.html);

                    $scope.html = html;

                    $("#htmlBoasVindas").show();
                    $("#conteudoBoasVindas").html(html);

                }

            }

        })

    }

    var criarObjeto = function(mapa, string) {

        var objeto = {};

        var pp = [{ campo: "", texto: string }];

        for (var i = 0; i < mapa.length; i++) {

            var m = mapa[i];

            for (var j = 0; j < pp.length; j++) {

                var p = pp[j];

                if (p.texto.indexOf(m[0]) >= 0) {

                    var k = p.texto.split(m[0], 2);

                    p.texto = k[0];

                    pp[pp.length] = {
                        campo: m,
                        texto: k[1]
                    };

                    break;

                }

            }


        }

        for (var i = 0; i < pp.length; i++) {

            var p = pp[i];

            if (p.campo === "") {
                continue;
            }

            objeto[p.campo[1]] = p.texto.split("\n")[1];

        }

        return objeto;

    }

    $scope.analisarReceita = function() {


        $scope.obj = criarObjeto([
            ["NÚMERO DE INSCRIÇÃO", "cnpj"],
            ["NOME EMPRESARIAL", "razao_social"],
            ["LOGRADOURO", "rua"],
            ["NÚMERO", "numero"],
            ["CEP", "cep"],
            ["BAIRRO/DISTRITO", "bairro"],
            ["MUNICÍPIO", "municipio"],
            ["UF", "estado"],
            ["SITUAÇÃO CADASTRAL", "situacao"]
        ], $scope.receita);

        $scope.cliente.cnpj.valor = $scope.obj.cnpj;
        $scope.cliente.razao_social = $scope.obj.razao_social;
        $scope.cliente.endereco.rua = $scope.obj.rua;
        $scope.cliente.endereco.numero = $scope.obj.numero;
        $scope.cliente.endereco.cep.valor = $scope.obj.cep;

        var estado = $scope.obj.estado;
        var cidade = $scope.obj.municipio;

        for (var i = 0; i < $scope.estados.length; i++) {
            if ($scope.estados[i].sigla === estado) {
                estado = $scope.estados[i];
                for (var j = 0; j < estado.cidades.length; j++) {

                    if (estado.cidades[j].nome === cidade) {

                        cidade = estado.cidades[j];

                        $scope.cliente.endereco.cidade = cidade;

                        break;

                    }

                }

                break;
            }
        }



        $("#importarReceita").modal("hide");

    }


    categoriaProspeccaoService.getCategorias(function(c) {

        $scope.categorias_prospeccao = c.categorias;

    })

    empresaService.getEmpresasClientes(function(e) {

        $scope.empresas_clientes = e.clientes;

    })

    $scope.analisaCredito = function(c) {

        window.location = "analise_credito.php?empresa=" + c.empresa.id + "&cliente=" + c.id;

    }

    $scope.getLinkConsignadoCliente = function(cliente) {

        return projeto + "/mod0-seja-bem-vindo.php?idc=" + encode64SPEC(cliente.id + "_" + cliente.razao_social);

    }

    $scope.getLinkTransportadora = function(cliente) {

        return projeto + "/tmod0-seja-bem-vindo.php?idc=" + encode64SPEC(cliente.id + "_" + cliente.razao_social);

    }

    $scope.removeCategoriaProspeccao = function(cat) {


        var nc = [];
        for (var i = 0; i < $scope.categorias_prospeccao_cliente.length; i++) {
            if ($scope.categorias_prospeccao_cliente[i].id === cat.id) {
                continue;
            }
            nc[nc.length] = $scope.categorias_prospeccao_cliente[i];
        }

        $scope.categorias_prospeccao_cliente = nc;

    }

    $scope.addCategoriaProspeccao = function() {

        for (var i = 0; i < $scope.categorias_prospeccao_cliente.length; i++) {
            if ($scope.categorias_prospeccao_cliente[i].id === $scope.categoria_prospeccao.id) {
                msg.erro("Essa categoria ja esta adcionada");
                return;
            }
        }

        $scope.categorias_prospeccao_cliente[$scope.categorias_prospeccao_cliente.length] = $scope.categoria_prospeccao;

    }

    $("#uploaderDocumentoCliente").change(function() {

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo");

            } else {

                var doc = angular.copy($scope.documento);

                for (var i = 0; i < arquivos.length; i++) {

                    var d = angular.copy(doc);
                    $scope.documento = d;
                    d.link = arquivos[i];

                    $scope.addDocumento();

                }

                msg.alerta("Upload feito com sucesso");
            }

        })

    })

    clienteService.getCliente(function(p) {
        $scope.cliente_novo = p.cliente;
        $scope.cliente_novo["documentos"] = [];
    })
    categoriaClienteService.getElementos(function(p) {
        $scope.categorias_cliente = p.elementos;
    })
    categoriaDocumentoService.getElementos(function(p) {
        $scope.categorias_documento = p.elementos;
        $scope.documento.categoria = $scope.categorias_documento[0];
    })
    documentoService.getDocumento(function(p) {
        $scope.documento_novo = p.documento;
        $scope.documento = angular.copy($scope.documento_novo);
        $scope.documento.categoria = $scope.categorias_documento[0];
    })
    telefoneService.getTelefone(function(p) {

        $scope.telefone_novo = p.telefone;
        $scope.telefone = angular.copy($scope.telefone_novo);
    })

    cidadeService.getElementos(function(p) {
        var estados = [];
        var cidades = p.elementos;
        $scope.cidades = cidades;

        lbl:
            for (var i = 0; i < cidades.length; i++) {
                var c = cidades[i];
                for (var j = 0; j < estados.length; j++) {
                    if (estados[j].id === c.estado.id) {
                        estados[j].cidades[estados[j].cidades.length] = c;
                        c.estado = estados[j];
                        continue lbl;
                    }
                }
                c.estado["cidades"] = [c];
                estados[estados.length] = c.estado;
            }

        $scope.estados = estados;
    })

    $scope.novoCliente = function() {

        $scope.cliente = angular.copy($scope.cliente_novo);
        equalize($scope.cliente, "empresa", $scope.empresas_clientes);

    }

    $scope.setCliente = function(cliente) {

        $scope.cliente = cliente;

        equalize($scope.cliente, "categoria", $scope.categorias_cliente);
        equalize($scope.cliente, "empresa", $scope.empresas_clientes);

        clienteService.getDocumentos($scope.cliente, function(d) {
            $scope.cliente["documentos"] = d.documentos;
            for (var i = 0; i < d.documentos.length; i++) {
                equalize(d.documentos[i], "categoria", $scope.categorias_documento);
            }
        })

        produtoService.empresa = $scope.cliente.empresa.id;
        $scope.produtos.attList();
        clienteService.getProdutos($scope.cliente, function(a) {

            $scope.cliente.produtos = a.produtos;

        })



        clienteService.getCategoriasProspeccao($scope.cliente, function(c) {
            $scope.categorias_prospeccao_cliente = c.categorias;

        })

        equalize(cliente.endereco, "cidade", $scope.cidades);
        if (typeof cliente.endereco.cidade !== 'undefined') {
            $scope.estado = cliente.endereco.cidade.estado;
        } else {
            cliente.endereco.cidade = $scope.cidades[0];
            $scope.estado = cliente.endereco.cidade.estado;
        }

        empresaService.logsOferecimetoModulo0(cliente, function(l) {

            if (l.sucesso) {

                cliente.logs_oferecimento_m0 = l.logs;

            }

        })

    }

    $scope.mergeCliente = function() {

        if ($scope.cliente.categoria == null) {
            msg.erro("Cliente sem categoria.");
            return;
        }

        if ($scope.cliente.endereco.cidade == null) {
            msg.erro("Cliente sem cidade.");
            return;
        }

        var alterar = $scope.cliente.id > 0;

        baseService.merge($scope.cliente, function(r) {
            if (r.sucesso) {
                $scope.cliente = r.o;
                clienteService.setDocumentos($scope.cliente, $scope.cliente.documentos, function(rr) {

                    if (rr.sucesso) {

                        clienteService.setCategoriasProspeccao($scope.cliente, $scope.categorias_prospeccao_cliente, function(ass) {

                            if (ass.sucesso) {

                                msg.alerta("Operacao efetuada com sucesso");

                                if (alterar) {

                                    sistemaService.aoAlterarCliente($scope.cliente, function(c) {});

                                } else {

                                    sistemaService.aoCadastrarCliente($scope.cliente, function(c) {});

                                }

                                $scope.setCliente($scope.cliente);
                                $scope.clientes.attList();

                            } else {

                                msg.erro("Ocorreu um problema ao atualizar as categorias de prospeccao do cliente, os demais dados foram alterados com sucesso.");

                            }

                        })




                    } else {
                        msg.erro("Cliente alterado, porem ocorreu um problema ao subir os documentos");

                    }

                })


            } else {
                msg.erro("Problema ao efetuar operacao. ");
            }
        });

    }
    $scope.deleteCliente = function() {
        baseService.delete($scope.cliente, function(r) {
            if (r.sucesso) {
                msg.alerta("Operacao efetuada com sucesso");
                $scope.clientes.attList();
            } else {
                msg.erro("Problema ao efetuar operacao");
            }
        });
    }

    $scope.removeDocumento = function(documento) {
        remove($scope.cliente.documentos, documento);
    }

    $scope.addDocumento = function() {

        $scope.cliente.documentos[$scope.cliente.documentos.length] = $scope.documento;
        $scope.documento = angular.copy($scope.documento_novo);
        $scope.documento.categoria = $scope.categorias_documento[0];

    }
    $scope.removeTelefone = function(tel) {

        remove($scope.cliente.telefones, tel);

    }
    $scope.addTelefone = function() {

        $scope.cliente.telefones[$scope.cliente.telefones.length] = $scope.telefone;
        $scope.telefone = angular.copy($scope.telefone_novo);
    }

})
rtc.controller("crtProdutos", function($scope, $sce, kimService, fabricanteService, ativoService, culturaService, sistemaService, uploadService, pragaService, produtoService, baseService, categoriaProdutoService, receituarioService) {

    $scope.cortar = function(texto, num) {

        if (texto.length < num) {

            return texto;

        }

        return texto.substring(0, num) + "...";

    }

    $scope.raiz = null;

    $scope.passado = null;

    kimService.getLogsRobo(function(l) {

        $scope.raiz = l.raiz;

    })

    var fe = function(f) {

        for (var i = 0; i < f.filhos.length; i++) {

            f.filhos[i].exibe_abaixo = false;

            fe(f.filhos[i]);

        }

    }

    $scope.ordenarAproximados = function(prod, tipo) {


        var ordem = false;

        if (typeof prod["ordem"] !== 'undefined') {

            ordem = prod["ordem"];

        } else {

            prod["ordem"] = ordem;

        }

        var tmp = prod.aproximados.listaTotal;

        for (var i = 1; i < tmp.length; i++) {
            for (var j = i; j > 0; j--) {

                var p1 = tmp[j];
                var p2 = tmp[j - 1];

                if (((p1[tipo] + "").localeCompare(p2[tipo] + "") * (ordem ? 1 : -1)) > 0) {

                    tmp[j - 1] = p1;
                    tmp[j] = p2;

                } else {

                    break;

                }


            }
        }

        prod["ordem"] = !prod["ordem"];

        prod.aproximados.attList();

    }

    $scope.attPassado = function(str) {

        $scope.passado = $scope.raiz;

        lbl:
            while (true) {

                for (var j = 0; j < $scope.passado.filhos.length; j++) {

                    var f = $scope.passado.filhos[j];

                    if (str.toUpperCase().indexOf(f.nome.toUpperCase()) == 0) {

                        $scope.passado = f;
                        $scope.passado.exibe_abaixo = true;
                        continue lbl;

                    }

                }

                break;

            }

        fe($scope.passado);

    }

    $scope.produtos = createAssinc(produtoService, 1, 10, 4);
    $scope.produtos.attList();
    assincFuncs(
        $scope.produtos,
        "produto", ["disponivel", "codigo", "id_logistica", "nome", "cat.nome_cat", "estoque", "troca", "transito", "valor_base", "ativo", "classe_risco", "dia_semana"],
        null, false);
    $scope.produtos.posload = function() {
        setTimeout(function() {
            loading.redux();
        }, 200)
    }

    $scope.produto = {};
    $scope.produto_novo = {};

    $scope.receituario_novo = {};
    $scope.receituario = {};

    $scope.tipos_produto = [];


    $scope.medidas = [];
    $scope.tipos_plantacao = [];

    $scope.setReceituario = function(r) {

        $scope.receituario = r;

    }


    $scope.produtoTemp = { nome: "", empresa: "", preco: 0.0, imagem: "", link: "", usado: false };

    $scope.cadastrarProdutoTemp = function(produto) {

        var tmp = produto.aproximados;
        produto.aproximados = [];

        produtoService.inserirAproximado(produto, $scope.produtoTemp, function(r) {

            $scope.produtoTemp = { nome: "", empresa: "", preco: 0.0, imagem: "", link: "", usado: false };

            $scope.setProduto(produto);
            produto.aproximados = tmp;

        })



    }

    $scope.nome_fabricante = "";
    $scope.novoFabricante = function() {

        for (var i = 0; i < $scope.fabricantes.length; i++) {
            if ($scope.fabricantes[i].nome == $scope.nome_fabricante) {
                $scope.produto.fabricante = $scope.nome_fabricante;
                return;
            }
        }

        sistemaService.novoFabricante($scope.nome_fabricante, function(r) {

            if (r.sucesso) {

                $scope.fabricantes[$scope.fabricantes.length] = { nome: $scope.nome_fabricante };
                $scope.produto.fabricante = $scope.nome_fabricante;

            } else {

                msg.erro("Ocorreu uma falha ao cadastrar o fabricante");

            }

        })

    }

    $scope.calculosReceita = function() {


        var str = $scope.receituario.qtd_calda +
            " " + $scope.receituario.unidade_qtd_calda[1] +
            " de " + $scope.receituario.unidade_usada + " a cada " +
            $scope.receituario.dosagem_max + " " + $scope.receituario.tipo_dosagem_max[1] + " de " +
            $scope.receituario.produto.nome + "<hr>";


        str += "CarÃªncia de: " + $scope.receituario.carencia + " Dias <hr>";

        str += "Area por unidade: ";


        var delegates = [
            function(r) {

                var quantidade_aplicacoes = (Math.max(r.produto.quantidade_unidade, 1) / (r.dosagem_max / r.tipo_dosagem_max[2])).toFixed(2).split(".").join(",");
                //aqui seria no caso da dosagem ser por hectar, ou seja, tamanho dessa calda seria = ao tamanho da calda / ha

                var area = quantidade_aplicacoes;

                return area + " HA";

            },
            function(r) {

                var quantidade_aplicacoes = (Math.max(r.produto.quantidade_unidade, 1) / (r.dosagem_max / r.tipo_dosagem_max[2]));

                var area = ((quantidade_aplicacoes * (r.qtd_calda / r.unidade_qtd_calda[2])) / (r.total_calda_ha / r.tipo_total_calda_ha[2])).toFixed(2).split(".").join(",");

                return area + " HA";

            },
            function(r) {

                var quantidade_aplicacoes = (Math.max(r.produto.quantidade_unidade, 1) / (r.dosagem_max / r.tipo_dosagem_max[2]));

                var area = (quantidade_aplicacoes * (r.qtd_calda / r.unidade_qtd_calda[2])).toFixed(2).split(".").join(",");

                return area + " HA";

            }
        ];

        str += delegates[$scope.receituario.tipo_plantacao[2]]($scope.receituario);

        return $sce.trustAsHtml(str);

    }

    receituarioService.getMedidas(function(m) {
        $scope.medidas = m.medidas;
    })

    receituarioService.getTiposPlantacao(function(t) {
        $scope.tipos_plantacao = t.tipos;
    })


    sistemaService.getTiposProduto(function(r) {

        if (r.sucesso) {

            $scope.tipos_produto = r.tipos;

        }

    })

    $scope.dias_semana = [
        { id: 0, nome: "Domingo" },
        { id: 1, nome: "Segunda-Feira" },
        { id: 2, nome: "Terca-Feira" },
        { id: 3, nome: "Quarta-Feira" },
        { id: 4, nome: "Quinta-Feira" },
        { id: 5, nome: "Sexta-Feira" },
        { id: 6, nome: "Sabado" }
    ];


    $scope.filtros_dia_semana = [
        { id: -1, nome: "Sem Filtro" }
    ];

    $scope.filtro_dia_semana = $scope.filtros_dia_semana[0];

    $scope.attDiaSemana = function(produto) {


        produtoService.attDiaSemana(produto, function(r) {

            if (!r.sucesso) {

                msg.erro("Ocorreu um problema");

            }

        });

    }

    $scope.filtrarDiaSemana = function() {

        if ($scope.filtro_dia_semana.id >= 0) {
            produtoService.filtro_base = "produto.disponivel>0 AND produto.dia_semana=" + $scope.filtro_dia_semana.id;
        } else {
            produtoService.filtro_base = "produto.disponivel>0";
        }

        $scope.produtos.attList();

    }

    if (typeof rtc["planilha_base_mode"] !== 'undefined') {
        $scope.filtrarDiaSemana();
    }

    for (var i = 0; i < $scope.dias_semana.length; i++) {

        $scope.filtros_dia_semana[$scope.filtros_dia_semana.length] = $scope.dias_semana[i];

    }

    $scope.nivel = function(nivel, vetor) {

        for (var i = 0; i < vetor.length; i++) {
            if (nivel <= vetor[i].nivel) {
                return vetor[i].nome;
            }
        }

        return "";

    }

    $scope.passarParaOutrasEmpresas = function(produto) {


        produtoService.passarParaOutrasEmpresas(produto, function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso");

            } else {

                msg.erro("Falha ao efetuar operacao");

            }

        })

    }

    $scope.ativos = [];

    $scope.categorias = [];

    $scope.culturas = [];

    $scope.pragas = [];

    $scope.logisticas = [];

    $scope.fabricantes = [];

    sistemaService.getLogisticas(function(rr) {

        $scope.logisticas = rr.logisticas;

    })

    ativoService.getAtivos(function(a) {

        $scope.ativos = a.ativos;

    })

    fabricanteService.getFabricantes(function(f) {

        $scope.fabricantes = f.fabricantes;

    })

    $scope.removerFoto = function(foto, produto) {

        var nf = [];

        for (var i = 0; i < produto.mais_fotos.length; i++) {

            if (produto.mais_fotos[i] !== foto) {

                nf[nf.length] = produto.mais_fotos[i];

            }

        }

        produto.mais_fotos = nf;

        produtoService.setMaisFotos(produto, produto.mais_fotos, function(rr) {

            if (rr.sucesso) {

                msg.alerta("Upload feito com sucesso");

            } else {

                msg.erro("Ocorreu um problema no servidor");

            }

        })

    }

    $("#flFicha").change(function() {

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo de imagem");

            } else {


                for (var i = 0; i < arquivos.length; i++) {

                    $scope.produto.ficha = arquivos[i];

                    produtoService.setFichaEmergencia($scope.produto, arquivos[i], function(r) {

                        if (r.sucesso) {

                            msg.alerta("Procedimento efetuado com sucesso");

                        } else {

                            msg.erro("Falha ao executar procedimento");

                        }


                    })


                    break;

                }


            }

        })

    })

    $scope.getMaisFotos = function(produto, tipo) {

        var f = [];

        for (var i = 0; i < produto.mais_fotos.length; i++) {

            var foto = produto.mais_fotos[i];

            if (foto.tipo === tipo) {

                f[f.length] = foto;

            }

        }

        return f;

    }

    $("#uploaderImagemProdutoSecundario").change(function() {

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo de imagem");

            } else {

                var mais_fotos = $scope.produto.mais_fotos;
                for (var i = 0; i < arquivos.length; i++) {
                    mais_fotos[mais_fotos.length] = { imagem: arquivos[i], tipo: 0 };
                }
                $scope.produto.mais_fotos = mais_fotos;
                produtoService.setMaisFotos($scope.produto, mais_fotos, function(rr) {

                    if (rr.sucesso) {

                        msg.alerta("Upload feito com sucesso");

                    } else {

                        msg.erro("Ocorreu um problema no servidor");

                    }

                })


            }

        })

    })

    $("#uploaderImagemProdutoSecundarioArmazenagem").change(function() {

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo de imagem");

            } else {

                var mais_fotos = $scope.produto.mais_fotos;
                for (var i = 0; i < arquivos.length; i++) {
                    mais_fotos[mais_fotos.length] = { imagem: arquivos[i], tipo: 1 };
                }
                $scope.produto.mais_fotos = mais_fotos;
                produtoService.setMaisFotos($scope.produto, mais_fotos, function(rr) {

                    if (rr.sucesso) {

                        msg.alerta("Upload feito com sucesso");

                    } else {

                        msg.erro("Ocorreu um problema no servidor");

                    }

                })


            }

        })

    })

    $("#uploaderImagemProdutoSecundarioVenda").change(function() {

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo de imagem");

            } else {

                var mais_fotos = $scope.produto.mais_fotos;
                for (var i = 0; i < arquivos.length; i++) {
                    mais_fotos[mais_fotos.length] = { imagem: arquivos[i], tipo: 2 };
                }
                $scope.produto.mais_fotos = mais_fotos;
                produtoService.setMaisFotos($scope.produto, mais_fotos, function(rr) {

                    if (rr.sucesso) {

                        msg.alerta("Upload feito com sucesso");

                    } else {

                        msg.erro("Ocorreu um problema no servidor");

                    }

                })


            }

        })

    })

    $("#uploaderImagemProdutoSecundarioLeilao").change(function() {

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo de imagem");

            } else {

                var mais_fotos = $scope.produto.mais_fotos;
                for (var i = 0; i < arquivos.length; i++) {
                    mais_fotos[mais_fotos.length] = { imagem: arquivos[i], tipo: 3 };
                }
                $scope.produto.mais_fotos = mais_fotos;
                produtoService.setMaisFotos($scope.produto, mais_fotos, function(rr) {

                    if (rr.sucesso) {

                        msg.alerta("Upload feito com sucesso");

                    } else {

                        msg.erro("Ocorreu um problema no servidor");

                    }

                })


            }

        })

    })

    $("#uploaderImagemProdutoSecundarioNormal").change(function() {

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo de imagem");

            } else {

                var mais_fotos = $scope.produto.mais_fotos;
                for (var i = 0; i < arquivos.length; i++) {
                    mais_fotos[mais_fotos.length] = { imagem: arquivos[i], tipo: 4 };
                }
                $scope.produto.mais_fotos = mais_fotos;
                produtoService.setMaisFotos($scope.produto, mais_fotos, function(rr) {

                    if (rr.sucesso) {

                        msg.alerta("Upload feito com sucesso");

                    } else {

                        msg.erro("Ocorreu um problema no servidor");

                    }

                })


            }

        })

    })


    $("#uploaderImagemArmazenagemProduto").change(function() {

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo de imagem");

            } else {

                $scope.produto.imagem_armazenagem = arquivos[0];

                var mais_fotos = $scope.produto.mais_fotos;
                for (var i = 1; i < arquivos.length; i++) {
                    mais_fotos[mais_fotos.length] = arquivos[i];
                }
                $scope.produto.mais_fotos = mais_fotos;
                produtoService.setMaisFotos($scope.produto, mais_fotos, function(rr) {

                    if (rr.sucesso) {

                        msg.alerta("Upload feito com sucesso");

                    } else {

                        msg.erro("Ocorreu um problema no servidor");

                    }

                })


            }

        })

    })


    $("#uploaderImagemVendaProduto").change(function() {

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo de imagem");

            } else {

                $scope.produto.imagem_venda = arquivos[0];

                var mais_fotos = $scope.produto.mais_fotos;
                for (var i = 1; i < arquivos.length; i++) {
                    mais_fotos[mais_fotos.length] = arquivos[i];
                }
                $scope.produto.mais_fotos = mais_fotos;
                produtoService.setMaisFotos($scope.produto, mais_fotos, function(rr) {

                    if (rr.sucesso) {

                        msg.alerta("Upload feito com sucesso");

                    } else {

                        msg.erro("Ocorreu um problema no servidor");

                    }

                })


            }

        })

    })

    $("#uploaderImagemLeilaoProduto").change(function() {

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo de imagem");

            } else {

                $scope.produto.imagem_leilao = arquivos[0];

                var mais_fotos = $scope.produto.mais_fotos;
                for (var i = 1; i < arquivos.length; i++) {
                    mais_fotos[mais_fotos.length] = arquivos[i];
                }
                $scope.produto.mais_fotos = mais_fotos;
                produtoService.setMaisFotos($scope.produto, mais_fotos, function(rr) {

                    if (rr.sucesso) {

                        msg.alerta("Upload feito com sucesso");

                    } else {

                        msg.erro("Ocorreu um problema no servidor");

                    }

                })


            }

        })

    })

    $("#uploaderImagemProduto").change(function() {

        uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

            if (!sucesso) {

                msg.erro("Falha ao subir arquivo de imagem");

            } else {

                $scope.produto.imagem = arquivos[0];

                var mais_fotos = $scope.produto.mais_fotos;
                for (var i = 1; i < arquivos.length; i++) {
                    mais_fotos[mais_fotos.length] = arquivos[i];
                }
                $scope.produto.mais_fotos = mais_fotos;
                produtoService.setMaisFotos($scope.produto, mais_fotos, function(rr) {

                    if (rr.sucesso) {

                        msg.alerta("Upload feito com sucesso");

                    } else {

                        msg.erro("Ocorreu um problema no servidor");

                    }

                })


            }

        })

    })



    setInterval(function() {



        $("input[type='file']").each(function() {

            if ($(this).attr("id").indexOf("flImgTemp_") != 0)
                return;

            if (typeof $(this).data("up") !== 'undefined')
                return;

            $(this).attr("data-up", "true");


            $(this).change(function() {

                uploadService.upload($(this).prop("files"), function(arquivos, sucesso) {

                    if (!sucesso) {

                        msg.erro("Falha ao subir arquivo de imagem");

                    } else {

                        $scope.produtoTemp.imagem = arquivos[0];

                        msg.alerta("Subida com sucesso");

                    }

                })


            })

        })


    }, 1000)

    $scope.deletarProduto = function() {

        var tmp = $scope.produto.aproximados;

        $scope.produto.aproximados = null;

        baseService.delete($scope.produto, function(r) {

            $scope.produto.aproximados = tmp;

            if (r.sucesso) {

                msg.alerta("Deletado com sucesso");
                $scope.produtos.attList();

            } else {

                msg.erro("Problema ao deletar");

            }



        });

    }


    $scope.mergeProdutoEspecifico = function(produto) {




        var validaGrade = produto.grade.str.split(",");
        var ant = -1;
        for (var i = 0; i < validaGrade.length; i++) {
            if (!isNormalInteger(validaGrade[i]) || parseInt(validaGrade[i]) == 0) {
                msg.erro("Grade incorreta");
                return;
            }

            if (parseInt(validaGrade[i]) > ant && ant >= 0) {
                msg.erro("Grade incorreta, sub unidade maior que unidade");
                return;
            }

            ant = parseInt(validaGrade[i]);
        }

        var tmp = produto["aproximados"];
        produto["aproximados"] = [];

        baseService.merge(produto, function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso");


                $scope.produto = r.o;
                produto["aproximados"] = tmp;
                $scope.receituario.produto = $scope.produto;
                equalize($scope.produto, "categoria", $scope.categorias);
                if ($scope.produto.logistica !== null) {
                    equalize($scope.produto, "logistica", $scope.logisticas);
                }

            } else {

                msg.erro("Problema ao efetuar operacao. " + r.mensagem);

            }



        });

    }

    $scope.mergeProduto = function() {




        var validaGrade = $scope.produto.grade.str.split(",");
        var ant = -1;
        for (var i = 0; i < validaGrade.length; i++) {
            if (!isNormalInteger(validaGrade[i]) || parseInt(validaGrade[i]) == 0) {
                msg.erro("Grade incorreta");
                return;
            }

            if (parseInt(validaGrade[i]) > ant && ant >= 0) {
                msg.erro("Grade incorreta, sub unidade maior que unidade");
                return;
            }

            ant = parseInt(validaGrade[i]);
        }

        var tmp = $scope.produto["aproximados"];
        $scope.produto["aproximados"] = [];

        baseService.merge($scope.produto, function(r) {

            if (r.sucesso) {

                msg.alerta("Operacao efetuada com sucesso");

                kimService.confirmarAlteracoes($scope.raiz, function(rr) {

                    $scope.raiz = rr.o.raiz;
                    $scope.passado = $scope.raiz;
                    $scope.attPassado($scope.produto.nome_fantasia);

                });

                $scope.produto = r.o;
                $scope.produto["aproximados"] = tmp;
                $scope.receituario.produto = $scope.produto;
                $scope.getReceituario($scope.produto);
                equalize($scope.produto, "categoria", $scope.categorias);
                $scope.produtos.attList();

                if ($scope.produto.logistica !== null) {
                    equalize($scope.produto, "logistica", $scope.logisticas);
                }

            } else {

                msg.erro("Problema ao efetuar operacao. " + r.mensagem);

            }



        });

    }

    $scope.temPermissao = function(produto) {

        if (produto.permissao) {
            $scope.mergeProdutoEspecifico(produto);
            return;
        }

        var tmp = produto.aproximados;
        produto.aproximados = [];
        produtoService.temPermissao(produto, function(r) {

            if (r.permissao) {

                produto.permissao = true;
                produto.aproximados = tmp;

                $scope.mergeProdutoEspecifico(produto);

            } else {

                var obs = window.prompt("Voce nao tem permissao para alterar, o sistema vai solicitar para o responsavel, digite o motivo:");

                if (obs == null)
                    return;

                produtoService.solicitarPermissao(produto, obs, function(rr) {

                    if (rr.sucesso) {

                        produto.aproximados = tmp;

                    }

                });

            }

        })

    }

    $scope.deleteReceituario = function(rec, produto) {

        baseService.delete(rec, function(r) {

            if (r.sucesso) {

                msg.alerta("Deletado com sucesso");
                $scope.getReceituario(produto);

            } else {

                msg.erro("Problema ao deletar");

            }



        })

    }

    $scope.mergeReceituario = function() {


        if ($scope.produto.id == 0) {

            msg.erro("Efetue o cadastro do produto primeiro");

            return;

        }

        if ($scope.receituario.cultura === null) {


            msg.erro("Selecione uma cultura");

            return;

        }

        if ($scope.receituario.praga === null) {


            msg.erro("Selecione uma praga");

            return;

        }

        var tmp = $scope.receituario.produto["aproximados"];
        $scope.receituario.produto["aproximados"] = [];

        baseService.merge($scope.receituario, function(r) {


            if (r.sucesso) {

                $scope.receituario = angular.copy($scope.receituario_novo);
                $scope.receituario.produto = $scope.produto;
                $scope.receituario.produto["aproximados"] = tmp;
                $scope.getReceituario($scope.produto);
                msg.alerta("Operacoes efetuada com sucesso");


            } else {

                msg.erro("Problema ao efetuar operacao");

            }



        });

    }

    $scope.abrirImagem = function(img) {

        window.open(img);

    }

    $scope.getReceituario = function(p) {

        produtoService.getReceituario(p, function(r) {

            p.receituario = r.receituario;

        });

    }

    $scope.novoProduto = function() {

        $scope.produto = angular.copy($scope.produto_novo);
    }

    $scope.aproximados = null;



    $scope.getMaximo = function(produto, usado) {

        var tmp = produto.aproximados;

        if (tmp == null)
            return;

        var valor = -1;

        for (var i = 0; i < tmp.listaTotal.length; i++) {

            var p = tmp.listaTotal[i];

            if (p.usado != usado)
                continue;

            if (valor == -1 || valor < p.preco) {

                valor = p.preco;

            }

        }

        if (valor < 0) {

            return "----";

        }

        return valor;

    }

    $scope.getMinimo = function(produto, usado) {

        var tmp = produto.aproximados;

        if (tmp == null)
            return;

        var valor = -1;

        for (var i = 0; i < tmp.listaTotal.length; i++) {

            var p = tmp.listaTotal[i];

            if (p.usado != usado)
                continue;

            if (valor == -1 || valor > p.preco) {

                valor = p.preco;

            }

        }

        if (valor < 0) {

            return "----";

        }

        return valor;

    }

    $scope.getResultado = function(produto) {



        var tmp = produto.aproximados;

        if (tmp == null)
            return;

        var valor = 0;
        var qtd = 0;

        var valorUsado = 0;
        var qtdUsado = 0;

        for (var i = 0; i < tmp.listaTotal.length; i++) {

            var p = tmp.listaTotal[i];

            if (!p.desativado && !p.usado) {

                valor += p.preco;
                qtd++;

            } else if (!p.desativado && p.usado) {

                valorUsado += p.preco;
                qtdUsado++;

            }

        }

        valor /= qtd;

        if (qtdUsado == 0) {
            valorUsado = valor;
        } else {
            valorUsado /= qtdUsado;
        }


        var resultado = eval(produto.formula_preco.split("mu").join(valorUsado + "").split("m").join(valor));

        return resultado.toFixed(2).split(".").join(",");

    }


    $scope.getMedia = function(produto, usado) {


        var tmp = produto.aproximados;

        if (tmp == null)
            return;

        var valor = 0;
        var qtd = 0;

        for (var i = 0; i < tmp.listaTotal.length; i++) {

            var p = tmp.listaTotal[i];

            if (!p.desativado && p.usado == usado) {

                valor += p.preco;
                qtd++;

            }

        }

        if (qtd == 0) {

            return "Sem parametros para calcular a media";

        } else {

            return "R$ " + (valor / qtd).toFixed(2).split(".").join(",");

        }

    }

    var des = function(p1, p2) {

        produtoService.desativar(p1, p2, function(r) {

            if (r.sucesso) {

                p2.desativado = true;

            }

        })

    };

    $scope.desativarTotal = function(produto, itemKim) {

        var tmp = produto.aproximados;
        produto.aproximados = [];

        var ads = [];

        for (var i = 0; i < tmp.elementos.length; i++) {

            var p = tmp.elementos[i][0];

            if (p.nome == itemKim.nome) {

                ads[ads.length] = p;

            }

        }

        for (var i = 0; i < ads.length; i++) {

            des(produto, ads[i]);

        }

        for (var i = 0; i < tmp.listaTotal.length; i++) {

            var tid = false;

            for (var j = 0; j < ads.length; j++) {
                if (ads[j].id == tmp.listaTotal[i].id) {
                    tid = true;
                    break;
                }
            }

            if (tid) {
                tmp.listaTotal[i] = null;
                for (var j = i; j < tmp.listaTotal.length - 1; j++) {
                    tmp.listaTotal[j] = tmp.listaTotal[j + 1];
                }
                tmp.listaTotal.length--;
                i--;
            }
        }

        tmp.attList();
        produto.aproximados = tmp;

    }

    $scope.desativar = function(produto, itemKim) {


        var tmp = produto.aproximados;
        produto.aproximados = [];
        produtoService.desativar(produto, itemKim, function(r) {

            if (r.sucesso) {

                itemKim.desativado = true;

            }

        })

        for (var i = 0; i < tmp.listaTotal.length; i++) {
            if (tmp.listaTotal[i].id == itemKim.id) {
                tmp.listaTotal[i] = null;
                for (var j = i; j < tmp.listaTotal.length - 1; j++) {
                    tmp.listaTotal[j] = tmp.listaTotal[j + 1];
                }
                tmp.listaTotal.length--;
            }
        }

        tmp.attList();
        produto.aproximados = tmp;

    }

    $scope.setProduto = function(produto) {



        $scope.produto = produto;

        $scope.attPassado(produto.nome_fantasia)

        $scope.receituario.produto = $scope.produto;
        equalize($scope.produto, "categoria", $scope.categorias);
        if ($scope.produto.logistica !== null) {
            equalize($scope.produto, "logistica", $scope.logisticas);
        }

        produtoService.getFichaEmergencia(produto, function(f) {

            produto.ficha = f.ficha;

        })


        produtoService.getAproximados(produto, function(r) {

            produto["aproximados"] = createList(r.produtos, 1, 40);

        })



    }

    produtoService.getProduto(function(p) {
        $scope.produto_novo = p.produto;
        $scope.receituario.produto = $scope.produto;
    })

    receituarioService.getReceituario(function(p) {
        $scope.receituario_novo = p.receituario;
        $scope.receituario = angular.copy(p.receituario);
        $scope.receituario.produto = $scope.produto;
    })

    categoriaProdutoService.getElementos(function(f) {
        $scope.categorias = f.elementos
    })

    culturaService.getElementos(function(f) {

        $scope.culturas = f.culturas;

    })

    pragaService.getElementos(function(f) {

        $scope.pragas = f.pragas;

    })

})
rtc.controller("crtLogin", function($scope, loginService, sistemaService) {
    $scope.usuario = "";
    $scope.senha = "";
    $scope.email = "";

    $scope.email_cliente = "";
    $scope.cliente = null;

    $scope.cadastrar = function() {

        if ($scope.cliente.senha === $scope.cliente.confirmacao_senha) {

            sistemaService.inserirClienteRTC($scope.cliente, function(s) {

                if (s.sucesso) {
                    $scope.cliente = null;
                    $scope.email_cliente = "";
                    msg.alerta("Cadastrado com sucesso, feche a tela e efetue o Login");
                } else {
                    msg.alerta("Erro: " + s.mensagem);
                }

            });

        } else {

            msg.erro("A confirmacao de senha difere da senha");

        }


    }

    $scope.buscar = function() {

        sistemaService.getClienteCadastro($scope.email_cliente, function(f) {

            if (f.clientes.length > 0) {
                $scope.cliente = f.clientes[0];
                msg.alerta("Escolha o login e senha");
            } else {
                $scope.cliente = null;
                msg.erro("Email nao cadastrado");
            }

        });

    }
    setTimeout(function() {

        $scope.logarVendas = function() {

            loginService.login($scope.usuario, $scope.senha, function(r) {


                if (r.usuario === null || !r.sucesso) {
                    msg.erro("Esse usuario nao existe");
                } else {
                    if (typeof rtc["redirect"] === 'undefined') {
                        window.location = "vendas_principal.php";
                    } else {
                        window.location = rtc["redirect"];
                    }
                }
            });
        };


    }, 5000)


    $scope.logar = function() {

        loginService.login($scope.usuario, $scope.senha, function(r) {


            if (r.usuario === null || !r.sucesso) {
                msg.erro("Esse usuario nao existe");
            } else {
                if (typeof rtc["redirect"] === 'undefined') {
                    window.location = "comprar.php";
                } else {
                    window.location = rtc["redirect"];
                }
            }
        });
    };

    if (typeof rtc["login"] !== 'undefined' &&
        typeof rtc["senha"] !== 'undefined') {

        $scope.usuario = rtc["login"];
        $scope.senha = rtc["senha"];
        $scope.logar();

    }

    $scope.recuperar = function() {

        loginService.recuperar($scope.email, function(r) {
            if (r.sucesso) {

                msg.alerta("Senha enviada para o email");

            } else {

                msg.erro("Falha ao recuperar, provavelmente esse email nao esta cadastrado");

            }

        });

    }

})
rtc.controller("crtLogo", function($scope, empresaService, uploadService) {

    $("#pic").change(function() {

        var ext = ['png', 'jpg'];
        var pre_arquivos = $(this).prop("files");
        var arquivos = [];
        var e = [];

        var total_size = 0;

        for (var i = 0; i < pre_arquivos.length; i++) {
            for (var j = 0; j < ext.length; j++) {
                if (ext[j] === pre_arquivos[i].name.split('.')[pre_arquivos[i].name.split('.').length - 1]) {
                    arquivos[arquivos.length] = pre_arquivos[i];
                    e[e.length] = pre_arquivos[i].name.split('.')[pre_arquivos[i].name.split('.').length - 1];
                    total_size += pre_arquivos[i].size;
                    break;
                }
            }
        }

        if (arquivos.length === 0) {
            msg.alerta("A Imagem deve ser do tipo PNG");
            return;
        }


        uploadService.upload(arquivos, function(arquivos, sucesso) {



            if (!sucesso) {

                msg.erro("Falha ao subir arquivo");

            } else {

                empresaService.setLogo(arquivos[0], function(t) {

                    if (t.sucesso) {

                        msg.alerta("Upload feito com sucesso");
                        location.reload();

                    } else {

                        msg.erro("Falha ao trocar logo");

                    }


                })


            }

        })

    })

})