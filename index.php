<?php
// ================= CONFIG SUPABASE =================
$host = "aws-0-us-west-2.pooler.supabase.com";
$db   = "postgres";
$user = "postgres.rzqrctmkiogcjtaizwuq";
$pass = "sTMCp43y5nY7yLKi";
$port = "6543";

$pdo = new PDO(
  "pgsql:host=$host;port=$port;dbname=$db;sslmode=require",
  $user,
  $pass,
  [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// ================= AJAX =================
if(isset($_POST['ajax'])){

  if($_POST['ajax']=='toggle'){
    $p=$_POST['p']; $e=$_POST['e']; $v=$_POST['v']=='true';
    $row=$pdo->query("SELECT eventos FROM participantes WHERE id=$p")->fetch();
    $ev=json_decode($row['eventos'],true);
    $ev[$e]=$v;
    $pdo->prepare("UPDATE participantes SET eventos=? WHERE id=?")
        ->execute([json_encode($ev),$p]);
    exit;
  }

  if($_POST['ajax']=='editevento'){
  $pdo->prepare("UPDATE eventos SET nome=? WHERE id=?")
      ->execute([$_POST['nome'], $_POST['id']]);
  exit;
}

if($_POST['ajax']=='delevento'){
  $id = $_POST['id'];

  $pdo->prepare("DELETE FROM eventos WHERE id=?")->execute([$id]);

  $rows = $pdo->query("SELECT id,eventos FROM participantes")->fetchAll();
  foreach($rows as $r){
    $ev = json_decode($r['eventos'],true);
    unset($ev[$id]);
    $pdo->prepare("UPDATE participantes SET eventos=? WHERE id=?")
        ->execute([json_encode($ev),$r['id']]);
  }
  exit;
}



  if($_POST['ajax']=='crianca'){
    $pdo->prepare("UPDATE participantes SET crianca=? WHERE id=?")
        ->execute([$_POST['v']=='true',$_POST['p']]);
    exit;
  }

  if($_POST['ajax']=='nome'){
    $pdo->prepare("UPDATE participantes SET nome=? WHERE id=?")
        ->execute([$_POST['v'],$_POST['p']]);
    exit;
  }
}

// ================= ACTIONS =================
if (isset($_POST['add'])) {
  $nome = $_POST['nome'];
$crianca = !empty($_POST['crianca']) ? true : false;
  $ids = $pdo->query("SELECT id FROM eventos ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
  $arr = [];
  foreach ($ids as $i) $arr[$i] = false;
  $pdo->prepare("INSERT INTO participantes(nome,crianca,eventos) VALUES (?,?,?)")
->execute([$nome, $crianca ? 'true' : 'false', json_encode($arr)]);
}

if (isset($_GET['del'])) {
  $pdo->prepare("DELETE FROM participantes WHERE id=?")->execute([$_GET['del']]);
}

if (isset($_POST['addevento'])) {
  $pdo->prepare("INSERT INTO eventos(nome) VALUES(?)")->execute([$_POST['evento']]);
}

// ================= DATA =================
$eventos = $pdo->query("SELECT * FROM eventos ORDER BY id")->fetchAll();
$participantes = $pdo->query("SELECT * FROM participantes ORDER BY nome")->fetchAll();

$totalCriancas = 0;
foreach($participantes as $p){
  if($p['crianca']) $totalCriancas++;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Organização de Evento</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.btn-yes{background:#28a745;color:#fff}
.btn-no{background:#dc3545;color:#fff}
th,td{text-align:center;vertical-align:middle}
td.nome{cursor:pointer}
</style>
</head>
<body class="p-4">
<div class="container">
<h3>Controle de Participantes</h3>

<form class="row g-2" method="post">
<input name="nome" class="form-control col" placeholder="Nome do convidado" required>
<div class="form-check col">
<input class="form-check-input" type="checkbox" name="crianca">
<label class="form-check-label">É criança</label>
</div>
<button name="add" class="btn btn-primary col">Adicionar</button>
</form>

<form class="row g-2 mt-2" method="post">
<input name="evento" class="form-control col" placeholder="Novo evento" required>
<button name="addevento" class="btn btn-secondary col">Criar evento</button>
</form>

<table class="table table-bordered mt-3">
<tr>
<th>Ações</th>
<th>Nome</th>
<th>Criança<br>👶 <?=$totalCriancas?></th>
<?php foreach($eventos as $e):
$sim=0;$nao=0;
foreach($participantes as $p){
  $ev=json_decode($p['eventos'],true);
  if(isset($ev[$e['id']])){$ev[$e['id']]?$sim++:$nao++;}
}
?>
<th style="cursor:pointer"
    onclick="eventoPopup(<?=$e['id']?>,'<?=htmlspecialchars($e['nome'])?>')">
  <?=$e['nome']?><br>✔ <?=$sim?>
</th>

<?php endforeach ?>
</tr>

<?php foreach($participantes as $p): $ev=json_decode($p['eventos'],true); ?>
<tr>
<td>
  <a href="?del=<?=$p['id']?>" class="btn btn-sm btn-danger">🗑</a>
</td>
<td class="nome" onclick="editNome(this,<?=$p['id']?>)"><?=$p['nome']?></td>
<td>
  <input type="checkbox"
    <?=$p['crianca']?'checked':''?>
    onchange="setCrianca(this,<?=$p['id']?>)">
</td>
<?php foreach($eventos as $e): ?>
<td>
  <input type="checkbox"
    <?=($ev[$e['id']]??false)?'checked':''?>
    onchange="setToggle(this,<?=$p['id']?>,<?=$e['id']?>)">
</td>
<?php endforeach ?>
</tr>
<?php endforeach ?>
</table>
</div>

<script>

    function eventoPopup(id,nome){
  let acao = prompt(
    "Evento: "+nome+
    "\n\nDigite:\n1 - Editar\n2 - Excluir"
  );

  if(acao=="1"){
    let n = prompt("Novo nome:",nome);
    if(n){
      post({ajax:'editevento',id:id,nome:n});
      location.reload();
    }
  }

  if(acao=="2"){
    if(confirm("Excluir '"+nome+"'?")){
      post({ajax:'delevento',id:id});
      location.reload();
    }
  }
}


function post(data){
  fetch("",{
    method:"POST",
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:new URLSearchParams(data)
  });
}

function setToggle(el,p,e){
  post({ajax:'toggle',p:p,e:e,v:el.checked});
}

function setCrianca(el,p){
  post({ajax:'crianca',p:p,v:el.checked});
}

function editNome(td,p){
  let atual = td.innerText;
  let n = prompt("Editar nome",atual);
  if(n && n!=atual){
    td.innerText = n;
    post({ajax:'nome',p:p,v:n});
  }
}

function delEvento(id,nome){
  if(!confirm("Excluir o evento '"+nome+"'?")) return;
  post({ajax:'delevento',id:id});
  location.reload();
}
</script>

</body>
</html>
