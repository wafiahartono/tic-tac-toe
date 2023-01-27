<?php

require_once "utils.php";

header("Content-Type: text/plain");

global $mysqli;

var_dump(get_round_state(206, 2, true));
var_dump(get_round_state(206, 2, false));