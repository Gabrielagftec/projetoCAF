<html lang="en" ng-app="appRtc">

    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <script src="js/angular.min.js"></script>
        <script src="js/rtc.js?<?php echo date('dmYH',microtime(true)); ?>"></script>
        <script src="js/filters.js?<?php echo date('dmYH',microtime(true)); ?>"></script>
        <script src="js/services.js?<?php echo date('dmYH',microtime(true)); ?>"></script>
        <script src="js/controllers.js?<?php echo date('dmYH',microtime(true)); ?>"></script>  <script src="assets/vendor/jquery/jquery-3.3.1.min.js"></script>    

        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
       
       <title>CAF</title>

    </head>

    <body ng-controller="crtPedidos">
        <!-- ============================================================== -->
        <!-- main wrapper -->
        <!-- ============================================================== -->
            <!-- ============================================================== -->
            <!-- navbar -->
            <!-- ============================================================== -->
            <!-- ============================================================== -->
            <!-- end navbar -->
            <!-- ============================================================== -->
            <!-- ============================================================== -->
            <!-- left sidebar -->
            <!-- ============================================================== -->
            <!-- ============================================================== -->
            <!-- end left sidebar -->
            <!-- ============================================================== -->
            <!-- ============================================================== -->
            <!-- wrapper  -->
            <!-- ============================================================== -->
            

                   
                        
                                
                                    <div class="form-group">
                                        <label for="">Cliente</label>
                                        <div class="form-row">
                                            <div class="col-2">
                                                <input type="text" ng-model="pedido.cliente.codigo" class="form-control" placeholder="Cod." value="9" disabled>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" ng-model="pedido.cliente.razao_social" class="form-control" placeholder="Nome do cliente" value="" disabled="">
                                            </div>
                                            <div class="col">
                                                <a href="#" class="" data-toggle="modal" ng-click="clientes.attList()" data-target="#clientes"></a>
                                            </div>

                                            <div class="modal fade" style="overflow-y:scroll" id="clientes" tabindex="-1" role="dialog" aria-labelledby="edit" aria-hidden="true">
                    
                            </div>
                            
                            <div class="modal-body">
                                <input type="text" class="form-control" id="filtroClientes" placeholder="Filtro">
                                <hr>
                                <table class="table table-striped table-bordered first">
                                    <thead>
                                    <th data-ordem="cliente.codigo">Cod.</th>
                                    <th data-ordem="cliente.razao_social">Nome</th>
                                    <th>Estado</th>
                                    <th>Selecionar</th>
                                    </thead>
                                    <tr ng-repeat="cli in clientes.elementos">
                                        <th>{{cli[0].codigo}}</th>
                                        <th>{{cli[0].razao_social}}</th>
                                        <th>{{cli[0].endereco.cidade.estado.sigla}}</th>
                                        <th><button class="btn btn-success" ng-click="setCliente(cli[0])"><i class="fa fa-info"></i></button></th>
                                    </tr> 
                                </table>
                                <paginacao assinc="clientes"></paginacao>

                            </div>
                            <div class="modal-footer">

                            </div>
                    
                    
                   
                                            
                                    
                                
                                
                                    
                                 

                   
                



                                    
                               
            
            
            
            
            
            
            
            
            
            
            
            
            <script src="assets/vendor/jquery/jquery.mask.min.js"></script>
            <script src="assets/libs/js/form-mask.js"></script>
            <script src="assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>

            <script src="assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>
            <script src="assets/vendor/datatables/js/buttons.bootstrap4.min.js"></script>
            <!-- slimscroll js -->
            <script src="assets/vendor/slimscroll/jquery.slimscroll.js"></script>
            <!-- main js -->
            <script src="assets/libs/js/main-js.js"></script>
            <!-- chart chartist js -->
            <script src="assets/vendor/charts/chartist-bundle/chartist.min.js"></script>
            <!-- sparkline js -->
            <script src="assets/vendor/charts/sparkline/jquery.sparkline.js"></script>
            <!-- morris js -->
            <script src="assets/vendor/charts/morris-bundle/raphael.min.js"></script>
            <script src="assets/vendor/charts/morris-bundle/morris.js"></script>
            <!-- chart c3 js -->
            <script src="assets/vendor/charts/c3charts/c3.min.js"></script>
            <script src="assets/vendor/charts/c3charts/d3-5.4.0.min.js"></script>
            <script src="assets/vendor/charts/c3charts/C3chartjs.js"></script>
            <script src="assets/libs/js/dashboard-ecommerce.js"></script>
            <!-- parsley js -->
            <script src="assets/vendor/parsley/parsley.js"></script>

            <!-- Optional JavaScript -->
            <script>

                                                var l = $('#loading');
                                                l.hide();
                                                var x = 0;
                                                var y = 0;
                                                $(document).mousemove(function (e) {

                                                x = e.clientX;
                                                y = e.clientY;
                                                var s = $(this).scrollTop();
                                                l.offset({top: (y + s), left: x});
                                                })

                                                        var sh = false;
                                                var it = null;
                                                loading.show = function () {
                                                l.show();
                                                var s = $(document).scrollTop();
                                                l.offset({top: (y + s), left: x});
                                                }

                                                loading.close = function () {
                                                l.hide();
                                                }

                                                $(document).ready(function () {
                                                $('.btnvis').tooltip({title: "Visualizar", placement: "top"});
                                                $('.btnedit').tooltip({title: "Editar", placement: "top"});
                                                $('.btndel').tooltip({title: "Deletar", placement: "top"});
                                                $('.btnaddprod').tooltip({title: "Adicionar", placement: "top"});
                                                });
                                                $(document).ready(function () {
                                                $(document).on({
                                                'show.bs.modal': function () {
                                                var zIndex = 1040 + (10 * $('.modal:visible').length);
                                                $(this).css('z-index', zIndex);
                                                setTimeout(function () {
                                                $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
                                                }, 0);
                                                },
                                                        'hidden.bs.modal': function () {
                                                        if ($('.modal:visible').length > 0) {
                                                        // restore the modal-open class to the body element, so that scrolling works
                                                        // properly after de-stacking a modal.
                                                        setTimeout(function () {
                                                        $(document.body).addClass('modal-open');
                                                        }, 0);
                                                        }
                                                        }
                                                }, '.modal');
                                                });
            </script>
    

    </body>

</html>