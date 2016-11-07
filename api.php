<?php
	require_once("tree.php");
    require_once("config.php");
	$tree = new Tree;
	session_start();
    mysql_connect($config["DB_ADDR"], $config["DB_USER"], $config["DB_PASS"]) or die(mysql_error());
    mysql_select_db($config["DB_NAME"]) or die(mysql_error());
    $strSQL = "INSERT INTO main_table(session_id,tree) VALUES('".session_id()."','')";    
    mysql_query($strSQL);
	$tree->load_from_db();
	$result = false;
	if ($_POST["action_type"] === "add") {
        $result = $tree->add((int) $_POST["parent_id"], $_POST["name"]);
        if ($result) {
        	echo json_encode(["result" => $result, "id" => $tree->get_max_id()]);
        } else {
        	echo json_encode(["result" => $result]);
        }
    } elseif ($_POST["action_type"] === "move") {
        $result = $tree->move((int) $_POST["target_id"], (int) $_POST["new_parent_id"]);
        echo json_encode(["result" => $result]);
    } elseif ($_POST["action_type"] === "remove") {
        $result = $tree->remove((int) $_POST["target_id"]);
        echo json_encode(["result" => $result]);
    } elseif ($_POST["action_type"] === "rename") {
        $result = $tree->rename((int) $_POST["target_id"], $_POST["name"]);
        echo json_encode(["result" => $result]);
    }
    if ($result) {
    	$tree->save_to_db();
    }
?>