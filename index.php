<?php

require_once 'app/utils.php';

global $mysqli;

$player = new stdClass();
$player->id = session_id();

$match_row = $mysqli
  ->query("select * from matches where `cross` = '$player->id' or circle = '$player->id'")
  ->fetch_assoc();

if ($match_row) {
  $match = new stdClass();
  $match->id = (int)$match_row["id"];
  $match->is_cross = $match_row["cross"] === $player->id;

  $match->round = new stdClass();
  $match->round->number = (int)$match_row["round"];

  $round_state = get_round_state($match->id, $match->round->number, $match->is_cross);
  $match->moves = $round_state->moves;
  $match->round->status = $round_state->status;
  $match->round->winning_moves = $round_state->winning_moves;

  $match->turn = sizeof($match->moves) % 2 === ($match->is_cross ? 0 : 1);

  $match->opponent = new stdClass();
  $match->opponent->id = $match->is_cross ? $match_row["circle"] : $match_row["cross"];

  $match->score = new stdClass();
  $match->score->cross = (int)$match_row["score_cross"];
  $match->score->circle = (int)$match_row["score_circle"];
  $match->score->draw = (int)$match_row["score_draw"];

  $player->name = $mysqli
    ->query("select name from players where id = '$player->id'")
    ->fetch_assoc()["name"];

  $match->opponent->name = $mysqli
    ->query("select name from players where id = '" . $match->opponent->id . "'")
    ->fetch_assoc()["name"];
}
?>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Tic-tac-toe</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Courier+Prime&display=swap" rel="stylesheet">
  <link href="css/app.css" rel="stylesheet">
</head>
<body>
<pre class="debug" style="display: none"><?= session_id() . (isset($match) ? " @ $match->id" : "") ?></pre>
<div class="app center<?= isset($match) ? "" : " center--vertical" ?>">
  <div class="player" style="display: <?= isset($match) ? "none" : "unset" ?>">
    <div class="form-group">
      <label for="name">Enter your name</label>
      <input required id="name" name="player" type="text">
    </div>
    <div class="form-group">
      <label>Choose your mark</label>
      <div class="radio">
        <input checked id="mark-cross" name="mark" type="radio" value="cross"/>
        <label for="mark-cross">Cross</label>
      </div>
      <div class="radio">
        <input id="mark-circle" name="mark" type="radio" value="circle"/>
        <label for="mark-circle">Circle</label>
      </div>
    </div>
    <button id="play">Play</button>
    <p id="queue-info" style="display: none">Finding player... (<span id="queue-time">0 s</span>)</p>
  </div>
  <div class="stats" style="display: <?= isset($match) ? "unset" : "none" ?>">
    <p>Playing against <span class="opponent">Opponent</span></p>
    <div class="scoreboard">
      <div class="score">
        <p>You</p>
        <p id="score-player" class="score__value">0</p>
      </div>
      <div class="score">
        <p>Draw</p>
        <p id="score-draw" class="score__value">0</p>
      </div>
      <div class="score">
        <p>Them</p>
        <p id="score-opponent" class="score__value">0</p>
      </div>
    </div>
  </div>
  <div class="board" style="display: <?= isset($match) ? "grid" : "none" ?>">
    <div class="space"></div>
    <div class="space"></div>
    <div class="space"></div>
    <div class="space"></div>
    <div class="space"></div>
    <div class="space"></div>
    <div class="space"></div>
    <div class="space"></div>
    <div class="space"></div>
  </div>
  <p class="status" style="display: <?= isset($match) ? "unset" : "none" ?>"></p>
  <button id="new-game" style="display: <?= isset($match) ? "unset" : "none" ?>">New game 🎮</button>
</div>
<script src="js/random-name.js" type="application/javascript"></script>
<script src="js/app.js" type="application/javascript"></script>
<?php if (isset($match)): ?>
  <script type="application/javascript">
    player.id = "<?= $player->id ?>";
    player.name = "<?= $player->name ?>";
    match.id = <?= $match->id ?>;
    match.round.number = <?= $match->round->number ?>;
    match.round.status = <?= $match->round->status ? "\"$match->round->status\"" : "null" ?>;
    match.round.winningMoves = [<?= implode(", ", $match->round->winning_moves) ?>];
    match.cross = <?= $match->is_cross ? "true" : "false" ?>;
    match.turn = <?= $match->turn ? "true" : "false" ?>;
    match.opponent.id = "<?= $match->opponent->id ?>";
    match.opponent.name = "<?= $match->opponent->name ?>";
    match.score.cross = <?= $match->score->cross ?>;
    match.score.circle = <?= $match->score->circle ?>;
    match.score.draw = <?= $match->score->draw ?>;
    match.moves = [<?= implode(", ", $match->moves) ?>];
    startUpdater();
    match.moves.forEach((pos, i) => {
      const el = document.querySelector(".space:nth-child(" + (pos + 1) + ")")
      el.setAttribute("disabled", "");
      el.classList.add(i % 2 === 0 ? "space--cross" : "space--circle");
    });
    updateMatchRoundUI();
  </script>
<?php endif ?>
</body>
</html>