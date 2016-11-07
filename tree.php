<?php
    class Tree
    {
        private $root;
        private $max_id = 0;
        private function delete($id, $start_node) {
            $result = false;
            foreach ($start_node->get_childrens() as $i => $node) {
                if ($node->get_id() === $id) {
                    $start_node->remove_children($i);
                    $result = true;
                } else {
                    $result = $this->delete($id, $node);
                }
                if ($result) {
                    break;
                }
            }
            return $result;
        }
        private function find($id, $nodes) {
            $result = NULL;
            foreach ($nodes as $i => $node) {
                if (!is_null($node)) {
                    if ($node->get_id() === $id) {
                        $result = $node;
                    } else {
                        $result = $this->find($id, $node->get_childrens());
                    }
                    if (!is_null($result)) {
                        break;
                    }
                }
            }
            return $result;
        }
        private function calculate_and_set_max_id($node) {
            if ($node->get_id() > $this->max_id) {
                $this->max_id = $node->get_id();
            }
            foreach ($node->get_childrens() as $i => $child) {
                $this->calculate_and_set_max_id($child);
            }
        }
        private function convert_node_from_map($map) {
            $nodes = [];
            foreach ($map as $i => $node_var) {
                $node = NULL;
                $id = 1;
                $name = "";
                $childrens = [];
                foreach ($node_var as $field => $value) {
                    switch ($field) {
                        case "id":
                            $id = $value;
                            break;

                        case "name":
                            $name = $value;
                            break;

                        case "childrens":
                            $childrens = $this->convert_node_from_map($value);
                            break;
                    }
                }
                $node = new Node($id, $name);
                $node->set_childrens($childrens);
                array_push($nodes, $node);
            }
            return $nodes;
        }
        private function set_from_json($json) {
            $map = json_decode($json);
            foreach ($map as $field => $value) {
                if ($field === "tree") {
                    $root = NULL;
                    $id = 1;
                    $name = "";
                    $childrens = [];
                    foreach ($value as $root_field => $root_value) {
                        switch ($root_field) {
                            case "name":
                                $name = $root_value;
                                break;
                            
                            case "childrens":
                                $childrens = $this->convert_node_from_map($root_value);
                                break;
                        }
                    }
                    $this->root = new Node($id, $name);
                    $this->root->set_childrens($childrens);
                }
            }
            if (!is_null($this->root)) {
                $this->calculate_and_set_max_id($this->root);
            }
        }
        private function to_json() {
            $result = "{}";
            if (!is_null($this->root)) {
                $result = json_encode(['tree' => $this->root->to_serializable()]);
            }
            return $result;
        }
        private function node_to_html($node) {
            $result = "<ul><li id='".$node->get_id()."'><div></div><div class='node'>".$node->get_id().": ".$node->get_name()."</div><div class='childrens'></div>";
            foreach ($node->get_childrens() as $i => $child) {
                $result = $result.$this->node_to_html($child);
            }
            $result = $result."</li></ul>";
            return $result;
        }
        public function get_max_id() {
            return $this->max_id;
        }
        public function add($parent_id, $name) {
            $result = false;
            if (($parent_id === 0) && (is_null($this->root))) {
                $this->root = new Node(++$this->max_id, $name);
                $result = true;
            } else {
                $parent = $this->find($parent_id, [$this->root]);
                if (!is_null($parent)) {
                    $parent->add_children(new Node(++$this->max_id, $name));
                    $result = true;
                }
            }
            return $result;
        }
        public function remove($id) {
            $result = false;
            if ($id === 1) {
                $this->root = NULL;
                $this->max_id = 0;
                $result = true;
            } else {
                $result = $this->delete($id, $this->root);
            }
            return $result;
        }
        public function rename($id, $new_name) {
            $result = false;
            $node = $this->find($id, [$this->root]);
            if (!is_null($node)) {
                $node->set_name($new_name);
                $result = true;
            }
            return $result;
        }
        public function move($target_id, $new_parent_id) {
            $result = false;
            $node = $this->find($target_id, [$this->root]);
            if (!is_null($node)) {
                $new_node = new Node($node->get_id(), $node->get_name());
                $new_node->set_childrens($node->get_childrens());
                if ($new_parent_id === 0) {
                    $this->root = $new_node;
                    $result = true;
                }
                $new_parent = $this->find($new_parent_id, $node->get_childrens());
                if (is_null($new_parent)) {
                    $new_parent = $this->find($new_parent_id, [$this->root]);
                    if (!is_null($new_parent)) {
                        $new_parent->add_children($new_node);
                        $result = true;
                    }
                }
            }
            if ($result) {
                $this->remove($target_id);
            }
            return $result;
        }
        public function to_html() {
            if (!is_null($this->root)){
                $result = $this->node_to_html($this->root);
            }
            return "<div id='tree'>".$result."</div>";
        }
        public function load_from_db() {
            $strSQL = "SELECT * FROM main_table WHERE session_id = '".session_id()."'";
            $result = mysql_query($strSQL) or die(mysql_error());
            if ($row = mysql_fetch_array($result)) {
                $this->set_from_json($row["tree"]);
            }
        }
        public function save_to_db() {
            $strSQL = "UPDATE main_table SET tree = '".addslashes($this->to_json())."' WHERE session_id = '".session_id()."'";
            mysql_query($strSQL) or die(mysql_error());
        }
    }

    class Node
    {
        private $id;
        private $name;
        private $childrens = [];
        public function get_id() { return $this->id; }
        public function set_name($name) { $this->name = $name; }
        public function get_name() { return $this->name; }
        public function set_childrens($childrens) { $this->childrens = $childrens; }
        public function get_childrens() { return $this->childrens; }
        public function add_children($new_node) { array_push($this->childrens, $new_node); }
        public function remove_children($index) { unset($this->childrens[$index]); }

        public function to_serializable() {
            $result = array(
                "id" => $this->id,
                "name" => $this->name,
            );
            if ($this->childrens != []) {
                $childrens_json = [];
                foreach ($this->childrens as $i => $node) {
                    array_push($childrens_json, $node->to_serializable());
                }
                $result["childrens"] = $childrens_json;
            }
            return $result;
        }

        public function __construct($id, $name) {
            $this->id   = $id;
            $this->name = $name;
        }
    }
?>