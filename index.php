<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<script type="text/javascript">
    function add(parent_id, id, name) {
        if (parent_id == 0) {
            $("#tree").append("<ul><li id='1'><div></div><div class='node'>1: "+name+"</div><div class='childrens'></div></li>");
        } else {
            $("#"+parent_id).append("<ul><li id="+id+"><div></div><div class='node'>"+id+": "+name+"</div><div class='childrens'></div></li></ul>");
        }
    }

    function move(target_id, new_parent_id) {
        var node = $("#"+target_id).parent().clone();
        $("#"+target_id).remove();
        if (new_parent_id == 0) {
            $("ul").remove();
            node.appendTo($("#tree"));
            $("#tree>ul>li>.node").text($("#tree").text().replace(String(target_id), "1"));
            $("#tree>ul>li").attr("id", "1");
        } else {
            node.appendTo($("#"+new_parent_id+">.childrens"));
        }
    }

    function remove(target_id) {
        $("#"+target_id).parent().remove();
    }

    function rename(target_id, new_name) {
        $("#"+target_id+">.node").text(target_id+": "+new_name);
    }

    $(document).ready(function(){
        $("#action_select").change(function(){
            var type = $("#action_select").val();
            switch (type) {
                case "add":
                    $("#target_id").hide();
                    $("#parent_id").show();
                    $("#new_parent_id").hide();
                    $("#name").show();
                    break;
                case "move":
                    $("#target_id").show();
                    $("#parent_id").hide();
                    $("#new_parent_id").show();
                    $("#name").hide();
                    break;
                case "remove":
                    $("#target_id").show();
                    $("#parent_id").hide();
                    $("#new_parent_id").hide();
                    $("#name").hide();
                    break;
                case "rename":
                    $("#target_id").show();
                    $("#parent_id").hide();
                    $("#new_parent_id").hide();
                    $("#name").show();
                    break;
            }
        });
        $("#send_button").click(function(){
            var type = $("#action_select").val();
            $.ajax({
                url: "/api.php",
                type: "POST",
                data: $("form").serialize(),
                dataType: "html",
                async: true,
                success: function(data){
                    data = JSON.parse(data);
                    if (data["result"]) {
                        switch (type) {
                            case "add":
                                add($("#parent_id").val(), data.id, $("#name").val());
                                $("#log").append("Elem added successful; id: "+$("#parent_id").val()+"<br>");
                                break;
                            case "move":
                                move($("#target_id").val(), $("#new_parent_id").val());
                                $("#log").append("Elem moved successful; id: "+$("#target_id").val()+"<br>");
                                break;
                            case "remove":
                                remove($("#target_id").val());
                                $("#log").append("Elem removed successful; id: "+$("#target_id").val()+"<br>");
                                break;
                            case "rename":
                                $("#log").append("Elem renamed successful; id: "+$("#parent_id").val()+"<br>");
                                rename($("#target_id").val(), $("#name").val());
                                break;
                        }
                    } else {
                        switch (type) {
                            case "add":
                                $("#log").append("<font color='red'>Failed to add element</font><br>");
                                break;
                            case "move":
                                $("#log").append("<font color='red'>Failed to move element</font><br>");
                                break;
                            case "remove":
                                $("#log").append("<font color='red'>Failed to remove element</font><br>");
                                break;
                            case "rename":
                                $("#log").append("<font color='red'>Failed to rename element</font><br>");
                                break;
                        }
                    }
                }
            })
        });
        $("#log").append("If tree is empty, add node with parent_id = 0");
        $("#action_select").change();
    });
</script>
<?php
    require_once("tree.php");
    require_once("config.php");

    session_start();
    mysql_connect($config["DB_ADDR"], $config["DB_USER"], $config["DB_PASS"]) or die(mysql_error());
    mysql_select_db($config["DB_NAME"]) or die(mysql_error());
    $strSQL = "INSERT INTO main_table(session_id,tree) VALUES('".session_id()."','')";    
    mysql_query($strSQL);

    $tree = new Tree;
    $tree->load_from_db();

    mysql_close();
?>
<table width="100%">
    <tr>
        <td width="80%" style="position: fixed;">
            <form method="post" action="/api.php">
                <select name="action_type" id="action_select">
                    <option>add</option>
                    <option>move</option>
                    <option>remove</option>
                    <option>rename</option>
                </select>
                <input type="text" name="target_id" value="target_id" id="target_id">
                <input type="text" name="parent_id" value="parent_id" id="parent_id">
                <input type="text" name="new_parent_id" value="new_parent_id" id="new_parent_id">
                <input type="text" name="name" value="name" id="name">
                <input type="button" name="" id="send_button" value="send">
            </form>
            <?=$tree->to_html();?>
        </td>
        <td width="20%" style="overflow: scroll;">
            <div id="log"></div>
        </td>
    </tr>
</table>