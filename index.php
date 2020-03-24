  <!DOCTYPE html>
<html>
<meta charset="utf-8">
 <head>
 <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/css/materialize.min.css">


  
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <script type = "text/javascript" src = "https://code.jquery.com/jquery-2.1.1.min.js"></script>           
  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/js/materialize.min.js"></script>
<style>
.nav-wrapper  {
    background-color: #18a049 !important;
    font-size: 14px;
    font-weight: bold;
  }
  
.nav-content  {
    background-color: #18a049 !important;
    font-size: 13px;

  }
  img{
  width: 20%;
  height:auto;
  margin-left: 3%;
}

</style>
 </head>

<body> 
<div class="container">
 <nav class="nav-extended">
    <div class="nav-wrapper">

      <a href="#" class="brand-logo"><img src="./img/logo.png"  alt="Calculadora de frete"></a>
    
      <a href="#" data-activates="mobile-demo" class="button-collapse"><i class="material-icons">menu</i></a>
      <ul id="nav-mobile" class="right hide-on-med-and-down">
        <li class="active"><a href="https://www.jquery-az.com/jquery-tips/">jQuery</a></li>
        <li><a href="https://www.jquery-az.com/javascript-tutorials/">JavaScript</a></li>
        <li><a href="https://www.jquery-az.com/html-tutorials/">HTML</a></li>
        <li><a href="https://www.jquery-az.com/css-tutorials/">CSS</a></li>
      </ul>
      <ul class="side-nav" id="mobile-demo">
        <li class="active"><a href="https://www.jquery-az.com/jquery-tips/">jQuery</a></li>
        <li><a href="https://www.jquery-az.com/javascript-tutorials/">JavaScript</a></li>
        <li><a href="https://www.jquery-az.com/html-tutorials/">HTML</a></li>
        <li><a href="https://www.jquery-az.com/css-tutorials/">CSS</a></li>
      </ul>
    </div>
    <div class="nav-content">
      <ul class="tabs tabs-transparent">
        <li class="tab"><a href="#tab1" >Clit.</a></li>
        <li class="tab"><a href="#tab2" >Prd.</a></li>
        <li class="tab"><a href="#tab3">Transpo.</a></li>
        <li class="tab"><a href="#tab4">Cotação</a></li>
      </ul>
    </div>
  </nav>
  <div id="tab1" class="col s12">

  <?php include 'test1.php' ?>
  
  </div>
  <div id="tab2" class="col s12">
  
  
  
  </div>
  <div id="tab3" class="col s12">Content for Tab 3</div>
  <div id="tab4" class="col s12">Content for Tab 4</div>
      
</div>

</body>   
</html>



  <script>$(".button-collapse").sideNav();</script>        
  <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
    </body>
  </html>
        