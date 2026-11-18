<?php
require __DIR__ . '/config.php';

$erros = [];
$criado = null;
$form = [
    'nome' => '', 'rgm' => '', 'situacao' => '1', 'situacao_texto' => '',
    'objetivo' => '', 'revisao_conceito' => '', 'metodologia' => '',
    'tempo' => '', 'procedimento' => '', 'resultado' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $campo => $_) {
        $form[$campo] = trim($_POST[$campo] ?? '');
    }
    $resp = api_call('POST', '/api/projetos', $form);
    if ($resp['erro']) {
        $erros[] = $resp['erro'];
    } elseif ($resp['status'] === 201) {
        $criado = $resp['body'];
    } else {
        $erros = $resp['body']['erros'] ?? [$resp['body']['erro'] ?? 'erro desconhecido'];
    }
}

$lista = api_call('GET', '/api/projetos');
$itens = $lista['erro'] ? [] : ($lista['body']['itens'] ?? []);

$secoes = [
    'objetivo'          => ['Objetivo', 'Como voce pretende solucionar a situacao-problema escolhida.'],
    'revisao_conceito'  => ['Revisao de Conceito', 'Conhecimentos adquiridos no curso usados como base de estudo.'],
    'metodologia'       => ['Metodologia', 'Abordagem, tecnica ou processo usado para resolver o problema.'],
    'tempo'             => ['Tempo', 'Quanto tempo gastou para a solucao.'],
    'procedimento'      => ['Procedimento e material utilizado', 'Passo a passo da resolucao e materiais.'],
    'resultado'         => ['Resultado e discussao', 'O que resultou do processo.'],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PIT - Projeto de Intervencao</title>
<link rel="stylesheet" href="estilo.css">
</head>
<body>
<header>
  <h1>Projeto de Intervencao (PIT)</h1>
  <p>Frontend PHP + backend Python. Preencha as secoes e gere o documento.</p>
</header>

<main>
<?php if ($criado): ?>
  <section class="card sucesso">
    <h2>Projeto gerado</h2>
    <p>Protocolo <strong><?= e($criado['id']) ?></strong> &middot; <?= e($criado['criado_em']) ?></p>
    <pre><?= e($criado['documento']) ?></pre>
    <p><a class="botao" href="ver.php?id=<?= e($criado['id']) ?>">Abrir documento</a>
       <a class="botao secundario" href="index.php">Novo projeto</a></p>
  </section>
<?php else: ?>

  <?php if ($erros): ?>
    <section class="card erro">
      <h2>Corrija antes de enviar</h2>
      <ul><?php foreach ($erros as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?></ul>
    </section>
  <?php endif; ?>

  <form class="card" method="post" action="index.php">
    <div class="linha">
      <label>Nome
        <input type="text" name="nome" value="<?= e($form['nome']) ?>" required>
      </label>
      <label>RGM
        <input type="text" name="rgm" value="<?= e($form['rgm']) ?>" required>
      </label>
      <label>Situacao-problema
        <select name="situacao">
          <?php foreach (['1', '2', '3'] as $n): ?>
            <option value="<?= $n ?>" <?= $form['situacao'] === $n ? 'selected' : '' ?>>Situacao-problema <?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <label>A situacao-problema escolhida
      <textarea name="situacao_texto" rows="3" required><?= e($form['situacao_texto']) ?></textarea>
    </label>

    <?php foreach ($secoes as $campo => $info): ?>
      <label><?= e($info[0]) ?>
        <small><?= e($info[1]) ?></small>
        <textarea name="<?= $campo ?>" rows="4" required><?= e($form[$campo]) ?></textarea>
      </label>
    <?php endforeach; ?>

    <button type="submit">Gerar projeto</button>
  </form>
<?php endif; ?>

  <section class="card">
    <h2>Projetos salvos (<?= count($itens) ?>)</h2>
    <?php if ($lista['erro']): ?>
      <p class="aviso">Backend offline: <?= e($lista['erro']) ?></p>
    <?php elseif (!$itens): ?>
      <p class="aviso">Nenhum projeto salvo ainda.</p>
    <?php else: ?>
      <ul class="lista">
        <?php foreach ($itens as $item): ?>
          <li>
            <a href="ver.php?id=<?= e($item['id']) ?>"><?= e($item['nome']) ?></a>
            <span>RGM <?= e($item['rgm']) ?> &middot; situacao <?= e($item['situacao']) ?> &middot; <?= e($item['criado_em']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
