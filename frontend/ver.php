<?php
require __DIR__ . '/config.php';

$id = preg_replace('/[^a-f0-9]/', '', $_GET['id'] ?? '');
$resp = $id ? api_call('GET', '/api/projetos/' . $id) : ['status' => 400, 'body' => null, 'erro' => 'id ausente'];
$reg = ($resp['status'] === 200) ? $resp['body'] : null;
if (!$reg) {
    // id invalido ou inexistente nao pode responder 200: devolve o status correto.
    http_response_code($resp['status'] === 400 ? 400 : 404);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PIT - documento</title>
<link rel="stylesheet" href="estilo.css">
</head>
<body>
<header>
  <h1>Documento do PIT</h1>
  <p><a class="botao secundario" href="index.php">Voltar</a></p>
</header>
<main>
<?php if (!$reg): ?>
  <section class="card erro">
    <h2>Nao foi possivel abrir</h2>
    <p><?= e($resp['erro'] ?? ($resp['body']['erro'] ?? 'projeto nao encontrado')) ?></p>
  </section>
<?php else: ?>
  <section class="card">
    <h2><?= e($reg['nome']) ?> &middot; RGM <?= e($reg['rgm']) ?></h2>
    <p>Protocolo <?= e($reg['id']) ?> &middot; <?= e($reg['criado_em']) ?></p>
    <pre><?= e($reg['documento']) ?></pre>
  </section>
<?php endif; ?>
</main>
</body>
</html>
