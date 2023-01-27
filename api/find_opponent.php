<?php

require_once "../app/utils.php";

global $mysqli;

$player = new stdClass();
$player->id = session_id();
$player->name = $_GET["name"];
$player->mark = $_GET["mark"];

$match = $mysqli
  ->query("select * from matches where `cross` = '$player->id' or circle = '$player->id'")
  ->fetch_assoc();

if ($match) {
  $mysqli->query("delete from queues where player = '$player->id'");

  $is_cross = $player->mark === "cross";

  $opponent = new stdClass();
  $opponent->id = $is_cross ? $match["circle"] : $match["cross"];
  $opponent->name = $mysqli
    ->query("select name from players where id = '" . $opponent->id . "'")
    ->fetch_assoc()["name"];

  echo json_encode([
    "status" => "match_found",
    "player" => [
      "id" => $player->id,
      "name" => $player->name
    ],
    "match" => [
      "id" => (int)$match["id"],
      "is_cross" => $is_cross,
      "opponent" => [
        "id" => $opponent->id,
        "name" => $opponent->name
      ]
    ]
  ]);
  exit();
}

$mysqli->query("insert into players (id, name) values ('$player->id', '$player->name') on duplicate key update name = '$player->name'");

$mysqli->query("insert into queues (player, mark) values ('$player->id', '$player->mark') on duplicate key update mark = '$player->mark'");

$opponent_row = $mysqli
  ->query("select player as id, name from queues join players on queues.player = players.id where mark != '$player->mark' and player != '$player->id' limit 1")
  ->fetch_assoc();

if (!$opponent_row) {
  echo json_encode(["status" => null]);
  exit();
}

$opponent = new stdClass();
$opponent->id = $opponent_row["id"];
$opponent->name = $opponent_row["name"];

$mysqli->query("delete from queues where player in ('$player->id', '$opponent->id')");
$mysqli->query("insert into matches (`cross`, circle) values ('" . ($player->mark === "cross" ? $player->id : $opponent->id) . "', '" . ($player->mark === "circle" ? $player->id : $opponent->id) . "')");

echo json_encode([
  "status" => "match_found",
  "player" => [
    "id" => $player->id,
    "name" => $player->name
  ],
  "match" => [
    "id" => $mysqli->insert_id,
    "is_cross" => $player->mark === "cross",
    "opponent" => [
      "id" => $opponent->id,
      "name" => $opponent->name
    ]
  ]
]);