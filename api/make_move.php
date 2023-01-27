<?php

require_once "../app/utils.php";

global $mysqli;

$match_id = $_POST["match_id"];
$position = $_POST["position"];

$round = $mysqli
  ->query("select round from matches where id = $match_id")
  ->fetch_assoc()["round"];

$mysqli->query("insert into moves (`match`, round, sequence, position) (select $match_id, $round, count(*), $position from moves where `match` = $match_id and round = $round)");

$cross = ((int)$mysqli
    ->query("select count(*) as count from moves where `match` = $match_id and round = $round")
    ->fetch_assoc()["count"]) % 2 === 1;

$round_state = get_round_state($match_id, $round, $cross);

if ($round_state->status) {
  $column = $round_state->status === "draw" ? "score_draw" : ($cross ? "score_cross" : "score_circle");
  $mysqli->query("update matches set round = round + 1, $column = $column + 1 where id = $match_id");
}

echo json_encode([
  "status" => $round_state->status,
  "moves" => $round_state->moves,
  "winning_moves" => $round_state->winning_moves
]);