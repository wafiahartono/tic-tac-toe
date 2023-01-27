<?php

require_once "../app/utils.php";

global $mysqli;

$match_id = $_GET["match_id"];
$round = $_GET["round"];
$cross = $_GET["cross"] === "true";

$round_state = get_round_state($match_id, $round, $cross);

echo json_encode([
  "status" => $round_state->status,
  "moves" => $round_state->moves,
  "winning_moves" => $round_state->winning_moves
]);