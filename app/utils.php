<?php

global $mysqli;

$mysqli = new mysqli(
  "localhost", "root", "", "tic_tac_toe"
);

function get_winning_moves($position, $moves): ?array
{
  $cross = sizeof($moves) % 2 === 1;

  $state = array_fill(0, 9, false);
  foreach ($moves as $i) $state[$i] = true;

  /*
 * 0 1 2
 * 3 4 5
 * 6 7 8
 * row = floor pos / 3
 * col = pos - row * 3
 * check row
 * for i in 0..3 @ i + row * 3
 * check column
 * for i in 0..3 @ col + i * 3
 * check diagonal
 * if pos % 2 > 0 return
 * if pos = 4 i in 0 4 8, i in 2, 4, 6
 * for i in 0..3 @ i + (pos % 4) + (4 - (p % 4) ^ 2)
 */

  $row = floor($position / 3);
  $col = $position - $row * 3;

  for ($i = 0, $w = []; $i < 3; $i++) {
    $check = $i + $row * 3;
    if ($state[$check] &&
      array_search($check, $moves) % 2 === ($cross ? 0 : 1)
    ) $w[] = $check;
  }
  if (sizeof($w) === 3) return $w;

  for ($i = 0, $w = []; $i < 3; $i++) {
    $check = $col + $i * 3;
    if ($state[$check] &&
      array_search($check, $moves) % 2 === ($cross ? 0 : 1)
    ) $w[] = $check;
  }
  if (sizeof($w) === 3) return $w;

  if ($position % 2 === 0) {
    if ($position === 4) {
      for ($i = 0, $w = []; $i < 3; $i++) {
        $check = 4 * $i;
        if ($state[$check] &&
          array_search($check, $moves) % 2 === ($cross ? 0 : 1)
        ) $w[] = $check;
      }
      if (sizeof($w) === 3) return $w;
      for ($i = 0, $w = []; $i < 3; $i++) {
        $check = 2 * ($i + 1);
        if ($state[$check] &&
          array_search($check, $moves) % 2 === ($cross ? 0 : 1)
        ) $w[] = $check;
      }
      if (sizeof($w) === 3) return $w;
    } else {
      for ($i = 0, $w = []; $i < 3; $i++) {
        $check = ($position % 4) + $i * (4 - ($position % 4));
        if ($state[$check] &&
          array_search($check, $moves) % 2 === ($cross ? 0 : 1)
        ) $w[] = $check;
      }
    }
    if (sizeof($w) === 3) return $w;
  }

  return sizeof($moves) === 9 ? null : [];
}

function get_round_state($match_id, $round, $cross): stdClass
{
  global $mysqli;

  $state = new stdClass();
  $state->status = null;
  $state->winning_moves = [];

  $state->moves = $mysqli
    ->query("select position from moves where `match` = $match_id and round = $round order by sequence")
    ->fetch_all();
  $state->moves = array_map("intval", array_merge(...$state->moves));

  if (sizeof($state->moves) > 4)
    $state->winning_moves = get_winning_moves($state->moves[sizeof($state->moves) - 1], $state->moves);

  if ($state->winning_moves === null)
    $state->status = "draw";
  else if (!empty($state->winning_moves))
    $state->status = sizeof($state->moves) % 2 === ($cross ? 1 : 0) ? "win" : "lose";

  return $state;
}

if (preg_match('/fsp\/tictactoe\/api/', $_SERVER["REQUEST_URI"])) {
  header("Content-Type: application/json");
}

session_start();
